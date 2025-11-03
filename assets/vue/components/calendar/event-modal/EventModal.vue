<!-- EventModal.vue -->
<template>
  <Transition name="modal">
    <div class="fixed inset-0 z-40" @click="handleClose">
      <!-- Responsive Modal Container -->
      <div
        ref="modalRef"
        class="fixed z-50 bg-window_bg rounded-window shadow-window flex flex-col"
        :class="[
          windowWidth < 640 
            ? 'inset-0 rounded-none' 
            : 'w-96 h-[500px]'
        ]"
        :style="windowWidth >= 640 ? modalPosition : {}"
        @click.stop
      >
        <!-- Header - Fix oben -->
        <EventModalHeader
          class="flex-shrink-0"
          :mode="currentMode"
          :event-data="eventData"
          :is-todo="isTodo"
          @update:mode="handleModeChange"
          @close="handleClose"
        />

        <!-- Content - Scrollbar -->
        <div v-if="isTodo" class="flex-1 overflow-y-auto min-h-0">
          <TodoModalContent
            :mode="currentMode"
            :todo-data="eventData"
          />
        </div>
        <div v-else class="flex-1 overflow-y-auto min-h-0">
          <EventModalContent
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

          <EventModalAdvanced
            v-model="showAdvancedSettings"
            :mode="currentMode"
            :event-data="eventData"
            :rooms="rooms"
            :categories="categories"
            :predefined-colors="predefinedColors"
          />
        </div>

        <!-- Footer - Fix unten -->
        <EventModalFooter
          class="flex-shrink-0"
          :mode="currentMode"
          :is-saving="isLoading"
          :is-todo="isTodo"
          :todo-data="eventData"
          @save="handleSave"
          @delete="handleDelete"
          @check-todo="handleCheckTodo"
        />
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch, onMounted, computed, onUnmounted } from "vue";
import EventModalHeader from "./EventModalHeader.vue";
import EventModalContent from "./EventModalContent.vue";
import EventModalAdvanced from "./EventModalAdvanced.vue";
import EventModalFooter from "./EventModalFooter.vue";
import TodoModalContent from "./TodoModalContent.vue";
import useEventPopover from "../../../composables/useEventPopover";
import useEventPosition from "../../../composables/useEventPosition";
import useEventUsers from "../../../composables/useEventUsers";
import useGhostEvent from "../../../composables/useGhostEvent";
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
  position: {
    type: Object,
    required: true,
  },
  initialPosition: {
    type: Object,
    required: true,
  },
  calendarWrapperRef: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["close", "save", "delete", "ghost-update"]);

// Local state for mode management
const currentMode = ref(props.mode);

// Check if the event is a todo
const isTodo = computed(() => {
  return props.event.id && props.event.id.toString().startsWith('todo-');
});

// Watch for external mode changes
watch(
  () => props.mode,
  (newMode) => {
    currentMode.value = newMode;
  },
);

const { modalPosition, isPositioned, calculatePosition } = useEventPosition();

// Set initial position right after composable initialization
modalPosition.value = props.initialPosition;

const {
  eventData,
  showAdvancedSettings,
  categories,
  rooms,
  isLoading,
  predefinedColors,
  initializeEventData,
  fetchRequiredData,
  validateEventData,
  prepareEventForSave,
  resetPopover,
} = useEventPopover({
  onGhostUpdate: (data) => emit("ghost-update", data),
  onClose: () => emit("close"),
});

const {
  searchQuery: userSearch,
  searchResults,
  isSearching,
  handleUserSearch,
  addUser,
  removeUser,
  initializeUsers,
  resetUsers,
} = useEventUsers({
  currentUserId: props.currentId,
  onUsersChange: (users) => (eventData.value.users = users),
});

const { deleteGhostEvent } = useGhostEvent(props.calendarWrapperRef);

// Initialize event data and users immediately
initializeEventData(props.event, props.mode);
if (!isTodo.value) {
  initializeUsers(props.event.users || []);
}

// For window size detection
const windowWidth = ref(window.innerWidth);

// Methods
const handleClose = () => {
  resetPopover();
  if (!isTodo.value) {
    resetUsers();
  }
  deleteGhostEvent();
  emit("close");
};

const handleModeChange = (newMode) => {
  currentMode.value = newMode;
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

  const errors = validateEventData();
  if (errors.length > 0) {
    console.error("Validation errors:", errors);
    return;
  }

  const eventToSave = prepareEventForSave();
  handleClose();
  emit("save", eventToSave);
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
    const todoId = eventData.value.id.toString().replace('todo-', '');
    await axios.patch(`/api/todo/${todoId}/check`, {}, {
      headers: {
        'Content-Type': 'application/json'
      }
    });
    
    handleClose();
  } catch (error) {
    console.error("Error checking todo:", error);
  }
};

const handleUserSearchUpdate = (query) => {
  userSearch.value = query;
  handleUserSearch({ target: { value: query } });
};

const handleAddUser = (user) => {
  addUser(user);
};

const handleRemoveUser = (user) => {
  removeUser(user);
};

// Keyboard event handler
const handleKeydown = (event) => {
  if (event.key === "Escape") {
    handleClose();
  }
  if (event.key === "Enter" && event.ctrlKey) {
    handleSave();
  }
};

// Window resize handler
const handleResize = () => {
  windowWidth.value = window.innerWidth;
  if (windowWidth.value >= 640) {
    calculatePosition(props.position);
  }
};

onMounted(() => {
  // Add event listeners
  document.addEventListener("keydown", handleKeydown);
  window.addEventListener("resize", handleResize);

  // Calculate position
  if (windowWidth.value >= 640) {
    calculatePosition(props.position);
  }

  // Fetch required data if not a todo
  if (!isTodo.value) {
    fetchRequiredData();
  }
});

onUnmounted(() => {
  document.removeEventListener("keydown", handleKeydown);
  window.removeEventListener("resize", handleResize);
});
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

/* Fullscreen animation for mobile */
@media (max-width: 639px) {
  .modal-enter-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
  }
  
  .modal-enter-from {
    opacity: 0;
    transform: translateY(50px);
  }
}
</style>
