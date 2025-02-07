<template>
    <div class="container py-5">
        <!-- Nagłówek -->
        <header class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold display-6">Articles</h1>
            <button
                @click="showCreateForm = true"
                class="btn btn-primary d-flex align-items-center gap-2 shadow-sm"
            >
                <i class="bi bi-plus-circle-fill"></i>
                <span>Add New Article</span>
            </button>
        </header>

        <!-- Loading indicator -->
        <div v-if="loading" class="d-flex justify-content-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <!-- Informacja o elementach -->
        <div v-else class="d-flex justify-content-between align-items-center mb-4">
            <span class="text-secondary">
                Showing {{ meta.per_page * (meta.current_page - 1) + 1 }} to
                {{ Math.min(meta.per_page * meta.current_page, meta.total) }} of
                {{ meta.total }} entries
            </span>
            <div class="d-flex align-items-center gap-2">
                <label class="text-muted fw-bold">Items per page:</label>
                <select v-model="meta.per_page" class="form-select form-select-sm w-auto" @change="changePerPage">
                    <option :value="10">10</option>
                    <option :value="20">20</option>
                    <option :value="50">50</option>
                    <option :value="100">100</option>
                </select>
            </div>
        </div>

        <!-- Lista artykułów -->
        <div class="row gy-4">
            <div v-for="article in articles" :key="article.id" class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm rounded-3 h-100 d-flex flex-column">
                    <div class="card-body d-flex flex-column flex-grow-1">
                        <h2 class="h5 card-title mb-3 text-primary fw-bold">{{ article.title }}</h2>
                        <p class="card-text text-muted flex-grow-1">{{ article.content }}</p>
                        <small class="text-muted">Created: {{ formatDate(article.created_at) }}</small>
                    </div>
                    <div class="card-footer d-flex justify-content-end gap-2">
                        <button
                            @click="editArticle(article)"
                            class="btn btn-sm btn-outline-secondary"
                            title="Edit"
                        >
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button
                            @click="deleteArticle(article.id)"
                            class="btn btn-sm btn-outline-danger"
                            title="Delete"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Wiadomość o braku artykułów -->
            <div v-if="articles.length === 0" class="col-12 text-center">
                <p class="fs-5 text-muted">No articles found</p>
            </div>
        </div>

        <!-- Paginacja -->
        <nav v-if="!loading && meta.last_page > 1" class="d-flex justify-content-center mt-5">
            <ul class="pagination">
                <li class="page-item" :class="{ disabled: meta.current_page === 1 }">
                    <a class="page-link" href="#" @click.prevent="loadPage(meta.current_page - 1)">Previous</a>
                </li>
                <li v-for="page in displayedPages" :key="page" class="page-item" :class="{ active: page === meta.current_page }">
                    <a class="page-link" href="#" @click.prevent="loadPage(page)">{{ page }}</a>
                </li>
                <li class="page-item" :class="{ disabled: meta.current_page === meta.last_page }">
                    <a class="page-link" href="#" @click.prevent="loadPage(meta.current_page + 1)">Next</a>
                </li>
            </ul>
        </nav>

        <!-- Modal formularza -->
        <div v-if="showCreateForm || editingArticle" class="modal fade show d-block" tabindex="-1" role="dialog" style="background-color: rgba(0, 0, 0, 0.5)">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ editingArticle ? 'Edit Article' : 'Create New Article' }}
                        </h5>
                        <button type="button" class="btn-close" @click="closeForm"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="submitForm">
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input
                                    v-model="form.title"
                                    class="form-control"
                                    :class="{ 'is-invalid': errors.title }"
                                    required
                                />
                                <div v-if="errors.title" class="invalid-feedback">{{ errors.title[0] }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Content</label>
                                <textarea
                                    v-model="form.content"
                                    class="form-control"
                                    :class="{ 'is-invalid': errors.content }"
                                    rows="4"
                                    required
                                ></textarea>
                                <div v-if="errors.content" class="invalid-feedback">{{ errors.content[0] }}</div>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" @click="closeForm" class="btn btn-secondary" :disabled="formLoading">Cancel</button>
                                <button type="submit" class="btn btn-primary" :disabled="formLoading">
                                    <span v-if="formLoading" class="spinner-border spinner-border-sm me-1"></span>
                                    {{ editingArticle ? 'Update' : 'Create' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>



<script>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

export default {
    setup() {
        const articles = ref([])
        const loading = ref(true)
        const formLoading = ref(false)
        const showCreateForm = ref(false)
        const editingArticle = ref(null)
        const errors = ref({})
        const form = ref({
            title: '',
            content: ''
        })
        const meta = ref({
            current_page: 1,
            per_page: 20,
            total: 0,
            last_page: 1
        })

        // Oblicza zakres numerów stron do wyświetlenia w paginacji (maksymalnie 5 stron)
        const displayedPages = computed(() => {
            const maxVisiblePages = 5;
            let startPage = Math.max(1, meta.value.current_page - Math.floor(maxVisiblePages / 2));
            let endPage = Math.min(meta.value.last_page, startPage + maxVisiblePages - 1);

            if (endPage - startPage + 1 < maxVisiblePages) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }

            const pages = [];
            for (let i = startPage; i <= endPage; i++) {
                pages.push(i);
            }
            return pages;
        });

        const startPage = computed(() => {
            return displayedPages.value[0];
        });

        const endPage = computed(() => {
            return displayedPages.value[displayedPages.value.length - 1];
        });

        const loadArticles = async (page = 1) => {
            loading.value = true
            try {
                const response = await axios.get('/api/articles', {
                    params: {
                        page,
                        per_page: meta.value.per_page
                    }
                })
                articles.value = response.data.data
                meta.value = response.data.meta
            } catch (error) {
                console.error('Error loading articles:', error)
                alert('Error loading articles. Please try again later.')
            } finally {
                loading.value = false
            }
        }

        const loadPage = (page) => {
            if (page > 0 && page <= meta.value.last_page) {
                loadArticles(page)
            }
        }

        const changePerPage = () => {
            meta.value.current_page = 1;
            loadArticles();
        };

        const submitForm = async () => {
            formLoading.value = true
            errors.value = {}

            try {
                let response
                if (editingArticle.value) {
                    response = await axios.put(`/api/articles/${editingArticle.value.id}`, form.value)
                    const index = articles.value.findIndex(a => a.id === editingArticle.value.id);
                    if (index !== -1) {
                        articles.value[index] = response.data;
                    }
                } else {
                    response = await axios.post('/api/articles', form.value)
                    articles.value.unshift(response.data);
                    meta.value.total++;
                }
                closeForm()
            } catch (error) {
                if (error.response?.status === 422) {
                    errors.value = error.response.data.errors
                } else {
                    alert('Error saving article. Please try again later.')
                }
                console.error('Error submitting form:', error)
            } finally {
                formLoading.value = false
            }
        }

        const editArticle = (article) => {
            editingArticle.value = article
            form.value = {
                title: article.title,
                content: article.content
            }
        }

        const deleteArticle = async (id) => {
            if (confirm('Are you sure you want to delete this article?')) {
                try {
                    await axios.delete(`/api/articles/${id}`)
                    articles.value = articles.value.filter(a => a.id !== id);
                    meta.value.total--;

                    if (articles.value.length === 0 && meta.value.current_page > 1) {
                        loadPage(meta.value.current_page - 1);
                    }
                } catch (error) {
                    console.error('Error deleting article:', error)
                    alert('Error deleting article. Please try again later.')
                }
            }
        }

        const closeForm = () => {
            showCreateForm.value = false
            editingArticle.value = null
            form.value = {
                title: '',
                content: ''
            }
            errors.value = {}
        }

        const formatDate = (date) => {
            return new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            })
        }

        onMounted(loadArticles)

        return {
            articles,
            loading,
            formLoading,
            showCreateForm,
            editingArticle,
            form,
            errors,
            meta,
            displayedPages,
            startPage,
            endPage,
            submitForm,
            editArticle,
            deleteArticle,
            closeForm,
            loadPage,
            formatDate,
            changePerPage
        }
    }
}
</script>

<style scoped>
.modal.fade.show {
    background-color: rgba(0,0,0,0.5);
}
.card {
    transition: transform 0.2s;
}
.card:hover {
    transform: translateY(-2px);
}
</style>
