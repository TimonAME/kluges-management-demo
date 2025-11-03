<script setup>
import {ref} from "vue";
import AddTaskForm from "./AddTaskForm.vue";

const props = defineProps({
  groupedTodos: {
    type: Array,
    required: true
  },
  activeView: {
    type: String,
    required: true
  },
  loadingTodos: {
    type: Array,
    required: true
  }
})

const editingTodo = ref(null);
const isOpen = ref(false);
const activeFormDate = ref(null);

/******** Emit Functions ********/
const emits = defineEmits(['toggleTodoStatus', 'handleAddTodo', 'handleUpdateTodo', "handleDeleteTodo"]);

const toggleTodoStatus = (todo) => {
  emits('toggleTodoStatus', todo);
};

const handleAddTodo = (newTodo) => {
  emits('handleAddTodo', newTodo);
};

const handleUpdateTodo = (updatedTodo) => {
  emits('handleUpdateTodo', updatedTodo);
  editingTodo.value = null;
};

const handleDeleteTodo = (todo) => {
  emits('handleDeleteTodo', todo);
};
/******** Emit Functions ********/



/******** Open / Close Forms ********/
const openEditForm = (todo) => {
  editingTodo.value = todo;
};

const handleCloseEditForm = () => {
  editingTodo.value = null;
};

const openTaskForm = (date) => {
  activeFormDate.value = date;
  isOpen.value = true;
};

const handleCloseForm = () => {
  isOpen.value = false;
  activeFormDate.value = null;
};
/******** Open / Close Forms ********/



/******** Delete Todo confirmation ********/
const confirmDelete = ref(null)

const deleteConfirmation = (accept, todo) => {
  if (accept) {
    handleDeleteTodo(todo)
  }
  confirmDelete.value = null
}
/******** Delete Todo confirmation ********/


/******** Format Time ********/
// method to get the time from dueDate
const formatDueTime = (date) => {
  const dateObj = new Date(date);
  if (dateObj.getHours() === 0 && dateObj.getMinutes() === 0) {
    return '';
  }
  return dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};
/******** Format Time ********/
</script>

<template>
  <div v-for="group in groupedTodos" :key="group.dueDate" class="mb-10">
    <h3 class="text-lg font-semibold text-primary_text">{{ group.formattedDate }}</h3>
      <TransitionGroup name="list">
        <div
            v-for="todo in group.todos"
            :key="todo.id"
            class="p-4 border-b border-gray-200"
            :class="[
                      loadingTodos.includes(todo.id) ? 'opacity-50' : '',
                      activeView !== 'erledigt' && todo.isCompleted ? 'list-leave-active' : ''
                    ]"
        >
        <div class="flex items-center gap-4 group/item">
          <svg
              @click="toggleTodoStatus(todo)"
              class="w-5 h-5 group/svg transition-colors"
              :class="activeView === 'erledigt' ? 'stroke-success_text' : 'stroke-icon_color'"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
          >
            <path :class="activeView === 'erledigt' ? 'hidden' : 'group-hover/svg:hidden block'" d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
            <path :class="activeView === 'erledigt' ? 'block' : 'group-hover/svg:block hidden'" d="M22 11.08V12C21.9988 14.1564 21.3005 16.2547 20.0093 17.9818C18.7182 19.709 16.9033 20.9725 14.8354 21.5839C12.7674 22.1953 10.5573 22.1219 8.53447 21.3746C6.51168 20.6273 4.78465 19.2461 3.61096 17.4371C2.43727 15.628 1.87979 13.4881 2.02168 11.3363C2.16356 9.18455 2.99721 7.13631 4.39828 5.49706C5.79935 3.85781 7.69279 2.71537 9.79619 2.24013C11.8996 1.7649 14.1003 1.98232 16.07 2.85999" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
            <path :class="activeView === 'erledigt' ? 'block' : 'stroke-icon_color hidden group-hover/svg:block'" d="M22 4L12 14.01L9 11.01" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>

          <div class="flex-1">
            <h3 class="font-medium break-all">
              {{ todo.title }}
            </h3>
            <p class="text-sm text-secondary_text break-all">{{ todo.description }}</p>
            <div class="flex flex-row items-center gap-4">
              <p v-if="todo.dueDate" class="text-sm text-secondary_text flex flex-row gap-1 items-center">
                <svg v-if="formatDueTime(todo.dueDate.date) !== null" class="w-4 h-4 stroke-icon_color" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M12 6V12L16 14" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>

                {{ formatDueTime(todo.dueDate.date) }}
              </p>
              <!-- Creator -->
              <div class="text-xs text-secondary_text flex-row gap-1 items-center group-hover/item:flex hidden">
                <a v-if="todo.creator_id" :href="`/user/${todo.creator_id}`" class="text-second_accent">{{ todo.first_name + ' ' + todo.last_name }}</a>
                <a v-else :href="`/user/${todo.creator.id}`" class="text-second_accent">{{ todo.creator.first_name + ' ' + todo.creator.last_name }}</a>

                <svg class="w-3 h-3 stroke-second_accent" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M9 10L4 15L9 20" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M20 4V11C20 12.0609 19.5786 13.0783 18.8284 13.8284C18.0783 14.5786 17.0609 15 16 15H4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
            </div>
          </div>

          <!-- Settings -->
          <div class="flex flex-row gap-2" v-if="!todo.recentlyAdded">
            <svg @click="openEditForm(todo)" class="w-6 h-6 stroke-icon_color invisible group-hover/item:visible hover:stroke-main" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 20H21" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M16.5 3.49998C16.8978 3.10216 17.4374 2.87866 18 2.87866C18.2786 2.87866 18.5544 2.93353 18.8118 3.04014C19.0692 3.14674 19.303 3.303 19.5 3.49998C19.697 3.69697 19.8532 3.93082 19.9598 4.18819C20.0665 4.44556 20.1213 4.72141 20.1213 4.99998C20.1213 5.27856 20.0665 5.55441 19.9598 5.81178C19.8532 6.06915 19.697 6.303 19.5 6.49998L7 19L3 20L4 16L16.5 3.49998Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>

            <div class="relative invisible group-hover/item:visible">
              <svg @click="confirmDelete = todo.id" class="w-6 h-6 stroke-icon_color hover:stroke-error_text transition-colors" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 6H5H21" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M10 11V17" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M14 11V17" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>

              <div v-if="confirmDelete === todo.id" class="flex flex-row gap-2 absolute right-8 top-0">
                <div @click="deleteConfirmation(true, todo)" class="p-2 bg-window_bg rounded-window shadow-window hover:bg-green-200 transition-colors cursor-pointer">
                  <svg class="w-4 h-4 stroke-success_text" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 6L9 17L4 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>
                <div @click="deleteConfirmation(false, null)" class="p-2 bg-window_bg rounded-window shadow-window hover:bg-red-200 transition-colors cursor-pointer">
                  <svg class="w-4 h-4 stroke-error_text" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M6 6L18 18" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- Edit Task Form -->
        <AddTaskForm
            class="mt-2"
            v-if="editingTodo && editingTodo === todo"
            :is-edit="true"
            :todo-to-edit="editingTodo"
            @update="handleUpdateTodo"
            @close="handleCloseEditForm"
        />
      </div>
      </TransitionGroup>

    <!-- Add Task Form -->
    <div>
      <div
          class="p-4 cursor-pointer"
          @click="openTaskForm(group.dueDate)"
      >
        <div class="flex items-center gap-4 group/add">
          <svg class="w-6 h-6 stroke-second_accent group-hover/add:hidden block" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 5V19" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M5 12H19" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg class="w-6 h-6 stroke-second_accent group-hover/add:block hidden" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 8V16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8 12H16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <p class="text-sm text-secondary_text group-hover/add:text-second_accent">Aufgabe hinzufügen</p>
        </div>
      </div>

      <AddTaskForm
          v-if="isOpen && activeFormDate === group.dueDate"
          :is-edit="false"
          :date="group.dueDate !== 'Kein Datum' ? group.dueDate : null"
          @add="handleAddTodo"
          @close="handleCloseForm"
      />
    </div>
  </div>
</template>

<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.4s ease;
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

/* Add this new class */
.opacity-50 {
  opacity: 0.5;
  pointer-events: none;
}
</style>