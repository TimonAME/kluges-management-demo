<script setup>
import {ref, computed, onMounted, watch} from 'vue';

const props = defineProps({
  user_id: {
    type: Number,
    required: true
  }
});

const todos = ref([]);
const loading = ref(true);
const loadingTodos = ref([]);
const activeView = ref('anstehend'); // 'anstehend' or 'erledigt'

onMounted(async () => {
  await fetchTodos();
});

const fetchTodos = async () => {
  try {
    const category = activeView.value === 'erledigt' ? 'done' : 'undone';
    const response = await fetch(`/api/user/${props.user_id}/todos?category=${category}`);
    const data = await response.json();

    // Map the todos directly from the array response
    todos.value = data.map(todo => ({
      id: todo.id,
      title: todo.title,
      description: todo.description,
      isCompleted: todo.userTodos[0]?.isChecked || false,
      dueDate: todo.dueDate // if this exists in your response
    }));
  } catch (error) {
    console.error('Error fetching todos:', error);
  } finally {
    loading.value = false;
  }
};

// Watch for activeView changes to refetch todos
watch(activeView, async () => {
  loading.value = true;
  await fetchTodos();
});

// Date formatting functions
const normalizeDate = (dateInput) => {
  if (!dateInput) return null;
  const dateStr = typeof dateInput === 'object' && dateInput.date ? dateInput.date : dateInput;
  const date = new Date(dateStr);
  date.setHours(0, 0, 0, 0);
  return date;
};

const formatDueDate = (dateInput) => {
  const dateStr = typeof dateInput === 'object' && dateInput.date ? dateInput.date : dateInput;
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

const getDateStatusClass = (dateInput) => {
  if (!dateInput) return '';
  const formattedDate = formatDueDate(dateInput);

  if (formattedDate.includes('Heute')) return 'text-green-500 stroke-green-600';
  if (formattedDate.includes('Morgen')) return 'text-blue-500 stroke-blue-500';
  if (/^[A-Za-z]/.test(formattedDate)) return 'text-yellow-500 stroke-yellow-500';
  if (isPast(dateInput)) return 'text-red-500 stroke-red-500';
  return 'text-primary_text stroke-primary_text';
};
</script>

<template>
  <div class="h-full p-2">
    <!-- Category Buttons -->
    <div class="flex space-x-2 mb-4">
      <button
          @click="activeView = 'anstehend'"
          class="px-4 py-2 rounded-lg transition-colors"
          :class="activeView === 'anstehend' ? 'bg-second_accent text-white' : 'hover:bg-gray-100 text-gray-700'"
      >
        Anstehend
      </button>
      <button
          @click="activeView = 'erledigt'"
          class="px-4 py-2 rounded-lg transition-colors"
          :class="activeView === 'erledigt' ? 'bg-second_accent text-white' : 'hover:bg-gray-100 text-gray-700'"
      >
        Erledigt
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-4">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-second_accent mx-auto"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="todos.length === 0" class="text-center text-gray-500 py-4">
      Keine TODOs verfügbar
    </div>

    <!-- Todos List -->
    <TransitionGroup v-else name="list">
      <div
          v-for="todo in todos"
          :key="todo.id"
          class="p-4 border-b border-gray-200"
          :class="loadingTodos.includes(todo.id) ? 'opacity-50' : ''"
      >
        <div class="flex items-center gap-4 group/item">
          <div class="flex-1">
            <h3 class="font-medium break-all">{{ todo.title }}</h3>
            <p class="text-sm text-secondary_text break-all">{{ todo.description }}</p>
            <div class="flex flex-row items-center gap-4">
              <div v-if="todo.dueDate">
                <p
                    class="text-sm flex flex-row gap-1 items-center"
                    :class="getDateStatusClass(todo.dueDate)"
                >
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z"
                        stroke-width="1"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M12 6V12L16 14"
                        stroke-width="1"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                  </svg>
                  {{ formatDueDate(todo.dueDate) }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </TransitionGroup>
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

.opacity-50 {
  opacity: 0.5;
  pointer-events: none;
}
</style>