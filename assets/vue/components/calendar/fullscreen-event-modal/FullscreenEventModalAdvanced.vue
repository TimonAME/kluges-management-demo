<template>
  <div class="space-y-6">
    <h2 class="text-lg font-medium mb-4">Weitere Optionen</h2>
    
    <!-- Room Selection -->
    <div class="space-y-2">
      <label class="block text-sm font-medium text-gray-600">Raum</label>
      <template v-if="mode !== 'view'">
        <select
          v-model="selectedRoomId"
          @change="updateRoom"
          class="modal-select w-full"
        >
          <option value="">Kein Raum</option>
          <option v-for="room in rooms" :key="room.id" :value="room.id">
            {{ room.roomNumber }}
          </option>
        </select>
      </template>
      <div v-else class="text-gray-700">
        {{ getRoomNumber }}
      </div>
    </div>

    <!-- Category Selection -->
    <div class="space-y-2">
      <label class="block text-sm font-medium text-gray-600">Kategorie</label>
      <template v-if="mode !== 'view'">
        <select
          v-model="selectedCategoryId"
          @change="updateCategory"
          class="modal-select w-full"
        >
          <option v-for="category in filteredCategories" :key="category.id" :value="category.id">
            {{ category.name }}
          </option>
        </select>
      </template>
      <div v-else class="text-gray-700">
        {{ getCategoryName }}
      </div>
    </div>

    <!-- Color Selection -->
    <div class="space-y-2">
      <label class="block text-sm font-medium text-gray-600">Farbe</label>
      <div v-if="mode !== 'view'" class="flex flex-wrap gap-2">
        <button
          v-for="color in predefinedColors"
          :key="color"
          @click="eventData.color = color"
          class="w-8 h-8 rounded-full border-2 transition-transform hover:scale-110"
          :class="[
            eventData.color === color
              ? 'border-white ring-2 ring-second_accent scale-110'
              : 'border-transparent',
          ]"
          :style="{ backgroundColor: color }"
        ></button>
      </div>
      <div v-else class="flex items-center">
        <div
          class="w-6 h-6 rounded-full mr-2"
          :style="{ backgroundColor: eventData.color }"
        ></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  mode: {
    type: String,
    required: true,
    validator: (value) => ["view", "edit", "new"].includes(value),
  },
  eventData: {
    type: Object,
    required: true,
  },
  rooms: {
    type: Array,
    default: () => [],
  },
  categories: {
    type: Array,
    default: () => [],
  },
  predefinedColors: {
    type: Array,
    default: () => [],
  },
});

defineEmits(["update:modelValue"]);

// Local state
const selectedRoomId = ref("");
const selectedCategoryId = ref("");

// Watch for changes in eventData
watch(
  () => props.eventData.room,
  (newRoom) => {
    selectedRoomId.value =
      typeof newRoom === "object" ? newRoom?.id || "" : newRoom || "";
  },
);

watch(
  () => props.eventData.appointmentCategory,
  (newCategory) => {
    selectedCategoryId.value =
      typeof newCategory === "object" ? newCategory?.id : newCategory;
  },
);

// Initialize with values from eventData
onMounted(() => {
  // Initialize with either the ID directly or extract it from object if needed
  selectedRoomId.value =
    typeof props.eventData.room === "object"
      ? props.eventData.room?.id || ""
      : props.eventData.room || "";

  selectedCategoryId.value =
    typeof props.eventData.appointmentCategory === "object"
      ? props.eventData.appointmentCategory.id
      : props.eventData.appointmentCategory;
      
  // Wenn wir im "new" Modus sind und startDate/endDate bereits gesetzt sind,
  // verwenden wir diese Werte (wie im EventModal)
  if (props.mode === "new" && props.eventData.startDate) {
    // Überprüfen, ob das Datum ein ISO-String mit Uhrzeit ist
    if (props.eventData.startDate.includes('T')) {
      // Datum aus dem ISO-String extrahieren (nur YYYY-MM-DD)
      const dateOnly = props.eventData.startDate.split('T')[0];
      props.eventData.startDate = dateOnly;
      
      // Wenn endDate auch gesetzt ist, auch dort das Format korrigieren
      if (props.eventData.endDate && props.eventData.endDate.includes('T')) {
        props.eventData.endDate = props.eventData.endDate.split('T')[0];
      }
      
      console.log("Neuer Termin mit formatiertem Datum:", props.eventData.startDate);
    } else {
      console.log("Neuer Termin mit vorausgefülltem Datum:", props.eventData.startDate);
    }
  }
});

// Computed properties for display values
const getRoomNumber = computed(() => {
  if (!props.eventData.room) return "Kein Raum";
  
  const roomId =
    typeof props.eventData.room === "object"
      ? props.eventData.room.id
      : props.eventData.room;

  if (!roomId) return "Kein Raum";
  
  const room = props.rooms.find((r) => r.id === roomId);
  return room?.roomNumber || "Kein Raum";
});

const getCategoryName = computed(() => {
  const categoryId =
    typeof props.eventData.appointmentCategory === "object"
      ? props.eventData.appointmentCategory.id
      : props.eventData.appointmentCategory;

  const category = props.categories.find((c) => c.id === categoryId);
  return category?.name || "";
});

// Methods to update the eventData
const updateRoom = () => {
  // Explizit prüfen ob ein leerer String und dann null setzen
  props.eventData.room = selectedRoomId.value === "" ? null : selectedRoomId.value;
};

const updateCategory = () => {
  // Store only the ID in eventData
  props.eventData.appointmentCategory = selectedCategoryId.value;
};

// Computed property to filter out "Nachhilfetermin" category
const filteredCategories = computed(() => {
  return props.categories.filter(category => 
    category.name.toLowerCase() !== "nachhilfetermin"
  );
});
</script>

<style scoped>
.modal-select {
  @apply border rounded-window px-3 py-2 focus:ring-2 focus:ring-second_accent focus:border-transparent border-gray-300;
}
</style> 