<!-- EventModalAdvanced.vue -->
<template>
  <div class="px-4">
    <!-- Toggle Button -->
    <button
      @click="$emit('update:modelValue', !modelValue)"
      class="w-full flex items-center justify-between px-3 py-2 mb-3 text-gray-600 hover:bg-gray-50 rounded-window transition-colors"
    >
      <span class="font-medium">Weitere Optionen</span>
      <ChevronDownIcon
        class="w-5 h-5 transition-transform duration-200"
        :class="{ 'rotate-180': modelValue }"
      />
    </button>

    <!-- Advanced Settings Content -->
    <div v-show="modelValue" class="space-y-4 my-3">
      <!-- Room Selection -->
      <div class="flex items-start gap-4">
        <div
          class="flex-shrink-0 mt-1 p-2 bg-gray-50 rounded-window text-gray-500"
        >
          <HomeIcon class="w-5 h-5" />
        </div>
        <div class="flex-grow">
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
          <span v-else class="text-gray-600">
            {{ getRoomNumber }}
          </span>
        </div>
      </div>

      <!-- Category Selection -->
      <div class="flex items-start gap-4">
        <div
          class="flex-shrink-0 mt-1 p-2 bg-gray-50 rounded-window text-gray-500"
        >
          <TagsIcon class="w-5 h-5" />
        </div>
        <div class="flex-grow">
          <template v-if="mode !== 'view'">
            <select
              v-model="selectedCategoryId"
              @change="updateCategory"
              class="modal-select w-full"
            >
              <option
                v-for="category in filteredCategories"
                :key="category.id"
                :value="category.id"
              >
                {{ category.name }}
              </option>
            </select>
          </template>
          <span v-else class="text-gray-600">
            {{ getCategoryName }}
          </span>
        </div>
      </div>

      <!-- Color Selection -->
      <div class="flex items-start gap-4">
        <div
          class="flex-shrink-0 mt-1 p-2 bg-gray-50 rounded-window text-gray-500"
        >
          <PaletteIcon class="w-5 h-5" />
        </div>
        <div class="flex-grow mt-3">
          <div v-if="mode !== 'view'" class="flex flex-wrap gap-2">
            <button
              v-for="color in predefinedColors"
              :key="color"
              @click="eventData.color = color"
              class="w-6 h-6 rounded-full border border-gray-300 transition-transform hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-second_accent"
              :style="{ backgroundColor: color }"
              :class="{
                'ring-2 ring-offset-2 ring-second_accent':
                  eventData.color === color,
              }"
            ></button>
          </div>
          <div v-else class="flex items-center">
            <div
              class="w-4 h-4 rounded-full mr-2"
              :style="{ backgroundColor: eventData.color }"
            ></div>
            <span class="text-gray-600">{{ eventData.color }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { ChevronDownIcon, HomeIcon, TagsIcon, PaletteIcon } from 'lucide-vue-next';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  mode: {
    type: String,
    default: "new",
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
const selectedCategoryId = ref(null);

// Setze die Standardkategorie auf "Termin", wenn verfügbar
const setDefaultCategory = () => {
  // Wenn bereits eine Kategorie gesetzt ist, diese beibehalten
  if (props.eventData.appointmentCategory) {
    selectedCategoryId.value = 
      typeof props.eventData.appointmentCategory === "object"
        ? props.eventData.appointmentCategory.id
        : props.eventData.appointmentCategory;
    return;
  }
  
  // Sonst nach "Termin" suchen
  const defaultCategory = props.categories.find(cat => cat.name === "Termin");
  if (defaultCategory) {
    selectedCategoryId.value = defaultCategory.id;
    props.eventData.appointmentCategory = defaultCategory.id;
  } else if (props.categories.length > 0) {
    // Fallback auf die erste Kategorie
    selectedCategoryId.value = props.categories[0].id;
    props.eventData.appointmentCategory = props.categories[0].id;
  }
};

// Watch für Änderungen an den Kategorien
watch(() => props.categories, (newCategories) => {
  if (newCategories.length > 0) {
    setDefaultCategory();
  }
}, { immediate: true });

onMounted(() => {
  // Initialize with either the ID directly or extract it from object if needed
  selectedRoomId.value =
    typeof props.eventData.room === "object"
      ? props.eventData.room?.id || ""
      : props.eventData.room || "";

  // Wenn die Kategorien bereits geladen sind, setze die Standardkategorie
  if (props.categories.length > 0) {
    setDefaultCategory();
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

.rotate-180 {
  transform: rotate(180deg);
}
</style>
