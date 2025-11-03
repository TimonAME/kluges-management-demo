<template>
  <div class="flex-shrink-0 flex items-center justify-between p-4 md:p-6 border-b border-gray-200 bg-white">
    <!-- Title Section -->
    <div class="flex-grow flex items-center gap-3">
      <!-- Zurück-Button -->
      <button
        @click="$emit('close')"
        class="header-button"
        aria-label="Schließen"
      >
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
          <path
            d="M19 12H5M5 12L12 19M5 12L12 5"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </button>

      <!-- Color Indicator for View Mode -->
      <div
        v-if="mode === 'view' && !isTodo"
        class="w-4 h-4 rounded-full flex-shrink-0"
        :style="{ backgroundColor: eventData.color }"
      ></div>
      
      <!-- Todo Icon für Todos -->
      <div
        v-if="isTodo"
        class="w-6 h-6 flex-shrink-0 text-gray-500"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M9 11l3 3L22 4" />
          <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" />
        </svg>
      </div>

      <!-- Title Input or Display -->
      <input
        v-if="mode !== 'view'"
        type="text"
        v-model="eventData.title"
        placeholder="Titel hinzufügen"
        class="w-full text-xl font-medium focus:outline-none focus:ring-2 focus:ring-second_accent focus:border-transparent rounded-window"
      />
      <h1 
        v-else 
        class="text-xl font-medium"
        :class="{ 
          'cursor-pointer hover:text-second_accent': !isTodo && nachhilfeterminCategoryId && isNachhilfetermin,
          'line-through text-gray-500': isTodo && eventData.completed
        }"
        @click="handleTitleClick"
      >
        {{ eventData.title }}
      </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center gap-2">
      <!-- Edit Button - Only shown in view mode -->
      <button
        v-if="mode === 'view'"
        @click="handleEditClick"
        class="header-button"
        aria-label="Bearbeiten"
      >
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
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
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
  mode: {
    type: String,
    required: true,
    validator: (value) => ["view", "edit", "new"].includes(value),
  },
  eventData: {
    type: Object,
    required: true,
  },
  isTodo: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(["update:mode", "close"]);
const nachhilfeterminCategoryId = ref(null);

// Computed property to check if this is a Nachhilfetermin
const isNachhilfetermin = computed(() => {
  if (!nachhilfeterminCategoryId.value || !props.eventData.appointmentCategory) return false;
  
  const categoryId = typeof props.eventData.appointmentCategory === 'object' 
    ? props.eventData.appointmentCategory.id 
    : props.eventData.appointmentCategory;
    
  return categoryId === nachhilfeterminCategoryId.value;
});

// Kategorie-ID für Nachhilfetermin laden
onMounted(async () => {
  if (!props.isTodo) {
    try {
      const response = await axios.get('/api/appointmentCategory');
      const categories = response.data;
      const nachhilfeCategory = categories.find(cat => cat.name === 'Nachhilfetermin');
      if (nachhilfeCategory) {
        nachhilfeterminCategoryId.value = nachhilfeCategory.id;
      }
    } catch (error) {
      console.error('Fehler beim Laden der Terminkategorien:', error);
    }
  }
});

// Funktion zum Behandeln des Bearbeiten-Klicks
const handleEditClick = () => {
  if (props.isTodo) {
    emit("update:mode", "edit");
    return;
  }
  
  // Prüfen, ob es sich um einen Nachhilfetermin handelt
  if (isNachhilfetermin.value) {
    // Zu Nachhilfetermin-Seite navigieren mit Edit-Modus
    window.location.href = `/nachhilfetermin/${props.eventData.id}?mode=edit`;
  } else {
    // Normales Bearbeiten für andere Termine
    emit("update:mode", "edit");
  }
};

// Funktion zum Behandeln des Titel-Klicks
const handleTitleClick = () => {
  if (props.mode === 'view' && !props.isTodo && isNachhilfetermin.value) {
    window.location.href = `/nachhilfetermin/${props.eventData.id}`;
  }
};
</script>

<style scoped>
.header-button {
  @apply p-2 hover:bg-gray-50 rounded-window transition-colors text-gray-500;
}

/* Button hover effects */
.header-button:hover svg {
  @apply scale-105 transition-transform;
}

/* Focus state for better accessibility */
.header-button:focus-visible {
  @apply outline-none ring-2 ring-second_accent;
}

/* Input focus state */
input:focus {
  @apply bg-gray-50/50;
}

/* Active state animation */
button:active {
  @apply scale-95;
}
</style> 