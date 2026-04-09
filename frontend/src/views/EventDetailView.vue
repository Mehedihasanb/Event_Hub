<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { api } from '../api/client';
import BookingForm from '../components/BookingForm.vue';

const props = defineProps({
  id: { type: [String, Number], required: true },
});

const route = useRoute();
const event = ref(null);
const loading = ref(true);
const err = ref('');

function formatDate(iso) {
  if (!iso) return '';
  return new Date(iso).toLocaleString(undefined, { dateStyle: 'full', timeStyle: 'short' });
}

function priceLabel(cents) {
  if (!cents) return 'Free';
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'EUR' }).format(
    cents / 100,
  );
}

async function load() {
  loading.value = true;
  err.value = '';
  try {
    const { data } = await api.get(`/events/${props.id}`);
    event.value = data.data;
  } catch (e) {
    err.value = e.message;
    event.value = null;
  } finally {
    loading.value = false;
  }
}

onMounted(load);
watch(
  () => route.params.id,
  () => load(),
);
</script>

<template>
  <p v-if="loading" class="text-secondary">Loading event…</p>
  <p v-else-if="err" class="text-danger">{{ err }}</p>
  <div v-else-if="event" class="row g-4">
    <div class="col-lg-8">
      <article class="card eh-card">
        <div class="card-body p-4">
          <p class="text-uppercase small text-muted mb-1">{{ event.venue }}</p>
          <h1 class="h2 mb-3">{{ event.title }}</h1>
          <p class="text-secondary mb-4">{{ event.description }}</p>
          <dl class="row small mb-0">
            <dt class="col-sm-3">Starts</dt>
            <dd class="col-sm-9">{{ formatDate(event.start_at) }}</dd>
            <dt class="col-sm-3">Ends</dt>
            <dd class="col-sm-9">{{ formatDate(event.end_at) }}</dd>
            <dt class="col-sm-3">Price</dt>
            <dd class="col-sm-9">{{ priceLabel(event.price_cents) }}</dd>
            <dt class="col-sm-3">Availability</dt>
            <dd class="col-sm-9">{{ event.tickets_available }} / {{ event.tickets_total }}</dd>
          </dl>
        </div>
      </article>
    </div>
    <div class="col-lg-4">
      <BookingForm :event="event" />
    </div>
  </div>
</template>
