<template>
  <Transition name="fullscreen-modal">
    <div class="fixed inset-0 z-50 bg-window_bg overflow-hidden flex flex-col">
      <!-- Header - Fix oben -->
      <FullscreenEventModalHeader
        class="flex-shrink-0"
        :mode="currentMode"
        :event-data="eventData"
        :is-todo="isTodo"
        @update:mode="handleModeChange"
        @close="handleClose"
      />

      <!-- Hauptinhalt mit zwei Spalten -->
      <div class="flex-1 flex flex-col md:flex-row overflow-hidden">
        <!-- Linke Spalte: Hauptinhalt -->
        <div class="flex-1 overflow-y-auto p-4 md:p-6">
          <FullscreenTodoModalContent
            v-if="isTodo"
            :mode="currentMode"
            :todo-data="eventData"
            @check-todo="handleCheckTodo"
          />
          <FullscreenEventModalContent
            v-else
            :mode="currentMode"
            :event-data="eventData"
            :user-search="userSearch"
            @update:userSearch="handleUserSearchUpdate"
            :search-results="searchResults"
            :is-searching="isSearching"
            :current-name="currentName"
            @add-user="handleAddUser"
            @remove-user="handleRemoveUser"
          />
        </div>

        <!-- Rechte Spalte: Erweiterte Einstellungen (nur für normale Events) -->
        <div v-if="!isTodo" class="md:w-80 bg-gray-50 overflow-y-auto p-4 md:p-6 border-t md:border-t-0 md:border-l border-gray-200">
          <FullscreenEventModalAdvanced
            v-model="showAdvancedSettings"
            :mode="currentMode"
            :event-data="eventData"
            :rooms="rooms"
            :categories="categories"
            :predefined-colors="predefinedColors"
          />
        </div>
      </div>

      <!-- Footer - Fix unten -->
      <FullscreenEventModalFooter
        class="flex-shrink-0"
        :mode="currentMode"
        :is-saving="isLoading"
        :is-todo="isTodo"
        :todo-data="isTodo ? eventData : null"
        @save="handleSave"
        @delete="handleDelete"
        @close="handleClose"
        @check-todo="handleCheckTodo"
      />
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount, computed } from "vue";
import FullscreenEventModalHeader from "./FullscreenEventModalHeader.vue";
import FullscreenEventModalContent from "./FullscreenEventModalContent.vue";
import FullscreenEventModalAdvanced from "./FullscreenEventModalAdvanced.vue";
import FullscreenEventModalFooter from "./FullscreenEventModalFooter.vue";
import FullscreenTodoModalContent from "./FullscreenTodoModalContent.vue";
import useEventPopover from "../../../composables/useEventPopover";
import useEventUsers from "../../../composables/useEventUsers";
import axios from "axios";

const props = defineProps({
  currentName: {
    type: String,
    required: true,
  },
  currentId: {
    type: Number,
    required: true,
  },
  mode: {
    type: String,
    required: true,
  },
  event: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["close", "save", "delete"]);

// Check if the event is a todo
const isTodo = computed(() => {
  return props.event.id && props.event.id.toString().startsWith('todo-');
});

// Event popover composable
const {
  eventData,
  showAdvancedSettings,
  currentMode,
  categories,
  rooms,
  predefinedColors,
  initializeEventData,
  fetchRequiredData,
  validateEventData,
  prepareEventForSave,
  resetPopover,
} = useEventPopover({
  onClose: () => emit("close"),
});

// Event users composable
const {
  searchQuery: userSearch,
  searchResults,
  isSearching,
  selectedUsers,
  handleUserSearch,
  addUser,
  removeUser,
  initializeUsers,
  resetUsers,
} = useEventUsers({
  currentUserId: props.currentId,
  onUsersChange: (users) => {
    eventData.value.users = users;
  },
});

// Loading state
const isLoading = ref(false);

// Initialize event data and users immediately
initializeEventData(props.event, props.mode);
if (!isTodo.value) {
  initializeUsers(props.event.users || []);
}

// Methods
const handleClose = () => {
  resetPopover();
  if (!isTodo.value) {
    resetUsers();
  }
  emit("close");
};

const handleModeChange = (newMode) => {
  currentMode.value = newMode;
};

const handleUserSearchUpdate = (query) => {
  userSearch.value = query;
  handleUserSearch(query);
};

const handleAddUser = (user) => {
  addUser(user);
  userSearch.value = "";
};

const handleRemoveUser = (user) => {
  removeUser(user);
};

const handleSave = async () => {
  if (isTodo.value) {
    try {
      isLoading.value = true;
      const todoId = eventData.value.id.toString().replace('todo-', '');
      await axios.put(`/api/todo/${todoId}`, {
        title: eventData.value.title,
        description: eventData.value.description,
        dueDate: eventData.value.dueDate || eventData.value.startDate
      });
      handleClose();
    } catch (error) {
      console.error("Error saving todo:", error);
    } finally {
      isLoading.value = false;
    }
    return;
  }

  // Validierung
  if (!validateEventData(eventData.value)) {
    return;
  }

  // Event für Speicherung vorbereiten
  const eventToSave = prepareEventForSave(eventData.value);

  // Event speichern und Modal schließen
  emit("save", eventToSave);
  handleClose(); // Hier wird das Modal nach dem Speichern geschlossen
};

const handleDelete = async () => {
  if (isTodo.value) {
    try {
      isLoading.value = true;
      const todoId = eventData.value.id.toString().replace('todo-', '');
      await axios.delete(`/api/todo/${todoId}`);
      handleClose();
    } catch (error) {
      console.error("Error deleting todo:", error);
    } finally {
      isLoading.value = false;
    }
    return;
  }

  emit("delete", eventData.value);
  handleClose();
};

const handleCheckTodo = async () => {
  if (!isTodo.value) return;
  
  try {
    // Lokale Variable statt ref verwenden
    const todoId = eventData.value.id.toString().replace('todo-', '');
    await axios.patch(`/api/todo/${todoId}/check`);
    
    // Schließen des Modals nach erfolgreicher Aktualisierung
    handleClose();
  } catch (error) {
    console.error("Error checking todo:", error);
  }
};

const handleKeydown = (e) => {
  if (e.key === "Escape") {
    handleClose();
  }
};

onMounted(() => {
  document.addEventListener("keydown", handleKeydown);
  
  // Fetch required data if not a todo
  if (!isTodo.value) {
    fetchRequiredData();
  }
});

onBeforeUnmount(() => {
  document.removeEventListener("keydown", handleKeydown);
  if (!isTodo.value) {
    resetUsers();
  }
  resetPopover();
});
</script>

<style scoped>
.fullscreen-modal-enter-active,
.fullscreen-modal-leave-active {
  transition: opacity 0.3s ease;
}

.fullscreen-modal-enter-from,
.fullscreen-modal-leave-to {
  opacity: 0;
}
</style> 