<script setup>
import { RouterLink } from 'vue-router';
</script>

<template>
  <section class="eh-hero mb-4">
    <div class="row align-items-center g-4">
      <div class="col-lg-8">
        <p class="text-white-50 text-uppercase small mb-2">Welcome</p>
        <h1 class="display-6 fw-bold mb-3">Find events you'll love — and book them in minutes</h1>
        <p class="lead mb-0 opacity-90">
          EventHub brings workshops, talks, and meetups together in one calm, simple place. Explore what's
          on near you, grab the seats you need, and keep every booking in your account whenever you come
          back.
        </p>
      </div>
      <div class="col-lg-4 text-lg-end">
        <RouterLink class="btn btn-light btn-lg w-100 w-lg-auto" to="/events">See what's on</RouterLink>
      </div>
    </div>
  </section>

  <div class="row g-4">
    <div class="col-md-4">
      <div class="card eh-card h-100 p-3">
        <h2 class="h5">Explore with ease</h2>
        <p class="text-secondary small mb-0">
          Search by name or venue, narrow dates that suit you, and flip through pages of events until
          something catches your eye — no clutter, just clear details.
        </p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card eh-card h-100 p-3">
        <h2 class="h5">Book with confidence</h2>
        <p class="text-secondary small mb-0">
          Create a free account, reserve your tickets, and pay when it’s time. Your bookings stay in one
          list so you always know what you’ve got coming up.
        </p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card eh-card h-100 p-3">
        <h2 class="h5">Behind the scenes</h2>
        <p class="text-secondary small mb-0">
          Running events? Trusted organizers can add new listings, update details, and help guests — so
          everyone sees accurate times, places, and availability.
        </p>
      </div>
    </div>
  </div>
</template>
