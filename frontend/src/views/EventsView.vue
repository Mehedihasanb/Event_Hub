<script setup>
import { ref, watch, onMounted } from 'vue';
import { api } from '../api/client';
import EventCard from '../components/EventCard.vue';
import PaginationBar from '../components/PaginationBar.vue';

const events = ref([]);
const meta = ref(null);
const loading = ref(true);
const err = ref('');

const filters = ref({
  search: '',
  venue: '',
  from: '',
  to: '',
  page: 1,
  per_page: 9,
});

async function load() {
  loading.value = true;
  err.value = '';
  try {
    const { data } = await api.get('/events', { params: { ...filters.value } });
    events.value = data.data;
    meta.value = data.meta;
  } catch (e) {
    err.value = e.message;
  } finally {
    loading.value = false;
  }
}

function onPage(p) {
  filters.value.page = p;
}

watch(filters, load, { deep: true });
onMounted(load);
</script>

<template>
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
    <div>
      <h1 class="h3 mb-1">Events</h1>
      <p class="text-secondary mb-0">Filter by text, venue, or date range. Results are paginated.</p>
    </div>
  </div>

  <div class="card eh-card mb-4">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label small">Search</label>
          <input v-model="filters.search" class="form-control" placeholder="Title or description" />
        </div>
        <div class="col-md-3">
          <label class="form-label small">Venue</label>
          <input v-model="filters.venue" class="form-control" placeholder="Campus, city…" />
        </div>
        <div class="col-md-2 col-6">
          <label class="form-label small">From</label>
          <input v-model="filters.from" type="datetime-local" class="form-control" />
        </div>
        <div class="col-md-2 col-6">
          <label class="form-label small">To</label>
          <input v-model="filters.to" type="datetime-local" class="form-control" />
        </div>
        <div class="col-md-1 d-flex align-items-end">
          <button type="button" class="btn btn-outline-secondary w-100" @click="filters.page = 1">
            Apply
          </button>
        </div>
      </div>
    </div>
  </div>

  <p v-if="loading" class="text-secondary">Loading…</p>
  <p v-else-if="err" class="text-danger">{{ err }}</p>
  <div v-else class="row g-4">
    <div v-for="ev in events" :key="ev.id" class="col-sm-6 col-xl-4">
      <EventCard :event="ev" />
    </div>
    <div v-if="!events.length" class="col-12">
      <p class="text-secondary">No events match your filters.</p>
    </div>
  </div>

  <PaginationBar :meta="meta" @change="onPage" />
</template>
