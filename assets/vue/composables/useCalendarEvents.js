import { ref, computed } from 'vue';
import axios from 'axios';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import timeGridRoomPlugin from '../components/calendar/time-grid-view-plugin';

/**
 * Composable for managing calendar events and their interactions
 * @param {Ref} toastContainer - Reference to toast container component
 * @param {Ref} calendarWrapperRef - Reference to the calendar wrapper component
 * @returns {Object} Calendar event methods and state
 */
export default function useCalendarEvents(toastContainer, calendarWrapperRef) {
  // State
  const events = ref([]);
  const isLoading = ref(false);
  const calendarLoaded = ref(false);
  const searchQuery = ref('');
  const currentFilter = ref('all');
  const todosLoaded = ref(false);

  // Constants
  const filters = [
    { label: 'Alle', value: 'all' },
    { label: 'Heute', value: 'today' },
    { label: 'Diese Woche', value: 'thisWeek' },
    { label: 'Dieser Monat', value: 'thisMonth' },
  ];

  /**
   * Base calendar configuration options
   */
  const calendarOptions = {
    plugins: [
      interactionPlugin,
      dayGridPlugin,
      timeGridPlugin,
      listPlugin,
      timeGridRoomPlugin,
    ],
    initialView: 'dayGridMonth',
    locale: 'de',
    headerToolbar: {
      left: '',
      center: 'title',
      right: '',
    },
    buttonText: {
      today: 'Heute',
      month: 'Monat',
      week: 'Woche',
      day: 'Tag',
      list: 'Liste',
    },
    navLinks: true,
    editable: false,
    firstDay: 1,
    selectable: false,
    displayEventTime: true,
    dayMaxEvents: true,
    eventTimeFormat: {
      hour: '2-digit',
      minute: '2-digit',
      meridiem: false,
    },
    eventClassNames: computeEventClassNames,
    // Entfernt: events: events.value, - stattdessen verwenden wir events direkt über die Calendar API
  };

  /**
   * Load events from API
   */
  const loadEvents = async () => {
    const calendarApi = calendarWrapperRef.value?.getApi();
    if (!calendarApi) return;
    
    try {
      isLoading.value = true;
      
      // Aktuelles Datum aus dem Kalender holen
      const currentDate = calendarApi.getDate();
      const formattedDate = currentDate.toISOString().split('T')[0]; // Format: YYYY-MM-DD
      
      // Nur Todos am Anfang laden, Termine bei jedem Aufruf
      let appointments = [];
      let todos = [];
      
      // Termine immer laden
      const appointmentsResponse = await axios.get(`/api/appointment/get/twoMonths?date=${formattedDate}`);
      appointments = mapAppointmentsToEvents(appointmentsResponse.data);
      
      // Todos nur beim ersten Laden holen
      if (!todosLoaded.value) {
        const todosResponse = await axios.get('/api/todo');
        todos = mapTodosToEvents(todosResponse.data);
        todosLoaded.value = true; // Markiere Todos als geladen
      } else {
        // Verwende bestehende Todos aus den aktuellen Events
        todos = events.value.filter(event => event.id.toString().startsWith('todo-'));
      }

      const allEvents = [...appointments, ...todos];
      events.value = allEvents;

      // Wenn eine Calendar API übergeben wurde, aktualisiere die Events direkt
      if (calendarApi) {
        calendarApi.removeAllEvents();
        calendarApi.addEventSource(allEvents);
      }

      calendarLoaded.value = true;
    } catch (error) {
      console.error('Error loading events:', error);
      showErrorToast('Fehler beim Laden der Termine');
    } finally {
      isLoading.value = false;
    }
  };

  /**
   * Save or update an event
   * @param {Object} eventData - Event data to save
   */
  const handleEventSave = async (eventData) => {
    const calendarApi = calendarWrapperRef.value?.getApi();
    if (!calendarApi) return;
    const toastId = toastContainer.value?.addToast({
      message: 'Wird gespeichert...',
      type: 'loading',
    });

    try {
      const apiData = prepareEventApiData(eventData);
      console.log('apiData', apiData);

      const newEvent = {
        id: eventData.id || `temp-${Date.now()}`,
        title: eventData.title,
        start: eventData.allDay
          ? eventData.startDate
          : `${eventData.startDate}T${eventData.startTime}`,
        end: eventData.allDay
          ? eventData.endDate
          : `${eventData.endDate}T${eventData.endTime}`,
        allDay: eventData.allDay,
        backgroundColor: eventData.color,
        extendedProps: {
          description: eventData.description || '',
          users: eventData.users || [],
          room: eventData.room,
          appointmentCategory: eventData.appointmentCategory,
          isSaving: true,
        },
      };

      if (calendarApi) {
        calendarApi.addEvent(newEvent);
      }

      if (
        !eventData.id ||
        eventData.id === 'null' ||
        eventData.id === 'ghost-event'
      ) {
        await axios.post('/api/appointment', apiData);
      } else {
        await axios.put(`/api/appointment/${eventData.id}`, apiData);
      }

      toastContainer.value?.updateToast(toastId, {
        message: 'Aktualisiere Kalender...',
        type: 'loading',
      });

      await loadEvents(calendarApi);

      toastContainer.value?.updateToast(toastId, {
        message: 'Erfolgreich gespeichert',
        type: 'success',
        timeout: 3000,
      });
    } catch (error) {
      console.error('Error saving event:', error);

      if (calendarApi) {
        const event = calendarApi.getEventById(newEvent.id);
        if (event) {
          event.remove();
        }
      }

      toastContainer.value?.updateToast(toastId, {
        message: 'Fehler beim Speichern',
        type: 'error',
        timeout: 3000,
      });
    }
  };

  /**
   * Delete an event
   * @param {Object} eventData - Event data to delete
   */
  const handleEventDelete = async (eventData) => {
    const calendarApi = calendarWrapperRef.value?.getApi();
    if (!calendarApi) return;
    const toastId = toastContainer.value?.addToast({
      message: 'Wird gelöscht...',
      type: 'loading',
    });

    try {
      // Optimistische UI-Aktualisierung
      if (calendarApi) {
        const event = calendarApi.getEventById(eventData.id);
        if (event) {
          event.setExtendedProp('isDeleting', true);
          event.setProp('backgroundColor', event.backgroundColor + '80');
        }
      }

      if (eventData.id.startsWith('todo-')) {
        const todoId = eventData.id.replace('todo-', '');
        await axios.delete(`/api/todo/${todoId}`);
      } else {
        await axios.delete(`/api/appointment/${eventData.id}`);
      }

      toastContainer.value?.updateToast(toastId, {
        message: 'Aktualisiere Kalender...',
        type: 'loading',
      });

      await loadEvents(calendarApi);

      toastContainer.value?.updateToast(toastId, {
        message: 'Erfolgreich gelöscht',
        type: 'success',
        timeout: 3000,
      });
    } catch (error) {
      console.error('Error deleting event:', error);

      // UI-Aktualisierung rückgängig machen bei Fehler
      if (calendarApi) {
        const event = calendarApi.getEventById(eventData.id);
        if (event) {
          event.setExtendedProp('isDeleting', false);
          event.setProp(
            'backgroundColor',
            event.backgroundColor.replace('80', '')
          );
        }
      }

      toastContainer.value?.updateToast(toastId, {
        message: 'Fehler beim Löschen',
        type: 'error',
        timeout: 3000,
      });
    }
  };

  // Helper Functions
  const mapAppointmentsToEvents = (appointments) => {
    return appointments.map((appointment) => {
      let endTime = appointment.endTime;

      if (appointment.allDay && appointment.startTime !== appointment.endTime) {
        const endDate = new Date(appointment.endTime);
        endDate.setDate(endDate.getDate() + 1);
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
          room: room, // Verwende die formatierte Room-ID
        },
      };
    });
  };

  const mapTodosToEvents = (todos) => {
    return todos.map((todo) => ({
      id: `todo-${todo.id}`,
      title: todo.title,
      start: todo.dueDate,
      end: todo.dueDate,
      allDay: true,
      color: '#4884f4',
      extendedProps: {
        description: todo.description,
        todo: true,
        isCompleted: todo.is_completed,
      },
    }));
  };

  const computeEventClassNames = (arg) => {
    const classes = [];

    if (arg.event.id === 'ghost-event') {
      classes.push(
        'opacity-70',
        'border-2',
        'border-dashed',
        'bg-gradient-to-r',
        'from-transparent',
        'to-white/20',
        'backdrop-opacity-50'
      );
    }

    if (arg.event.extendedProps.isSaving) {
      classes.push('saving-event', 'pointer-events-none', 'opacity-60');
    }

    if (arg.event.extendedProps.isDeleting) {
      classes.push('deleting-event', 'pointer-events-none', 'opacity-60');
    }

    return classes;
  };

  const prepareEventApiData = (eventData) => {
    // Convert date and time to ISO string
    const startDateTime = eventData.allDay
      ? new Date(`${eventData.startDate}T00:00:00.000Z`).toISOString()
      : new Date(
          `${eventData.startDate}T${eventData.startTime}:00.000Z`
        ).toISOString();

    const endDateTime = eventData.allDay
      ? new Date(`${eventData.endDate}T00:00:00.000Z`).toISOString()
      : new Date(
          `${eventData.endDate}T${eventData.endTime}:00.000Z`
        ).toISOString();

    // Erstelle das API-Datenobjekt, mit korrekter Behandlung für room
    const apiData = {
      startTime: startDateTime,
      endTime: endDateTime,
      location: {
        id: 1,
      },
      appointmentCategory: {
        id: eventData.appointmentCategory || 1,
      },
      users: (eventData.users || []).map((user) => ({
        id: user.id,
      })),
      title: eventData.title,
      description: eventData.description || '',
      allDay: eventData.allDay,
      color: eventData.color || '#ffffff',
    };

    // Füge room nur hinzu, wenn tatsächlich einer ausgewählt wurde
    if (eventData.room) {
      apiData.room = {
        id: eventData.room
      };
    } else {
      apiData.room = null;
    }

    return apiData;
  };

  /**
   * Computed property für gefilterte Events
   */
  const filteredEvents = computed(() => {
    if (!events.value) return [];
    
    console.log('Current Filter:', currentFilter.value); // Debug-Ausgabe
    
    const now = new Date();
    const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const startOfWeek = new Date(startOfToday);
    startOfWeek.setDate(startOfToday.getDate() - startOfToday.getDay() + 1); // Montag
    const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);

    return events.value.filter(event => {
      // Debug-Ausgaben
      //console.log('Event:', event.title, new Date(event.start));
      
      const eventStart = new Date(event.start);
      
      // Suchfilter
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        const title = event.title?.toLowerCase() || '';
        const description = event.extendedProps?.description?.toLowerCase() || '';
        if (!title.includes(query) && !description.includes(query)) {
          return false;
        }
      }

      // Zeitfilter
      switch (currentFilter.value) {
        case 'today':
          const isToday = eventStart >= startOfToday && 
                         eventStart < new Date(startOfToday.getTime() + 24 * 60 * 60 * 1000);
          //console.log('Today check:', event.title, isToday); // Debug-Ausgabe
          return isToday;
        case 'thisWeek':
          const endOfWeek = new Date(startOfWeek);
          endOfWeek.setDate(startOfWeek.getDate() + 7);
          return eventStart >= startOfWeek && eventStart < endOfWeek;
        case 'thisMonth':
          const endOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0);
          return eventStart >= startOfMonth && eventStart <= endOfMonth;
        case 'all':
        default:
          return true;
      }
    });
  });

  /**
   * Aktualisiert den aktuellen Filter
   */
  const updateFilter = (newFilter) => {
    console.log('Updating filter to:', newFilter); // Debug-Ausgabe
    currentFilter.value = newFilter;
  };

  /**
   * Aktualisiert die Suchanfrage
   */
  const updateSearchQuery = (query) => {
    searchQuery.value = query;
  };

  const showErrorToast = (message) => {
    if (toastContainer.value) {
      toastContainer.value.addToast({
        message,
        type: 'error',
        timeout: 3000,
      });
    }
  };

  return {
    // State
    events,
    isLoading,
    calendarLoaded,
    searchQuery,
    currentFilter,
    filters,
    filteredEvents,
    calendarOptions,
    todosLoaded,

    // Methods
    loadEvents,
    handleEventSave,
    handleEventDelete,
    updateFilter,
    updateSearchQuery,
  };
}
