import Vue from 'vue';
import izitoast from 'vue-izitoast';
import 'izitoast/dist/css/iziToast.min.css';
import app from './app.vue';
import api from './api';

if (window.location.protocol !== 'https:' && window.location.port !== '8080') {
  window.location.replace(`https:${window.location.href.substring(window.location.protocol.length)}`);
}

if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js');
}

Vue.config.productionTip = false;

Vue.use(izitoast);

Vue.prototype.api = api(Vue.prototype.toast);

new Vue({
  render: h => h(app),
}).$mount('#app');
