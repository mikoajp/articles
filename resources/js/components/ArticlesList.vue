<template>
    <div class="container mt-4">
        <!-- Nagłówek -->
        <div class="mb-5">
            <h1 class="h2 mb-4">Articles</h1>
            <button
                @click="showCreateForm = true"
                class="btn btn-primary mb-3"
            >
                Add New Article
            </button>
        </div>

        <!-- Loading indicator - początkowe ładowanie -->
        <div v-if="loading && !articles.length" class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <!-- Lista artykułów -->
        <div class="row g-4">
            <div v-for="article in articles"
                 :key="article.id"
                 class="col-12 card shadow-sm"
            >
                <div class="card-body">
                    <h2 class="card-title h5">{{ article.title }}</h2>
                    <p class="card-text text-muted">{{ article.content }}</p>
                    <small class="text-muted d-block mb-2">Created: {{ formatDate(article.created_at) }}</small>
                    <div class="mt-3 d-flex gap-2">
                        <button
                            @click="editArticle(article)"
                            class="btn btn-warning btn-sm"
                        >
                            Edit
                        </button>
                        <button
                            @click="deleteArticle(article.id)"
                            class="btn btn-danger btn-sm"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading więcej danych -->
            <div v-if="loadingMore" class="col-12 text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading more...</span>
                </div>
            </div>

            <!-- Brak artykułów -->
            <div v-if="articles.length === 0 && !loading" class="col-12 text-center">
                <p class="text-muted">No articles found</p>
            </div>

            <!-- Koniec listy -->
            <div v-if="!hasMore && articles.length > 0" class="col-12 text-center py-4">
                <p class="text-muted">No more articles to load</p>
            </div>
        </div>

        <!-- Modal formularza -->
        <div v-if="showCreateForm || editingArticle"
             class="modal fade show d-block"
             tabindex="-1"
             role="dialog"
             style="background-color: rgba(0, 0, 0, 0.5)"
        >
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
                                <button
                                    type="button"
                                    @click="closeForm"
                                    class="btn btn-secondary"
                                    :disabled="formLoading"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    :disabled="formLoading"
                                >
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
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash-es'

export default {
    setup() {
        const articles = ref([])
        const loading = ref(true)
        const loadingMore = ref(false)
        const currentPage = ref(1)
        const hasMore = ref(true)
        const errors = ref({})
        const formLoading = ref(false)
        const showCreateForm = ref(false)
        const editingArticle = ref(null)
        const form = ref({
            title: '',
            content: ''
        })

        const perPage = 15
        const scrollDebounce = 200
        const scrollThreshold = 200

        // Metody
        const loadArticles = async () => {
            if (!hasMore.value || loadingMore.value) return

            const controller = new AbortController()

            try {
                loadingMore.value = true
                const response = await axios.get('/api/articles', {
                    params: {
                        page: currentPage.value,
                        per_page: perPage
                    },
                    signal: controller.signal
                })

                if (response.data.data.length === 0) {
                    hasMore.value = false
                    return
                }

                articles.value = [...articles.value, ...response.data.data]
                currentPage.value++

                if (shouldLoadMore()) {
                    loadArticles()
                }
            } catch (error) {
                if (!axios.isCancel(error)) {
                    console.error('Error loading articles:', error)
                }
            } finally {
                loading.value = false
                loadingMore.value = false
            }
        }

        const shouldLoadMore = () => {
            const { scrollY, innerHeight } = window
            const { scrollHeight } = document.documentElement
            return scrollY + innerHeight >= scrollHeight - scrollThreshold
        }

        const handleScroll = debounce(() => {
            if (!loadingMore.value && hasMore.value && shouldLoadMore()) {
                loadArticles()
            }
        }, scrollDebounce)

        const submitForm = async () => {
            formLoading.value = true
            errors.value = {}

            try {
                let response
                if (editingArticle.value) {
                    response = await axios.put(`/api/articles/${editingArticle.value.id}`, form.value)
                    const index = articles.value.findIndex(a => a.id === editingArticle.value.id)
                    if (index !== -1) {
                        articles.value[index] = response.data
                    }
                } else {
                    response = await axios.post('/api/articles', form.value)
                    articles.value.unshift(response.data)
                }
                closeForm()
            } catch (error) {
                if (error.response?.status === 422) {
                    errors.value = error.response.data.errors
                } else {
                    alert('Error saving article. Please try again later.')
                }
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
            showCreateForm.value = true
        }

        const deleteArticle = async (id) => {
            if (!confirm('Are you sure you want to delete this article?')) return

            try {
                await axios.delete(`/api/articles/${id}`)
                articles.value = articles.value.filter(a => a.id !== id)

                if (articles.value.length === 0 && hasMore.value) {
                    loadArticles()
                }
            } catch (error) {
                console.error('Error deleting article:', error)
                alert('Error deleting article. Please try again later.')
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

        onMounted(() => {
            loadArticles()
            window.addEventListener('scroll', handleScroll)
        })

        onUnmounted(() => {
            window.removeEventListener('scroll', handleScroll)
        })

        return {
            articles,
            loading,
            loadingMore,
            hasMore,
            formLoading,
            errors,
            showCreateForm,
            editingArticle,
            form,
            submitForm,
            editArticle,
            deleteArticle,
            closeForm,
            formatDate,
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
    min-height: 150px;
}

.card:hover {
    transform: translateY(-2px);
}
</style>
