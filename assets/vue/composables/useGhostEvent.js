import { ref } from "vue";

/**
 * Composable for managing ghost event functionality in the calendar
 * @param {Ref} calendarWrapperRef - Reference to the calendar wrapper component
 * @returns {Object} Ghost event methods and state
 */
export default function useGhostEvent(calendarWrapperRef) {
  const ghostEvent = ref(null);

  /**
   * Creates or updates the ghost event in the calendar
   * @param {Object} eventData - Event data for the ghost event
   */
  const handleGhostEventUpdate = (eventData) => {
    const calendarApi = calendarWrapperRef.value?.getApi();
    if (!calendarApi) return;

    // Remove existing ghost event
    removeGhostEvent();

    // Configure ghost event
    const ghostEventConfig = {
      id: "ghost-event",
      title: eventData.title || "Neuer Termin",
      start: eventData.start,
      end: eventData.end,
      allDay: eventData.allDay,
      backgroundColor: eventData.backgroundColor,
      borderColor: eventData.borderColor,
      textColor: eventData.textColor || "#000000",
      classNames: ["ghost-event"],
      display: "auto",
      overlap: false,
      extendedProps: {
        isGhost: true,
      },
    };

    // Add new ghost event
    ghostEvent.value = calendarApi.addEvent(ghostEventConfig);

    // Scroll to ghost event
    scrollToGhostEvent();
  };

  /**
   * Removes the current ghost event from the calendar
   */
  const removeGhostEvent = () => {
    console.log("removeGhostEvent");
    if (ghostEvent.value) {
      ghostEvent.value.remove();
      ghostEvent.value = null;
    }

    const calendarApi = calendarWrapperRef.value?.getApi();
    if (!calendarApi) return;

    // Cleanup any stray ghost events
    const existingGhost = calendarApi.getEventById("ghost-event");
    if (existingGhost) {
      existingGhost.remove();
    }
  };

  /**
   * Scrolls the calendar to the ghost event position
   */
  const scrollToGhostEvent = () => {
    if (!ghostEvent.value) return;

    const calendarApi = calendarWrapperRef.value?.getApi();
    if (!calendarApi) return;

    calendarApi.scrollToTime(ghostEvent.value.start);
  };

  /**
   * Updates ghost event properties
   * @param {Object} props - Properties to update
   */
  const updateGhostEventProps = (props) => {
    if (!ghostEvent.value) return;

    Object.entries(props).forEach(([key, value]) => {
      if (key === "extendedProps") {
        Object.entries(value).forEach(([propKey, propValue]) => {
          ghostEvent.value.setExtendedProp(propKey, propValue);
        });
      } else {
        ghostEvent.value.setProp(key, value);
      }
    });
  };

  /**
   * Checks if an event is a ghost event
   * @param {Object} event - Event to check
   * @returns {boolean} True if event is a ghost event
   */
  const isGhostEvent = (event) => {
    return event?.id === "ghost-event" || event?.extendedProps?.isGhost;
  };

  /**
   * Gets the current ghost event data
   * @returns {Object|null} Ghost event data or null if no ghost event exists
   */
  const getGhostEventData = () => {
    if (!ghostEvent.value) return null;

    return {
      title: ghostEvent.value.title,
      start: ghostEvent.value.start,
      end: ghostEvent.value.end,
      allDay: ghostEvent.value.allDay,
      backgroundColor: ghostEvent.value.backgroundColor,
      borderColor: ghostEvent.value.borderColor,
      textColor: ghostEvent.value.textColor,
      extendedProps: ghostEvent.value.extendedProps,
    };
  };

  /**
   * Deletes the ghost event from the calendar
   * @returns {void} Nothing
   */
  const deleteGhostEvent = () => {
    const calendarApi = calendarWrapperRef.getApi();
    console.log(calendarApi);
    if (!calendarApi) return;

    console.log("deleteGhostEvent");

    // Direkt alle Ghost Events entfernen
    calendarApi.getEvents().forEach((event) => {
      if (event.id === "ghost-event") {
        event.remove();
      }
    });

    // Reset der lokalen Referenz
    ghostEvent.value = null;
  };

  return {
    ghostEvent,
    handleGhostEventUpdate,
    removeGhostEvent,
    updateGhostEventProps,
    isGhostEvent,
    getGhostEventData,
    deleteGhostEvent,
  };
}
