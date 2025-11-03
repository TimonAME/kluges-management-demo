/**
 * Calendar utility functions for date parsing, formatting, and calendar operations
 */

/**
 * Parses a date string based on the current calendar view
 * @param {string} dateString - The date string to parse
 * @param {string} view - The current calendar view type
 * @returns {Date|null} The parsed Date object or null if invalid
 */
export const parseMonthString = (dateString, view) => {
  const monthMap = {
    Januar: 0,
    Februar: 1,
    März: 2,
    April: 3,
    Mai: 4,
    Juni: 5,
    Juli: 6,
    August: 7,
    September: 8,
    Oktober: 9,
    November: 10,
    Dezember: 11,
  };

  try {
    if (view === "dayGridMonth") {
      const [month, year] = dateString.split(" ");
      return new Date(parseInt(year), monthMap[month], 1);
    }

    if (view === "timeGridDay" || view === "timeGridRoom") {
      const [day, month, year] = dateString.split(" ");
      return new Date(parseInt(year), monthMap[month], parseInt(day));
    }

    return null;
  } catch (error) {
    console.error("Error parsing date string:", error);
    return null;
  }
};

/**
 * Calculates the position for event popovers
 * @param {HTMLElement} eventEl - The event element
 * @param {Event} clickEvent - The click event
 * @returns {Object} The calculated position {x, y}
 */
export const calculatePopoverPosition = (eventEl, clickEvent) => {
  const POPOVER_WIDTH = 384;
  const MARGIN = 16;

  if (!eventEl) {
    return {
      x: Math.min(
        clickEvent.clientX,
        window.innerWidth - POPOVER_WIDTH - MARGIN,
      ),
      y: Math.max(MARGIN, clickEvent.clientY - 100),
    };
  }

  const eventRect = eventEl.getBoundingClientRect();
  const spaceRight = window.innerWidth - eventRect.right;
  const spaceLeft = eventRect.left;

  // Calculate vertical position
  let y = eventRect.top - MARGIN;
  if (y + 500 > window.innerHeight) {
    y = Math.max(MARGIN, window.innerHeight - 500 - MARGIN);
  }

  // Calculate horizontal position
  let x;
  if (spaceRight >= POPOVER_WIDTH + MARGIN) {
    x = eventRect.right + MARGIN;
  } else if (spaceLeft >= POPOVER_WIDTH + MARGIN) {
    x = eventRect.left - POPOVER_WIDTH - MARGIN;
  } else {
    x = Math.max(
      MARGIN,
      Math.min(
        window.innerWidth - POPOVER_WIDTH - MARGIN,
        eventRect.left + (eventRect.width - POPOVER_WIDTH) / 2,
      ),
    );
  }

  return { x, y };
};

/**
 * Formats a date for display
 * @param {Date} date - The date to format
 * @param {boolean} allDay - Whether the event is all-day
 * @returns {string} The formatted date string
 */
export const formatDate = (date, allDay) => {
  if (!date) return "";

  const options = {
    year: "numeric",
    month: "long",
    day: "numeric",
  };

  if (!allDay) {
    options.hour = "2-digit";
    options.minute = "2-digit";
  }

  return new Date(date).toLocaleDateString("de-DE", options);
};

/**
 * Formats an event date for display in the sidebar
 * @param {Object} event - The calendar event object
 * @returns {string} The formatted event date string
 */
export const formatEventDate = (event) => {
  const start = new Date(event.start);
  const options = {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  };

  if (event.allDay) {
    delete options.hour;
    delete options.minute;
  }

  return start.toLocaleDateString("de-DE", options);
};

/**
 * Updates the URL with the current view
 * @param {string} view - The current calendar view
 */
export const updateUrlWithView = (view) => {
  const url = new URL(window.location);
  url.searchParams.set("view", view);
  window.history.pushState({}, "", url);
};

/**
 * Gets the rounded current time to the nearest 15 minutes
 * @returns {Date} The rounded current time
 */
export const getRoundedCurrentTime = () => {
  const currentTime = new Date();
  const roundedMinutes = Math.round(currentTime.getMinutes() / 15) * 15;
  currentTime.setMinutes(roundedMinutes);
  currentTime.setSeconds(0);
  currentTime.setMilliseconds(0);
  return currentTime;
};

/**
 * Gets the time string in HH:mm format
 * @param {Date} date - The date object
 * @returns {string} The formatted time string
 */
export const getTimeString = (date) => {
  return date.toTimeString().slice(0, 5);
};

export const DEFAULT_EVENT_DURATION = 60 * 60 * 1000; // 1 hour in milliseconds
