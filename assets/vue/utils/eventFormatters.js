/**
 * Event-specific formatting utilities
 */

/**
 * Format a date and time for the event display
 * @param {string} dateString - ISO date string
 * @param {boolean} allDay - Whether the event is all-day
 * @returns {string} Formatted date string in German locale
 */
// TODO: Fix the formatting of the date and time for allday events
export const formatDateTime = (dateString, allDay) => {
  if (!dateString) return "";
  const date = new Date(dateString);
  const options = {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
    ...(allDay ? {} : { hour: "2-digit", minute: "2-digit" }),
  };
  return date.toLocaleDateString("de-DE", options);
};

/**
 * Format a date for input fields
 * @param {Date} date - Date object
 * @returns {string} YYYY-MM-DD formatted string
 */
export const formatDateForInput = (date) => {
  if (!date) return "";

  // Offset für die Zeitzone berücksichtigen
  const offset = date.getTimezoneOffset();
  const localDate = new Date(date.getTime() - offset * 60 * 1000);

  return localDate.toISOString().split("T")[0];
};

/**
 * Format time for input fields
 * @param {Date} date - Date object
 * @returns {string} HH:mm formatted string
 */
export const formatTimeForInput = (date) => {
  if (!date) return "";
  return date.toLocaleTimeString("de-DE", {
    hour: "2-digit",
    minute: "2-digit",
    hour12: false,
  });
};

/**
 * Get text color based on background color contrast
 * @param {string} backgroundColor - Hex color code
 * @returns {string} Black or white hex color code
 */
export const getTextColor = (backgroundColor) => {
  const hex = backgroundColor.replace("#", "");
  const r = parseInt(hex.substr(0, 2), 16);
  const g = parseInt(hex.substr(2, 2), 16);
  const b = parseInt(hex.substr(4, 2), 16);
  const brightness = (r * 299 + g * 587 + b * 114) / 1000;
  return brightness > 128 ? "#000000" : "#FFFFFF";
};

/**
 * Format event data for API submission
 * @param {Object} eventData - Event form data
 * @returns {Object} Formatted event data for API
 */
export const formatEventForApi = (eventData) => {
  return {
    appointment: {
      startTime: `${eventData.startDate} ${eventData.allDay ? "00:00" : eventData.startTime}:00`,
      endTime: `${eventData.endDate} ${eventData.allDay ? "23:59" : eventData.endTime}:00`,
      location: eventData.location,
      room: eventData.room,
      appointmentCategory: eventData.appointmentCategory,
      title: eventData.title || "Neuer Termin",
      description: eventData.description || "",
      allDay: eventData.allDay,
      color: eventData.color,
      creator: eventData.creator,
      users: eventData.users?.map((user) => user.id) || [],
    },
  };
};

/**
 * Format event data for ghost event display
 * @param {Object} data - Event form data
 * @returns {Object} Formatted ghost event data
 */
export const formatGhostEvent = (data) => {
  return {
    id: "ghost-event",
    title: data.title || "Neuer Termin",
    start: data.allDay ? data.startDate : `${data.startDate}T${data.startTime}`,
    end: data.allDay ? data.endDate : `${data.endDate}T${data.endTime}`,
    allDay: data.allDay,
    backgroundColor: data.color + "80", // 50% Transparenz
    borderColor: data.color,
    textColor: getTextColor(data.color),
    classNames: ["ghost-event", "cursor-pointer"],
    extendedProps: {
      description: data.description,
      location: data.location,
      appointmentCategory: data.appointmentCategory,
      users: data.users,
    },
  };
};
