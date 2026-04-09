<script setup>
import { ref } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();

const email = ref('');
const password = ref('');
async function submit() {
  await auth.register(email.value, password.value);
  router.push({ name: 'login', query: { registered: '1' } });
}
</script>

<template>
  <div class="row justify-content-center">
    <div class="col-md-5">
      <h1 class="h3 mb-4">Create an account</h1>
      <form class="card eh-card" @submit.prevent="submit">
        <div class="card-body p-4">
          <div class="mb-3">
            <label class="form-label" for="remail">Email</label>
            <input id="remail" v-model="email" type="email" class="form-control" required />
          </div>
          <div class="mb-3">
            <label class="form-label" for="rpw">Password</label>
            <input
              id="rpw"
              v-model="password"
              type="password"
              class="form-control"
              minlength="8"
              required
            />
            <div class="form-text">At least 8 characters.</div>
          </div>
          <p v-if="auth.error" class="text-danger small">{{ auth.error }}</p>
          <button type="submit" class="btn btn-primary w-100" :disabled="auth.loading">
            {{ auth.loading ? 'Please wait…' : 'Register' }}
          </button>
          <p class="small text-muted mt-3 mb-0">
            Already registered?
            <RouterLink to="/login">Log in</RouterLink>
          </p>
        </div>
      </form>
    </div>
  </div>
</template>
