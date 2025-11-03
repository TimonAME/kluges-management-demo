<script setup>
import { ref, computed, watch, onMounted } from "vue";
import axios from 'axios';
import {
  CalendarDays,
  Save,
  Clock,
  ChevronLeft,
  ChevronRight,
  Calendar,
  CalendarRange,
  X,
  PlusCircle,
  Edit,
  MousePointer,
  Pencil,
  Plus,
} from "lucide-vue-next";

const props = defineProps({
  user_id: {
    type: Number,
    required: true,
  },
});

// State Management
const templateSchedule = ref([]);
const weeklySchedules = ref(new Map());
const isLoading = ref(false);
const error = ref(null);

// API Funktionen
const fetchWorkingHours = async () => {
  isLoading.value = true;
  error.value = null;
  
  try {
    const response = await axios.get(`/api/user/work/${props.user_id}`);
    templateSchedule.value = response.data.workingHours.template;
    
    // Konvertiere die individuellen Zeitpläne
    const individual = response.data.workingHours.individual || {};
    weeklySchedules.value = new Map(Object.entries(individual));
  } catch (err) {
    console.error('Fehler beim Laden der Arbeitszeiten:', err);
    error.value = 'Die Arbeitszeiten konnten nicht geladen werden.';
  } finally {
    isLoading.value = false;
  }
};

const saveWorkingHours = async () => {
  isLoading.value = true;
  error.value = null;

  try {
    await axios.patch(`/api/user/work/${props.user_id}`, {
      workingHours: {
        template: templateSchedule.value,
        individual: Object.fromEntries(weeklySchedules.value),
      },
    });
  } catch (err) {
    console.error('Fehler beim Speichern der Arbeitszeiten:', err);
    error.value = 'Die Änderungen konnten nicht gespeichert werden.';
  } finally {
    isLoading.value = false;
  }
};

// Lade Daten beim Komponenten-Mount
onMounted(() => {
  fetchWorkingHours();
});

// Base state management
const scheduleMode = ref("template");
const currentWeek = ref(new Date());
const showTimeSelector = ref(false);
const editingDay = ref(null);
const editingSlot = ref(null);
const editingPosition = ref({ x: 0, y: 0 });
const customStartTime = ref("13:00");
const customEndTime = ref("14:00");
const isEditing = ref(false);
const drawMode = ref(false);
const isDrawing = ref(false);
const drawStart = ref(null);
const drawEnd = ref(null);
const currentDrawDay = ref(null);

// Constants
const HOUR_HEIGHT = 60;
const TOTAL_HOURS = 14;
const HEADER_HEIGHT = 40;
const GRID_HEIGHT = HOUR_HEIGHT * TOTAL_HOURS;
const weekDays = ["Mo", "Di", "Mi", "Do", "Fr", "Sa", "So"];

// Helper functions
const getWeekNumber = (date) => {
  const firstDayOfYear = new Date(date.getFullYear(), 0, 1);
  const pastDaysOfYear = (date - firstDayOfYear) / 86400000;
  return Math.ceil((pastDaysOfYear + firstDayOfYear.getDay() + 1) / 7);
};

const generateWeekKey = (date) => {
  const year = date.getFullYear();
  const week = getWeekNumber(date);
  return `${year}-W${week}`;
};

// Time grid lines
const timeGridLines = computed(() => {
  const lines = [];
  for (let hour = 0; hour <= TOTAL_HOURS; hour++) {
    lines.push({
      position: hour * HOUR_HEIGHT + HEADER_HEIGHT,
      type: "hour",
      label: (hour + 8).toString().padStart(2, "0") + ":00",
    });

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

const timeToPixel = (time) => {
  const [hours, minutes] = time.split(":").map(Number);
  const totalMinutes = (hours - 8) * 60 + minutes;
  return (totalMinutes / 60) * HOUR_HEIGHT + HEADER_HEIGHT;
};

const pixelToTime = (pixel) => {
  const adjustedPixel = pixel - HEADER_HEIGHT;
  const totalMinutes = (adjustedPixel / HOUR_HEIGHT) * 60;
  const hours = Math.floor(totalMinutes / 60) + 8;
  const minutes = Math.round((totalMinutes % 60) / 15) * 15;

  if (hours < 8) return "08:00";
  if (hours >= 22) return "22:00";

  return `${hours.toString().padStart(2, "0")}:${minutes.toString().padStart(2, "0")}`;
};

const snapToGrid = (pixel) => {
  const quarterHour = HOUR_HEIGHT / 4;
  return Math.round(pixel / quarterHour) * quarterHour;
};

// Time selection options
const availableTimes = computed(() => {
  const times = [];
  for (let hour = 8; hour <= 21; hour++) {
    for (let minute of ["00", "15", "30", "45"]) {
      times.push(`${hour.toString().padStart(2, "0")}:${minute}`);
    }
  }
  return times;
});

const availableEndTimes = computed(() => {
  const times = [];
  const [startHour, startMinute] = customStartTime.value.split(":").map(Number);

  for (let hour = startHour; hour <= 21; hour++) {
    for (let minute of ["00", "15", "30", "45"]) {
      if (hour === startHour && Number(minute) <= startMinute) continue;
      times.push(`${hour.toString().padStart(2, "0")}:${minute}`);
    }
  }
  return times;
});

// Drawing functions
const startDrawing = (dayIndex, event) => {
  if (!drawMode.value || isPastDay(dayIndex)) return;

  const rect = event.currentTarget.getBoundingClientRect();
  const y = event.clientY - rect.top;
  const snappedY = snapToGrid(y);

  isDrawing.value = true;
  currentDrawDay.value = dayIndex;
  drawStart.value = Math.min(
    Math.max(snappedY, HEADER_HEIGHT),
    GRID_HEIGHT + HEADER_HEIGHT
  );
  drawEnd.value = drawStart.value;
};

const updateDrawing = (event) => {
  if (!isDrawing.value || !drawMode.value) return;

  const rect = event.currentTarget.getBoundingClientRect();
  const y = event.clientY - rect.top;
  const snappedY = snapToGrid(y);

  drawEnd.value = Math.min(
    Math.max(snappedY, HEADER_HEIGHT),
    GRID_HEIGHT + HEADER_HEIGHT
  );
};

// Neue Funktion zum Beenden des Zeichnens und Erstellen des Zeitblocks
const endDrawing = () => {
  if (!isDrawing.value || !drawMode.value || currentDrawDay.value === null) return;
  
  // Erstelle den Zeitblock
  const startTime = pixelToTime(Math.min(drawStart.value, drawEnd.value));
  const endTime = pixelToTime(Math.max(drawStart.value, drawEnd.value));
  
  // Prüfe, ob der Zeitraum gültig ist (mindestens 15 Minuten)
  if (startTime === endTime) {
    isDrawing.value = false;
    currentDrawDay.value = null;
    drawStart.value = null;
    drawEnd.value = null;
    return;
  }
  
  // Prüfe auf Überschneidungen
  const daySchedule = currentSchedule.value[currentDrawDay.value] || [];
  const hasOverlap = daySchedule.some(slot => {
    return (
      (startTime >= slot.start && startTime < slot.end) ||
      (endTime > slot.start && endTime <= slot.end) ||
      (startTime <= slot.start && endTime >= slot.end)
    );
  });
  
  if (!hasOverlap) {
    // Füge den neuen Zeitblock hinzu
    addTimeBlock(currentDrawDay.value, startTime, endTime);
  }
  
  // Zurücksetzen des Zeichenmodus
  isDrawing.value = false;
  currentDrawDay.value = null;
  drawStart.value = null;
  drawEnd.value = null;
};

// Hilfsfunktion zum Hinzufügen eines Zeitblocks
const addTimeBlock = (dayIndex, startTime, endTime) => {
  const newSlot = {
    start: startTime,
    end: endTime,
  };
  
  if (scheduleMode.value === "template") {
    // Stelle sicher, dass templateSchedule initialisiert ist
    if (!templateSchedule.value || !Array.isArray(templateSchedule.value)) {
      templateSchedule.value = Array(7).fill().map(() => []);
    }
    
    // Stelle sicher, dass der Tag ein Array ist
    if (!Array.isArray(templateSchedule.value[dayIndex])) {
      templateSchedule.value[dayIndex] = [];
    }
    
    templateSchedule.value[dayIndex].push(newSlot);
  } else {
    const key = weekKey.value;
    
    // Stelle sicher, dass für den Wochenschlüssel ein Eintrag existiert
    if (!weeklySchedules.value.has(key)) {
      weeklySchedules.value.set(key, Array(7).fill().map(() => []));
    }
    
    const schedule = weeklySchedules.value.get(key);
    
    // Stelle sicher, dass der Tag ein Array ist
    if (!Array.isArray(schedule[dayIndex])) {
      schedule[dayIndex] = [];
    }
    
    schedule[dayIndex].push(newSlot);
  }
};

const currentDrawingStyle = computed(() => {
  if (!isDrawing.value || drawStart.value === null || drawEnd.value === null) {
    return {};
  }

  const top = Math.min(drawStart.value, drawEnd.value);
  const height = Math.abs(drawEnd.value - drawStart.value);

  return {
    top: `${top}px`,
    height: `${Math.max(height, 1)}px`,
  };
});

// Week management
const weekKey = computed(() => generateWeekKey(currentWeek.value));

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
    });
  }

  return dates;
});

// Time validation
const isTimePast = (timeString) => {
  const now = new Date();
  const [hours, minutes] = timeString.split(":").map(Number);
  return (
    now.getHours() > hours ||
    (now.getHours() === hours && now.getMinutes() > minutes)
  );
};

const isPastDay = (dayIndex) => {
  if (scheduleMode.value === "template") return false;

  const selectedDate = new Date(currentWeek.value);
  selectedDate.setDate(
    selectedDate.getDate() - selectedDate.getDay() + dayIndex + 1,
  );
  const now = new Date();
  return selectedDate < new Date(now.setHours(0, 0, 0, 0));
};

// Schedule management
const currentSchedule = computed(() => {
  const weekKey = generateWeekKey(currentWeek.value);
  let schedule = {};

  if (scheduleMode.value === 'template') {
    return templateSchedule.value;
  }

  // Kopiere zuerst die Template-Zeiten
  weekDays.forEach((_, dayIndex) => {
    schedule[dayIndex] = [...(templateSchedule.value[dayIndex] || [])];
  });

  // Überprüfe individuelle Anpassungen
  const individualSchedule = weeklySchedules.value.get(weekKey) || {};
  weekDays.forEach((_, dayIndex) => {
    if (individualSchedule[dayIndex]) {
      individualSchedule[dayIndex].forEach(slot => {
        if (slot.start.startsWith('-')) {
          // Entferne überlappende Template-Zeiten
          const originalStart = slot.start.substring(1);
          schedule[dayIndex] = schedule[dayIndex].filter(templateSlot => 
            templateSlot.start !== originalStart || templateSlot.end !== slot.end
          );
        } else {
          // Füge oder aktualisiere individuelle Zeiten hinzu
          const existingIndex = schedule[dayIndex].findIndex(existing => 
            hasTimeOverlap(existing, slot)
          );
          
          if (existingIndex !== -1) {
            schedule[dayIndex][existingIndex] = slot;
          } else {
            schedule[dayIndex].push(slot);
          }
        }
      });
    }
  });

  return schedule;
});

// Hilfsfunktion für Zeitüberlappungen
const hasTimeOverlap = (slot1, slot2) => {
  const start1 = slot1.start.startsWith('-') ? slot1.start.substring(1) : slot1.start;
  const start2 = slot2.start.startsWith('-') ? slot2.start.substring(1) : slot2.start;
  
  return (
    (start1 >= start2 && start1 < slot2.end) ||
    (slot1.end > start2 && slot1.end <= slot2.end) ||
    (start1 <= start2 && slot1.end >= slot2.end)
  );
};

// Modifiziere removeTimeBlock
const removeTimeBlock = (dayIndex, blockIndex) => {
  if (scheduleMode.value === 'template') {
    templateSchedule.value[dayIndex].splice(blockIndex, 1);
  } else {
    const weekKey = generateWeekKey(currentWeek.value);
    const individualSchedule = weeklySchedules.value.get(weekKey) || {};
    
    // Hole den zu löschenden Block
    const blockToRemove = currentSchedule.value[dayIndex][blockIndex];
    
    // Prüfe ob es ein Template-Block ist
    const isTemplateBlock = templateSchedule.value[dayIndex]?.some(templateSlot =>
      templateSlot.start === blockToRemove.start && templateSlot.end === blockToRemove.end
    );

    if (isTemplateBlock) {
      // Markiere den Template-Block als gelöscht
      if (!individualSchedule[dayIndex]) {
        individualSchedule[dayIndex] = [];
      }
      individualSchedule[dayIndex].push({
        start: `-${blockToRemove.start}`,
        end: blockToRemove.end
      });
    } else {
      // Lösche den individuellen Block
      if (individualSchedule[dayIndex]) {
        individualSchedule[dayIndex] = individualSchedule[dayIndex].filter(slot =>
          slot.start !== blockToRemove.start || slot.end !== blockToRemove.end
        );
      }
    }

    weeklySchedules.value.set(weekKey, individualSchedule);
  }
};

// Time slot management
const addTimeSlot = (dayIndex, start, end) => {
  const newSlot = { start, end };

  if (scheduleMode.value === "template") {
    if (!hasOverlapWithExisting(templateSchedule.value[dayIndex], newSlot)) {
      templateSchedule.value[dayIndex].push(newSlot);
      templateSchedule.value[dayIndex].sort((a, b) =>
        a.start.localeCompare(b.start),
      );
    }
  } else {
    const key = weekKey.value;
    if (!weeklySchedules.value.has(key)) {
      weeklySchedules.value.set(
        key,
        templateSchedule.value.map((day) => [...day]),
      );
    }
    const weekSchedule = weeklySchedules.value.get(key);
    if (!hasOverlapWithExisting(weekSchedule[dayIndex], newSlot)) {
      weekSchedule[dayIndex].push(newSlot);
      weekSchedule[dayIndex].sort((a, b) => a.start.localeCompare(b.start));
    }
  }
};

const hasOverlapWithExisting = (daySchedule, newSlot) => {
  return daySchedule.some(
    (existing) => newSlot.start < existing.end && newSlot.end > existing.start,
  );
};

// Add this computed property after other computed properties
const hasOverlap = computed(() => {
  if (!editingDay.value && editingDay.value !== 0) return false;
  
  const daySchedule = currentSchedule.value[editingDay.value] || [];
  const newStart = customStartTime.value;
  const newEnd = customEndTime.value;
  
  // Skip checking the slot being edited
  return daySchedule.some((slot, index) => {
    if (isEditing.value && index === editingSlot.value) return false;
    
    return (
      (newStart >= slot.start && newStart < slot.end) ||
      (newEnd > slot.start && newEnd <= slot.end) ||
      (newStart <= slot.start && newEnd >= slot.end)
    );
  });
});

// Modify the saveTimeSlot function to properly check hasOverlap
const saveTimeSlot = () => {
  if (!isValidTimeRange.value || hasOverlap.value || !editingDay.value && editingDay.value !== 0) return;
  
  const newSlot = {
    start: customStartTime.value,
    end: customEndTime.value,
  };
  
  if (scheduleMode.value === "template") {
    if (!templateSchedule.value || !Array.isArray(templateSchedule.value)) {
      templateSchedule.value = Array(7).fill().map(() => []);
    }
    
    if (!Array.isArray(templateSchedule.value[editingDay.value])) {
      templateSchedule.value[editingDay.value] = [];
    }
    
    if (isEditing.value && editingSlot.value !== null) {
      templateSchedule.value[editingDay.value][editingSlot.value] = newSlot;
    } else {
      templateSchedule.value[editingDay.value].push(newSlot);
    }
  } else {
    const key = weekKey.value;
    
    if (!weeklySchedules.value.has(key)) {
      weeklySchedules.value.set(key, Array(7).fill().map(() => []));
    }
    
    const schedule = weeklySchedules.value.get(key);
    
    if (!Array.isArray(schedule[editingDay.value])) {
      schedule[editingDay.value] = [];
    }
    
    if (isEditing.value && editingSlot.value !== null) {
      schedule[editingDay.value][editingSlot.value] = newSlot;
    } else {
      schedule[editingDay.value].push(newSlot);
    }
  }
  
  closeTimeSelector();
};

// UI management
const openTimeSelector = (dayIndex, event, slot = null, index = null) => {
  if (drawMode.value) return;

  editingDay.value = dayIndex;
  editingSlot.value = index;
  isEditing.value = slot !== null;

  if (slot) {
    customStartTime.value = slot.start;
    customEndTime.value = slot.end;
  } else {
    customStartTime.value = "13:00";
    customEndTime.value = "14:00";
  }

  const rect = event.target.getBoundingClientRect();
  editingPosition.value = {
    x: Math.min(rect.left, window.innerWidth - 320),
    y: Math.min(rect.bottom + window.scrollY, window.innerHeight - 400),
  };
  showTimeSelector.value = true;
};

const closeTimeSelector = () => {
  showTimeSelector.value = false;
  editingDay.value = null;
  editingSlot.value = null;
  isEditing.value = false;
};

// Time block management
const changeWeek = (delta) => {
  const newDate = new Date(currentWeek.value);
  newDate.setDate(newDate.getDate() + delta * 7);
  currentWeek.value = newDate;
};

// Der Rest des Codes bleibt gleich, nur saveSchedule wird aktualisiert
const saveSchedule = async () => {
  await saveWorkingHours();
};

// Computed property für die Zeitbereich-Validierung
const isValidTimeRange = computed(() => {
  const [startHours, startMinutes] = customStartTime.value.split(':').map(Number);
  const [endHours, endMinutes] = customEndTime.value.split(':').map(Number);
  
  // Vergleiche die Zeiten
  if (startHours < endHours) {
    return true;
  } else if (startHours === endHours) {
    return startMinutes < endMinutes;
  }
  return false;
});
</script>

<template>
  <div
    class="absolute bg-window_bg rounded-window shadow-window w-[100%] h-[100%] left-0 top-0 p-4 overflow-auto"
  >
    <!-- Loading Overlay -->
    <div v-if="isLoading" class="absolute inset-0 bg-white bg-opacity-75 z-50 flex items-center justify-center">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-secondmain mx-auto"></div>
        <p class="mt-4 text-gray-600">Wird geladen...</p>
      </div>
    </div>

    <!-- Error Message -->
    <div v-if="error" class="mb-4 p-4 bg-red-50 text-red-600 rounded-lg">
      {{ error }}
    </div>

    <!-- Header mit Modus-Auswahl -->
    <div
      class="flex items-center justify-between mb-6 border-b border-gray-200 pb-4"
    >
      <h2 class="text-xl font-semibold flex items-center text-gray-800">
        <CalendarDays class="mr-2 text-main" />
        Arbeitszeiten
      </h2>
      <div class="flex items-center space-x-4 select-none">
        <!-- Wochen/Individual Modus -->
        <div class="flex p-1 bg-gray-100 rounded-lg">
          <button
            @click="scheduleMode = 'template'"
            class="px-4 py-2 rounded-md text-sm flex items-center transition-all duration-200"
            :class="
              scheduleMode === 'template'
                ? 'bg-white shadow-sm text-main'
                : 'text-gray-600 hover:bg-gray-50'
            "
          >
            <Calendar class="w-4 h-4 mr-2" />
            Wöchentlich
          </button>
          <button
            @click="scheduleMode = 'individual'"
            class="px-4 py-2 rounded-md text-sm flex items-center transition-all duration-200"
            :class="
              scheduleMode === 'individual'
                ? 'bg-white shadow-sm text-main'
                : 'text-gray-600 hover:bg-gray-50'
            "
          >
            <CalendarRange class="w-4 h-4 mr-2" />
            Individuell
          </button>
        </div>

        <!-- Eingabemodus -->
        <div class="flex p-1 bg-gray-100 rounded-lg select-none">
          <button
            @click="drawMode = false"
            class="px-4 py-2 rounded-md text-sm flex items-center transition-all duration-200"
            :class="
              !drawMode
                ? 'bg-white shadow-sm text-main'
                : 'text-gray-600 hover:bg-gray-50'
            "
          >
            <MousePointer class="w-4 h-4 mr-2" />
            Manuell
          </button>
          <button
            @click="drawMode = true"
            class="px-4 py-2 rounded-md text-sm flex items-center transition-all duration-200"
            :class="
              drawMode
                ? 'bg-white shadow-sm text-main'
                : 'text-gray-600 hover:bg-gray-50'
            "
          >
            <Pencil class="w-4 h-4 mr-2" />
            Zeichnen
          </button>
        </div>
      </div>
    </div>

    <!-- Wochen-Navigation -->
    <div class="flex justify-between items-center mb-6 select-none">
      <div class="text-sm text-gray-600 flex items-center space-x-2">
        <span>
          {{
            scheduleMode === "template"
              ? "Wöchentlich wiederkehrende Zeiten"
              : "Individuelle Wochenplanung"
          }}
        </span>
        <span
          v-if="drawMode"
          class="bg-second_accent bg-opacity-30 text-main px-2 py-0.5 rounded text-xs border border-blue-100"
        >
          {{ drawMode ? "Ziehen zum Auswählen" : "Klicken zum Hinzufügen" }}
        </span>
      </div>

      <div
        class="flex items-center space-x-2 bg-white rounded-lg shadow-sm px-2"
      >
        <button
          @click="() => changeWeek(-1)"
          class="p-2 rounded-lg hover:bg-gray-50 transition-colors"
          :disabled="scheduleMode === 'individual' && isPastDay(0)"
        >
          <ChevronLeft
            class="w-5 h-5"
            :class="
              scheduleMode === 'individual' && isPastDay(0)
                ? 'text-gray-300'
                : 'text-gray-600'
            "
          />
        </button>
        <span class="text-sm font-medium px-2">
          {{
            scheduleMode === "template"
              ? "Vorlagenwoche"
              : `Woche vom ${formattedWeek[0].formattedDate}`
          }}
        </span>
        <button
          @click="() => changeWeek(1)"
          class="p-2 rounded-lg hover:bg-gray-50 transition-colors"
        >
          <ChevronRight class="w-5 h-5 text-gray-600" />
        </button>
      </div>
    </div>

    <!-- Kalender-Grid mit Zeitachse -->
    <div class="grid grid-cols-[60px_repeat(7,1fr)] gap-4 select-none px-1">
      <!-- Zeitachse -->
      <div class="relative top-[42px] h-[880px]">
        <div class="h-[40px]"></div>
        <div
          v-for="line in timeGridLines"
          :key="line.position"
          class="absolute w-full select-none pointer-events-none"
          :style="{ top: `${line.position}px` }"
        >
          <div v-if="line.type === 'hour'" class="flex items-center h-8 -mt-4">
            <span class="text-xs text-gray-500 w-full text-right pr-2">{{
              line.label
            }}</span>
            <div class="absolute right-0 w-2 border-t border-gray-300" />
          </div>
          <div v-else class="absolute right-0 w-1 border-t border-gray-200" />
        </div>
      </div>

      <!-- Tageskolumnen -->
      <div
        v-for="(date, index) in formattedWeek"
        :key="index"
        class="relative bg-white rounded-lg shadow-window overflow-hidden px-2"
      >
        <!-- Tagesheader -->
        <div
          class="text-center py-2 border-b border-gray-100 bg-white sticky top-0 z-20"
          style="position: sticky; margin: 0 -8px; padding-left: 8px; padding-right: 8px;"
        >
          <div class="font-medium text-gray-800">{{ date.day }}</div>
          <div class="text-sm text-gray-500">
            {{ scheduleMode === "template" ? "" : date.formattedDate }}
          </div>
        </div>

        <!-- Zeitgrid mit Zeichenfläche -->
        <div
          class="relative h-[880px] group"
          @mousedown="(e) => startDrawing(index, e)"
          @mousemove="updateDrawing"
          @mouseup="endDrawing"
          @mouseleave="isDrawing && endDrawing()"
        >
          <!-- Stundenraster -->
          <div
            v-for="line in timeGridLines"
            :key="line.position"
            :style="{ top: `${line.position}px` }"
            :class="[
              'absolute w-full',
              line.type === 'hour'
                ? 'border-t border-gray-200'
                : 'border-t border-gray-100',
            ]"
          />

          <!-- Zeit-Hinzufügen-Button (nur im manuellen Modus) -->
          <button
            v-if="!drawMode && !isPastDay(index)"
            @click="(e) => openTimeSelector(index, e)"
            class="absolute right-2 top-2 opacity-0 group-hover:opacity-100 transition-opacity bg-main text-white rounded-full p-1.5 shadow-sm hover:bg-main"
          >
            <Plus class="w-4 h-4" />
          </button>

          <!-- Bestehende Zeitblöcke -->
          <div
            v-for="(block, blockIndex) in currentSchedule[index]"
            :key="blockIndex"
            class="absolute left-0 right-0 px-2 group/slot"
            :style="{
              top: `${timeToPixel(block.start)}px`,
              height: `${timeToPixel(block.end) - timeToPixel(block.start)}px`,
            }"
            @click.stop="
              (e) =>
                !drawMode &&
                !isPastDay(index) &&
                openTimeSelector(index, e, block, blockIndex)
            "
          >
            <div
              class="absolute inset-0 rounded transition-all duration-200"
              :class="[
                isPastDay(index)
                  ? 'bg-gray-100 text-gray-500'
                  : 'bg-second_accent bg-opacity-30 text-main group-hover/slot:bg-second_accent group-hover/slot:bg-opacity-15 cursor-pointer border border-transparent group-hover/slot:border-main group-hover/slot:border-opacity-15',
              ]"
            >
              <div class="px-2 py-1 text-xs truncate">
                {{ block.start }} - {{ block.end }}
              </div>
            </div>

            <!-- Lösch-Button -->
            <button
              v-if="!isPastDay(index) && !drawMode"
              @click.stop="() => removeTimeBlock(index, blockIndex)"
              class="absolute -right-1 -top-1 opacity-0 group-hover/slot:opacity-100 bg-white text-red-500 hover:text-red-700 p-1 rounded-full shadow-sm border border-gray-200 transition-all duration-200"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>

          <!-- Aktuelle Zeichnung -->
          <div
            v-if="isDrawing && currentDrawDay === index"
            class="absolute left-0 right-0 px-2 bg-second_accent bg-opacity-30 rounded pointer-events-none border border-main border-opacity-50"
            :style="currentDrawingStyle"
          >
            <div class="px-2 py-1 text-xs text-main font-medium">
              {{ pixelToTime(Math.min(drawStart, drawEnd)) }} - 
              {{ pixelToTime(Math.max(drawStart, drawEnd)) }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Zeit-Auswahl Modal -->
    <div
      v-if="showTimeSelector"
      class="fixed bg-white rounded-lg shadow-lg border border-gray-200 p-4 z-50 w-72"
      :style="{
        top: `${editingPosition.y}px`,
        left: `${editingPosition.x}px`,
      }"
    >
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-medium text-gray-800">
          {{ isEditing ? "Zeit bearbeiten" : "Zeit hinzufügen" }}
        </h3>
        <button
          @click="closeTimeSelector"
          class="text-gray-500 hover:text-gray-700 p-1 rounded-lg hover:bg-gray-100"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs text-gray-600 mb-1.5">Von</label>
            <select
              v-model="customStartTime"
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option v-for="time in availableTimes" :key="time" :value="time">
                {{ time }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-xs text-gray-600 mb-1.5">Bis</label>
            <select
              v-model="customEndTime"
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option
                v-for="time in availableEndTimes"
                :key="time"
                :value="time"
              >
                {{ time }}
              </option>
            </select>
          </div>
        </div>

        <!-- Validierungsmeldungen -->
        <div v-if="!isValidTimeRange || hasOverlap" class="space-y-1">
          <div
            v-if="!isValidTimeRange"
            class="text-xs text-red-600 bg-red-50 p-2 rounded"
          >
            Die Endzeit muss nach der Startzeit liegen
          </div>
          <div
            v-if="hasOverlap"
            class="text-xs text-red-600 bg-red-50 p-2 rounded"
          >
            Diese Zeit überschneidet sich mit einer bereits vorhandenen Zeit
          </div>
        </div>

        <button
          @click="saveTimeSlot"
          :disabled="!isValidTimeRange || hasOverlap"
          class="w-full px-4 py-2 bg-main text-white rounded-lg hover:bg-main disabled:opacity-50 disabled:cursor-not-allowed text-sm flex items-center justify-center transition-colors"
        >
          <PlusCircle v-if="!isEditing" class="w-4 h-4 mr-2" />
          <Edit v-else class="w-4 h-4 mr-2" />
          {{ isEditing ? "Zeit aktualisieren" : "Zeit hinzufügen" }}
        </button>
      </div>
    </div>

    <!-- Modal Overlay -->
    <div
      v-if="showTimeSelector"
      class="fixed inset-0 z-40 bg-black bg-opacity-20"
      @click="closeTimeSelector"
    ></div>

    <!-- Footer -->
    <div class="fixed bottom-4 right-6 z-50 p-4">
      <button
        @click="saveSchedule"
        :disabled="isLoading"
        class="bg-main text-white px-6 py-2.5 rounded-lg hover:opacity-90 transition-colors flex items-center shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <Save class="w-4 h-4 mr-2" />
        {{ isLoading ? 'Wird gespeichert...' : 'Änderungen speichern' }}
      </button>
    </div>

    <!-- Abstandhalter für den Footer -->
    <div class="h-16"></div>
  </div>
</template>