<template>
  <div class="space-y-6">
    <!-- Date/Time Section -->
    <div class="bg-white rounded-window shadow-sm p-4 md:p-6">
      <h2 class="text-lg font-medium mb-4">Datum & Uhrzeit</h2>
      
      <!-- All Day Toggle -->
      <div class="mb-4" v-if="mode !== 'view'">
        <label class="flex items-center text-sm text-gray-600">
          <input
            type="checkbox"
            v-model="eventData.allDay"
            class="mr-2 rounded border-gray-300 text-second_accent focus:ring-second_accent"
          />
          Ganztägig
        </label>
      </div>

      <!-- Date/Time Inputs -->
      <div class="space-y-4">
        <!-- Start Date/Time -->
        <div class="flex flex-col md:flex-row md:items-center gap-2 md:gap-4">
          <label class="text-sm text-gray-600 md:w-24">Beginn</label>
          <div class="flex flex-1 flex-col sm:flex-row gap-2">
            <input
              type="date"
              v-model="eventData.startDate"
              :disabled="mode === 'view'"
              class="flex-1 date-input"
            />
            <input
              v-if="!eventData.allDay"
              type="time"
              v-model="eventData.startTime"
              :disabled="mode === 'view'"
              class="flex-1 time-input"
            />
          </div>
        </div>

        <!-- End Date/Time -->
        <div class="flex flex-col md:flex-row md:items-center gap-2 md:gap-4">
          <label class="text-sm text-gray-600 md:w-24">Ende</label>
          <div class="flex flex-1 flex-col sm:flex-row gap-2">
            <input
              type="date"
              v-model="eventData.endDate"
              :disabled="mode === 'view'"
              class="flex-1 date-input"
            />
            <input
              v-if="!eventData.allDay"
              type="time"
              v-model="eventData.endTime"
              :disabled="mode === 'view'"
              class="flex-1 time-input"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Description Section -->
    <div class="bg-white rounded-window shadow-sm p-4 md:p-6">
      <h2 class="text-lg font-medium mb-4">Beschreibung</h2>
      <textarea
        v-if="mode !== 'view'"
        v-model="eventData.description"
        placeholder="Beschreibung hinzufügen..."
        rows="4"
        class="w-full border rounded-window px-3 py-2 focus:ring-2 focus:ring-second_accent focus:border-transparent border-gray-300"
      ></textarea>
      <div v-else class="text-gray-700 whitespace-pre-wrap">
        {{ eventData.description || "Keine Beschreibung" }}
      </div>
    </div>

    <!-- Participants Section -->
    <div class="bg-white rounded-window shadow-sm p-4 md:p-6">
      <h2 class="text-lg font-medium mb-4">Teilnehmer</h2>

      <!-- User Search Input - Only in edit/new mode -->
      <div v-if="mode !== 'view'" class="mb-4 relative">
        <input
          type="text"
          :value="userSearch"
          @input="$emit('update:userSearch', $event.target.value)"
          placeholder="Nach Teilnehmern suchen..."
          class="w-full border rounded-window px-3 py-2 focus:ring-2 focus:ring-second_accent focus:border-transparent border-gray-300"
        />
        
        <!-- Loading Indicator -->
        <div v-if="isSearching" class="absolute right-3 top-2.5">
          <svg class="animate-spin h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none">
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
        </div>
        
        <!-- Search Results -->
        <div
          v-if="userSearch && searchResults.length > 0"
          class="search-results"
        >
          <div
            v-for="user in searchResults"
            :key="user.id"
            @click="$emit('add-user', user)"
            class="search-result-item"
          >
            <div class="font-medium text-gray-700">
              {{ user.first_name }} {{ user.last_name }}
            </div>
            <div class="text-sm text-gray-500">{{ user.email }}</div>
          </div>
        </div>
      </div>

      <!-- Selected Users -->
      <div class="flex flex-wrap gap-2">
        <!-- Creator -->
        <div class="creator-tag">
          <span class="font-medium text-white">{{
            mode === "new" ? currentName : eventData.creator
          }}</span>
          <span class="text-xs text-white ml-1">(Ersteller)</span>
        </div>
        
        <!-- Other Users -->
        <div
          v-for="user in eventData.users"
          :key="user.id"
          class="user-tag"
        >
          <span class="text-gray-600">{{ user.first_name }} {{ user.last_name }}</span>
          <button
            v-if="mode !== 'view'"
            @click="$emit('remove-user', user)"
            class="ml-1 text-gray-400 hover:text-gray-600 transition-colors"
          >
            ×
          </button>
        </div>
      </div>
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
  eventData: {
    type: Object,
    required: true,
  },
  userSearch: {
    type: String,
    required: true,
  },
  searchResults: {
    type: Array,
    default: () => [],
  },
  isSearching: {
    type: Boolean,
    default: false,
  },
  currentName: {
    type: String,
    default: "",
  },
});

defineEmits(["update:userSearch", "add-user", "remove-user"]);
</script>

<style scoped>
.date-input,
.time-input {
  @apply border rounded-window px-3 py-2 focus:ring-2 focus:ring-second_accent focus:border-transparent border-gray-300;
}

/* Search results */
.search-results {
  @apply absolute z-10 w-full mt-1 bg-window_bg border rounded-window shadow-lg max-h-48 overflow-y-auto border-gray-200;
}

.search-result-item {
  @apply px-3 py-2 hover:bg-gray-50 cursor-pointer transition-colors;
}

/* Tags */
.creator-tag {
  @apply flex items-center gap-1 bg-second_accent text-white rounded-full px-3 py-1 text-sm;
}

.user-tag {
  @apply flex items-center gap-1 bg-gray-50 rounded-full px-3 py-1 text-sm;
}

/* Scrollbar */
.overflow-y-auto {
  scrollbar-width: thin;
  scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
}

.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background-color: rgba(0, 0, 0, 0.2);
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background-color: rgba(0, 0, 0, 0.3);
}
</style> 