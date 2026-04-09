<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { api } from '../api/client';
import { useAuthStore } from '../stores/auth';
import { lineTotalFromCents } from '../utils/money';

const props = defineProps({
  event: { type: Object, required: true },
});

const router = useRouter();
const auth = useAuthStore();

const quantity = ref(1);
const busy = ref(false);
const message = ref('');
const error = ref('');

const maxQty = computed(() => Math.min(10, props.event.tickets_available || 0));

const estimatedTotal = computed(() =>
  lineTotalFromCents(props.event.price_cents, quantity.value),
);

async function submit() {
  message.value = '';
  error.value = '';
  if (!auth.isAuthenticated) {
    router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } });
    return;
  }
  busy.value = true;
  try {
    const { data } = await api.post('/bookings', {
      event_id: props.event.id,
      quantity: quantity.value,
    });
    message.value = `Booking #${data.data.id} created. You can pay from My bookings.`;
  } catch (e) {
    error.value = e.message;
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <div class="card eh-card">
    <div class="card-body">
      <h3 class="h6 text-uppercase text-muted mb-3">Book tickets</h3>
      <div v-if="event.tickets_available < 1" class="alert alert-warning mb-0">Sold out.</div>
      <template v-else>
        <div class="mb-3">
          <label class="form-label" for="qty">Quantity</label>
          <input
            id="qty"
            v-model.number="quantity"
            type="number"
            class="form-control"
            min="1"
            :max="maxQty"
          />
          <div class="form-text">Maximum {{ maxQty }} for this demo.</div>
        </div>
        <p class="small mb-3">
          <span class="text-muted">Estimated total:</span>
          <strong class="ms-1">{{ estimatedTotal }}</strong>
        </p>
        <button
          type="button"
          class="btn btn-accent w-100"
          :disabled="busy"
          @click="submit"
        >
          {{ auth.isAuthenticated ? 'Reserve tickets' : 'Log in to book' }}
        </button>
        <p v-if="message" class="text-success small mt-2 mb-0">{{ message }}</p>
        <p v-if="error" class="text-danger small mt-2 mb-0">{{ error }}</p>
      </template>
    </div>
  </div>
</template>
