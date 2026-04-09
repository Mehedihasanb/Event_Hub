<script setup>
import { ref, onMounted, reactive } from 'vue';
import { api } from '../api/client';
import { formatEurosFromCents, eurosToCents, centsToEurosInput } from '../utils/money';

const events = ref([]);
const loading = ref(true);
const err = ref('');
const info = ref('');

const form = reactive({
  id: null,
  title: '',
  description: '',
  venue: '',
  start_at: '',
  end_at: '',
  tickets_total: 50,
  /** Ticket price in euros (per ticket) for the form; API still uses cents. */
  price_euros: 0,
});

function resetForm() {
  form.id = null;
  form.title = '';
  form.description = '';
  form.venue = '';
  form.start_at = '';
  form.end_at = '';
  form.tickets_total = 50;
  form.price_euros = 0;
}

function editRow(ev) {
  form.id = ev.id;
  form.title = ev.title;
  form.description = ev.description || '';
  form.venue = ev.venue;
  form.start_at = ev.start_at?.replace(' ', 'T').slice(0, 16);
  form.end_at = ev.end_at?.replace(' ', 'T').slice(0, 16);
  form.tickets_total = ev.tickets_total;
  form.price_euros = centsToEurosInput(ev.price_cents);
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function load() {
  loading.value = true;
  err.value = '';
  try {
    const { data } = await api.get('/events', { params: { per_page: 100 } });
    events.value = data.data;
  } catch (e) {
    err.value = e.message;
  } finally {
    loading.value = false;
  }
}

async function save() {
  info.value = '';
  err.value = '';
  const payload = {
    title: form.title,
    description: form.description,
    venue: form.venue,
    start_at: form.start_at.replace('T', ' ') + ':00',
    end_at: form.end_at.replace('T', ' ') + ':00',
    tickets_total: Number(form.tickets_total),
    price_cents: eurosToCents(form.price_euros),
  };
  try {
    if (form.id) {
      await api.put(`/events/${form.id}`, payload);
      info.value = 'Event updated.';
    } else {
      await api.post('/events', payload);
      info.value = 'Event created.';
    }
    resetForm();
    await load();
  } catch (e) {
    err.value = e.message;
  }
}

async function remove(id) {
  if (!confirm('Delete this event?')) return;
  err.value = '';
  try {
    await api.delete(`/events/${id}`);
    info.value = 'Event deleted.';
    await load();
  } catch (e) {
    err.value = e.message;
  }
}

onMounted(load);
</script>

<template>
  <h1 class="h3 mb-3">Admin · Events</h1>
  <p v-if="info" class="alert alert-success py-2">{{ info }}</p>
  <p v-if="err" class="alert alert-danger py-2">{{ err }}</p>

  <div class="card eh-card mb-4">
    <div class="card-body">
      <h2 class="h5 mb-3">{{ form.id ? 'Edit event' : 'Create event' }}</h2>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Title</label>
          <input v-model="form.title" class="form-control" required />
        </div>
        <div class="col-md-6">
          <label class="form-label">Venue</label>
          <input v-model="form.venue" class="form-control" required />
        </div>
        <div class="col-12">
          <label class="form-label">Description</label>
          <textarea v-model="form.description" class="form-control" rows="2" />
        </div>
        <div class="col-md-3 col-6">
          <label class="form-label">Start</label>
          <input v-model="form.start_at" type="datetime-local" class="form-control" required />
        </div>
        <div class="col-md-3 col-6">
          <label class="form-label">End</label>
          <input v-model="form.end_at" type="datetime-local" class="form-control" required />
        </div>
        <div class="col-md-3 col-6">
          <label class="form-label">Tickets total</label>
          <input v-model.number="form.tickets_total" type="number" min="0" class="form-control" />
        </div>
        <div class="col-md-3 col-6">
          <label class="form-label">Ticket price (€)</label>
          <div class="input-group">
            <span class="input-group-text">€</span>
            <input
              v-model.number="form.price_euros"
              type="number"
              class="form-control"
              min="0"
              step="0.01"
            />
          </div>
          <div class="form-text">Per ticket, including zero for free events.</div>
        </div>
        <div class="col-12 d-flex gap-2">
          <button type="button" class="btn btn-primary" @click="save">
            {{ form.id ? 'Update' : 'Create' }}
          </button>
          <button v-if="form.id" type="button" class="btn btn-outline-secondary" @click="resetForm">
            Cancel edit
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
          <th>Title</th>
          <th class="d-none d-lg-table-cell">Venue</th>
          <th>Tickets</th>
          <th class="text-end">Price</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="ev in events" :key="ev.id">
          <td>{{ ev.title }}</td>
          <td class="d-none d-lg-table-cell small">{{ ev.venue }}</td>
          <td class="small">{{ ev.tickets_available }}/{{ ev.tickets_total }}</td>
          <td class="text-end small">{{ formatEurosFromCents(ev.price_cents) }}</td>
          <td class="text-end text-nowrap">
            <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="editRow(ev)">
              Edit
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" @click="remove(ev.id)">
              Delete
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
