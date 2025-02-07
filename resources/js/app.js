
import { createApp, h } from 'vue';
import ArticlesList from './components/ArticlesList.vue';
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap'
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';

const app = createApp({
    render: () => h(ArticlesList)
});

app.mount('#app');
