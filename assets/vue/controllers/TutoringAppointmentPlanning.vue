<script setup>
import { ref, computed, onMounted, watch } from "vue";
import {
  ChevronLeft,
  ChevronRight,
  Calendar,
  Users,
  GraduationCap,
  Clock,
  Info,
} from "lucide-vue-next";
import axios from "axios";
import { debounce } from "lodash";

// URL-Parameter auslesen
const urlParams = new URLSearchParams(window.location.search);
const dateParam = urlParams.get('date');

// Constants for the grid
const HOUR_HEIGHT = 60;
const TOTAL_HOURS = 14;
const HEADER_HEIGHT = 0;
const SLOT_DURATION = 15; // in minutes

// Reactive data
const subjects = ref([]);
const teachers = ref([]);

// Active state
const currentWeek = ref(dateParam ? new Date(dateParam) : new Date());
const selectedSubject = ref("");
const selectedTeacher = ref(null);
const selectedSlot = ref(null);
const showBookingModal = ref(false);
const studentSearchInput = ref(null);

// Additional reactive variables
const isLoading = ref(false);
const selectedBookingTeacher = ref(null);
const selectedEndTime = ref(null);
const showStudentSearch = ref(false);

// Studenten-bezogene Variablen
const studentSearchQuery = ref('');
const studentSearchResults = ref([]);
const isSearchingStudents = ref(false);
const selectedStudents = ref([]);

// Füge diese reaktiven Variablen zu deinem Setup hinzu
const availableRooms = ref([]);
const selectedRoom = ref(null);
const isLoadingRooms = ref(false);

// Füge diese reaktiven Variablen zu deinem Setup hinzu
const isBooking = ref(false);
const successMessage = ref('');
const showSuccessMessage = ref(false);
const errorMessage = ref('');
const showErrorMessage = ref(false);

// Füge diese reaktiven Variablen zu deinem Setup hinzu
const appointmentCategories = ref([]);
const tutoringCategoryId = ref(null);

// Füge diese reaktive Variable für Lehrertermine hinzu
const teacherAppointments = ref({});

// Fetch all subjects from the API
const fetchSubjects = async () => {
  isLoading.value = true;
  try {
    const response = await axios.get("/api/subject");
    subjects.value = response.data;
    // Set the first subject as default if available
    if (subjects.value.length > 0 && !selectedSubject.value) {
      selectedSubject.value = subjects.value[0].name;
    }
  } catch (error) {
    console.error("Error fetching subjects:", error);
  } finally {
    isLoading.value = false;
  }
};

// Fetch teachers by subject
const fetchTeachersBySubject = async (subject) => {
  if (!subject) return;
  
  isLoading.value = true;
  try {
    // Aktualisierter API-Endpunkt
    const response = await axios.get(`/api/subject/tutoring/teachers-by-subject?subject=${subject}`);
    // Convert API response to the format our app expects
    // If response is an array, use it directly, otherwise wrap single object in array
    const teachersData = Array.isArray(response.data) ? response.data : [response.data];
    
    teachers.value = teachersData.map(teacher => ({
      id: teacher.id,
      name: `${teacher.first_name} ${teacher.last_name}`,
      email: teacher.email,
      subjects: [teacher.subject_name], // Assuming this endpoint only returns teachers matching the subject
      regularAvailability: parseWorkingHours(teacher.working_hours),
      exceptions: [],
      bookings: [],
      color: teacher.color_hex_code
    }));
  } catch (error) {
    console.error("Error fetching teachers by subject:", error);
    teachers.value = [];
  } finally {
    isLoading.value = false;
  }
};

// Parse working hours from API format to our app format
const parseWorkingHours = (workingHours) => {
  if (!workingHours || !workingHours.template) return {};
  
  const weekDays = ["Mo", "Di", "Mi", "Do", "Fr", "Sa", "So"];
  const regularAvailability = {};
  
  workingHours.template.forEach((daySchedule, index) => {
    if (daySchedule && daySchedule.length > 0) {
      regularAvailability[weekDays[index]] = daySchedule.map(slot => ({
        start: slot.start,
        end: slot.end
      }));
    } else {
      regularAvailability[weekDays[index]] = [];
    }
  });
  
  return regularAvailability;
};

// Watch for changes in selectedSubject and update teachers
watch(selectedSubject, async (newSubject) => {
  selectedTeacher.value = null;
  if (newSubject) {
    await fetchTeachersBySubject(newSubject);
    await fetchTeacherAppointments(); // Termine nach Lehrern laden
  }
});

// Initialize data on component mount
onMounted(async () => {
  await fetchSubjects();
  await fetchAppointmentCategories();
  await fetchTeacherAppointments(); // Termine werden nach Lehrern geladen, wenn ein Fach ausgewählt ist
});

// Available teachers for the selected subject
const availableTeachers = computed(() => {
  return teachers.value;
});

// Generate time slots
function generateTimeSlots(startHour = 8, endHour = 22) {
  const slots = [];
  for (let hour = startHour; hour < endHour; hour++) {
    for (let minute = 0; minute < 60; minute += SLOT_DURATION) {
      slots.push({
        time: `${hour.toString().padStart(2, "0")}:${minute.toString().padStart(2, "0")}`,
        pixel: ((hour - 8) * 60 + minute) * (HOUR_HEIGHT / 60) + HEADER_HEIGHT,
      });
    }
  }
  return slots;
}

const timeSlots = computed(() => generateTimeSlots());

// Timeline labels
const timeGridLines = computed(() => {
  const lines = [];
  for (let hour = 0; hour <= TOTAL_HOURS; hour++) {
    lines.push({
      position: hour * HOUR_HEIGHT + HEADER_HEIGHT,
      type: "hour",
      label: (hour + 8).toString().padStart(2, "0") + ":00",
    });

    // 15-minute markers
    if (hour < TOTAL_HOURS) {
      for (let quarter = 1; quarter < 4; quarter++) {
        lines.push({
          position:
            hour * HOUR_HEIGHT + (quarter * HOUR_HEIGHT) / 4 + HEADER_HEIGHT,
          type: "quarter",
        });
      }
    }
  }
  return lines;
});

// Weekdays and formatting
const weekDays = ["Mo", "Di", "Mi", "Do", "Fr", "Sa", "So"];

const formattedWeek = computed(() => {
  const start = new Date(currentWeek.value);
  start.setDate(start.getDate() - start.getDay() + 1);
  const dates = [];

  for (let i = 0; i < 7; i++) {
    const date = new Date(start);
    date.setDate(date.getDate() + i);
    dates.push({
      date,
      day: weekDays[i],
      formattedDate: `${date.getDate()}.${date.getMonth() + 1}.`,
      isoDate: date.toISOString().split("T")[0],
    });
  }

  return dates;
});

// Check availability for a specific time slot
function getSlotAvailability(date, timeSlot) {
  const relevantTeachers = selectedTeacher.value
    ? [selectedTeacher.value]
    : availableTeachers.value;

  const availableTeachersForSlot = relevantTeachers.filter((teacher) => {
    // Check regular availability
    const dayAvailability = teacher.regularAvailability[date.day] || [];
    const isRegularlyAvailable = dayAvailability.some(
      (slot) => timeSlot >= slot.start && timeSlot <= slot.end,
    );

    if (!isRegularlyAvailable) return false;

    // Check exceptions
    const exception = (teacher.exceptions || []).find((e) => e.date === date.isoDate);
    if (exception) {
      if (exception.type === "blocked") return false;
      if (exception.type === "modified") {
        return exception.availability.some(
          (slot) => timeSlot >= slot.start && timeSlot <= slot.end,
        );
      }
    }

    // Check existing bookings
    const hasConflictingBooking = (teacher.bookings || []).some(
      (booking) =>
        booking.date === date.isoDate &&
        timeSlot >= booking.start &&
        timeSlot <= booking.end,
    );

    return !hasConflictingBooking;
  });

  return {
    isAvailable: availableTeachersForSlot.length > 0,
    teacherCount: availableTeachersForSlot.length,
    teachers: availableTeachersForSlot,
  };
}

// Verfügbare Endzeiten generieren (15-Minuten-Intervalle)
const availableEndTimes = computed(() => {
  if (!selectedSlot.value || !selectedBookingTeacher.value) return [];

  const startTime = selectedSlot.value.time;
  const [startHour, startMinute] = startTime.split(':').map(Number);
  const endTimes = [];

  // Füge den ersten Slot hinzu (gleiche Zeit wie Startzeit für 15-Minuten-Buchung)
  endTimes.push(startTime);

  // Erhöhe die maximale Dauer auf 4 Stunden (240 Minuten)
  const maxDurationMinutes = 240; 

  // Prüfe, ob der Lehrer an diesem Tag Termine hat
  const selectedDate = selectedSlot.value.date.isoDate;
  const teacherBookings = selectedBookingTeacher.value.bookings || [];
  const bookingsOnSelectedDay = teacherBookings.filter(booking => booking.date === selectedDate);

  // Finde den nächsten Termin nach der Startzeit
  let nextAppointmentStartTime = null;
  bookingsOnSelectedDay.forEach(booking => {
    // Wenn die Startzeit des Termins nach unserer aktuellen Startzeit liegt
    // und entweder noch kein nächster Termin gefunden wurde oder dieser Termin früher beginnt
    if (booking.start > startTime && (!nextAppointmentStartTime || booking.start < nextAppointmentStartTime)) {
      nextAppointmentStartTime = booking.start;
    }
  });

  for (let minutes = 15; minutes <= maxDurationMinutes; minutes += 15) {
    let endMinute = startMinute + minutes;
    let endHour = startHour + Math.floor(endMinute / 60);
    endMinute = endMinute % 60;
    
    // Prüfen, ob die Endzeit innerhalb der Verfügbarkeit des Lehrers liegt
    const endTimeString = `${endHour.toString().padStart(2, '0')}:${endMinute.toString().padStart(2, '0')}`;
    
    // Prüfen, ob der Lehrer zu dieser Zeit noch verfügbar ist (reguläre Verfügbarkeit)
    const dayAvailability = selectedBookingTeacher.value.regularAvailability[selectedSlot.value.date.day] || [];
    const isRegularlyAvailable = dayAvailability.some(
      (slot) => endTimeString <= slot.end
    );
    
    // Prüfen, ob die Endzeit mit einem bestehenden Termin kollidiert
    const hasConflictingBooking = bookingsOnSelectedDay.some(booking => {
      // Wenn die Endzeit nach dem Start eines anderen Termins liegt
      // und die Startzeit vor dem Ende eines anderen Termins liegt,
      // dann gibt es eine Überschneidung
      return (
        (endTimeString > booking.start && startTime < booking.end) ||
        (startTime < booking.end && endTimeString > booking.start)
      );
    });

    // Prüfen, ob die Endzeit gleich oder nach dem Start des nächsten Termins liegt
    const reachesNextAppointment = nextAppointmentStartTime && endTimeString >= nextAppointmentStartTime;

    if (isRegularlyAvailable && !hasConflictingBooking && !reachesNextAppointment) {
      endTimes.push(endTimeString);
    } else {
      break; // Keine weiteren Zeiten hinzufügen, wenn der Lehrer nicht mehr verfügbar ist
    }
  }
  
  return endTimes;
});

// Hilfsfunktion zur Berechnung der tatsächlichen Endzeit (für die Anzeige)
const formatEndTimeDisplay = (timeString) => {
  if (!timeString) return '';
  
  // Wenn die Endzeit gleich der Startzeit ist (15-Minuten-Slot)
  if (selectedSlot.value && timeString === selectedSlot.value.time) {
    const [hours, minutes] = timeString.split(':').map(Number);
    let displayMinutes = minutes + 15;
    let displayHours = hours;
    
    if (displayMinutes >= 60) {
      displayHours += 1;
      displayMinutes -= 60;
    }
    
    return `${displayHours.toString().padStart(2, '0')}:${displayMinutes.toString().padStart(2, '0')}`;
  }
  
  // Für alle anderen Endzeiten
  const [hours, minutes] = timeString.split(':').map(Number);
  let displayMinutes = minutes + 15;
  let displayHours = hours;
  
  if (displayMinutes >= 60) {
    displayHours += 1;
    displayMinutes -= 60;
  }
  
  return `${displayHours.toString().padStart(2, '0')}:${displayMinutes.toString().padStart(2, '0')}`;
};

// Create booking
const createBooking = (date, time) => {
  const availability = getSlotAvailability(date, time);
  selectedSlot.value = {
    date,
    time,
    availability,
  };
  // If only one teacher is available, select them automatically
  if (availability.teachers.length === 1) {
    selectedBookingTeacher.value = availability.teachers[0];
  } else {
    selectedBookingTeacher.value = null;
  }
  selectedEndTime.value = null;
  showBookingModal.value = true;
};

// Select teacher for booking
const selectTeacher = (teacher) => {
  selectedBookingTeacher.value = teacher;
  selectedEndTime.value = null; // Reset end time when changing teacher
};

// Aktualisiere confirmBooking, um die neuen Schülerdaten zu verwenden
const confirmBooking = () => {
  if (
    !selectedBookingTeacher.value ||
    !selectedEndTime.value ||
    selectedStudents.value.length === 0
  )
    return;

  const booking = {
    date: selectedSlot.value.date.isoDate,
    startTime: selectedSlot.value.time,
    endTime: selectedEndTime.value,
    subject: selectedSubject.value,
    teacher: selectedBookingTeacher.value.id,
    students: selectedStudents.value.map((s) => ({
      id: s.id,
      name: `${s.first_name} ${s.last_name}`
    })),
  };

  console.log("New booking:", booking);
  // Hier würde normalerweise die Backend-Integration stattfinden

  showBookingModal.value = false;
  selectedSlot.value = null;
  selectedBookingTeacher.value = null;
  selectedEndTime.value = null;
  resetStudents();
  showStudentSearch.value = false;
};

// Change week
const changeWeek = (delta) => {
  const newDate = new Date(currentWeek.value);
  newDate.setDate(newDate.getDate() + delta * 7);
  currentWeek.value = newDate;
  
  // Nach Änderung der Woche die Termine neu laden, falls ein Fach ausgewählt ist
  if (selectedSubject.value) {
    fetchTeacherAppointments();
  }
};

// Helper functions for display
const getSlotStyle = (availability) => {
  if (!availability.isAvailable) return null;

  return {
    backgroundColor:
      availability.teacherCount > 1
        ? "rgba(163,140,255,0.3)"
        : "rgba(209,156,255,0.3)",
    borderColor:
      availability.teacherCount > 1
        ? "rgba(163,140,255,0.5)"
        : "rgba(209,156,255,0.5)",
  };
};

const getTeacherTooltip = (availability) => {
  if (!availability.isAvailable) return "";

  const teacherNames = availability.teachers.map((t) => t.name).join(", ");
  return `Verfügbare Lehrer: ${teacherNames}`;
};

// Check if a day is in the past
const isPastDay = (date) => {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  return date < today;
};

// Funktion zum Suchen von Schülern
const searchStudents = debounce(async (query) => {
  if (!query.trim()) {
    studentSearchResults.value = [];
    isSearchingStudents.value = false;
    return;
  }
  
  try {
    isSearchingStudents.value = true;
    // Neue API-Schnittstelle verwenden
    const response = await axios.get('/api/subject/student/search', {
      params: { 
        search: query,
        subject: selectedSubject.value // Fach als Parameter übergeben
      }
    });
    
    studentSearchResults.value = response.data;
  } catch (error) {
    console.error('Fehler bei der Schülersuche:', error);
    studentSearchResults.value = [];
  } finally {
    isSearchingStudents.value = false;
  }
}, 300);

// Funktion zum Hinzufügen eines Schülers
const addStudent = (student) => {
  if (!selectedStudents.value.some(s => s.id === student.id)) {
    selectedStudents.value.push(student);
  }
  studentSearchQuery.value = '';
  studentSearchResults.value = [];
};

// Funktion zum Entfernen eines Schülers
const removeStudent = (student) => {
  selectedStudents.value = selectedStudents.value.filter(s => s.id !== student.id);
};

// Funktion zum Zurücksetzen der Schülerauswahl
const resetStudents = () => {
  selectedStudents.value = [];
  studentSearchQuery.value = '';
  studentSearchResults.value = [];
};

// Funktion zum Behandeln der Sucheingabe
const handleStudentSearch = (event) => {
  studentSearchQuery.value = event.target.value;
  searchStudents(studentSearchQuery.value);
};

// Computed property für gefilterte Schüler
const filteredStudents = computed(() => {
  if (!studentSearchQuery.value.trim()) {
    return [];
  }
  
  return studentSearchResults.value;
});

// Verbesserte Funktion zum Laden verfügbarer Räume
const fetchAvailableRooms = async () => {
  if (!selectedEndTime.value || !selectedSlot.value) return;
  
  try {
    isLoadingRooms.value = true;
    
    // Startzeit korrekt extrahieren
    const slotDate = selectedSlot.value.date.isoDate || selectedSlot.value.date;
    const startTime = new Date(slotDate);
    const [startHour, startMinute] = selectedSlot.value.time.split(':').map(Number);
    startTime.setHours(startHour);
    startTime.setMinutes(startMinute);
    
    // Endzeit korrekt erstellen
    const [endHour, endMinute] = selectedEndTime.value.split(':').map(Number);
    const endTime = new Date(slotDate);
    endTime.setHours(endHour);
    endTime.setMinutes(endMinute);
    
    // Prüfen, ob die Zeiten gültig sind
    if (isNaN(startTime.getTime()) || isNaN(endTime.getTime())) {
      console.error('Ungültige Zeit erkannt:', { 
        startTimeValid: !isNaN(startTime.getTime()), 
        endTimeValid: !isNaN(endTime.getTime())
      });
      throw new Error('Ungültige Zeitwerte');
    }
    
    // Entferne den Location-Parameter
    const response = await axios.get('/api/appointment/get/available-rooms', {
      params: {
        startTime: startTime.toISOString(),
        endTime: endTime.toISOString(),
      }
    });
    
    // Prüfe die Struktur der Antwort und protokolliere sie
    console.log('Verfügbare Räume:', response.data);
    
    availableRooms.value = response.data;
    // Setze den ersten verfügbaren Raum als Standard
    if (availableRooms.value.length > 0) {
      selectedRoom.value = availableRooms.value[0].id;
    } else {
      selectedRoom.value = null;
    }
  } catch (error) {
    console.error('Fehler beim Laden der verfügbaren Räume:', error);
    availableRooms.value = [];
    selectedRoom.value = null;
  } finally {
    isLoadingRooms.value = false;
  }
};

// Füge einen Watcher hinzu, der die Räume lädt, wenn die Endzeit geändert wird
watch(selectedEndTime, (newValue) => {
  if (newValue) {
    fetchAvailableRooms();
  } else {
    availableRooms.value = [];
    selectedRoom.value = null;
  }
});

// Funktion zum Laden der Terminkategorien
const fetchAppointmentCategories = async () => {
  try {
    const response = await axios.get("/api/appointmentCategory");
    appointmentCategories.value = response.data;
    
    // Finde die ID der "Nachhilfetermin"-Kategorie
    const tutoringCategory = appointmentCategories.value.find(
      category => category.name === "Nachhilfetermin"
    );
    
    if (tutoringCategory) {
      tutoringCategoryId.value = tutoringCategory.id;
      console.log("Nachhilfetermin-Kategorie-ID:", tutoringCategoryId.value);
    } else {
      console.error("Keine 'Nachhilfetermin'-Kategorie gefunden!");
    }
  } catch (error) {
    console.error("Fehler beim Laden der Terminkategorien:", error);
  }
};

// Aktualisiere die bookAppointment-Funktion, um den Lehrer als Benutzer hinzuzufügen
const bookAppointment = async () => {
  // Überprüfe, ob alle erforderlichen Daten vorhanden sind
  if (
    !selectedSlot.value ||
    !selectedBookingTeacher.value ||
    !selectedEndTime.value ||
    selectedStudents.value.length === 0 ||
    !selectedRoom.value
  ) {
    errorMessage.value = "Bitte fülle alle erforderlichen Felder aus.";
    showErrorMessage.value = true;
    setTimeout(() => {
      showErrorMessage.value = false;
    }, 5000);
    return;
  }
  
  try {
    isBooking.value = true;
    
    // Startzeit und Endzeit korrekt formatieren
    const startDate = new Date(selectedSlot.value.date.date);
    const [startHour, startMinute] = selectedSlot.value.time.split(':').map(Number);
    startDate.setHours(startHour, startMinute, 0);
    // Zeitzonenoffset in Minuten zu Millisekunden umrechnen und zum Datum hinzufügen
    const startTimezoneOffset = startDate.getTimezoneOffset() * 60000;
    const startUtcDate = new Date(startDate.getTime() - startTimezoneOffset);
    
    const endDate = new Date(selectedSlot.value.date.date);
    const [endHour, endMinute] = selectedEndTime.value.split(':').map(Number);
    endDate.setHours(endHour, endMinute, 0);
    // Zeitzonenoffset in Minuten zu Millisekunden umrechnen und zum Datum hinzufügen
    const endTimezoneOffset = endDate.getTimezoneOffset() * 60000;
    const endUtcDate = new Date(endDate.getTime() - endTimezoneOffset + 15 * 60000);
    
    // Array von gut lesbaren Farben für Nachhilfetermine
    const tutorColors = [
      '#2ecc71',  // Grün
      '#27ae60',  // Dunkelgrün
      '#3498db',  // Hellblau
      '#2980b9',  // Dunkelblau
      '#e67e22',  // Orange
      '#d35400',  // Dunkelorange
      '#f1c40f',  // Gelb
      '#f39c12',  // Goldgelb
      '#16a085',  // Türkis
      '#1abc9c'   // Helltürkis
    ];
    
    // Zufällige Farbe auswählen
    const randomColor = tutorColors[Math.floor(Math.random() * tutorColors.length)];
    
    // Finde den ausgewählten Raum in der Liste der verfügbaren Räume
    const selectedRoomData = availableRooms.value.find(room => room.id === selectedRoom.value);
    
    // Erstelle das Appointment-Objekt mit korrekten Referenzen
    const appointmentData = {
      title: `Nachhilfe ${selectedSubject.value}`,
      description: `Nachhilfetermin für ${selectedStudents.value.map(s => s.first_name + ' ' + s.last_name).join(', ')}`,
      startTime: startUtcDate.toISOString(),
      endTime: endUtcDate.toISOString(),
      allDay: false,
      color: randomColor,
      appointmentCategory: {
        id: tutoringCategoryId.value || 1
      },
      room: {
        id: selectedRoom.value
      },
      location: selectedRoomData && selectedRoomData.inLocation ? {
        id: selectedRoomData.inLocation.id
      } : null,
      teacher: {
        id: selectedBookingTeacher.value.id
      },
      // Benutzer als Array von Objekten mit IDs übergeben - jetzt auch mit dem Lehrer
      users: [
        // Füge den Lehrer als Benutzer hinzu
        {
          id: selectedBookingTeacher.value.id
        },
        // Füge alle Schüler als Benutzer hinzu
        ...selectedStudents.value.map(student => ({
          id: student.id
        }))
      ]
    };
    
    console.log("Sende Termindaten:", appointmentData);
    
    // Sende die Anfrage an die API
    const response = await axios.post('/api/appointment', appointmentData);
    
    console.log("Termin erfolgreich gebucht:", response.data);
    
    // Zeige Erfolgsmeldung
    successMessage.value = "Nachhilfetermin wurde erfolgreich gebucht!";
    showSuccessMessage.value = true;
    setTimeout(() => {
      showSuccessMessage.value = false;
    }, 5000);
    
    // Schließe das Modal und setze die Auswahl zurück
    showBookingModal.value = false;
    selectedSlot.value = null;
    selectedBookingTeacher.value = null;
    selectedEndTime.value = null;
    selectedRoom.value = null;
    resetStudents();
    
    // Aktualisiere die Lehrertermine nach erfolgreicher Buchung
    await fetchTeacherAppointments();
    
  } catch (error) {
    console.error("Fehler beim Buchen des Termins:", error);
    errorMessage.value = "Fehler beim Buchen des Termins. Bitte versuche es später erneut.";
    showErrorMessage.value = true;
    setTimeout(() => {
      showErrorMessage.value = false;
    }, 5000);
  } finally {
    isBooking.value = false;
  }
};

// Neue Funktion zum Abrufen der Lehrertermine
const fetchTeacherAppointments = async () => {
  if (!teachers.value || teachers.value.length === 0) return;
  
  try {
    // Zeitspanne für die aktuelle Woche berechnen
    const startDate = new Date(formattedWeek.value[0].date);
    startDate.setHours(0, 0, 0, 0);
    
    const endDate = new Date(formattedWeek.value[6].date);
    endDate.setHours(23, 59, 59, 999);
    
    // Für jeden Lehrer die Termine abrufen
    for (const teacher of teachers.value) {
      const response = await axios.get(`/api/appointment/search/tutoring/timespan/${teacher.id}`, {
        params: {
          startDate: startDate.toISOString(),
          endDate: endDate.toISOString()
        }
      });
      
      console.log('Vorhandene Termine:', response.data);
      
      // Termine nach Datum und Uhrzeit formatieren
      const appointments = response.data.map(appointment => {
        const endDate = new Date(appointment.endTime);
        endDate.setMinutes(endDate.getMinutes() - 1);
        
        return {
          date: new Date(appointment.startTime).toISOString().split('T')[0],
          start: new Date(appointment.startTime).toTimeString().substring(0, 5),
          end: endDate.toTimeString().substring(0, 5)
        };
      });

      console.log('Termine:', appointments);
      
      // Termine zum Lehrer hinzufügen
      teacher.bookings = appointments;
    }
    
  } catch (error) {
    console.error('Fehler beim Abrufen der Lehrertermine:', error);
  }
};
</script>

<template>
  <div
    class="absolute bg-window_bg rounded-window shadow-window w-[100%] h-full left-0 top-0 p-4 overflow-y-auto"
  >
    <!-- Header -->
    <div
      class="flex items-center justify-between mb-6 border-b border-gray-200 pb-4"
    >
      <h2 class="text-xl font-semibold flex items-center text-gray-800">
        <Calendar class="mr-2 text-main" />
        Nachhilfe Terminplanung
      </h2>

      <!-- Filter -->
      <div class="flex items-center space-x-4">
        <select
          v-model="selectedSubject"
          class="bg-white rounded-lg px-3 py-2 text-gray-800 border border-gray-200"
        >
          <option
            v-for="subject in subjects"
            :key="subject.name"
            :value="subject.name"
          >
            {{ subject.name }}
          </option>
        </select>

        <select
          v-model="selectedTeacher"
          class="bg-white rounded-lg px-3 py-2 text-gray-800 border border-gray-200"
        >
          <option :value="null">Alle Lehrer</option>
          <option
            v-for="teacher in availableTeachers"
            :key="teacher.id"
            :value="teacher"
          >
            {{ teacher.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Week navigation and info -->
    <div class="flex justify-between items-center mb-6 select-none">
      <div class="text-sm text-gray-600 flex items-center space-x-2">
        <GraduationCap class="w-4 h-4 text-main" />
        <span>{{
          selectedTeacher ? selectedTeacher.name : "Alle verfügbaren Lehrer"
        }}</span>
        <span class="text-gray-400">|</span>
        <Users class="w-4 h-4 text-main" />
        <span>{{ selectedSubject }}</span>
      </div>

      <div
        class="flex items-center space-x-2 bg-white rounded-lg shadow-sm px-2"
      >
        <button
          @click="() => changeWeek(-1)"
          class="p-2 rounded-lg hover:bg-gray-50 transition-colors"
          :disabled="isPastDay(formattedWeek[0].date)"
        >
          <ChevronLeft
            class="w-5 h-5"
            :class="
              isPastDay(formattedWeek[0].date)
                ? 'text-gray-300'
                : 'text-gray-600'
            "
          />
        </button>
        <span class="text-sm font-medium px-2">
          Woche vom {{ formattedWeek[0].formattedDate }}
        </span>
        <button
          @click="() => changeWeek(1)"
          class="p-2 rounded-lg hover:bg-gray-50 transition-colors"
        >
          <ChevronRight class="w-5 h-5 text-gray-600" />
        </button>
      </div>
    </div>

    <!-- Color coding legend -->
    <div class="mb-4 flex items-center space-x-6 text-sm text-gray-600">
      <div class="flex items-center space-x-2">
        <div
          class="w-4 h-4 rounded bg-first_accent bg-opacity-30 border border-first_accent border-opacity-50"
        ></div>
        <span>1 Lehrer verfügbar</span>
      </div>
      <div class="flex items-center space-x-2">
        <div
          class="w-4 h-4 rounded bg-second_accent bg-opacity-30 border border-second_accent border-opacity-50"
        ></div>
        <span>Mehrere Lehrer verfügbar</span>
      </div>
      <div class="flex items-center space-x-2">
        <Info class="w-4 h-4 text-main" />
        <span>Klicken Sie auf einen verfügbaren Zeitslot zur Buchung</span>
      </div>
    </div>

    <!-- Calendar grid -->
    <div class="grid grid-cols-[60px_repeat(7,1fr)] gap-4 select-none">
      <!-- Time axis -->
      <div class="relative h-[880px]">
        <div class="h-[40px]"></div>
        <div
          v-for="line in timeGridLines"
          :key="line.position"
          class="absolute w-full select-none pointer-events-none"
          :style="{ top: `${line.position}px` }"
        >
          <div v-if="line.type === 'hour'" class="flex items-center h-8 -mt-4">
            <span class="text-sm text-gray-500 w-full text-right pr-2">{{
              line.label
            }}</span>
            <div class="absolute right-0 w-2 border-t border-gray-300" />
          </div>
          <div v-else class="absolute right-0 w-1 border-t border-gray-200" />
        </div>
      </div>

      <!-- Day columns -->
      <div
        v-for="(date, index) in formattedWeek"
        :key="index"
        class="relative bg-white rounded-lg shadow-sm border border-gray-100"
      >
        <!-- Day header -->
        <div
          class="text-center py-2 border-b border-gray-100 bg-white"
        >
          <div class="font-medium text-gray-800">{{ date.day }}</div>
          <div class="text-base text-gray-500">{{ date.formattedDate }}</div>
        </div>

        <!-- Time grid -->
        <div class="relative h-[880px]">
          <!-- Hour grid -->
          <div
            v-for="line in timeGridLines"
            :key="line.position"
            class="absolute w-full border-t border-gray-100"
            :style="{ top: `${line.position}px` }"
          />

          <!-- Available slots -->
          <div
            v-for="slot in timeSlots"
            :key="`${date.isoDate}-${slot.time}`"
            class="absolute w-full"
            :style="{ top: `${slot.pixel}px`, height: `${HOUR_HEIGHT / 4}px` }"
          >
            <div
              v-if="
                !isPastDay(date.date) &&
                getSlotAvailability(date, slot.time).isAvailable
              "
              class="absolute inset-x-1 h-full -mt-[1px] border rounded cursor-pointer transition-all duration-200 group hover:shadow-sm"
              :style="getSlotStyle(getSlotAvailability(date, slot.time))"
              @click="createBooking(date, slot.time)"
            >
              <!-- Hover Tooltip -->
              <div
                class="hidden group-hover:block absolute -top-8 left-1/2 transform -translate-x-1/2 bg-white text-xs px-2 py-1 rounded shadow-sm border border-gray-200 z-20 whitespace-nowrap"
              >
                {{ slot.time }} -
                {{ getTeacherTooltip(getSlotAvailability(date, slot.time)) }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Booking modal -->
    <div
      v-if="showBookingModal"
      class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
    >
      <div
        class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"
      >
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-semibold text-gray-800">
              Nachhilfetermin buchen
            </h3>
            <button
              @click="showBookingModal = false"
              class="text-gray-400 hover:text-gray-600"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-6 w-6"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>

          <!-- Modal content -->
          <div class="space-y-6">
            <!-- Date and start time -->
            <div>
              <div class="text-gray-600 mb-2 text-base">Termin</div>
              <div class="flex items-center space-x-2 text-lg font-medium">
                <Calendar class="w-5 h-5 text-main" />
                <span>{{ selectedSlot.date.formattedDate }}</span>
                <Clock class="w-5 h-5 text-main ml-4" />
                <span>ab {{ selectedSlot.time }} Uhr</span>
              </div>
            </div>

            <!-- Subject -->
            <div>
              <div class="text-gray-600 mb-2 text-base">Fach</div>
              <div class="flex items-center space-x-2 text-lg">
                <GraduationCap class="w-5 h-5 text-main" />
                <span>{{ selectedSubject }}</span>
              </div>
            </div>

            <!-- Teacher selection -->
            <div>
              <div class="text-gray-600 mb-2 text-base">Verfügbare Lehrer</div>
              <div class="grid gap-2">
                <button
                  v-for="teacher in selectedSlot.availability.teachers"
                  :key="teacher.id"
                  @click="selectTeacher(teacher)"
                  class="flex items-center p-3 rounded-lg border transition-all duration-200"
                  :class="
                    selectedBookingTeacher?.id === teacher.id
                      ? 'border-main border-opacity-50 bg-second_accent bg-opacity-20 text-main'
                      : 'border-gray-200 hover:border-blue-200 hover:bg-blue-50'
                  "
                >
                  <Users class="w-5 h-5 text-main mr-3" />
                  <span class="text-base">{{ teacher.name }}</span>
                </button>
              </div>
            </div>

            <!-- Student selection -->
            <div>
              <div class="text-gray-600 mb-2 text-base">Schüler auswählen</div>

              <!-- Student search -->
              <div class="relative mb-4">
                <!-- Search field -->
                <input
                  ref="studentSearchInput"
                  v-model="studentSearchQuery"
                  @input="handleStudentSearch"
                  @focus="showStudentSearch = true"
                  @blur="showStudentSearch = false"
                  type="text"
                  placeholder="Schüler suchen..."
                  class="w-full p-3 rounded-lg border border-gray-200 text-base"
                />

                <!-- Search results -->
                <div
                  v-show="showStudentSearch"
                  @mousedown.prevent
                  class="absolute z-50 top-full left-0 right-0 mt-1 bg-white rounded-lg border border-gray-200 shadow-lg"
                >
                  <div v-if="isSearchingStudents" class="p-3 text-gray-500 text-center">
                    <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-main mx-auto"></div>
                    <p class="mt-2">Suche...</p>
                  </div>
                  <div
                    v-else-if="filteredStudents.length > 0"
                    class="max-h-64 overflow-y-auto divide-y divide-gray-100"
                  >
                    <div
                      v-for="student in filteredStudents"
                      :key="student.id"
                      @mousedown="addStudent(student)"
                      class="p-3 hover:bg-gray-50 cursor-pointer transition-colors flex items-center justify-between"
                    >
                      <div>
                        <div class="text-base font-medium">
                          {{ student.first_name }} {{ student.last_name }}
                        </div>
                      </div>
                      <!-- Fächer anzeigen -->
                      <div class="flex flex-wrap gap-1" v-if="student.subject_names">
                        <span
                          v-for="(subject, index) in student.subject_names.split(',')"
                          :key="subject"
                          :style="{ backgroundColor: student.subject_colors ? student.subject_colors.split(',')[index] : '' }"
                          class="text-xs text-white px-2 py-0.5 rounded"
                        >
                          {{ subject }}
                        </span>
                      </div>
                    </div>
                  </div>
                  <div v-else-if="studentSearchQuery.trim()" class="p-3 text-gray-500 text-center">
                    Keine passenden Schüler gefunden
                  </div>
                </div>
              </div>

              <!-- Selected students - jetzt nach dem Suchfeld -->
              <div v-if="selectedStudents.length > 0" class="mt-3 mb-4">
                <div class="flex flex-wrap gap-2">
                  <div
                    v-for="student in selectedStudents"
                    :key="student.id"
                    class="group bg-second_accent bg-opacity-30 text-main px-3 py-1.5 rounded-lg text-base flex items-center gap-2"
                  >
                    <span>{{ student.first_name }} {{ student.last_name }}</span>
                    <button
                      @click="removeStudent(student)"
                      class="hover:bg-blue-100 rounded-full p-1"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-4 h-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                      >
                        <path d="M18 6L6 18M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- End time selection -->
            <div v-if="selectedBookingTeacher && selectedStudents.length > 0">
              <div class="text-gray-600 mb-2 text-base">Dauer auswählen</div>
              <select
                v-model="selectedEndTime"
                class="w-full p-3 rounded-lg border border-gray-200 text-base"
              >
                <option value="">Bitte Endzeit wählen</option>
                <option
                  v-for="time in availableEndTimes"
                  :key="time"
                  :value="time"
                >
                  bis {{ formatEndTimeDisplay(time) }} Uhr
                </option>
              </select>
            </div>

            <!-- Room selection - nach der Endzeit-Auswahl hinzufügen -->
            <div v-if="selectedEndTime">
              <div class="text-gray-600 mb-2 text-base">Raum auswählen</div>
              <div v-if="isLoadingRooms" class="text-center py-2">
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-main mx-auto"></div>
                <p class="mt-2 text-sm text-gray-500">Suche verfügbare Räume...</p>
              </div>
              <select
                v-else
                v-model="selectedRoom"
                class="w-full p-3 rounded-lg border border-gray-200 text-base"
              >
                <option v-if="availableRooms.length === 0" value="">Keine Räume verfügbar</option>
                <option v-else-if="!selectedRoom" value="">Bitte Raum wählen</option>
                <option
                  v-for="room in availableRooms"
                  :key="room.id"
                  :value="room.id"
                >
                  Raum {{ room.roomNumber }} {{ room.in_location_city ? `(${room.in_location_city})` : '' }}
                </option>
              </select>
            </div>

            <!-- Action buttons -->
            <div
              class="border-t border-gray-200 mt-6 pt-6 flex justify-end space-x-4"
            >
              <button
                @click="showBookingModal = false"
                class="px-6 py-2.5 text-base text-gray-600 hover:text-gray-800 transition-colors"
              >
                Abbrechen
              </button>
              <button
                @click="bookAppointment"
                :disabled="
                  !selectedSlot ||
                  !selectedBookingTeacher ||
                  !selectedEndTime ||
                  selectedStudents.length === 0 ||
                  !selectedRoom
                "
                class="px-6 py-2.5 bg-main text-white text-base rounded-lg hover:opacity-90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Termin buchen
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div v-if="isLoading" class="absolute inset-0 bg-white bg-opacity-75 z-50 flex items-center justify-center">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-secondmain mx-auto"></div>
        <p class="mt-4 text-gray-600">Wird geladen...</p>
      </div>
    </div>

    <!-- Success Message -->
    <div 
      v-if="showSuccessMessage" 
      class="fixed top-4 right-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-md z-50"
    >
      <div class="flex items-center">
        <div class="py-1"><svg class="w-6 h-6 mr-4 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg></div>
        <p>{{ successMessage }}</p>
      </div>
    </div>

    <!-- Error Message -->
    <div 
      v-if="showErrorMessage" 
      class="fixed top-4 right-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-md z-50"
    >
      <div class="flex items-center">
        <div class="py-1"><svg class="w-6 h-6 mr-4 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg></div>
        <p>{{ errorMessage }}</p>
      </div>
    </div>
  </div>
</template>