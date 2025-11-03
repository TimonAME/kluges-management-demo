// EventModalContent.vue
<template>
  <div class="flex-1 overflow-y-auto min-h-0">
    <div class="p-4 space-y-4">
      <!-- Date/Time Section -->
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
          <!-- All Day Toggle -->
          <div class="mb-2" v-if="mode !== 'view'">
            <label class="flex items-center text-sm text-gray-500 mb-2">
              <input
                type="checkbox"
                v-model="eventData.allDay"
                class="mr-2 rounded border-gray-300 text-second_accent focus:ring-second_accent"
              />
              Ganztägig
            </label>
          </div>

          <!-- Date/Time Inputs -->
          <div class="space-y-2">
            <!-- Start Date/Time -->
            <div class="flex items-center gap-2">
              <template v-if="mode !== 'view'">
                <input
                  type="date"
                  v-model="eventData.startDate"
                  class="date-input flex-grow"
                />
                <input
                  v-if="!eventData.allDay"
                  type="time"
                  v-model="eventData.startTime"
                  class="time-input"
                />
              </template>
              <span v-else class="text-gray-600">
                {{
                  formatDateTime(
                    eventData.startDate + "T" + eventData.startTime,
                    eventData.allDay,
                  )
                }}
              </span>
            </div>

            <!-- End Date/Time -->
            <div class="flex items-center gap-2">
              <template v-if="mode !== 'view'">
                <input
                  type="date"
                  v-model="eventData.endDate"
                  :disabled="!eventData.allDay"
                  class="date-input flex-grow"
                  :class="{
                    'opacity-50 cursor-not-allowed': !eventData.allDay,
                  }"
                />
                <input
                  v-if="!eventData.allDay"
                  type="time"
                  v-model="eventData.endTime"
                  class="time-input"
                />
              </template>
              <span v-else class="text-gray-600">
                {{
                  formatDateTime(
                    eventData.endDate + "T" + eventData.endTime,
                    eventData.allDay,
                  )
                }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Users Section -->
      <div class="flex items-start gap-4">
        <div
          class="flex-shrink-0 mt-1 p-2 bg-gray-50 rounded-window text-gray-500"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
            <path
              d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
            <path
              d="M9 11C11.2091 11 13 9.20914 13 7C13 4.79086 11.2091 3 9 3C6.79086 3 5 4.79086 5 7C5 9.20914 6.79086 11 9 11Z"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
            <path
              d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
            <path
              d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </div>
        <div class="flex-grow">
          <!-- User Search -->
          <div class="space-y-2">
            <div v-if="mode !== 'view'" class="relative">
              <input
                type="text"
                :value="userSearch"
                @input="$emit('update:userSearch', $event.target.value)"
                placeholder="Nach Teilnehmern suchen..."
              />
              <!-- Loading Indicator -->
              <div v-if="isSearching" class="absolute right-3 top-2.5">
                <svg
                  class="animate-spin h-5 w-5 text-gray-400"
                  viewBox="0 0 24 24"
                  fill="none"
                >
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
          </div>

          <!-- Selected Users -->
          <div class="flex flex-wrap gap-2 mt-2">
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
              <span class="text-gray-600"
                >{{ user.first_name }} {{ user.last_name }}</span
              >
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

      <!-- Description -->
      <div class="flex items-start gap-4">
        <div
          class="flex-shrink-0 mt-1 p-2 bg-gray-50 rounded-window text-gray-500"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
            <path
              d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </div>
        <div class="flex-grow">
          <textarea
            v-if="mode !== 'view'"
            v-model="eventData.description"
            placeholder="Beschreibung hinzufügen"
            class="description-input"
            rows="3"
          ></textarea>
          <p v-else class="text-gray-600">
            {{ eventData.description || "Keine Beschreibung" }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from "vue";

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
  userSearch: {
    type: String,
    default: "",
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
    required: true,
  },
});

const emit = defineEmits([
  "user-search",
  "add-user",
  "remove-user",
  "update:userSearch",
]);

const formatDateTime = (dateString, allDay) => {
  if (!dateString) return "";
  if (allDay) {
    const date = new Date(dateString);
    return date.toLocaleDateString("de-DE", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  } else {
    const date = new Date(dateString);
    return date.toLocaleDateString("de-DE", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  }
};
</script>

<style scoped>
/* Input styles */
.date-input,
.time-input,
.search-input,
.description-input {
  @apply border rounded-window px-3 py-2 focus:ring-2 focus:ring-second_accent focus:border-transparent border-gray-300;
}

.description-input {
  @apply w-full resize-none;
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
  @apply flex items-center gap-1 bg-second_accent rounded-full px-3 py-1 text-sm;
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
