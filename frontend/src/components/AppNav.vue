<script setup>
import { computed } from 'vue';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();

const brandSubtitle = computed(() =>
  auth.isAdmin ? 'Admin' : auth.isAuthenticated ? 'Signed in' : '',
);
</script>

<template>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
      <RouterLink class="navbar-brand d-flex align-items-center gap-2" to="/">
        <span>EventHub</span>
        <span v-if="brandSubtitle" class="badge bg-secondary fw-normal small">{{
          brandSubtitle
        }}</span>
      </RouterLink>
      <button
        class="navbar-toggler"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#mainNav"
        aria-controls="mainNav"
        aria-expanded="false"
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon" />
      </button>
      <div id="mainNav" class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-lg-2">
          <li class="nav-item">
            <RouterLink class="nav-link" to="/events">Events</RouterLink>
          </li>
          <template v-if="auth.isAuthenticated">
            <li class="nav-item">
              <RouterLink class="nav-link" to="/bookings">My bookings</RouterLink>
            </li>
            <template v-if="auth.isAdmin">
              <li class="nav-item">
                <RouterLink class="nav-link" to="/admin/events">Admin · Events</RouterLink>
              </li>
              <li class="nav-item">
                <RouterLink class="nav-link" to="/admin/bookings">Admin · Bookings</RouterLink>
              </li>
            </template>
            <li class="nav-item ms-lg-2">
              <button type="button" class="btn btn-outline-light btn-sm" @click="auth.logout()">
                Log out
              </button>
            </li>
          </template>
          <template v-else>
            <li class="nav-item">
              <RouterLink class="nav-link" to="/login">Log in</RouterLink>
            </li>
            <li class="nav-item ms-lg-2">
              <RouterLink class="btn btn-light btn-sm" to="/register">Register</RouterLink>
            </li>
          </template>
        </ul>
      </div>
    </div>
  </nav>
</template>
