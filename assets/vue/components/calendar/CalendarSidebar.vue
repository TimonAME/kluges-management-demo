<!-- CalendarSidebar.vue -->

<template>
  <div
    class="absolute h-full top-0 right-0 w-[300px] flex flex-col justify-start gap-[8px] select-none"
  >
    <!-- Header mit Mini Calendar und Export -->
    <div class="bg-window_bg shadow-window rounded-window p-1">
      <div class="flex items-center justify-between mb-2 px-3">
        <h2 class="text-sm font-medium text-gray-700">Kalender</h2>
        <CalendarExport :categories="categories" />
      </div>
      <MiniCalendar
        :events="sidebarEvents"
        :selected-date="selectedDate"
        :is-loading="isLoading"
        @date-select="handleDateSelect"
        @event-click="handleEventClick"
      />
    </div>

    <!-- Search and Event List Container -->
    <div
      class="bg-window_bg shadow-window rounded-window h-[calc(100vh-80px-273.6px-8px)] w-full flex flex-col overflow-hidden"
    >
      <!-- Event List -->
      <EventList
        :events="filteredSidebarEvents"
        :is-loading="isLoading"
        :search-query="searchQuery"
        :current-filter="currentFilter"
        :filters="filters"
        :categories="categories"
        :empty-state-message="emptyStateMessage"
        @update:searchQuery="handleSearchUpdate"
        @filter-change="handleFilterChange"
        @category-filter="handleCategoryFilter"
        @event-click="handleEventClick"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import MiniCalendar from "./MiniCalendar.vue";
import EventList from "./EventList.vue";
import axios from "axios";
import { debounce } from "lodash";
import CalendarExport from "./CalendarExport.vue";
import { DownloadIcon } from "lucide-vue-next";
import { useToastContainer } from "../../composables/useToastContainer";

/**
 * Calendar sidebar component combining mini calendar, search, and event list
 * @displayName CalendarSidebar
 */

const props = defineProps({
  /**
   * Current search query
   */
  searchQuery: {
    type: String,
    default: "",
  },
  /**
   * Current active filter
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
  /**
   * Loading state indicator
   */
  isLoading: {
    type: Boolean,
    default: false,
  },
  categories: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits([
  "update:searchQuery",
  "filter-change",
  "date-select",
  "event-click",
]);

// Local state
const selectedDate = ref(new Date());
const sidebarEvents = ref([]);
const localIsLoading = ref(false);
const categories = ref([]);
const selectedCategoryId = ref(null);

// Computed
const isLoading = computed(() => {
  return props.isLoading || localIsLoading.value;
});

const emptyStateMessage = computed(() => {
  if (props.searchQuery) {
    return "Keine Ergebnisse gefunden";
  }
  switch (props.currentFilter) {
    case "today":
      return "Keine Termine heute";
    case "thisWeek":
      return "Keine Termine diese Woche";
    case "thisMonth":
      return "Keine Termine diesen Monat";
    default:
      return "Keine zukünftigen Termine";
  }
});

const filteredSidebarEvents = computed(() => {
  return sidebarEvents.value.map((event) => ({
    ...event,
    class: getEventClass(event),
  }));
});

// Methods
const fetchEvents = async () => {
  localIsLoading.value = true;
  try {
    let response;

    // Zeitspannenfilter basierend auf currentFilter
    if (
      props.currentFilter === "today" ||
      props.currentFilter === "thisWeek" ||
      props.currentFilter === "thisMonth"
    ) {
      const now = new Date();
      let startDate = new Date(now);
      let endDate = new Date(now);

      if (props.currentFilter === "today") {
        startDate.setHours(0, 0, 0, 0);
        endDate.setHours(23, 59, 59, 999);
      } else if (props.currentFilter === "thisWeek") {
        // Montag dieser Woche
        const day = startDate.getDay();
        const diff = startDate.getDate() - day + (day === 0 ? -6 : 1);
        startDate = new Date(startDate.setDate(diff));
        startDate.setHours(0, 0, 0, 0);

        // Sonntag dieser Woche
        endDate = new Date(startDate);
        endDate.setDate(startDate.getDate() + 6);
        endDate.setHours(23, 59, 59, 999);
      } else if (props.currentFilter === "thisMonth") {
        startDate = new Date(now.getFullYear(), now.getMonth(), 1);
        endDate = new Date(
          now.getFullYear(),
          now.getMonth() + 1,
          0,
          23,
          59,
          59,
          999,
        );
      }

      response = await axios.get("/api/appointment/search/timespan", {
        params: {
          startDate: startDate.toISOString(),
          endDate: endDate.toISOString(),
        },
      });
    }
    // Kategoriefilter
    else if (selectedCategoryId.value) {
      response = await axios.get("/api/appointment/search/category", {
        params: {
          categoryId: selectedCategoryId.value,
        },
      });
    }
    // Titelfilter (Suche)
    else if (props.searchQuery) {
      response = await axios.get("/api/appointment/search/title", {
        params: {
          title: props.searchQuery,
        },
      });
    }
    // Standardfall: Alle zukünftigen Termine
    else {
      const now = new Date();
      const startDate = now.toISOString();

      // Setze ein Datum weit in der Zukunft für "alle" zukünftigen Termine
      const endDate = new Date(now);
      endDate.setFullYear(endDate.getFullYear() + 10); // 10 Jahre in die Zukunft

      response = await axios.get("/api/appointment/search/timespan", {
        params: {
          startDate: startDate,
          endDate: endDate.toISOString(),
        },
      });
    }

    // Verarbeite die Antwort
    const appointments = Array.isArray(response.data)
      ? response.data
      : response.data["hydra:member"] || [];

    // Konvertiere die Termine in das richtige Format für den Kalender
    sidebarEvents.value = appointments.map((appointment) => {
      let endTime = appointment.endTime;
      if (appointment.allDay) {
        const endDate = new Date(appointment.endTime);
        endDate.setHours(23, 59, 59);
        endTime = endDate.toISOString();
      }

      // Stelle sicher, dass room korrekt formatiert ist
      const room = appointment.room?.id || appointment.room;

      return {
        id: appointment.id.toString(),
        title: appointment.title,
        start: appointment.startTime,
        end: endTime,
        allDay: appointment.allDay,
        color: appointment.color,
        extendedProps: {
          description: appointment.description,
          location: appointment.location,
          creator: appointment.creator,
          appointmentCategory: appointment.appointmentCategory,
          users: appointment.users,
          room: room,
        },
      };
    });
  } catch (error) {
    console.error("Fehler beim Laden der Termine:", error);
    sidebarEvents.value = [];
  } finally {
    localIsLoading.value = false;
  }
};

const fetchCategories = async () => {
  try {
    const response = await axios.get("/api/appointmentCategory");
    categories.value = response.data;
  } catch (error) {
    console.error("Fehler beim Laden der Terminkategorien:", error);
  }
};

const handleSearchUpdate = debounce((newQuery) => {
  emit("update:searchQuery", newQuery);
  fetchEvents();
}, 300);

const handleFilterChange = (newFilter) => {
  emit("filter-change", newFilter);
  fetchEvents();
};

const handleCategoryFilter = (categoryId) => {
  selectedCategoryId.value =
    categoryId === selectedCategoryId.value ? null : categoryId;
  fetchEvents();
};

const handleDateSelect = (date) => {
  selectedDate.value = date;
  emit("date-select", date);
};

const handleEventClick = (event) => {
  emit("event-click", event);
};

const getEventClass = (event) => {
  return event.extendedProps?.todo ? "todo-event" : "";
};

// Watch for external search query changes
watch(
  () => props.searchQuery,
  (newVal) => {
    if (newVal !== undefined) {
      fetchEvents();
    }
  },
);

// Watch for filter changes
watch(
  () => props.currentFilter,
  (newVal) => {
    if (newVal) {
      fetchEvents();
    }
  },
);

// Initialisierung
onMounted(() => {
  fetchCategories();
  fetchEvents();
});
</script>

<style scoped>
/* Smooth transitions for container heights */
div {
  transition:
    height 0.2s ease-in-out,
    transform 0.3s ease-in-out;
}

/* Custom scrollbar for the entire sidebar */
:deep(.overflow-y-auto) {
  scrollbar-width: thin;
  scrollbar-color: theme("colors.gray.300") theme("colors.gray.100");
}

:deep(.overflow-y-auto::-webkit-scrollbar) {
  width: 6px;
}

:deep(.overflow-y-auto::-webkit-scrollbar-track) {
  background-color: theme("colors.gray.100");
  border-radius: 3px;
}

:deep(.overflow-y-auto::-webkit-scrollbar-thumb) {
  background-color: theme("colors.gray.300");
  border-radius: 3px;
}

:deep(.overflow-y-auto::-webkit-scrollbar-thumb:hover) {
  background-color: theme("colors.gray.400");
}

/* Shadow transition */
.shadow-window {
  transition: box-shadow 0.2s ease-in-out;
}

/* Responsive height adjustments */
@media (max-height: 768px) {
  .h-\[calc\(100vh-80px-273\.6px-8px\)\] {
    height: calc(100vh - 80px - 230px - 8px);
  }
}
</style>
