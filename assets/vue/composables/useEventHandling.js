import { calculateInitialPosition } from './useEventPosition';
import useGhostEvent from './useGhostEvent';
import { getRoundedCurrentTime, getTimeString } from '../utils/calendarUtils';

/**
 * Composable for handling calendar event interactions
 * @param {Object} params - Required parameters and state refs
 * @returns {Object} Event handling methods
 */
export default function useEventHandling({
  selectedDate,
  showTypeSelector,
  selectedEvent,
  showEventPopover,
  popoverMode,
  popoverPosition,
  initialModalPosition,
  calendarWrapperRef,
}) {
  const { handleGhostEventUpdate, removeGhostEvent } =
    useGhostEvent(calendarWrapperRef);

  /**
   * Handles click events on calendar dates
   * @param {Object} info - Date click information
   */
  const handleDateClick = (info) => {
    selectedDate.value = info;
    showTypeSelector.value = true;
  };

  /**
   * Handles click events on calendar events
   * @param {Object} info - Event click information
   */
  const handleEventClick = (info) => {
    if (info.event.id === 'ghost-event') return;
    if (
      info.event.extendedProps.isSaving ||
      info.event.extendedProps.isDeleting
    )
      return;

    // Set the selected event data
    selectedEvent.value = {
      id: info.event.id,
      title: info.event.title,
      start: info.event.start,
      end: info.event.end || info.event.start,
      description: info.event.extendedProps.description,
      creator: info.event.extendedProps.creator,
      users: info.event.extendedProps.users,
      todo: info.event.extendedProps.todo,
      room: info.event.extendedProps.room,
      appointmentCategory: info.event.extendedProps.appointmentCategory,
      color: info.event.backgroundColor,
      allDay: info.event.allDay,
    };

    // Set position and mode
    popoverPosition.value = info;
    
    // Nur Position berechnen, wenn wir das normale Modal verwenden
    const calendarApi = calendarWrapperRef.value?.getApi();
    const isMonthView = calendarApi?.view.type === 'dayGridMonth';
    
    if (isMonthView) {
      initialModalPosition.value = calculateInitialPosition(info);
    }
    
    popoverMode.value = 'view';
    showEventPopover.value = true;
  };

  /**
   * Handles event clicks from the sidebar
   * @param {Object} event - Calendar event
   */
  const handleSidebarEventClick = (event) => {
    const calendarApi = calendarWrapperRef.value?.getApi();
    if (!calendarApi) return;

    // Stelle sicher, dass start ein Date-Objekt ist
    const startDate =
      event.start instanceof Date ? event.start : new Date(event.start);
    const endDate =
      event.end instanceof Date
        ? event.end
        : new Date(event.end || event.start);

    // Navigate to event date
    calendarApi.gotoDate(startDate);

    selectedEvent.value = {
      id: event.id,
      title: event.title,
      start: startDate,
      end: endDate,
      description: event.extendedProps?.description,
      creator: event.extendedProps?.creator,
      users: event.extendedProps?.users,
      todo: event.extendedProps?.todo,
      room: event.extendedProps?.room,
      appointmentCategory: event.extendedProps?.appointmentCategory,
      color: event.backgroundColor,
      allDay: event.allDay,
    };

    // Find date column element - Verwende das konvertierte Date-Objekt
    const dateStr = startDate.toISOString().split('T')[0];
    const dateColumnEl = findDateColumnElement(calendarApi, dateStr);

    if (dateColumnEl) {
      const position = {
        el: dateColumnEl,
        date: startDate,
      };

      popoverPosition.value = position;
      
      // Nur Position berechnen, wenn wir das normale Modal verwenden
      const isMonthView = calendarApi.view.type === 'dayGridMonth';
      if (isMonthView) {
        initialModalPosition.value = calculateInitialPosition(position);
      }
      
      popoverMode.value = 'view';
      showEventPopover.value = true;
    }
  };

  /**
   * Handles regular appointment creation
   * @param {Object} dateInfo - Date information
   */
  const handleRegularAppointment = (dateInfo) => {
    const currentTime = getRoundedCurrentTime();
    const startTime = getTimeString(currentTime);
    const endTime = getTimeString(
      new Date(currentTime.getTime() + 60 * 60 * 1000)
    );

    selectedEvent.value = {
      title: '',
      startDate: dateInfo.dateStr,
      endDate: dateInfo.dateStr,
      startTime: startTime,
      endTime: endTime,
      allDay: dateInfo.allDay,
      color: '#039BE5',
      description: '',
      users: [],
      location: 1,
      appointmentCategory: 1,
      room: 1,
    };

    const ghostEventData = {
      id: 'ghost-event',
      title: 'Neuer Termin',
      start: `${selectedEvent.value.startDate}T${selectedEvent.value.startTime}`,
      end: `${selectedEvent.value.endDate}T${selectedEvent.value.endTime}`,
      allDay: selectedEvent.value.allDay,
      backgroundColor: selectedEvent.value.color + '80',
      borderColor: selectedEvent.value.color,
      textColor: '#000000',
    };

    handleGhostEventUpdate(ghostEventData);
    
    // Nur Position berechnen, wenn wir das normale Modal verwenden
    const calendarApi = calendarWrapperRef.value?.getApi();
    const isMonthView = calendarApi?.view.type === 'dayGridMonth';
    
    if (isMonthView) {
      setupPopoverPosition(dateInfo);
    } else {
      // Für Fullscreen-Modal brauchen wir keine Position
      popoverMode.value = 'new';
      showEventPopover.value = true;
    }
  };

  /**
   * Shows event popover at today's position
   */
  const showEventPopoverAtToday = () => {
    const today = new Date();
    selectedDate.value = {
      date: today,
      dateStr: today.toISOString().split('T')[0],
      allDay: false,
    };
    showTypeSelector.value = true;
  };

  /**
   * Closes the event popover and resets related state
   */
  const closeEventPopover = () => {
    showEventPopover.value = false;
    popoverMode.value = 'new';
    selectedEvent.value = null;
    popoverPosition.value = { x: 0, y: 0 };

    const calendarApi = calendarWrapperRef.value?.getApi();
    if (!calendarApi) return;

    // Remove temporary events
    calendarApi.getEvents().forEach((event) => {
      if (event.id.startsWith('temp-')) {
        event.remove();
      }
    });

    calendarApi.updateSize();
  };

  // Helper functions
  const findDateColumnElement = (calendarApi, dateStr) => {
    if (calendarApi.view.type === 'dayGridMonth') {
      return calendarApi.el.querySelector(`.fc-day[data-date="${dateStr}"]`);
    }
    if (calendarApi.view.type.includes('timeGrid')) {
      return calendarApi.el.querySelector(
        `.fc-timegrid-col[data-date="${dateStr}"]`
      );
    }
    return null;
  };

  const setupPopoverPosition = (dateInfo) => {
    const calendarApi = calendarWrapperRef.value?.getApi();
    if (!calendarApi) return;

    const todayStr = dateInfo.dateStr;
    const currentView = calendarApi.view.type;
    const targetEl = findDateColumnElement(calendarApi, todayStr);

    if (targetEl) {
      const position = {
        el: targetEl,
        date: dateInfo.date,
      };

      popoverPosition.value = position;
      initialModalPosition.value = calculateInitialPosition(position);
      popoverMode.value = 'new';
      showEventPopover.value = true;

      if (currentView.includes('timeGrid')) {
        const currentTime = getRoundedCurrentTime();
        calendarApi.scrollToTime({
          hours: currentTime.getHours(),
          minutes: currentTime.getMinutes(),
        });
      }
    }
  };

  return {
    handleDateClick,
    handleEventClick,
    handleSidebarEventClick,
    handleRegularAppointment,
    showEventPopoverAtToday,
    closeEventPopover,
  };
}
