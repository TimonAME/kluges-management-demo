/**
 * Common date and time formatting utilities specialized for German locale
 */

/**
 * Maps English month names to German
 */
export const MONTH_MAP = {
  January: "Januar",
  February: "Februar",
  March: "März",
  April: "April",
  May: "Mai",
  June: "Juni",
  July: "Juli",
  August: "August",
  September: "September",
  October: "Oktober",
  November: "November",
  December: "Dezember",
};

/**
 * Maps German month names to their index
 */
export const MONTH_INDEX = {
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

/**
 * Base date formatting options for German locale
 */
const BASE_DATE_OPTIONS = {
  weekday: "long",
  year: "numeric",
  month: "long",
  day: "numeric",
};

/**
 * Time formatting options for German locale
 */
const TIME_OPTIONS = {
  hour: "2-digit",
  minute: "2-digit",
  hour12: false,
};

/**
 * Formats a full date with time in German format
 * @param {Date|string} date - Date to format
 * @returns {string} Formatted date string
 */
export const formatFullDateTime = (date) => {
  if (!date) return "";
  const dateObj = new Date(date);
  return dateObj.toLocaleDateString("de-DE", {
    ...BASE_DATE_OPTIONS,
    ...TIME_OPTIONS,
  });
};

/**
 * Formats a date in German format without time
 * @param {Date|string} date - Date to format
 * @returns {string} Formatted date string
 */
export const formatDateOnly = (date) => {
  if (!date) return "";
  const dateObj = new Date(date);
  return dateObj.toLocaleDateString("de-DE", BASE_DATE_OPTIONS);
};

/**
 * Formats time in 24h German format
 * @param {Date|string} date - Date to get time from
 * @returns {string} Formatted time string
 */
export const formatTime = (date) => {
  if (!date) return "";
  const dateObj = new Date(date);
  return dateObj.toLocaleTimeString("de-DE", TIME_OPTIONS);
};

/**
 * Formats a calendar event date based on whether it's all-day or not
 * @param {Object} event - Calendar event object
 * @returns {string} Formatted event date string
 */
export const formatEventDateTime = (event) => {
  if (!event?.start) return "";
  const dateObj = new Date(event.start);
  const options = { ...BASE_DATE_OPTIONS };

  if (!event.allDay) {
    Object.assign(options, TIME_OPTIONS);
  }

  return dateObj.toLocaleDateString("de-DE", options);
};

/**
 * Formats a date range in German format
 * @param {Date|string} startDate - Start date
 * @param {Date|string} endDate - End date
 * @param {boolean} includeTime - Whether to include time
 * @returns {string} Formatted date range string
 */
export const formatDateRange = (startDate, endDate, includeTime = false) => {
  if (!startDate || !endDate) return "";

  const start = new Date(startDate);
  const end = new Date(endDate);
  const options = { ...BASE_DATE_OPTIONS };

  if (includeTime) {
    Object.assign(options, TIME_OPTIONS);
  }

  const startStr = start.toLocaleDateString("de-DE", options);
  const endStr = end.toLocaleDateString("de-DE", options);

  return `${startStr} bis ${endStr}`;
};

/**
 * Gets current month and year in German format
 * @param {Date} [date] - Optional date, defaults to current date
 * @returns {string} Formatted month year string
 */
export const getMonthYearString = (date = new Date()) => {
  return date.toLocaleDateString("de-DE", {
    month: "long",
    year: "numeric",
  });
};

/**
 * Formats a time range in German 24h format
 * @param {string} startTime - Start time in HH:mm format
 * @param {string} endTime - End time in HH:mm format
 * @returns {string} Formatted time range string
 */
export const formatTimeRange = (startTime, endTime) => {
  if (!startTime || !endTime) return "";
  return `${startTime} - ${endTime} Uhr`;
};

/**
 * Rounds a date to the nearest 15 minutes
 * @param {Date} date - Date to round
 * @returns {Date} Rounded date
 */
export const roundToNearestQuarter = (date) => {
  const roundedDate = new Date(date);
  const minutes = roundedDate.getMinutes();
  const roundedMinutes = Math.round(minutes / 15) * 15;

  roundedDate.setMinutes(roundedMinutes);
  roundedDate.setSeconds(0);
  roundedDate.setMilliseconds(0);

  return roundedDate;
};
