import { ref, watch } from "vue";
import axios from "axios";
import {
  formatDateForInput,
  formatTimeForInput,
  formatGhostEvent,
} from "../utils/eventFormatters";

/**
 * Composable for managing event popover functionality
 * @param {Object} options Configuration options
 * @param {Function} options.onGhostUpdate Callback when ghost event should be updated
 * @param {Function} options.onClose Callback when popover should be closed
 * @returns {Object} Event popover methods and state
 */
export default function useEventPopover({ onGhostUpdate, onClose }) {
  /**
   * Core event data state with default values
   * @type {import('vue').Ref<Object>}
   */
  const eventData = ref({
    id: "null",
    title: "",
    startDate: new Date().toISOString().split("T")[0],
    endDate: new Date().toISOString().split("T")[0],
    startTime: "12:00",
    endTime: "13:00",
    allDay: false,
    location: 1,
    description: "",
    color: "#039BE5",
    appointmentCategory: 1,
    room: null,
    users: [],
  });

  /**
   * Toggle state for advanced settings visibility
   * @type {import('vue').Ref<boolean>}
   */
  const showAdvancedSettings = ref(false);

  /**
   * Current mode of the popover (new/edit/view)
   * @type {import('vue').Ref<string>}
   */
  const currentMode = ref("new");

  /**
   * Available categories for appointments
   * @type {import('vue').Ref<Array>}
   */
  const categories = ref([]);

  /**
   * Available rooms for appointments
   * @type {import('vue').Ref<Array>}
   */
  const rooms = ref([]);

  // Event data watchers
  /**
   * Watch for changes in allDay status to update end date
   */
  watch(
    () => eventData.value.allDay,
    (newValue) => {
      if (!newValue) {
        eventData.value.endDate = eventData.value.startDate;
      }
    },
  );

  /**
   * Watch for changes in start date to update end date
   */
  watch(
    () => eventData.value.startDate,
    (newValue) => {
      if (!eventData.value.allDay) {
        eventData.value.endDate = newValue;
      }
    },
  );

  /**
   * Watch for changes in event data to update ghost event
   */
  watch(
    eventData,
    (newData) => {
      if (currentMode.value === "new") {
        updateGhostEvent(newData);
      }
    },
    { deep: true },
  );

  /**
   * Extract the deepest ID value from a potentially nested object
   * @param {any} value - The value to extract ID from
   * @returns {number} The extracted ID
   */
  const extractId = (value) => {
    if (!value) return null;
    if (typeof value === "number") return value;
    if (typeof value === "object" && value.id) {
      return extractId(value.id);
    }
    return null;
  };

  /**
   * Initialize event data with provided event
   * @param {Object} event Event data to initialize with
   * @param {string} mode Mode to initialize in (new/edit/view)
   */
  const initializeEventData = (event, mode) => {
    currentMode.value = mode;

    if (!event) return;

    if (mode === "new") {
      eventData.value = {
        ...eventData.value,
        startDate: event.startDate || new Date().toISOString().split("T")[0],
        endDate: event.endDate || new Date().toISOString().split("T")[0],
      };
      onGhostUpdate?.(formatGhostEvent(eventData.value));
    } else {
      const startDate = new Date(event.start);
      let endDate = event.end ? new Date(event.end) : new Date(startDate);

      // Hier kommt die neue Logik für All-Day Events
      if (event.allDay) {
        // Prüfen ob das Event über mehrere Tage geht
        const startDay = startDate.getDate();
        const endDay = endDate.getDate();
        const startMonth = startDate.getMonth();
        const endMonth = endDate.getMonth();
        const startYear = startDate.getFullYear();
        const endYear = endDate.getFullYear();

        if (
          startDay !== endDay ||
          startMonth !== endMonth ||
          startYear !== endYear
        ) {
          // Einen Tag vom Enddatum abziehen
          endDate.setDate(endDate.getDate() - 1);
        }
      }

      eventData.value = {
        id: event.id,
        title: event.title,
        startDate: formatDateForInput(startDate),
        endDate: formatDateForInput(endDate),
        startTime: formatTimeForInput(startDate),
        endTime: formatTimeForInput(endDate),
        allDay: event.allDay,
        description: event.description || "",
        color:
          event.backgroundColor ||
          event.color ||
          event.extendedProps?.color ||
          "#039BE5",
        location: event.location || 1,
        appointmentCategory: extractId(event.appointmentCategory),
        room: extractId(event.room),
        creator:
          event.creator?.first_name && event.creator?.last_name
            ? `${event.creator.first_name} ${event.creator.last_name}`
            : "Test User",
        users: event.users || [],
      };
    }
  };

  /**
   * Update the ghost event preview
   * @param {Object} data Event data to update ghost with
   */
  const updateGhostEvent = (data) => {
    if (currentMode.value === "new") {
      onGhostUpdate?.(formatGhostEvent(data));
    }
  };

  /**
   * Fetch required data for categories and rooms
   * @throws {Error} When API requests fail
   */
  const fetchRequiredData = async () => {
    try {
      const [categoriesResponse, roomsResponse] = await Promise.all([
        axios.get("/api/appointmentCategory"),
        axios.get("/api/room"),
      ]);

      categories.value = categoriesResponse.data;
      rooms.value = roomsResponse.data;
      
      // Wenn wir im "new" Modus sind, setze die Standard-Kategorie auf "Termin"
      if (currentMode.value === "new") {
        const defaultCategory = categories.value.find(cat => cat.name === "Termin");
        if (defaultCategory) {
          eventData.value.appointmentCategory = defaultCategory.id;
        } else if (categories.value.length > 0) {
          // Fallback auf die erste Kategorie
          eventData.value.appointmentCategory = categories.value[0].id;
        }
      }
    } catch (error) {
      console.error("Fehler beim Laden der Daten:", error);
    }
  };

  /**
   * Validate event data before saving
   * @returns {Array<string>} Array of error messages, empty if valid
   */
  const validateEventData = () => {
    const errors = [];

    if (!eventData.value.title.trim()) {
      errors.push("Bitte geben Sie einen Titel ein");
    }

    if (!eventData.value.startDate) {
      errors.push("Bitte wählen Sie ein Startdatum");
    }

    if (!eventData.value.endDate) {
      errors.push("Bitte wählen Sie ein Enddatum");
    }

    const startDateTime = new Date(
      `${eventData.value.startDate}T${eventData.value.startTime}`,
    );
    const endDateTime = new Date(
      `${eventData.value.endDate}T${eventData.value.endTime}`,
    );

    if (endDateTime < startDateTime) {
      errors.push("Das Enddatum muss nach dem Startdatum liegen");
    }

    return errors;
  };

  /**
   * Prepare event data for API submission
   * @returns {Object} Formatted event data ready for API
   */
  const prepareEventForSave = () => {
    console.log(eventData.value);
    const extractedCategoryId = extractId(eventData.value.appointmentCategory);
    const extractedRoomId = extractId(eventData.value.room);

    return {
      ...eventData.value,
      title: eventData.value.title || "Neuer Termin",
      appointmentCategory: extractedCategoryId,
      room: extractedRoomId,
    };
  };

  /**
   * Reset popover state to default values
   */
  const resetPopover = () => {
    showAdvancedSettings.value = false;
    currentMode.value = "new";
    
    // Standardwerte setzen, aber Kategorie-ID noch nicht festlegen
    eventData.value = {
      id: "null",
      title: "",
      startDate: new Date().toISOString().split("T")[0],
      endDate: new Date().toISOString().split("T")[0],
      startTime: "12:00",
      endTime: "13:00",
      allDay: false,
      location: 1,
      description: "",
      color: "#039BE5",
      appointmentCategory: null, // Wird später gesetzt
      room: null,
      users: [],
    };
    
    // Wenn Kategorien bereits geladen sind, setze die Standard-Kategorie
    if (categories.value.length > 0) {
      const defaultCategory = categories.value.find(cat => cat.name === "Termin");
      if (defaultCategory) {
        eventData.value.appointmentCategory = defaultCategory.id;
      } else {
        // Fallback auf die erste Kategorie, falls "Termin" nicht gefunden wird
        eventData.value.appointmentCategory = categories.value[0].id;
      }
    }
  };

  return {
    // State
    eventData,
    showAdvancedSettings,
    currentMode,
    categories,
    rooms,

    // Methods
    initializeEventData,
    fetchRequiredData,
    validateEventData,
    prepareEventForSave,
    resetPopover,
    handleClose: () => {
      resetPopover();
      onClose?.();
    },

    // Toggles
    toggleAdvancedSettings: () =>
      (showAdvancedSettings.value = !showAdvancedSettings.value),

    // Predefined colors
    predefinedColors: [
      "#039BE5", // Blau
      "#7986CB", // Indigo
      "#33B679", // Grün
      "#8E24AA", // Lila
      "#E67C73", // Rot
      "#F6BF26", // Gelb
      "#F4511E", // Orange
      "#B39DDB", // Helles Lila
      "#9E9E9E", // Grau
    ],
  };
}
