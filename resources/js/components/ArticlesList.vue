<template>
    <div class="container py-4">
        <!-- Nagłówek -->
        <header class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-5">
            <div>
                <h1 class="display-4 fw-bold text-gradient-primary mb-2">Knowledge Base</h1>
                <p class="lead text-muted">Manage your articles and documentation</p>
            </div>
            <button
                @click="showCreateForm = true"
                class="btn btn-primary btn-lg rounded-pill px-4 px-md-5 d-flex align-items-center gap-2 shadow-sm"
            >
                <i class="bi bi-plus-lg me-1"></i>
                New Article
            </button>
        </header>

        <!-- Stan ładowania -->
        <div v-if="loading && !articles.length" class="text-center py-5">
            <div class="spinner-grow text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <!-- Lista artykułów -->
        <TransitionGroup
            name="article-list"
            tag="div"
            class="row"
        >
            <!-- Karty artykułów -->
            <div
                v-for="(article, index) in articles"
                :key="article.id"
                class="col-12 mb-4"
                :style="{ '--delay': `${index * 0.1}s` }"
            >
                <div class="card border-0 shadow-sm hover-shadow transition-all">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h3 class="h5 card-title text-truncate mb-0">{{ article.title }}</h3>
                            <div class="dropdown">
                                <button
                                    class="btn btn-link text-muted p-0"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                >
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button
                                            @click="editArticle(article)"
                                            class="dropdown-item d-flex align-items-center gap-2"
                                        >
                                            <i class="bi bi-pencil"></i>
                                            Edit
                                        </button>
                                    </li>
                                    <li>
                                        <button
                                            @click="deleteArticle(article.id)"
                                            class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                        >
                                            <i class="bi bi-trash"></i>
                                            Delete
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <p class="card-text text-muted flex-grow-1 line-clamp-3 mb-4">{{ article.content }}</p>

                        <div class="d-flex justify-content-between align-items-center mt-auto">
                            <small class="text-muted d-flex align-items-center gap-2">
                                <i class="bi bi-clock"></i>
                                {{ formatDate(article.created_at) }}
                            </small>
                            <span class="badge bg-primary-subtle text-primary rounded-pill">
                                {{ article.category || 'General' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ładowanie kolejnych elementów -->
            <div v-if="loadingMore" class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <!-- Brak artykułów -->
            <div v-if="articles.length === 0 && !loading" class="col-12">
                <div class="text-center py-5 bg-light rounded-4">
                    <i class="bi bi-file-text display-1 text-primary opacity-25 mb-4"></i>
                    <h2 class="h4 mb-3">No articles found</h2>
                    <button
                        @click="showCreateForm = true"
                        class="btn btn-link text-primary p-0"
                    >
                        Create your first article
                    </button>
                </div>
            </div>
        </TransitionGroup>

        <!-- Przycisk mobilny -->
        <button
            @click="showCreateForm = true"
            class="btn btn-primary btn-lg rounded-circle shadow-lg fixed-bottom d-md-none"
            style="right: 1rem; bottom: 1rem; width: 56px; height: 56px;"
        >
            <i class="bi bi-plus-lg"></i>
        </button>

        <!-- Modal formularza -->
        <div
            v-if="showCreateForm || editingArticle"
            class="modal fade show d-block bg-dark bg-opacity-25"
            tabindex="-1"
            @click.self="closeForm"
        >
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg overflow-hidden">
                    <div class="modal-header bg-primary text-white">
                        <h2 class="modal-title fs-5">
                            {{ editingArticle ? 'Edit Article' : 'New Article' }}
                        </h2>
                        <button
                            type="button"
                            class="btn-close btn-close-white"
                            @click="closeForm"
                        ></button>
                    </div>

                    <form @submit.prevent="submitForm" class="modal-body">
                        <div class="mb-4">
                            <label class="form-label fw-medium mb-2">Title</label>
                            <input
                                v-model="form.title"
                                type="text"
                                class="form-control form-control-lg"
                                :class="{ 'is-invalid': errors.title }"
                                placeholder="Enter article title"
                                maxlength="120"
                            >
                            <div class="d-flex justify-content-between mt-2">
                                <div v-if="errors.title" class="invalid-feedback">
                                    {{ errors.title[0] }}
                                </div>
                                <small class="text-muted ms-auto">
                                    {{ form.title.length }}/120
                                </small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-medium mb-2">Content</label>
                            <textarea
                                v-model="form.content"
                                class="form-control"
                                :class="{ 'is-invalid': errors.content }"
                                rows="8"
                                placeholder="Write your content here..."
                                maxlength="5000"
                            ></textarea>
                            <div class="d-flex justify-content-between mt-2">
                                <div v-if="errors.content" class="invalid-feedback">
                                    {{ errors.content[0] }}
                                </div>
                                <small class="text-muted ms-auto">
                                    {{ form.content.length }}/5000
                                </small>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-3 pt-4">
                            <button
                                type="button"
                                @click="closeForm"
                                class="btn btn-link text-muted"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="btn btn-primary px-5"
                                :disabled="formLoading"
                            >
                                <template v-if="formLoading">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Processing...
                                </template>
                                <template v-else>
                                    {{ editingArticle ? 'Save Changes' : 'Publish' }}
                                </template>
                            </button>
                        </div>
                    </form>
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

        const initialLoadCount = 5
        const perPage = 1
        const scrollDebounce = 200
        const scrollThreshold = 200
        let initialLoadCompleted = false

        const loadArticles = async () => {
            if (!hasMore.value || loadingMore.value) return

            try {
                loadingMore.value = true
                const response = await axios.get('/api/articles', {
                    params: {
                        page: currentPage.value,
                        per_page: initialLoadCompleted ? perPage : initialLoadCount
                    }
                })

                const newArticles = response.data.data

                if (newArticles.length === 0) {
                    hasMore.value = false
                    return
                }

                articles.value.push(...newArticles)
                currentPage.value++
                if (!initialLoadCompleted) initialLoadCompleted = true
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

