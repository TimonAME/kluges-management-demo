<template>
  <div
    class="flex-shrink-0 flex justify-between items-center p-4 md:p-6 bg-white border-t border-gray-200"
  >
    <!-- Left side: Delete button (only in view mode) -->
    <div>
      <button
        v-if="mode === 'view'"
        @click="emit('delete')"
        class="px-4 py-2 text-red-600 hover:bg-red-50 rounded-window transition-colors"
      >
        Löschen
      </button>
    </div>
    
    <!-- Right side: Cancel and Save buttons -->
    <div class="flex gap-3">
      <!-- Check Todo Button - Only shown for todos in view mode -->
      <button
        v-if="mode === 'view' && isTodo"
        @click="emit('check-todo')"
        class="px-4 py-2 text-second_accent hover:bg-second_accent/10 rounded-window transition-colors"
      >
        {{ todoData?.completed ? 'Als unerledigt markieren' : 'Als erledigt markieren' }}
      </button>
      
      <button
        @click="emit('close')"
        class="px-4 py-2 text-gray-700 hover:bg-gray-50 rounded-window transition-colors"
      >
        Abbrechen
      </button>
      
      <button
        v-if="mode !== 'view'"
        :disabled="isSaving"
        @click="emit('save')"
        class="px-6 py-2 bg-second_accent text-white rounded-window hover:bg-second_accent/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <template v-if="!isSaving"> Speichern </template>
        <div v-else class="flex items-center gap-2">
          <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            />
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            />
          </svg>
          Wird gespeichert...
        </div>
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  mode: {
    type: String,
    required: true,
    validator: (value) => ["view", "edit", "new"].includes(value),
  },
  isSaving: {
    type: Boolean,
    default: false,
  },
  isTodo: {
    type: Boolean,
    default: false,
  },
  todoData: {
    type: Object,
    default: null,
  }
});

const emit = defineEmits(["save", "delete", "close", "check-todo"]);
</script>

<style scoped>
/* Smooth transitions */
.transition-colors {
  transition-property: colors, background-color;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

/* Loading spinner animation */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Better focus states */
button:focus-visible {
  outline: 2px solid theme("colors.second_accent");
  outline-offset: 2px;
}

/* Active state animation */
button:active:not(:disabled) {
  transform: scale(0.98);
}
</style> 