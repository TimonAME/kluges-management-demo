<script setup>
import { computed, onMounted, ref } from 'vue';

const todos = ref([]);
const loadingTodos = ref([]);

const props = defineProps({
  todos: Object,
});

// Update the computed properties
const displayedTodos = computed(() => {
  // Check if window width is less than 768px
  const isMobile = window.innerWidth < 768;
  const MAX_TODOS = 4; // Set max todos for mobile

  // Only slice the todos if on mobile
  return isMobile ? todos.value.slice(0, MAX_TODOS) : todos.value;
});

const hasMoreTodos = computed(() => {
  // Check if window width is less than 768px
  const isMobile = window.innerWidth < 768;
  const MAX_TODOS = 4; // Set max todos for mobile

  // Only show "more todos" button if on mobile and exceeding limit
  return isMobile && todos.value.length > MAX_TODOS;
});

onMounted(() => {
  todos.value = props.todos;
  //console.log('TODOs:', todos.value);
});

const toggleCheck = async (todo) => {
  loadingTodos.value.push(todo.id);
  try {
    const response = await fetch(`/api/todo/${todo.id}/check`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ todo })
    });
    if (response.ok) {
      todos.value = todos.value.filter(t => t.id !== todo.id);
      loadingTodos.value = loadingTodos.value.filter((loadingId) => loadingId !== todo.id);
    } else {
      console.error('Error toggling todo status:', response.statusText);
    }
  } catch (error) {
    console.error('Error toggling todo status:', error);
  }
}

const goToTODO = () => {
  window.location.href = '/todo';
};

/******** Date Display ********/
const normalizeDate = (dateInput) => {
  if (!dateInput) return null;

  // Handle object with date property
  const dateStr = typeof dateInput === 'object' && dateInput.date
      ? dateInput.date
      : dateInput;

  const date = new Date(dateStr);
  date.setHours(0, 0, 0, 0);
  return date;
};

const formatDueDate = (dateInput) => {
  const dateStr = typeof dateInput === 'object' && dateInput.date
      ? dateInput.date
      : dateInput;

  const date = new Date(dateStr);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  date.setHours(0, 0, 0, 0);

  const diffTime = date.getTime() - today.getTime();
  const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));

  const timeStr = date.getHours() !== 0 || date.getMinutes() !== 0
      ? `, ${date.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' })}`
      : '';

  if (diffDays === 0) return `Heute${timeStr}`;
  if (diffDays === 1) return `Morgen${timeStr}`;
  if (diffDays > 1 && diffDays <= 7) {
    return `${date.toLocaleDateString('de-DE', { weekday: 'long' })}${timeStr}`;
  }

  return `${date.toLocaleDateString('de-DE', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })}${timeStr}`;
};

const isPast = (dateInput) => {
  if (!dateInput) return false;

  const normalizedDate = normalizeDate(dateInput);
  const today = normalizeDate(new Date());

  return normalizedDate < today;
};

// Helper function to get date status class
const getDateStatusClass = (dateInput) => {
  if (!dateInput) return '';

  const formattedDate = formatDueDate(dateInput);

  if (formattedDate.includes('Heute')) return 'text-green-500 stroke-green-600';
  if (formattedDate.includes('Morgen')) return 'text-blue-500 stroke-blue-500';
  if (/^[A-Za-z]/.test(formattedDate)) return 'text-yellow-500 stroke-yellow-500';
  if (isPast(dateInput)) return 'text-red-500 stroke-red-500';

  return 'text-primary_text stroke-primary_text';
};
/******** Date Display ********/
</script>

<template>
  <div
      class="sm:h-[100%] max-h-96 lg:max-h-none w-full bg-window_bg rounded-window shadow-window overflow-hidden flex flex-col">
    <!-- Header -->
    <div class="px-4 pt-2 border-b border-gray-100">
      <div class="flex items-center justify-between">
        <h2 class="text-primary_text text-base font-semibold">Todos</h2>
        <div class="flex items-center gap-2">
          <!-- Navigation Icon -->
          <button @click="goToTODO"
                  aria-label="Zur TODO Liste navigieren"
                  class="p-1.5 rounded-window hover:bg-gray-100 transition-colors">
            <svg class="w-5 h-5 stroke-secondary_text" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M7 17L17 7M7 7H17V17" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Todo List -->
    <div class="flex-grow overflow-y-auto">
      <div v-if="todos.length === 0" class="text-center text-secondary_text py-4">
        Keine Aufgaben für Heute.
      </div>

      <div v-else>
        <div v-for="todo in displayedTodos"
             :key="todo.id"
             @click="toggleCheck(todo)"
             class="px-4 py-2 select-none border-b border-gray-200 cursor-pointer group/item"
             :class="loadingTodos.includes(todo.id) ? 'animate-pulse opacity-50' : ''">
          <div class="flex items-center gap-4">
            <!-- Checkbox -->
            <svg class="w-5 h-5 transition-colors stroke-icon_color"
                 viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path class="group-hover/item:hidden block"
                    d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z"
                    stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
              <path class="hidden group-hover/item:block"
                    d="M22 11.08V12C21.9988 14.1564 21.3005 16.2547 20.0093 17.9818C18.7182 19.709 16.9033 20.9725 14.8354 21.5839C12.7674 22.1953 10.5573 22.1219 8.53447 21.3746C6.51168 20.6273 4.78465 19.2461 3.61096 17.4371C2.43727 15.628 1.87979 13.4881 2.02168 11.3363C2.16356 9.18455 2.99721 7.13631 4.39828 5.49706C5.79935 3.85781 7.69279 2.71537 9.79619 2.24013C11.8996 1.7649 14.1003 1.98232 16.07 2.85999"
                    stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
              <path class="stroke-icon_color hidden group-hover/item:block"
                    d="M22 4L12 14.01L9 11.01" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>

            <!-- Todo Content -->
            <div class="flex-1 break-all">
              <h3>{{ todo.title }}</h3>
              <p class="text-sm text-secondary_text break-all">{{ todo.description }}</p>

              <div class="flex flex-row items-center gap-4">
                <!-- Due Date -->
                <div v-if="todo.dueDate">
                  <p
                      class="text-sm flex flex-row gap-1 items-center"
                      :class="getDateStatusClass(todo.dueDate)"
                  >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M12 6V12L16 14" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    {{ formatDueDate(todo.dueDate) }}
                  </p>
                </div>
                <!-- Ersteller -->
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
          </div>
        </div>

        <!-- Show All button when there are more TODOs -->
        <div v-if="hasMoreTodos" class="text-center py-2">
          <button
              @click="goToTODO"
              class="text-sm text-primary hover:underline"
          >
            Alle Aufgaben anzeigen ({{ todos.length }})
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>