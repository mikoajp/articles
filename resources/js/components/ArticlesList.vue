<template>
    <div class="container py-5">
        <div class="row mb-5 align-items-center">
            <div class="col">
                <h1 class="display-5 fw-bold text-primary mb-0">Articles</h1>
            </div>
            <div class="col-auto">
                <button
                    @click="showCreateForm = true"
                    class="btn btn-primary rounded-pill px-4 d-flex align-items-center gap-2"
                >
                    <i class="bi bi-plus-circle"></i>
                    Add New Article
                </button>
            </div>
        </div>

        <div v-if="loading && !articles.length" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <TransitionGroup
            name="article-list"
            tag="div"
            class="row g-4"
        >
            <div v-for="(article, index) in articles"
                 :key="article.id"
                 class="col-12 article-item fade-from-left"
                 :style="{ '--delay': `${index * 0.1}s` }"
            >
                <div class="card border-0 shadow-sm hover-shadow transition-all">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h2 class="h4 card-title mb-0 text-dark">{{ article.title }}</h2>
                            <span class="badge bg-primary-subtle rounded-pill px-3">Article</span>
                        </div>
                        <p class="card-text text-secondary mb-4">{{ article.content }}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted d-flex align-items-center gap-2">
                                <i class="bi bi-calendar3"></i>
                                {{ formatDate(article.created_at) }}
                            </small>
                            <div class="btn-group">
                                <button
                                    @click="editArticle(article)"
                                    class="btn btn-outline-warning rounded-pill px-3 me-2 d-flex align-items-center gap-2"
                                >
                                    <i class="bi bi-pencil"></i>
                                    Edit
                                </button>
                                <button
                                    @click="deleteArticle(article.id)"
                                    class="btn btn-outline-danger rounded-pill px-3 d-flex align-items-center gap-2"
                                >
                                    <i class="bi bi-trash"></i>
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="loadingMore"
                 :key="'loading-more'"
                 class="col-12 text-center py-4 fade-in">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading more...</span>
                </div>
            </div>

            <div v-if="articles.length === 0 && !loading"
                 :key="'empty-state'"
                 class="col-12 fade-in">
                <div class="text-center py-5 bg-light rounded-3">
                    <i class="bi bi-journal-text display-1 text-secondary mb-3 d-block"></i>
                    <h3 class="text-secondary">No articles found</h3>
                    <p class="text-muted">Start by adding your first article</p>
                </div>
            </div>

            <div v-if="!hasMore && articles.length > 0"
                 :key="'end-of-list'"
                 class="col-12 text-center py-4 fade-in">
                <p class="text-muted mb-0">No more articles to load</p>
            </div>
        </TransitionGroup>

        <div v-if="showCreateForm || editingArticle"
             class="modal fade show d-block"
             tabindex="-1"
             role="dialog"
             style="background-color: rgba(0, 0, 0, 0.5)"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold mb-3">
                            {{ editingArticle ? 'Edit Article' : 'Create New Article' }}
                        </h5>
                        <button type="button"
                                class="btn-close mb-2"
                                @click="closeForm"
                                aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="submitForm">
                            <div class="mb-4">
                                <label class="form-label fw-medium">Title</label>
                                <input
                                    v-model="form.title"
                                    class="form-control form-control-lg"
                                    :class="{ 'is-invalid': errors.title }"
                                    placeholder="Enter article title"
                                    required
                                />
                                <div v-if="errors.title" class="invalid-feedback">{{ errors.title[0] }}</div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-medium">Content</label>
                                <textarea
                                    v-model="form.content"
                                    class="form-control"
                                    :class="{ 'is-invalid': errors.content }"
                                    rows="6"
                                    placeholder="Write your article content here..."
                                    required
                                ></textarea>
                                <div v-if="errors.content" class="invalid-feedback">{{ errors.content[0] }}</div>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button
                                    type="button"
                                    @click="closeForm"
                                    class="btn btn-light rounded-pill px-4"
                                    :disabled="formLoading"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="btn btn-primary rounded-pill px-4 d-flex align-items-center gap-2"
                                    :disabled="formLoading"
                                >
                                    <span v-if="formLoading" class="spinner-border spinner-border-sm"></span>
                                    <i v-else class="bi" :class="editingArticle ? 'bi-check2-circle' : 'bi-plus-circle'"></i>
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

<style scoped>
/* Gradient background for modern look */
.container {
    min-height: 100vh;
}

/* Header styling */
.display-5 {
    letter-spacing: -0.05em;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Card styling */
.card {
    border-radius: 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.1);
}

/* Badge styling */
.badge {
    color: white;
    font-size: 0.8em;
    padding: 0.5em 1em;
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    border: 1px solid rgba(255,255,255,0.2);
}

/* Button enhancements */
.btn-primary {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.btn-outline-warning:hover {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
}

.btn-outline-danger:hover {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}

/* Modal styling */
.modal-content {
    border-radius: 20px;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(12px);
}

.modal-header {
    padding: 1.5rem;
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    color: white;
}

.modal-title {
    font-weight: 700;
    letter-spacing: -0.02em;
}

/* Form input styling */
.form-control {
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Animations */
.article-list-enter-active,
.article-list-leave-active {
    transition: all 0.4s ease;
}

.article-list-enter-from,
.article-list-leave-to {
    opacity: 0;
    transform: translateX(30px);
}

.fade-from-left {
    animation: fadeFromLeft 0.6s ease forwards;
    opacity: 0;
}

@keyframes fadeFromLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.fade-in {
    animation: fadeIn 0.4s ease forwards;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Loading spinner customization */
.spinner-border {
    width: 2.5rem;
    height: 2.5rem;
    border-width: 0.2em;
}

/* Empty state styling */
.bi-journal-text {
    opacity: 0.8;
    font-size: 4rem;
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Hover effects */
.btn {
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
}
</style>
