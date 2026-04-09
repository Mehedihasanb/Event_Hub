<script setup>
import { ref, onMounted } from 'vue';
import { api } from '../api/client';
import PaginationBar from '../components/PaginationBar.vue';
import { lineTotalFromCents } from '../utils/money';

const rows = ref([]);
const meta = ref(null);
const page = ref(1);
const loading = ref(true);
const err = ref('');
const info = ref('');

async function load() {
  loading.value = true;
  err.value = '';
  try {
    const { data } = await api.get('/bookings', { params: { page: page.value, per_page: 10 } });
    rows.value = data.data;
    meta.value = data.meta;
  } catch (e) {
    err.value = e.message;
  } finally {
    loading.value = false;
  }
}

/** User-facing payment confirmation (no internal provider names). */
function paymentConfirmationText(data) {
  const refId = data.payment?.reference || '';
  const provider = String(data.payment?.provider || '').toLowerCase();

  let headline = 'Your payment was confirmed.';
  if (provider === 'internal' || refId.startsWith('free-')) {
    headline = 'No payment needed — this booking is free and confirmed.';
  } else if (provider === 'stripe') {
    headline = 'Card payment completed successfully.';
  } else if (provider === 'sandbox') {
    headline = 'Test payment completed successfully.';
  }

  if (refId && !refId.startsWith('free-')) {
    return `${headline} Confirmation code: ${refId}.`;
  }
  return headline;
}

async function pay(id) {
  info.value = '';
  try {
    const { data } = await api.post(`/bookings/${id}/pay`);
    info.value = paymentConfirmationText(data);
    await load();
  } catch (e) {
    err.value = e.message;
  }
}

async function cancelBooking(id) {
  info.value = '';
  if (!confirm('Cancel this pending booking?')) return;
  try {
    await api.delete(`/bookings/${id}`);
    info.value = 'Booking cancelled.';
    await load();
  } catch (e) {
    err.value = e.message;
  }
}

async function onPage(p) {
  page.value = p;
  await load();
}

onMounted(load);
</script>

<template>
  <h1 class="h3 mb-3">My bookings</h1>
  <p class="text-secondary mb-0">
    Here you can see every reservation you have made. For tickets that still need payment, use
    <strong>Pay</strong> to complete checkout, or <strong>Cancel</strong> to release your seats. Once a booking is
    marked <strong>paid</strong>, it is treated as final in this demo (changes need an administrator).
  </p>
  <p v-if="info" class="alert alert-success py-2 mt-3 mb-0">{{ info }}</p>
  <p v-if="loading" class="text-secondary mt-3">Loading…</p>
  <p v-else-if="err" class="text-danger mt-3">{{ err }}</p>
  <div v-else class="table-responsive card eh-card mt-3">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>Event</th>
          <th class="d-none d-md-table-cell">When</th>
          <th>Qty</th>
          <th class="text-end">Total</th>
          <th>Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="b in rows" :key="b.id">
          <td>
            <div class="fw-semibold">{{ b.event_title }}</div>
            <div class="small text-muted">{{ b.venue }}</div>
          </td>
          <td class="d-none d-md-table-cell small">{{ new Date(b.start_at).toLocaleString() }}</td>
          <td>{{ b.quantity }}</td>
          <td class="text-end text-nowrap small">
            {{ lineTotalFromCents(b.price_cents, b.quantity) }}
          </td>
          <td>
            <span class="badge" :class="b.status === 'paid' ? 'bg-success' : 'bg-warning text-dark'">{{
              b.status
            }}</span>
          </td>
          <td class="text-end text-nowrap">
            <button
              v-if="b.status === 'pending'"
              type="button"
              class="btn btn-sm btn-primary me-1"
              @click="pay(b.id)"
            >
              Pay
            </button>
            <button
              v-if="b.status === 'pending'"
              type="button"
              class="btn btn-sm btn-outline-danger"
              @click="cancelBooking(b.id)"
            >
              Cancel
            </button>
          </td>
        </tr>
        <tr v-if="!rows.length">
          <td colspan="6" class="text-center text-muted py-4">No bookings yet.</td>
        </tr>
      </tbody>
    </table>
  </div>
  <PaginationBar :meta="meta" @change="onPage" />
</template>
