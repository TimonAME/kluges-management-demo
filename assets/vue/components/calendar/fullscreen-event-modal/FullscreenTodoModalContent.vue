<template>
  <div class="space-y-6">
    <!-- Due Date -->
    <div class="bg-white rounded-window shadow-sm p-4 md:p-6">
      <h3 class="text-lg font-medium mb-4">Fälligkeitsdatum</h3>
      <div class="flex flex-col md:flex-row md:items-center gap-2 md:gap-4">
        <input
          v-if="mode !== 'view'"
          type="date"
          v-model="todoData.dueDate"
          class="date-input w-full md:w-auto"
        />
        <div v-else class="text-gray-700 text-lg">
          {{ formatDate(todoData.dueDate) }}
        </div>
      </div>
    </div>

    <!-- Description -->
    <div class="bg-white rounded-window shadow-sm p-4 md:p-6">
      <h3 class="text-lg font-medium mb-4">Beschreibung</h3>
      <textarea
        v-if="mode !== 'view'"
        v-model="todoData.description"
        placeholder="Beschreibung hinzufügen..."
        rows="6"
        class="description-input"
      ></textarea>
      <div v-else class="text-gray-700 whitespace-pre-wrap">
        {{ todoData.description || "Keine Beschreibung" }}
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