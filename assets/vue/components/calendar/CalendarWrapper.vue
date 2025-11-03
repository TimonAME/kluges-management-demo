<!-- CalendarWrapper.vue -->

<template>
  <div
    class="calendar-wrapper absolute bg-window_bg shadow-window h-[calc(100%-56px-8px)] top-[64px] left-0 rounded-window transition-all duration-300"
    :class="[
      $attrs.class || 'w-[calc(100%-300px-16px)]'
    ]"
  >
    <FullCalendar
      ref="calendarRef"
      :options="mergedCalendarOptions"
      class="h-full select-none"
    >
      <template #eventContent="arg">
        <EventContent
          :event="arg.event"
          :time-text="arg.timeText"
          :background-color="arg.backgroundColor"
        />
      </template>
    </FullCalendar>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";
import timeGridPlugin from "@fullcalendar/timegrid";
import listPlugin from "@fullcalendar/list";
import timeGridRoomPlugin, { setCalendarApi } from "./time-grid-view-plugin";
import EventContent from "./CalendarEventContent.vue";

/**
 * Main calendar wrapper component using FullCalendar
 * @displayName CalendarWrapper
 */

const props = defineProps({
  /**
   * Current calendar view
   */
  currentView: {
    type: String,
    required: true,
  },
  /**
   * Base calendar options
   */
  calendarOptions: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["date-click", "event-click"]);

// Refs
const calendarRef = ref(null);

// Base calendar configuration
const baseOptions = {
  plugins: [
    interactionPlugin,
    dayGridPlugin,
    timeGridPlugin,
    listPlugin,
    timeGridRoomPlugin,
  ],
  locale: "de",
  headerToolbar: {
    left: "",
    center: "title",
    right: "",
  },
  dateClick: handleDateClick,
  eventClick: handleEventClick,
  navLinks: true,
  editable: false,
  firstDay: 1,
  selectable: false,
  displayEventTime: true,
  dayMaxEvents: true,
  eventTimeFormat: {
    hour: "2-digit",
    minute: "2-digit",
    meridiem: false,
  },
};

// Merge base options with provided options
const mergedCalendarOptions = computed(() => ({
  ...baseOptions,
  ...props.calendarOptions,
  initialView: props.currentView,
}));

// Methods
function handleDateClick(info) {
  emit("date-click", info);
}

function handleEventClick(info) {
  emit("event-click", info);
}

function getApi() {
  return calendarRef.value?.getApi();
}

// Expose calendar API methods
defineExpose({
  getApi,
  calendarRef,
});

// Lifecycle hooks
onMounted(() => {
  const api = getApi();
  if (api) {
    setCalendarApi(api);
  }
});

onBeforeUnmount(() => {
  const api = getApi();
  if (api) {
    api.destroy();
  }
});
</script>

<style scoped>
/* FullCalendar Custom Styles */
:deep(.fc) {
  --fc-border-color: theme("colors.gray.200");
  --fc-button-text-color: theme("colors.gray.700");
  --fc-button-bg-color: theme("colors.white");
  --fc-button-border-color: theme("colors.gray.300");
  --fc-button-hover-bg-color: theme("colors.gray.100");
  --fc-button-hover-border-color: theme("colors.gray.400");
  --fc-button-active-bg-color: theme("colors.gray.200");
  --fc-today-bg-color: theme("colors.blue.50");
  height: 100%;
}

:deep(.fc-view) {
  overflow: hidden;
  border: none !important;
}

:deep(.fc-toolbar-title) {
  font-size: 1.25rem !important;
  font-weight: 500 !important;
  color: theme("colors.gray.700");
}

:deep(.fc-event) {
  border-radius: theme("borderRadius.md");
  margin: 1px 0;
  transition: transform 0.1s ease-in-out;
}

:deep(.fc-event:hover) {
  transform: scale(1.02);
}

:deep(.fc-timegrid-event) {
  padding: 2px 4px;
}

:deep(.fc-daygrid-event) {
  padding: 2px 4px;
  margin: 2px 4px;
}

/* Scrollbar Styles */
:deep(.fc-scroller) {
  scrollbar-width: thin;
  scrollbar-color: theme("colors.gray.300") theme("colors.gray.100");
}

:deep(.fc-scroller::-webkit-scrollbar) {
  width: 6px;
  height: 6px;
}

:deep(.fc-scroller::-webkit-scrollbar-track) {
  background: theme("colors.gray.100");
  border-radius: 3px;
}

:deep(.fc-scroller::-webkit-scrollbar-thumb) {
  background-color: theme("colors.gray.300");
  border-radius: 3px;
}

:deep(.fc-scroller::-webkit-scrollbar-thumb:hover) {
  background-color: theme("colors.gray.400");
}

/* Loading State */
.calendar-wrapper.is-loading {
  position: relative;
}

.calendar-wrapper.is-loading::after {
  content: "";
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
}
</style>
