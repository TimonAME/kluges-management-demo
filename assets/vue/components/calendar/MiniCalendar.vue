<!-- MiniCalendar.vue -->

<template>
  <div class="bg-window_bg w-full border-t border-gray-100">
    <vue-cal
      ref="vuecal"
      class="vuecal--date-picker p-1"
      :class="{ 'vuecal--loading': isLoading }"
      xsmall
      hide-view-selector
      :time="false"
      :transitions="false"
      :disable-views="['year', 'week', 'day']"
      active-view="month"
      :events="events"
      :on-event-click="handleEventClick"
      @cell-click="handleDateSelection"
      :selected-date="selectedDate"
      locale="de"
      :min-date="minDate"
      :max-date="maxDate"
    >
      <!-- Custom Header -->
      <template #header="{ view, previousPeriod, nextPeriod }">
        <div class="vuecal__header px-2 py-1">
          <div class="flex justify-between items-center">
            <!-- Navigation Buttons -->
            <button
              @click="previousPeriod"
              class="p-1 rounded-full hover:bg-gray-100 transition-colors"
              :aria-label="'Vorheriger ' + view.id"
            >
              <svg
                class="w-4 h-4 text-gray-600"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 19l-7-7 7-7"
                />
              </svg>
            </button>

            <!-- Current Period Label -->
            <span class="text-sm font-medium text-gray-700">
              {{ formatMonthYear(view.startDate) }}
            </span>

            <button
              @click="nextPeriod"
              class="p-1 rounded-full hover:bg-gray-100 transition-colors"
              :aria-label="'Nächster ' + view.id"
            >
              <svg
                class="w-4 h-4 text-gray-600"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7"
                />
              </svg>
            </button>
          </div>
        </div>
      </template>

      <!-- Custom Cell -->
      <template #cell="{ cell, events, isToday, isOutOfScope }">
        <div
          :class="[
            'vuecal__cell-content rounded-md transition-colors',
            {
              'bg-second_accent/10': isToday,
              'text-gray-400': isOutOfScope,
              'hover:bg-gray-100': !isOutOfScope,
            },
          ]"
        >
          <!-- Date Number -->
          <span
            :class="[
              'text-xs font-medium',
              {
                'text-second_accent': isToday,
                'text-gray-900': !isOutOfScope && !isToday,
              },
            ]"
          >
            {{ cell.content }}
          </span>

          <!-- Event Indicators -->
          <div class="flex gap-0.5 mt-0.5 justify-center" v-if="events.length">
            <div
              v-for="(event, i) in events.slice(0, 3)"
              :key="i"
              class="w-1 h-1 rounded-full"
              :style="{ backgroundColor: event.color || '#4884f4' }"
            />
            <div
              v-if="events.length > 3"
              class="w-1 h-1 rounded-full bg-gray-400"
            />
          </div>
        </div>
      </template>
    </vue-cal>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";
import VueCal from "vue-cal";
import "vue-cal/dist/vuecal.css";
import { getMonthYearString } from "../../utils/dateFormatters";

/**
 * Mini calendar component for sidebar navigation
 * @displayName MiniCalendar
 */

const props = defineProps({
  /**
   * Calendar events to display
   */
  events: {
    type: Array,
    default: () => [],
  },
  /**
   * Currently selected date
   */
  selectedDate: {
    type: Date,
    default: () => new Date(),
  },
  /**
   * Loading state indicator
   */
  isLoading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["date-select", "event-click"]);

// Refs
const vuecal = ref(null);

// Computed
const minDate = computed(() => {
  const date = new Date();
  date.setFullYear(date.getFullYear() - 1);
  return date;
});

const maxDate = computed(() => {
  const date = new Date();
  date.setFullYear(date.getFullYear() + 2);
  return date;
});

// Methods
const formatMonthYear = (date) => {
  return getMonthYearString(date);
};

const handleDateSelection = (date) => {
  emit("date-select", date);
};

const handleEventClick = (event, e) => {
  e.stopPropagation();
  emit("event-click", event);
};
</script>

<style scoped>
/* Custom VueCal Styles */
:deep(.vuecal__menu) {
  display: none;
}

:deep(.vuecal__header) {
  background: transparent;
}

:deep(.vuecal__cell) {
  padding: 2px;
}

:deep(.vuecal__cell-content) {
  padding: 4px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 28px;
}

/* Loading State */
.vuecal--loading {
  position: relative;
  pointer-events: none;
}

.vuecal--loading::after {
  content: "";
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Smooth Transitions */
.vuecal__cell-content {
  transition: background-color 0.2s ease;
}

/* Better Focus States */
button:focus-visible {
  outline: 2px solid theme("colors.second_accent");
  outline-offset: 2px;
}

/* Hover States */
button:hover svg {
  transform: scale(1.1);
  transition: transform 0.2s ease;
}

:deep(.vuecal__cell) {
  margin: 0;
  padding: 0;
}
</style>
