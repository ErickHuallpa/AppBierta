import { reactive } from 'vue';
import axios from 'axios';
import router from '../router';

// Configuración base de axios para Sanctum
axios.defaults.baseURL = 'http://127.0.0.1:8000';

export const authState = reactive({
  token: localStorage.getItem('token') || null,
  user: JSON.parse(localStorage.getItem('user') || 'null'),

  get isAuthenticated() {
    return !!this.token;
  },

  get isAdmin() {
    return this.user?.role === 'admin';
  },

  get isEmployee() {
    return this.user?.role === 'employee';
  },
  
  get isClient() {
    return this.user?.role === 'client';
  },

  get isDelivery() {
    return this.user?.role === 'delivery';
  },

  setAuth(token: string, user: any) {
    this.token = token;
    this.user = user;
    localStorage.setItem('token', token);
    localStorage.setItem('user', JSON.stringify(user));
    this.setupInterceptor();
  },

  logout() {
    this.token = null;
    this.user = null;
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    delete axios.defaults.headers.common['Authorization'];
    router.push('/login');
  },

  setupInterceptor() {
    if (this.token) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
    }
  }
});

// Inicializar interceptor si ya había token guardado
authState.setupInterceptor();
