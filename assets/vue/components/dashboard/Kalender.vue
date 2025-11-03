<script setup>
import { ref, onMounted, nextTick } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import timeGridPlugin from '@fullcalendar/timegrid';
import timeGridRoomPlugin, {
  setCalendarApi,
} from '../calendar/time-grid-view-plugin';
import axios from 'axios';

const calendar = ref(null);

const calendarOptions = {
  plugins: [timeGridPlugin, timeGridRoomPlugin],
  initialView: 'timeGridRoom',
  headerToolbar: false,
  allDaySlot: false,
  editable: false,
  selectable: false,
  initialDate: new Date(),
  height: 'auto',
  locale: 'de',
  events: [],
};

const loadEvents = async () => {
  try {
    // Prüfen, ob calendar.value existiert und eine API hat
    if (!calendar.value) {
      console.warn('Kalender noch nicht initialisiert');
      return;
    }
    
    const calendarApi = calendar.value.getApi();
    if (!calendarApi) {
      console.warn('Kalender-API noch nicht verfügbar');
      return;
    }

    // Heutiges Datum für Zeitspanne festlegen
    const today = new Date();
    const startOfDay = new Date(today);
    startOfDay.setHours(0, 0, 0, 0);
    const endOfDay = new Date(today);
    endOfDay.setHours(23, 59, 59, 999);

    // API-Endpunkt mit Zeitspanne für den heutigen Tag aufrufen
    const [appointmentsResponse] = await Promise.all([
      axios.get(`/api/appointment/search/timespan?startDate=${startOfDay.toISOString()}&endDate=${endOfDay.toISOString()}`),
    ]);

    // Debug-Ausgabe
    //console.log('API Response:', appointmentsResponse.data);

    const appointments = mapAppointmentsToEvents(appointmentsResponse.data);

    // Debug-Ausgabe der formatierten Termine
    //console.log('All events:', appointments);

    // Update calendar events
    calendarApi.removeAllEvents();
    calendarApi.addEventSource(appointments);
  } catch (error) {
    console.error('Error loading events:', error);
  }
};

// Helper Funktionen zum Formatieren der Events
const mapAppointmentsToEvents = (data) => {
  const appointments = Array.isArray(data) ? data : data['hydra:member'] || [];

  return appointments.map((appointment) => {
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
};

// Fügen Sie eine Funktion hinzu, um die Termine neu zu laden
const refreshEvents = () => {
  loadEvents();
};

onMounted(async () => {
  // Warten auf den nächsten Render-Zyklus, damit der Kalender vollständig initialisiert ist
  await nextTick();
  
  // Verzögerung hinzufügen, um sicherzustellen, dass der Kalender vollständig geladen ist
  setTimeout(async () => {
    if (calendar.value) {
      const calendarApi = calendar.value.getApi();
      if (calendarApi) {
        setCalendarApi(calendarApi);
        await loadEvents();

        // Optional: Aktualisiere die Termine alle 5 Minuten
        setInterval(refreshEvents, 5 * 60 * 1000);
      }
    }
  }, 100);
});
</script>

<template>
  <div
    class="w-full h-full bg-window_bg rounded-window shadow-window overflow-auto"
  >
    <FullCalendar
      ref="calendar"
      :options="calendarOptions"
      class="w-full h-full"
    />
  </div>
</template>

<style>
.fc .fc-timegrid-slot-minor {
  border-top: none;
}

.fc .fc-timegrid-slot {
  height: 40px;
}

.fc .fc-timegrid-slot-label {
  @apply text-gray-500 text-sm;
}

.fc-direction-ltr .fc-timegrid-col-events {
  @apply px-1;
}

/* Stelle sicher, dass der Kalender seine volle Höhe annimmt */
.fc-view-harness {
  height: auto !important;
}

/* Verhindere horizontales Scrolling */
.fc-scrollgrid {
  overflow-x: hidden !important;
}
</style>
