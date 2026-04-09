import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { api, setAuthToken } from '../api/client';

const TOKEN_KEY = 'eventhub_token';

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem(TOKEN_KEY) || '');
  const user = ref(null);
  const loading = ref(false);
  const error = ref('');

  if (token.value) {
    setAuthToken(token.value);
  }

  const isAuthenticated = computed(() => Boolean(token.value));
  const isAdmin = computed(() => user.value?.role === 'admin');

  function persistToken(t) {
    token.value = t;
    if (t) {
      localStorage.setItem(TOKEN_KEY, t);
      setAuthToken(t);
    } else {
      localStorage.removeItem(TOKEN_KEY);
      setAuthToken(null);
    }
  }

  async function fetchMe() {
    if (!token.value) {
      user.value = null;
      return;
    }
    const { data } = await api.get('/auth/me');
    user.value = data.data;
  }

  async function login(email, password) {
    loading.value = true;
    error.value = '';
    try {
      const { data } = await api.post('/auth/login', { email, password });
      persistToken(data.token);
      user.value = data.user;
    } catch (e) {
      error.value = e.message;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function register(email, password) {
    loading.value = true;
    error.value = '';
    try {
      await api.post('/auth/register', { email, password });
    } catch (e) {
      error.value = e.message;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  function logout() {
    persistToken('');
    user.value = null;
  }

  return {
    token,
    user,
    loading,
    error,
    isAuthenticated,
    isAdmin,
    login,
    register,
    logout,
    fetchMe,
  };
});
