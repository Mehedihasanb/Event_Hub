import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
  {
    path: '/',
    name: 'home',
    component: () => import('../views/HomeView.vue'),
  },
  {
    path: '/events',
    name: 'events',
    component: () => import('../views/EventsView.vue'),
  },
  {
    path: '/events/:id',
    name: 'event-detail',
    component: () => import('../views/EventDetailView.vue'),
    props: true,
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/LoginView.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('../views/RegisterView.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/bookings',
    name: 'bookings',
    component: () => import('../views/MyBookingsView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/events',
    name: 'admin-events',
    component: () => import('../views/AdminEventsView.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/admin/bookings',
    name: 'admin-bookings',
    component: () => import('../views/AdminBookingsView.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 };
  },
});

router.beforeEach(async (to) => {
  const auth = useAuthStore();

  if (auth.token && !auth.user) {
    try {
      await auth.fetchMe();
    } catch {
      auth.logout();
    }
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } };
  }

  if (to.meta.requiresAdmin && !auth.isAdmin) {
    return { name: 'home' };
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { name: 'home' };
  }

  return true;
});

export default router;
