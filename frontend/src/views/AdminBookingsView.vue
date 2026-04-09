<script setup>
import { ref, onMounted, reactive, watch } from 'vue';
import { api } from '../api/client';
import PaginationBar from '../components/PaginationBar.vue';
import { lineTotalFromCents } from '../utils/money';

const rows = ref([]);
const meta = ref(null);
const loading = ref(true);
const err = ref('');
const info = ref('');

const filters = reactive({
  event_id: '',
  user_id: '',
  status: '',
  page: 1,
  per_page: 15,
});

async function load() {
  loading.value = true;
  err.value = '';
  const params = { ...filters };
  if (!params.event_id) delete params.event_id;
  else params.event_id = Number(params.event_id);
  if (!params.user_id) delete params.user_id;
  else params.user_id = Number(params.user_id);
  if (!params.status) delete params.status;
  try {
    const { data } = await api.get('/bookings', { params });
    rows.value = data.data;
    meta.value = data.meta;
  } catch (e) {
    err.value = e.message;
  } finally {
    loading.value = false;
  }
}

async function setStatus(id, status) {
  info.value = '';
  try {
    await api.put(`/bookings/${id}`, { status });
    info.value = 'Booking updated.';
    await load();
  } catch (e) {
    err.value = e.message;
  }
}

async function remove(id) {
  if (!confirm('Delete this booking? Tickets will be returned unless already cancelled.')) return;
  try {
    await api.delete(`/bookings/${id}`);
    info.value = 'Booking deleted.';
    await load();
  } catch (e) {
    err.value = e.message;
  }
}

function onPage(p) {
  filters.page = p;
}

watch(filters, load, { deep: true });
onMounted(load);
</script>

<template>
  <h1 class="h3 mb-3">Admin · Bookings</h1>
  <p v-if="info" class="alert alert-success py-2">{{ info }}</p>
  <p v-if="err" class="alert alert-danger py-2">{{ err }}</p>

  <div class="card eh-card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3 col-6">
          <label class="form-label small mb-0">User ID</label>
          <input v-model="filters.user_id" class="form-control form-control-sm" placeholder="optional" />
        </div>
        <div class="col-md-3 col-6">
          <label class="form-label small mb-0">Event ID</label>
          <input v-model="filters.event_id" class="form-control form-control-sm" placeholder="optional" />
        </div>
        <div class="col-md-3 col-6">
          <label class="form-label small mb-0">Status</label>
          <select v-model="filters.status" class="form-select form-select-sm">
            <option value="">Any</option>
            <option value="pending">pending</option>
            <option value="paid">paid</option>
            <option value="cancelled">cancelled</option>
          </select>
        </div>
        <div class="col-md-3 col-6">
          <button type="button" class="btn btn-sm btn-outline-secondary w-100" @click="filters.page = 1">
            Apply filters
          </button>
        </div>
      </div>
    </div>
  </div>

  <p v-if="loading" class="text-secondary">Loading…</p>
  <div v-else class="table-responsive card eh-card">
    <table class="table table-sm mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>User</th>
          <th>Event</th>
          <th>Qty</th>
          <th class="text-end">Total</th>
          <th>Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="b in rows" :key="b.id">
          <td>{{ b.id }}</td>
          <td>{{ b.user_id }}</td>
          <td>
            <div class="small fw-semibold">{{ b.event_title }}</div>
            <div class="small text-muted">#{{ b.event_id }}</div>
          </td>
          <td>{{ b.quantity }}</td>
          <td class="text-end small text-nowrap">
            {{ lineTotalFromCents(b.price_cents, b.quantity) }}
          </td>
          <td>
            <select
              class="form-select form-select-sm"
              :value="b.status"
              @change="setStatus(b.id, $event.target.value)"
            >
              <option value="pending">pending</option>
              <option value="paid">paid</option>
              <option value="cancelled">cancelled</option>
            </select>
          </td>
          <td class="text-end">
            <button type="button" class="btn btn-sm btn-outline-danger" @click="remove(b.id)">
              Delete
            </button>
          </td>
        </tr>
        <tr v-if="!rows.length">
          <td colspan="7" class="text-center text-muted py-4">No bookings.</td>
        </tr>
      </tbody>
    </table>
  </div>
  <PaginationBar :meta="meta" @change="onPage" />
</template>
