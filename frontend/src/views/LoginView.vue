<script setup>
import { ref, computed } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const justRegistered = computed(() => route.query.registered === '1');

const email = ref('');
const password = ref('');

async function submit() {
  const e = email.value.trim();
  await auth.login(e, password.value);
  const redirect = route.query.redirect || '/bookings';
  router.push(typeof redirect === 'string' ? redirect : '/bookings');
}
</script>

<template>
  <div class="row justify-content-center">
    <div class="col-md-5">
      <h1 class="h3 mb-2">Log in</h1>
      <p class="text-secondary mb-4">
        Welcome back. Sign in to view your bookings, finish payments, and pick up where you left off.
      </p>
      <p v-if="justRegistered" class="alert alert-success py-2">Account created. You can sign in below.</p>
      <form class="card eh-card" @submit.prevent="submit">
        <div class="card-body p-4">
          <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input id="email" v-model="email" type="email" class="form-control" required />
          </div>
          <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input id="password" v-model="password" type="password" class="form-control" required />
          </div>
          <p v-if="auth.error" class="text-danger small">{{ auth.error }}</p>
          <button type="submit" class="btn btn-primary w-100" :disabled="auth.loading">
            {{ auth.loading ? 'Please wait…' : 'Sign in' }}
          </button>
          <p class="small text-muted mt-3 mb-0">
            No account?
            <RouterLink to="/register">Register</RouterLink>
          </p>
        </div>
      </form>
    </div>
  </div>
</template>
