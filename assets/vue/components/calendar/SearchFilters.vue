<!-- SeatchFilters.vue -->

<template>
  <div class="flex flex-col gap-2">
    <!-- Search Input -->
    <div class="p-2 flex-shrink-0">
      <div class="relative">
        <input
          type="text"
          :value="modelValue"
          @input="$emit('update:modelValue', $event.target.value)"
          placeholder="Termine suchen..."
          class="w-full px-4 py-2 pr-10 rounded-window border border-gray-300 focus:outline-none focus:ring-2 focus:ring-second_accent focus:border-transparent"
        />
        <svg
          class="absolute right-3 top-2.5 h-5 w-5 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
        </svg>
      </div>
    </div>

    <!-- Filter Buttons -->
    <div class="px-4 py-2 flex-shrink-0 flex flex-wrap gap-2">
      <button
        v-for="filter in filters"
        :key="filter.value"
        @click="$emit('filter-change', filter.value)"
        :class="[
          'px-3 py-1 rounded-full text-sm font-medium transition-colors',
          currentFilter === filter.value
            ? 'bg-second_accent text-white'
            : 'bg-gray-50 text-icon_color hover:bg-gray-100',
        ]"
      >
        {{ filter.label }}
      </button>
    </div>
  </div>
</template>

<script setup>
/**
 * Search and filter interface for calendar events
 * @displayName SearchFilters
 */

// Props
const props = defineProps({
  /**
   * Current search query
   */
  modelValue: {
    type: String,
    default: "",
  },
  /**
   * Currently active filter
   */
  currentFilter: {
    type: String,
    required: true,
  },
  /**
   * Available filter options
   */
  filters: {
    type: Array,
    required: true,
  },
});

/**
 * Emitted when search value changes
 * @event update:modelValue
 * @type {string}
 */

/**
 * Emitted when filter selection changes
 * @event filter-change
 * @type {string}
 */

defineEmits(["update:modelValue", "filter-change"]);
</script>

<style scoped>
/* Fokus-Ring-Animation */
input:focus {
  transition: all 0.2s ease-in-out;
}

/* Filter-Button-Animation */
button {
  transition: all 0.2s ease-in-out;
}

button:active {
  transform: scale(0.95);
}
</style>
