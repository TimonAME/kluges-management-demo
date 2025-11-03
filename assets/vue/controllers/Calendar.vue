<!-- Calendar.vue -->
eventlist
<template>
  <div class="relative w-full h-[calc(100vh-78px)]">
    <!-- TODO: 78px ist nicht ganz genau, aber passt fürs erste -->
    <!-- Calendar Header -->
    <CalendarHeader
      :current-view="currentView"
      :view-labels="viewLabels"
      @date-navigate="handleDateNavigation"
      @view-change="handleViewChange"
      @new-event="showEventPopoverAtToday"
      :class="{ 
        'w-full': isSidebarCollapsed || isSmallScreen || isExtraSmallScreen, 
        'w-[calc(100%-300px-16px)]': !isSidebarCollapsed && !isSmallScreen 
      }"
    />

    <!-- Calendar Wrapper -->
    <CalendarWrapper
      ref="calendarWrapperRef"
      :current-view="currentView"
      :calendar-options="calendarOptions"
      @date-click="handleDateClick"
      @event-click="handleEventClick"
      :class="{ 
        'w-full': isSidebarCollapsed || isSmallScreen || isExtraSmallScreen, 
        'w-[calc(100%-300px-16px)]': !isSidebarCollapsed && !isSmallScreen 
      }"
      @touchstart="handleTouchStart"
      @touchmove="handleTouchMove"
      @touchend="handleTouchEnd"
      @wheel="handleWheel"
    />

    <!-- Calendar Sidebar (nur für größere Bildschirme) -->
    <div 
      v-if="!isExtraSmallScreen"
      class="absolute h-full top-0 right-0 z-10 transition-all duration-300 ease-in-out w-[300px]" 
      :style="{ transform: isSidebarCollapsed ? 'translateX(300px)' : 'translateX(0)' }"
    >
      <CalendarSidebar
        :search-query="searchQuery"
        :current-filter="currentFilter"
        :filters="filters"
        :is-loading="isLoading"
        @update:searchQuery="handleSearch"
        @filter-change="handleFilterChange"
        @event-click="handleSidebarEventClick"
        @date-select="handleDateSelection"
      />
    </div>

    <!-- Sidebar Toggle Button (nur auf mittleren Bildschirmen) -->
    <button 
      v-if="isSmallScreen && !isExtraSmallScreen"
      @click="toggleSidebar" 
      class="absolute top-1/2 -translate-y-1/2 z-20 bg-second_accent text-white rounded-l-full p-2 shadow-lg hover:bg-second_accent/90 transition-all duration-300"
      :class="{ 'right-0': isSidebarCollapsed, 'right-[300px]': !isSidebarCollapsed }"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" :class="{ 'rotate-180': !isSidebarCollapsed }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
    </button>

    <!-- Swipe-Overlay für Animation (nur während des Swipens sichtbar) -->
    <div 
      v-if="isSwipingLeft || isSwipingRight" 
      class="absolute top-[64px] left-0 z-30 h-[calc(100%-64px)] pointer-events-none"
      :class="{ 
        'w-full': isSidebarCollapsed || isSmallScreen || isExtraSmallScreen, 
        'w-[calc(100%-300px-16px)]': !isSidebarCollapsed && !isSmallScreen 
      }"
    >
      <div 
        class="relative w-full h-full transition-transform duration-300 ease-out"
        :style="{ transform: `translateX(${swipeOffset}px)` }"
      >
        <!-- Vorherige Periode (nur während Swipe nach rechts sichtbar) -->
        <div 
          v-if="isSwipingLeft" 
          class="absolute top-0 right-full w-full h-full bg-window_bg shadow-window rounded-window"
        >
          <div class="w-full h-full opacity-80 flex items-center justify-center text-gray-500">
            <span class="text-lg">{{ getNavigationLabel.prev }}</span>
          </div>
        </div>
        
        <!-- Nächste Periode (nur während Swipe nach links sichtbar) -->
        <div 
          v-if="isSwipingRight" 
          class="absolute top-0 left-full w-full h-full bg-window_bg shadow-window rounded-window"
        >
          <div class="w-full h-full opacity-80 flex items-center justify-center text-gray-500">
            <span class="text-lg">{{ getNavigationLabel.next }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Event Creation/Edit Popover -->
    <Transition name="modal">
      <EventModal
        v-if="showEventPopover && !useFullscreenModal"
        :event="selectedEvent"
        :position="popoverPosition"
        :current-name="name"
        :current-id="id"
        :mode="popoverMode"
        :initial-position="initialModalPosition"
        :calendar-wrapper-ref="calendarWrapperRef"
        @save="handleEventSave"
        @delete="handleEventDelete"
        @close="closeEventPopover"
        @ghost-update="handleGhostEventUpdate"
      />
    </Transition>

    <Transition name="modal">
      <!-- Fullscreen Event Modal für nicht-Monatsansichten -->
      <FullscreenEventModal
        v-if="showEventPopover && useFullscreenModal"
        :event="selectedEvent"
        :current-name="name"
        :current-id="id"
        :mode="popoverMode"
        :calendar-wrapper-ref="calendarWrapperRef"
        @save="handleEventSave"
        @delete="handleEventDelete"
        @close="closeEventPopover"
        @ghost-update="handleGhostEventUpdate"
      />
    </Transition>

    <ToastContainer ref="toastContainer" />

    <AppointmentTypeSelector
      :show="showTypeSelector"
      :date="selectedDate"
      @close="showTypeSelector = false"
      @select-regular="handleRegularAppointment"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, computed, watch } from 'vue';
import CalendarHeader from '../components/calendar/CalendarHeader.vue';
import CalendarWrapper from '../components/calendar/CalendarWrapper.vue';
import CalendarSidebar from '../components/calendar/CalendarSidebar.vue';
import EventModal from '../components/calendar/event-modal/EventModal.vue';
import FullscreenEventModal from '../components/calendar/fullscreen-event-modal/FullscreenEventModal.vue';
import ToastContainer from '../components/calendar/ToastContainer.vue';
import AppointmentTypeSelector from '../components/calendar/AppointmentTypeSelector.vue';
import useCalendarEvents from '../composables/useCalendarEvents';
import useCalendarNavigation from '../composables/useCalendarNavigation';
import useEventHandling from '../composables/useEventHandling';
import useGhostEvent from '../composables/useGhostEvent';
import timeGridRoomPlugin, {
  setCalendarApi,
} from '../components/calendar/time-grid-view-plugin';
import { parseMonthString } from '../utils/calendarUtils';

// Props
const props = defineProps({
  name: String,
  id: Number,
});

// Refs
const calendarWrapperRef = ref(null);
const toastContainer = ref(null);

// State
const currentView = ref('dayGridMonth');
const showEventPopover = ref(false);
const showTypeSelector = ref(false);
const selectedDate = ref(null);
const popoverMode = ref('new');
const popoverPosition = ref({ x: 0, y: 0 });
const selectedEvent = ref(null);
const mutationObserver = ref(null);
const initialModalPosition = ref({ top: '0px', left: '0px' });
const searchQuery = ref('');
const isLoading = ref(false);

// Responsive state
const isSidebarCollapsed = ref(false);
const isSmallScreen = ref(false);
const isExtraSmallScreen = ref(false);

// Constants
const viewLabels = {
  dayGridMonth: 'Monat',
  timeGridWeek: 'Woche',
  timeGridDay: 'Tag',
  listWeek: 'Liste',
  timeGridRoom: 'Raum',
};

const filters = [
  { label: 'Alle', value: 'all' },
  { label: 'Heute', value: 'today' },
  { label: 'Woche', value: 'thisWeek' },
  { label: 'Monat', value: 'thisMonth' },
];

// Composables
const {
  calendarOptions,
  handleEventSave,
  handleEventDelete,
  loadEvents,
  filteredEvents,
  updateFilter,
  updateSearchQuery,
  events,
  currentFilter,
  calendarLoaded,
} = useCalendarEvents(toastContainer, calendarWrapperRef);

const {
  handleDateNavigation,
  handleViewChange,
  handleDateSelection,
  setupScrollListener,
} = useCalendarNavigation(calendarWrapperRef, currentView, loadEvents);

const {
  handleDateClick,
  handleEventClick,
  handleSidebarEventClick,
  handleRegularAppointment,
  closeEventPopover,
  showEventPopoverAtToday,
} = useEventHandling({
  selectedDate,
  showTypeSelector,
  selectedEvent,
  showEventPopover,
  popoverMode,
  popoverPosition,
  initialModalPosition,
  calendarWrapperRef,
});

const { handleGhostEventUpdate } = useGhostEvent(calendarWrapperRef);

// Swipe-Funktionalität
const touchStartX = ref(0);
const touchEndX = ref(0);
const swipeOffset = ref(0);
const isSwipingLeft = ref(false);
const isSwipingRight = ref(false);
const isAnimating = ref(false);
const swipeThreshold = 80; // Minimale Pixel für einen erfolgreichen Swipe

// Touch-Handler
const handleTouchStart = (e) => {
  // Entferne die Monatsansicht-Beschränkung
  touchStartX.value = e.touches[0].clientX;
  touchEndX.value = e.touches[0].clientX;
};

const handleTouchMove = (e) => {
  // Entferne die Monatsansicht-Beschränkung
  if (isAnimating.value) return;
  
  touchEndX.value = e.touches[0].clientX;
  const diff = touchEndX.value - touchStartX.value;
  
  // Begrenzen der Swipe-Distanz
  const maxSwipe = window.innerWidth * 0.4; // 40% der Bildschirmbreite
  swipeOffset.value = Math.max(Math.min(diff, maxSwipe), -maxSwipe);
  
  // Anzeigen der entsprechenden Navigation basierend auf Swipe-Richtung
  isSwipingLeft.value = diff > 0;
  isSwipingRight.value = diff < 0;
};

const handleTouchEnd = () => {
  if (isAnimating.value) return;
  
  const diff = touchEndX.value - touchStartX.value;
  
  // Wenn der Swipe stark genug war, zur nächsten/vorherigen Periode wechseln
  if (diff > swipeThreshold) {
    // Nach rechts gewischt -> vorherige Periode
    completeSwipeAnimation('prev');
  } else if (diff < -swipeThreshold) {
    // Nach links gewischt -> nächste Periode
    completeSwipeAnimation('next');
  } else {
    // Nicht weit genug gewischt -> zurück zur Ausgangsposition
    resetSwipe();
  }
};

const completeSwipeAnimation = (direction) => {
  isAnimating.value = true;
  
  // Animation zum Rand fortsetzen
  const targetOffset = direction === 'prev' ? window.innerWidth : -window.innerWidth;
  swipeOffset.value = targetOffset;
  
  // Nach der Animation den Kalender aktualisieren und zurücksetzen
  setTimeout(() => {
    handleDateNavigation(direction);
    
    // Sofort zurücksetzen ohne Animation
    swipeOffset.value = 0;
    isSwipingLeft.value = false;
    isSwipingRight.value = false;
    
    // Animation-Flag zurücksetzen
    setTimeout(() => {
      isAnimating.value = false;
    }, 50);
  }, 300); // Entspricht der Animationsdauer
};

const resetSwipe = () => {
  isAnimating.value = true;
  swipeOffset.value = 0;
  
  // Nach der Animation zurücksetzen
  setTimeout(() => {
    isSwipingLeft.value = false;
    isSwipingRight.value = false;
    isAnimating.value = false;
  }, 300);
};

// Anpassen der Swipe-Overlay Texte basierend auf der aktuellen Ansicht
const getNavigationLabel = computed(() => {
  switch (currentView.value) {
    case 'dayGridMonth':
      return {
        prev: 'Vorheriger Monat',
        next: 'Nächster Monat'
      };
    case 'timeGridWeek':
      return {
        prev: 'Vorherige Woche',
        next: 'Nächste Woche'
      };
    case 'timeGridDay':
      return {
        prev: 'Vorheriger Tag',
        next: 'Nächster Tag'
      };
    case 'listWeek':
      return {
        prev: 'Vorherige Woche',
        next: 'Nächste Woche'
      };
    default:
      return {
        prev: 'Zurück',
        next: 'Vor'
      };
  }
});

// Methods
const handleFilterChange = (newFilter) => {
  updateFilter(newFilter);
};

const handleSearch = (query) => {
  searchQuery.value = query;
  updateSearchQuery(query);
};

// Responsive methods
const toggleSidebar = () => {
  isSidebarCollapsed.value = !isSidebarCollapsed.value;
  
  // Wir müssen warten, bis die DOM-Änderungen abgeschlossen sind
  nextTick(() => {
    const api = calendarWrapperRef.value?.getApi();
    if (api) {
      api.updateSize();
    }
  });
};

const checkScreenSize = () => {
  isSmallScreen.value = window.innerWidth < 1024; // lg breakpoint in Tailwind ist 1024px
  isExtraSmallScreen.value = window.innerWidth < 640; // sm breakpoint in Tailwind ist 640px
  
  // Wenn der Bildschirm klein ist, Sidebar automatisch einklappen
  if (isSmallScreen.value) {
    isSidebarCollapsed.value = true;
  } else {
    isSidebarCollapsed.value = false;
  }
  
  // Kalender-Größe aktualisieren
  nextTick(() => {
    const api = calendarWrapperRef.value?.getApi();
    if (api) {
      api.updateSize();
    }
  });
};

// Wheel-Handler für Monatswechsel
const handleWheel = (e) => {
  // Nur in der Monatsansicht das Rad-Scrollen für Monatswechsel verwenden
  if (currentView.value !== 'dayGridMonth') return;

  // Verhindere Standard-Scroll
  e.preventDefault();

  // Ignoriere horizontales Scrollen
  if (Math.abs(e.deltaX) > Math.abs(e.deltaY)) return;

  // Verhindere zu schnelles Scrollen durch Debouncing
  if (isAnimating.value) return;

  // Bestimme die Scroll-Richtung
  if (e.deltaY < 0) {
    // Nach oben = vorheriger Monat
    completeSwipeAnimation('prev');
  } else {
    // Nach unten = nächster Monat
    completeSwipeAnimation('next');
  }
};

// Computed property, um zu bestimmen, welches Modal angezeigt werden soll
const useFullscreenModal = computed(() => {
  return currentView.value !== 'dayGridMonth';
});

// Lifecycle hooks
onMounted(async () => {
  try {
    // Bildschirmgröße überprüfen
    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);
    
    // URL-Parameter verarbeiten
    const urlParams = new URLSearchParams(window.location.search);
    const viewParam = urlParams.get('view') || 'dayGridMonth';
    currentView.value = viewParam;

    // Korrigierter Zugriff auf die Calendar API
    const calendarApi = calendarWrapperRef.value?.getApi();
    if (calendarApi) {
      setCalendarApi(calendarApi); // Für das timeGridRoomPlugin
      calendarApi.changeView(viewParam);

      const dateParam = urlParams.get('date');
      if (dateParam) {
        const date = parseMonthString(dateParam, viewParam);
        if (date) {
          calendarApi.gotoDate(date);
        }
      }
    }

    // Events laden
    await loadEvents(calendarApi);

    // MutationObserver für Kalender-Container einrichten
    await nextTick();
    const calendarContainer = document.querySelector('.calendar-wrapper');
    if (calendarContainer && calendarContainer instanceof Node) {
      const observer = new MutationObserver(() => {
        const api = calendarWrapperRef.value?.getApi();
        if (api) {
          api.updateSize();
        }
      });

      observer.observe(calendarContainer, {
        attributes: true,
        childList: true,
        subtree: true,
      });
    }

    // Scroll-Listener einrichten
    setupScrollListener();
  } catch (error) {
    console.error('Fehler beim Initialisieren des Kalenders:', error);
    toastContainer.value?.addToast({
      message: 'Fehler beim Initialisieren des Kalenders',
      type: 'error',
      timeout: 3000,
    });
  }
});

onBeforeUnmount(() => {
  if (mutationObserver.value) {
    mutationObserver.value.disconnect();
  }
  window.removeEventListener('resize', checkScreenSize);
});

// Watch für Sidebar-Änderungen
watch(isSidebarCollapsed, () => {
  nextTick(() => {
    const api = calendarWrapperRef.value?.getApi();
    if (api) {
      api.updateSize();
    }
  });
});
</script>

<style scoped>
.v-enter-active,
.v-leave-active {
  transition: opacity 0.25s ease;
}

.v-enter-from,
.v-leave-to {
  opacity: 0;
}

/* Slide transitions */
.slide-prev-enter-active,
.slide-prev-leave-active,
.slide-next-enter-active,
.slide-next-leave-active {
  transition: all 0.1s ease;
}

.slide-prev-enter-from {
  transform: translateX(-100px);
  opacity: 0;
}

.slide-prev-leave-to {
  transform: translateX(100px);
  opacity: 0;
}

.slide-next-enter-from {
  transform: translateX(100px);
  opacity: 0;
}

.slide-next-leave-to {
  transform: translateX(-100px);
  opacity: 0;
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from :deep(.fixed),
.modal-leave-to :deep(.fixed) {
  transform: scale(0.95);
}

:deep(.fixed) {
  transition: transform 0.2s ease;
}

/* Swipe-Animation Styles */
.calendar-animation-container {
  will-change: transform;
}
</style>
