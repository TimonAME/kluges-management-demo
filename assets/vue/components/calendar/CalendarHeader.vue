<!-- CalendarHeader.vue -->
<template>
  <div
    class="absolute bg-window_bg rounded-window shadow-window h-[56px] top-0 p-2 overflow-hidden select-none transition-all duration-300"
    :class="[
      $attrs.class || 'w-[calc(100%-300px-16px)]'
    ]"
  >
    <!-- Desktop Header (ab md) -->
    <div v-if="windowWidth >= 768" class="flex justify-between items-center h-full">
      <!-- Left Section: Navigation -->
      <div class="flex flex-row gap-4 justify-center items-center">
        <!-- Today Button -->
        <button
          @click="$emit('date-navigate', 'today')"
          class="px-4 py-2 rounded-window font-medium transition-colors hover:bg-gray-100 text-icon_color"
        >
          Heute
        </button>

        <!-- Month Navigation -->
        <div class="flex flex-row justify-around">
          <button
            @click="$emit('date-navigate', 'prev')"
            class="flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100 transition-colors"
            aria-label="Vorheriger Monat"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 stroke-icon_color" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <button
            @click="$emit('date-navigate', 'next')"
            class="flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100 transition-colors"
            aria-label="Nächster Monat"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 stroke-icon_color" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>

        <!-- Current Month/Year Display -->
        <div class="text-base font-medium text-text_color sm:hidden">
          {{ currentMonthYear }}
        </div>
      </div>

      <!-- Center Section: View Controls -->
      <div class="flex space-x-2">
        <!-- Monat -->
        <button
          @click="handleViewChange('dayGridMonth')"
          class="px-4 py-2 rounded-window font-medium transition-colors"
          :class="[
            currentView === 'dayGridMonth'
              ? 'bg-second_accent text-white'
              : 'text-icon_color hover:bg-gray-100'
          ]"
        >
          <span>{{ viewLabels.dayGridMonth }}</span>
        </button>

        <!-- Woche -->
        <button
          @click="handleViewChange('timeGridWeek')"
          class="px-4 py-2 rounded-window font-medium transition-colors"
          :class="[
            currentView === 'timeGridWeek'
              ? 'bg-second_accent text-white'
              : 'text-icon_color hover:bg-gray-100'
          ]"
        >
          <span>{{ viewLabels.timeGridWeek }}</span>
        </button>

        <!-- Tag -->
        <button
          @click="handleViewChange('timeGridDay')"
          class="px-4 py-2 rounded-window font-medium transition-colors"
          :class="[
            currentView === 'timeGridDay'
              ? 'bg-second_accent text-white'
              : 'text-icon_color hover:bg-gray-100'
          ]"
        >
          <span>{{ viewLabels.timeGridDay }}</span>
        </button>

        <!-- Raumansicht -->
        <button
          @click="handleViewChange('timeGridRoom')"
          class="px-4 py-2 rounded-window font-medium transition-colors"
          :class="[
            currentView === 'timeGridRoom'
              ? 'bg-second_accent text-white'
              : 'text-icon_color hover:bg-gray-100'
          ]"
        >
          <span>{{ viewLabels.timeGridRoom }}</span>
        </button>
      </div>

      <!-- Right Section: New Event Button -->
      <button
        @click="$emit('new-event')"
        class="px-4 py-2 rounded-window font-medium transition-all hover:shadow-lg bg-second_accent text-white flex items-center gap-2"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Neuer Termin</span>
      </button>
    </div>

    <!-- Mobile Header (unter md) -->
    <div v-else class="flex justify-between items-center h-full">
      <!-- Left: Menu Button -->
      <button @click="toggleMobileSidebar" class="p-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 stroke-icon_color" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
      
      <!-- Center: Title (Klickbar für Datumsauswahl) -->
      <div 
        class="text-md font-medium cursor-pointer flex items-center gap-1"
        @click="toggleMobileDatePicker"
      >
        {{ currentMonthYear }}
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 stroke-icon_color" :class="{ 'rotate-180': showMobileDatePicker }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </div>
      
      <!-- Right: Action Icons -->
      <div class="flex items-center gap-2">
        <!-- Today Button -->
        <button @click="$emit('date-navigate', 'today')" class="p-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 stroke-icon_color" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </button>
        
        <!-- Search Button -->
        <button @click="openSearch" class="p-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 stroke-icon_color" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </button>
        
        <!-- Todo Button -->
        <button @click="navigateToTodo" class="p-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 stroke-icon_color" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Mobile Sidebar -->
    <div 
      v-if="windowWidth < 768 && showMobileSidebar"
      class="fixed inset-0 z-50 flex"
    >
      <!-- Backdrop -->
      <div 
        class="absolute inset-0 bg-black bg-opacity-50"
        @click="showMobileSidebar = false"
      ></div>
      
      <!-- Sidebar Content -->
      <div class="relative w-[280px] bg-white h-full overflow-y-auto transform transition-transform duration-300 translate-x-0">
        <!-- Header -->
        <div class="p-4 border-b flex justify-between items-center">
          <h2 class="text-lg font-medium text-text_color">Kalender</h2>
          <button @click="showMobileSidebar = false" class="p-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 stroke-icon_color" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- View Selection -->
        <div class="p-4">
          <div class="space-y-2">
            <!-- Monat -->
            <button
              @click="handleViewChangeAndCloseSidebar('dayGridMonth')"
              class="flex items-center w-full px-3 py-2 rounded-lg transition-colors hover:bg-gray-100"
              :class="{ 'bg-gray-100': currentView === 'dayGridMonth' }"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 stroke-icon_color mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 4H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2z" />
              </svg>
              <span class="text-sm text-text_color">Monat</span>
            </button>

            <!-- Woche -->
            <button
              @click="handleViewChangeAndCloseSidebar('timeGridWeek')"
              class="flex items-center w-full px-3 py-2 rounded-lg transition-colors hover:bg-gray-100"
              :class="{ 'bg-gray-100': currentView === 'timeGridWeek' }"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 stroke-icon_color mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span class="text-sm text-text_color">Woche</span>
            </button>

            <!-- Tag -->
            <button
              @click="handleViewChangeAndCloseSidebar('timeGridDay')"
              class="flex items-center w-full px-3 py-2 rounded-lg transition-colors hover:bg-gray-100"
              :class="{ 'bg-gray-100': currentView === 'timeGridDay' }"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 stroke-icon_color mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span class="text-sm text-text_color">Tag</span>
            </button>

            <!-- Raumansicht -->
            <button
              @click="handleViewChangeAndCloseSidebar('timeGridRoom')"
              class="flex items-center w-full px-3 py-2 rounded-lg transition-colors hover:bg-gray-100"
              :class="{ 'bg-gray-100': currentView === 'timeGridRoom' }"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 stroke-icon_color mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
              <span class="text-sm text-text_color">Raumansicht</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Mobile Search Overlay -->
    <div 
      v-if="windowWidth < 768 && showMobileSearch"
      class="fixed inset-0 bg-white z-50"
    >
      <div class="p-4 flex items-center gap-2 border-b">
        <button @click="showMobileSearch = false" class="p-1">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 stroke-icon_color" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </button>
        <input 
          type="text" 
          v-model="mobileSearchQuery"
          placeholder="Termine suchen" 
          class="flex-1 p-2 outline-none"
          ref="mobileSearchInput"
        />
        <button v-if="mobileSearchQuery" @click="mobileSearchQuery = ''" class="p-1">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 stroke-icon_color" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      
      <div class="p-4">
        <div v-if="mobileSearchQuery" class="text-sm text-gray-500">
          Suche nach "{{ mobileSearchQuery }}"...
        </div>
        <div v-else class="text-sm text-gray-500">
          Gib einen Suchbegriff ein, um Termine zu finden.
        </div>
      </div>
    </div>

    <!-- Mobile Date Picker Overlay -->
    <div 
      v-if="windowWidth < 768 && showMobileDatePicker"
      class="fixed inset-0 z-50 flex items-center justify-center"
    >
      <!-- Backdrop -->
      <div 
        class="absolute inset-0 bg-black bg-opacity-50"
        @click="showMobileDatePicker = false"
      ></div>
      
      <!-- Date Picker Content -->
      <div class="relative bg-white rounded-window shadow-lg w-[300px] max-h-[80vh] overflow-y-auto">
        <div class="p-4 border-b flex justify-between items-center">
          <h2 class="text-lg font-medium">Datum auswählen</h2>
          <button @click="showMobileDatePicker = false" class="p-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 stroke-icon_color" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <!-- TODO: Datepicker soll sich beim ausgewählten Monat öffnen-->
        <!-- Mini Calendar für Datumsauswahl -->
        <div class="p-4">
          <vue-cal
            ref="mobileDatePicker"
            class="vuecal--date-picker"
            xsmall
            hide-view-selector
            :time="false"
            :transitions="false"
            :disable-views="['year', 'week', 'day']"
            active-view="month"
            @cell-click="handleMobileDateSelection"
            locale="de"
            :selected-date="currentCalendarDate"
          >
            <!-- Custom Header -->
            <template #header="{ view, previousPeriod, nextPeriod }">
              <div class="vuecal__header px-2 py-1">
                <div class="flex justify-between items-center">
                  <!-- Navigation Buttons -->
                  <button
                    @click="previousPeriod"
                    class="p-1 rounded-full hover:bg-gray-100 transition-colors"
                    :aria-label="'Vorheriger ' + view.id"
                  >
                    <svg
                      class="w-4 h-4 text-gray-600"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 19l-7-7 7-7"
                      />
                    </svg>
                  </button>

                  <!-- Current Period Label -->
                  <span class="text-sm font-medium text-gray-700">
                    {{ formatMonthYear(view.startDate) }}
                  </span>

                  <!-- Next Button -->
                  <button
                    @click="nextPeriod"
                    class="p-1 rounded-full hover:bg-gray-100 transition-colors"
                    :aria-label="'Nächster ' + view.id"
                  >
                    <svg
                      class="w-4 h-4 text-gray-600"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5l7 7-7 7"
                      />
                    </svg>
                  </button>
                </div>
              </div>
            </template>
          </vue-cal>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue';
import VueCal from 'vue-cal';
import 'vue-cal/dist/vuecal.css';
import { formatEventDate } from '../../utils/calendarUtils';

// URL-Hilfsfunktion
const updateUrlWithView = (view) => {
  const url = new URL(window.location);
  url.searchParams.set('view', view);
  window.history.pushState({}, '', url);
};

const props = defineProps({
  currentView: {
    type: String,
    required: true,
  },
  viewLabels: {
    type: Object,
    default: () => ({
      dayGridMonth: "Monat",
      timeGridWeek: "Woche",
      timeGridDay: "Tag",
      timeGridRoom: "Raum",
      listWeek: "Liste",
    }),
  },
});

const emit = defineEmits(["date-navigate", "view-change", "new-event"]);

// All views in one array
const views = ["dayGridMonth", "timeGridWeek", "timeGridDay", "timeGridRoom"];

// Responsive state
const windowWidth = ref(window.innerWidth);
const showViewDropdown = ref(false);
// Month selector ref removed
const showMobileSidebar = ref(false);
const showMobileSearch = ref(false);
const mobileSearchQuery = ref('');
const mobileSearchInput = ref(null);

// Mobile calendar data
const mobileCalendars = ref([
  { name: 'Mein Kalender', active: true },
  { name: 'Arbeit', active: true },
  { name: 'Geburtstage', active: false },
  { name: 'Feiertage', active: true }
]);

// Monate für den Monatsauswähler
const months = [
  'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
  'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'
];

// Aktueller Monat und Jahr
const currentDate = new Date();
const currentMonth = ref(currentDate.getMonth());
const currentYear = ref(currentDate.getFullYear());
const calendarTitle = ref('');

// Computed properties für responsive Ansichten
const visibleViews = computed(() => {
  if (windowWidth.value >= 768) { // md
    return views; // Alle Ansichten anzeigen
  } else if (windowWidth.value >= 640) { // sm
    return views.slice(0, 2);
  } else {
    return views.slice(0, 1);
  }
});

const hiddenViews = computed(() => {
  return views.filter(view => !visibleViews.value.includes(view));
});

const currentMonthYear = computed(() => {
  // Wenn ein Titel aus dem FullCalendar verfügbar ist, diesen verwenden
  if (calendarTitle.value) {
    return calendarTitle.value;
  }
  // Sonst Fallback auf lokale Werte
  return `${months[currentMonth.value]} ${currentYear.value}`;
});

// Methods
const handleViewChange = (view) => {
  updateUrlWithView(view);
  emit("view-change", { target: { value: view } });
};

const handleViewChangeAndCloseDropdown = (view) => {
  handleViewChange(view);
  showViewDropdown.value = false;
};

// funktion die aus der url die monat und jahr ausliest und setzt
const getCalendarDate = () => {
  const url = new URL(window.location);
  const dateParam = url.searchParams.get('date');
  
  if (dateParam) {
    return dateParam;
  } else {
    // Fallback: Aktuelles Datum im Format "Monat Jahr" zurückgeben
    return `${months[currentMonth.value]} ${currentYear.value}`;
  }
};

const handleResize = () => {
  windowWidth.value = window.innerWidth;
  
      // Schließe mobile Overlays, wenn Bildschirm größer wird
  if (windowWidth.value >= 640) {
    showMobileSidebar.value = false;
    showMobileSearch.value = false;
  }
};

// Mobile methods
const toggleMobileSidebar = () => {
  showMobileSidebar.value = !showMobileSidebar.value;
};

const toggleCalendar = (index) => {
  mobileCalendars.value[index].active = !mobileCalendars.value[index].active;
};

// Month selector functions removed

const openSearch = () => {
  showMobileSearch.value = true;
  
  // Fokussiere das Suchfeld nach dem Rendern
  nextTick(() => {
    mobileSearchInput.value?.focus();
  });
};

const navigateToTodo = () => {
  window.location.href = '/todo';
};

// Click outside to close dropdown
const handleClickOutside = (event) => {
  // Dropdown schließen
  if (showViewDropdown.value) {
    const dropdown = document.querySelector('.view-dropdown');
    if (dropdown && !dropdown.contains(event.target)) {
      showViewDropdown.value = false;
    }
  }
  
  // Date Picker schließen, wenn außerhalb geklickt wird
  if (showMobileDatePicker.value) {
    const datePicker = document.querySelector('.vuecal--date-picker');
    const datePickerTrigger = document.querySelector('.mobile-date-trigger');
    if (datePicker && !datePicker.contains(event.target) && 
        datePickerTrigger && !datePickerTrigger.contains(event.target)) {
      showMobileDatePicker.value = false;
    }
  }
};

// Funktion zum Aktualisieren des Titels aus dem FullCalendar
const updateCalendarTitle = () => {
  const titleElement = document.querySelector('.fc-toolbar-title');
  if (titleElement) {
    calendarTitle.value = titleElement.textContent.trim();
    
    // URL mit aktuellem Datum aktualisieren
    const url = new URL(window.location);
    url.searchParams.set('date', calendarTitle.value);
    window.history.replaceState({}, '', url);
  }
};

// Lifecycle hooks
onMounted(() => {
  window.addEventListener('resize', handleResize);
  document.addEventListener('click', handleClickOutside);
  
  // Initial den Titel aktualisieren
  nextTick(() => {
    updateCalendarTitle();
    
    // MutationObserver einrichten, um Änderungen am Titel zu überwachen
    const titleObserver = new MutationObserver(() => {
      updateCalendarTitle();
    });
    
    // Wir beobachten den gesamten Kalender-Container, um Änderungen am Titel zu erfassen
    const calendarContainer = document.querySelector('.fc');
    if (calendarContainer) {
      titleObserver.observe(calendarContainer, { 
        childList: true, 
        subtree: true, 
        characterData: true 
      });
    } else {
      // Fallback: Direktes Beobachten des Titel-Elements
      const titleElement = document.querySelector('.fc-toolbar-title');
      if (titleElement) {
        titleObserver.observe(titleElement, { 
          childList: true, 
          subtree: true, 
          characterData: true 
        });
      }
    }
  });
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize);
  document.removeEventListener('click', handleClickOutside);
});

// Watch für mobile Suche
watch(mobileSearchQuery, (newVal) => {
  // Hier könnte man die Suche implementieren
  console.log('Suche nach:', newVal);
});

// State für den Date Picker
const showMobileDatePicker = ref(false);
const mobileDatePicker = ref(null);
const currentCalendarDate = ref(new Date()); // Immer mit einem gültigen Datum initialisieren

// Toggle für den mobilen Date Picker
const toggleMobileDatePicker = () => {
  // Aktuelles Datum aus dem Kalender-Titel extrahieren
  updateCurrentCalendarDate();
  showMobileDatePicker.value = !showMobileDatePicker.value;
};

// Aktuelles Datum aus dem Kalender-Titel extrahieren
const updateCurrentCalendarDate = () => {
  try {
    // Sicherstellen, dass wir immer ein gültiges Datum haben
    let newDate = new Date();
    
    // Wenn ein Titel aus dem FullCalendar verfügbar ist, diesen verwenden
    if (calendarTitle.value) {
      // Versuche, das Datum aus dem Titel zu extrahieren
      const titleParts = calendarTitle.value.split(' ');
      if (titleParts.length >= 2) {
        const monthName = titleParts[0];
        const year = parseInt(titleParts[1], 10);
        
        if (!isNaN(year)) {
          const monthIndex = months.findIndex(m => m === monthName);
          if (monthIndex !== -1) {
            // Setze das Datum auf den 1. des Monats
            newDate = new Date(year, monthIndex, 1);
          }
        }
      }
    } else {
      // Fallback auf lokale Werte
      newDate = new Date(currentYear.value, currentMonth.value, 1);
    }
    
    // Validiere das Datum, bevor wir es setzen
    if (newDate instanceof Date && !isNaN(newDate)) {
      currentCalendarDate.value = newDate;
    } else {
      // Wenn das Datum ungültig ist, verwende das aktuelle Datum
      console.warn('Ungültiges Datum erstellt, verwende aktuelles Datum');
      currentCalendarDate.value = new Date();
    }
  } catch (error) {
    console.error('Fehler beim Parsen des Kalenderdatums:', error);
    // Fallback auf aktuelles Datum
    currentCalendarDate.value = new Date();
  }
};

// Datumsauswahl aus dem Kalender
const handleMobileDateSelection = (selectedDate) => {
  // Validiere das Datum, bevor wir es senden
  if (selectedDate instanceof Date && !isNaN(selectedDate)) {
    // Datum an die übergeordnete Komponente senden
    emit("date-navigate", { type: 'date', date: selectedDate });
    showMobileDatePicker.value = false;
  } else {
    console.error('Ungültiges Datum bei der Auswahl', selectedDate);
  }
};

// Watch für Änderungen am Kalendertitel
watch(calendarTitle, () => {
  if (showMobileDatePicker.value) {
    updateCurrentCalendarDate();
  }
});

// Initialisiere das Datum beim Komponenten-Mount
onMounted(() => {
  updateCurrentCalendarDate();
});

// New method for handling view change and closing sidebar
const handleViewChangeAndCloseSidebar = (view) => {
  handleViewChange(view);
  showMobileSidebar.value = false;
};
</script>

<style scoped>
button {
  transition: all 0.2s ease-in-out;
}

button:active {
  transform: scale(0.98);
}

svg {
  transition: transform 0.2s ease-in-out;
}

button:hover svg {
  transform: scale(1.1);
}

button:focus-visible {
  outline: 2px solid theme("colors.second_accent");
  outline-offset: 2px;
}

/* Mobile Sidebar Animation */
.translate-x-0 {
  transform: translateX(0);
}

.-translate-x-full {
  transform: translateX(-100%);
}

/* Month selector styles removed */
</style>