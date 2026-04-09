<script setup>
defineProps({
  event: { type: Object, required: true },
});

function formatDate(iso) {
  if (!iso) return '';
  const d = new Date(iso);
  return d.toLocaleString(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  });
}

function priceLabel(cents) {
  if (!cents) return 'Free';
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'EUR' }).format(
    cents / 100,
  );
}
</script>

<template>
  <div class="card eh-card h-100">
    <div class="card-body d-flex flex-column">
      <h2 class="h5 card-title">{{ event.title }}</h2>
      <p class="eh-muted small mb-2">{{ event.venue }}</p>
      <p class="card-text text-secondary flex-grow-1 small">
        {{ event.description?.slice(0, 140) }}{{ event.description?.length > 140 ? '…' : '' }}
      </p>
      <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mt-2">
        <span class="badge bg-primary-subtle text-primary-emphasis">{{
          formatDate(event.start_at)
        }}</span>
        <span class="fw-semibold">{{ priceLabel(event.price_cents) }}</span>
      </div>
      <div class="small text-muted mt-2">
        {{ event.tickets_available }} / {{ event.tickets_total }} tickets left
      </div>
      <RouterLink
        class="btn btn-primary btn-sm mt-3 align-self-start"
        :to="{ name: 'event-detail', params: { id: event.id } }"
      >
        View &amp; book
      </RouterLink>
    </div>
  </div>
</template>
