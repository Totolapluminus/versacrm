import axios from 'axios';
window.axios = axios;

import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

Pusher.logToConsole = true

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true
});

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.axios.interceptors.request.use(
    (res) => res,
    (err) => {
        const status = err?.response?.status

        if(status === 401 || status === 419) {
            localStorage.removeItem('token')
            delete window.axios.defaults.headers.common['Authorization']
            window.location.href = '/login'
            return
        }

        return Promise.reject(err)
    }
)

