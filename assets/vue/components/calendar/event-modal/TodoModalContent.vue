<template>
  <div class="p-4 space-y-4">
    <!-- Due Date -->
    <div class="flex items-start gap-4">
      <div
        class="flex-shrink-0 mt-1 p-2 bg-gray-50 rounded-window text-gray-500"
      >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path
            d="M19 4H5C3.89543 4 3 4.89543 3 6V20C3 21.1046 3.89543 22 5 22H19C20.1046 22 21 21.1046 21 20V6C21 4.89543 20.1046 4 19 4Z"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
          <path
            d="M16 2V6M8 2V6M3 10H21"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </div>
      <div class="flex-grow">
        <div class="text-sm text-gray-500 mb-1">Fälligkeitsdatum</div>
        <input
          v-if="mode !== 'view'"
          type="date"
          v-model="todoData.dueDate"
          class="date-input w-full"
        />
        <div v-else class="text-gray-700">
          {{ formatDate(todoData.dueDate) }}
        </div>
      </div>
    </div>

    <!-- Description -->
    <div class="flex items-start gap-4">
      <div
        class="flex-shrink-0 mt-1 p-2 bg-gray-50 rounded-window text-gray-500"
      >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path
            d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
          <path
            d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </div>
      <div class="flex-grow">
        <div class="text-sm text-gray-500 mb-1">Beschreibung</div>
        <textarea
          v-if="mode !== 'view'"
          v-model="todoData.description"
          placeholder="Beschreibung hinzufügen..."
          rows="4"
          class="description-input"
        ></textarea>
        <div v-else class="text-gray-700 whitespace-pre-wrap">
          {{ todoData.description || "Keine Beschreibung" }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
  mode: {
    type: String,
    required: true,
    validator: (value) => ["view", "edit", "new"].includes(value),
  },
  todoData: {
    type: Object,
    required: true,
  },
});

defineEmits(['check-todo']);

// Format date for display
const formatDate = (dateString) => {
  if (!dateString) return 'Kein Fälligkeitsdatum';
  
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('de-DE', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  }).format(date);
};
</script>

<style scoped>
.date-input {
  @apply border rounded-window px-3 py-2 focus:ring-2 focus:ring-second_accent focus:border-transparent border-gray-300;
}

.description-input {
  @apply w-full border rounded-window px-3 py-2 focus:ring-2 focus:ring-second_accent focus:border-transparent border-gray-300 resize-none;
}
</style> 