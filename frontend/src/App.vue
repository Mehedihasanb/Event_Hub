<script setup>
import AppNav from './components/AppNav.vue';
</script>

<template>
  <div class="d-flex flex-column min-vh-100">
    <AppNav />
    <main class="flex-grow-1 py-4">
      <div class="container">
        <RouterView />
      </div>
    </main>
    <footer class="border-top py-3 mt-auto bg-white">
      <div class="container small text-muted text-center">
        EventHub — live experiences, made simple.
      </div>
    </footer>
  </div>
</template>
