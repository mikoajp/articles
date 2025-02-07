
import { createApp, h } from 'vue';
import ArticlesList from './components/ArticlesList.vue';
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap'

const app = createApp({
    render: () => h(ArticlesList)
});

app.mount('#app');
