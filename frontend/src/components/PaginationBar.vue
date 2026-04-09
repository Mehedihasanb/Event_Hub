<script setup>
import { computed } from 'vue';

const props = defineProps({
  meta: { type: Object, default: null },
});

const emit = defineEmits(['change']);

const totalPages = computed(() => {
  if (!props.meta?.total) return 1;
  return Math.max(1, Math.ceil(props.meta.total / props.meta.per_page));
});

function go(page) {
  if (!props.meta) return;
  if (page < 1 || page > totalPages.value) return;
  emit('change', page);
}
</script>

<template>
  <nav v-if="meta && meta.total > meta.per_page" class="mt-3" aria-label="Pagination">
    <ul class="pagination pagination-sm flex-wrap mb-0">
      <li class="page-item" :class="{ disabled: meta.page <= 1 }">
        <button type="button" class="page-link" @click="go(meta.page - 1)">Previous</button>
      </li>
      <li class="page-item disabled">
        <span class="page-link"
          >Page {{ meta.page }} / {{ totalPages }} ({{ meta.total }} total)</span
        >
      </li>
      <li class="page-item" :class="{ disabled: meta.page >= totalPages }">
        <button type="button" class="page-link" @click="go(meta.page + 1)">Next</button>
      </li>
    </ul>
  </nav>
</template>
