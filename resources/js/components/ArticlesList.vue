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

        <!-- Lista artykułów -->
        <div class="row g-4">
            <div v-for="article in articles"
                 :key="article.id"
                 class="col-12 card shadow-sm"
            >
                <div class="card-body">
                    <h2 class="card-title h5">{{ article.title }}</h2>
                    <p class="card-text text-muted">{{ article.content }}</p>
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
        </div>

        <!-- Modal formularza -->
        <div v-if="showCreateForm || editingArticle"
             class="modal fade show d-block"
             tabindex="-1"
             role="dialog"
             style="background-color: rgba(0,0,0,0.5)"
        >
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ editingArticle ? 'Edit Article' : 'Create New Article' }}
                        </h5>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="submitForm">
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input
                                    v-model="form.title"
                                    class="form-control"
                                    required
                                >
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Content</label>
                                <textarea
                                    v-model="form.content"
                                    class="form-control"
                                    rows="4"
                                    required
                                ></textarea>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button
                                    type="button"
                                    @click="closeForm"
                                    class="btn btn-secondary"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >
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
import { ref, onMounted } from 'vue'
import axios from 'axios'

export default {
    setup() {
        const articles = ref([])
        const showCreateForm = ref(false)
        const editingArticle = ref(null)
        const form = ref({
            title: '',
            content: ''
        })

        const loadArticles = async () => {
            try {
                const response = await axios.get('/api/articles')
                articles.value = response.data
            } catch (error) {
                console.error('Error loading articles:', error)
                alert('Error loading articles')
            }
        }

        const submitForm = async () => {
            try {
                if (editingArticle.value) {
                    await axios.put(`/api/articles/${editingArticle.value.id}`, form.value)
                } else {
                    await axios.post('/api/articles', form.value)
                }
                await loadArticles()
                closeForm()
            } catch (error) {
                console.error('Error submitting form:', error)
                alert('Error saving article')
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
                    await loadArticles()
                } catch (error) {
                    console.error('Error deleting article:', error)
                    alert('Error deleting article')
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
        }

        onMounted(loadArticles)

        return {
            articles,
            showCreateForm,
            editingArticle,
            form,
            submitForm,
            editArticle,
            deleteArticle,
            closeForm
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
