<script setup>
import {ref, computed, watch} from 'vue'
import ViewGrouped from "../components/todo/ViewGrouped.vue";
import ViewList from "../components/todo/ViewList.vue";
import AddTaskModal from "../components/todo/AddTaskModal.vue";
import { debounce } from 'lodash';

// get 'todos' from props
const props = defineProps({
  todos: {
    type: Array,
    required: true
  }
})

const activeView = ref('heute');
const searchQuery = ref('');
const isDropdownOpen = ref(false);
const isOpen = ref(false);
const isLoading = ref(false);
const todos = ref(props.todos);
const pendingView = ref(null);
const loadingTodos = ref([]);




/********** FETCH TODOs **********/
// Map view names to API endpoints
const viewToEndpoint = {
  'heute': 'today',
  'demnächst': 'upcoming',
  'eingang': 'inbox',
  'erledigt': 'completed',
  'suche': 'search'
};

// Fetch todos based on active view
const fetchTodos = async (view = activeView.value) => {
  try {
    const endpoint = viewToEndpoint[view];
    let url = `/api/todos/${endpoint}`;

    if (view === 'suche' && searchQuery.value) {
      url += `?query=${encodeURIComponent(searchQuery.value)}`;
    }

    const response = await fetch(url);
    if (response.ok) {
      const data = await response.json();
      todos.value = data.map(todo => ({
        ...todo,
        dueDate: todo.dueDate ? { date: todo.dueDate } : null
      }));

      // log the todos
      //console.log(url)
      //console.log(todos.value)
    }
  } catch (error) {
    console.error('Error fetching todos:', error);
  }
};

// Watch for search query changes
watch(searchQuery, debounce(() => {
  if (activeView.value === 'suche') {
    fetchTodos();
  }
}, 300));

const updateSearchQuery = (query) => {
  searchQuery.value = query;
};
/********** FETCH TODOs **********/



/********* TODO LISTS *********/
// Computed properties for the filtered todos
const filteredTodos = computed(() => todos.value);

// Computed property for grouped todos
const groupedTodos = computed(() => {
  if (activeView.value !== 'demnächst') return null;

  const grouped = { 'Kein Datum': [] };
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  todos.value.forEach(todo => {
    if (!todo.dueDate || !todo.dueDate.date) {
      grouped['Kein Datum'].push(todo);
    } else {
      try {
        // Parse the date string into a Date object
        const dateStr = todo.dueDate.date;

        // Create date object and set to start of day in local timezone
        const date = new Date(dateStr);

        // Skip invalid dates
        if (isNaN(date.getTime())) {
          grouped['Kein Datum'].push(todo);
          return;
        }

        // Format date to YYYY-MM-DD to eliminate time portion
        const dateKey = date.toISOString().split('T')[0];

        // Create a date object for comparison with today
        const compareDate = new Date(dateKey);

        // Skip if date is today or before
        if (compareDate <= today) {
          return;
        }

        // Create the group if it doesn't exist
        if (!grouped[dateKey]) {
          grouped[dateKey] = [];
        }

        // Add the todo to its date group
        grouped[dateKey].push(todo);
      } catch (error) {
        console.error('Error parsing date:', todo.dueDate, error);
        grouped['Kein Datum'].push(todo);
      }
    }
  });

  // Sort the groups by date
  return Object.entries(grouped)
      .sort(([dateA], [dateB]) => {
        if (dateA === 'Kein Datum') return -1;
        if (dateB === 'Kein Datum') return 1;
        return new Date(dateA) - new Date(dateB);
      })
      .map(([dueDate, todos]) => ({
        dueDate,
        formattedDate: dueDate === 'Kein Datum'
            ? 'Kein Datum'
            : new Date(dueDate).toLocaleDateString('de-DE', {
              weekday: 'long',
              year: 'numeric',
              month: 'long',
              day: 'numeric'
            }),
        todos
      }));
});
/********* TODO LISTS *********/



/********* COUNTS *********/
const activeTodosCount = computed(() => {
  if (activeView.value === 'demnächst') {
    return groupedTodos.value ? groupedTodos.value.reduce((count, group) => count + group.todos.length, 0) : 0;
  }
  return filteredTodos.value.length;
});
/********* COUNTS *********/




/********* EMITS *********/
const toggleTodoStatus = async (todo) => {
  // If todo is already loading, return
  if (loadingTodos.value.includes(todo.id)) {
    return;
  }

  // Add todo to loading state
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
      // If we're in a view where completed items should be removed
      if (activeView.value !== 'erledigt') {
        setTimeout(() => {
          todos.value = todos.value.filter(t => t.id !== todo.id);
        }, 400); // Match this with the transition duration
      }
    } else {
      console.error('Error toggling todo status:', response.statusText);
    }
  } catch (error) {
    console.error('Error toggling todo status:', error);
  } finally {
    // Remove todo from loading state
    loadingTodos.value = loadingTodos.value.filter(id => id !== todo.id);
  }
};

const handleAddTodo = async (newTodo) => {
  console.log(newTodo)
  try {
    const response = await fetch('/api/todo', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(newTodo)
    });
    if (response.ok) {
      const data = await response.json();
      console.log(data)
      // Get current user from API
      const meResponse = await fetch('/api/user/me');
      if (!meResponse.ok) throw new Error('Failed to fetch current user');
      const currentUser = await meResponse.json();

      // Add recentlyAdded flag
      data.recentlyAdded = true;

      // Check if current user is in userTodos
      const isUserAssigned = data.userTodos.some(ut => ut.user.id === currentUser.id);

      // Only add to list if current user is assigned
      if (isUserAssigned) {
        todos.value.push({
          ...data,
          dueDate: data.dueDate ? { date: data.dueDate } : null
        });
      }
    } else {
      console.error('Error adding todo:', response.statusText);
    }
  } catch (error) {
    console.error('Error adding todo:', error);
  }
}

const handleUpdateTodo = async (updatedTodo) => {
  //console.log(updatedTodo);

  try {
    // Format the todo for the API
    const todoForApi = {
      ...updatedTodo,
      // Ensure the date is in the correct format for the API
      dueDate: updatedTodo.dueDate?.date ?
          new Date(updatedTodo.dueDate.date).toISOString() :
          updatedTodo.dueDate
    };

    const response = await fetch(`/api/todo/${updatedTodo.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(todoForApi)
    });

    if (response.ok) {
      const data = await response.json();
      const index = todos.value.findIndex(todo => todo.id === updatedTodo.id);
      if (index !== -1) {
        // Format the response data to match your app's structure
        todos.value[index] = {
          ...data,
          dueDate: data.dueDate ? { date: data.dueDate } : null
        };
      }
    } else {
      console.error('Error updating todo:', response.statusText);
    }
  } catch (error) {
    console.error('Error updating todo:', error);
  }
};

const handleDeleteTodo = async (todo) => {
  try {
    const response = await fetch(`/api/todo/${todo.id}`, {
      method: 'DELETE'
    });
    if (response.ok) {
      const index = todos.value.findIndex(t => t.id === todo.id);
      if (index !== -1) {
        todos.value.splice(index, 1);
      }
    } else {
      console.error('Error deleting todo:', response.statusText);
    }
  } catch (error) {
    console.error('Error deleting todo:', error);
  }
}
/********* EMITS *********/


/********* CHANGE VIEW *********/
const aufgabenRef = ref(null);

const changeView = async (newView) => {
  // if the view is already loading, don't do anything
  if (isLoading.value) return

  // if the view is already active, don't do anything
  if (activeView.value === newView) return

  pendingView.value = newView // Store the requested view
  isLoading.value = true // Start loading state

  if (aufgabenRef.value) {
    aufgabenRef.value.scrollTo({ top: 0 });
  }

  try {
    await fetchTodos(newView) // Pass the new view to fetchTodos
    activeView.value = newView // Only change the view after successful fetch
  } catch (error) {
    console.error('Error changing view:', error)
  } finally {
    isLoading.value = false
    pendingView.value = null
  }
}
/********* CHANGE VIEW *********/

</script>

<template>
  <!-- Ansichten, Linke Spalte -->
  <div class="absolute bg-window_bg rounded-window shadow-lg md:w-[300px] w-full md:h-[100%] h-[56px] left-0 top-0 p-2">
  <!-- Desktop Header -->
    <div class="md:block hidden space-y-2">
      <!-- Add Todo Button -->
      <button
          @click="isOpen = true"
          class="flex flex-row gap-2 items-center w-full text-left px-4 py-2 rounded-window transition-colors cursor-pointer select-none hover:bg-gray-100 group/add"
      >
        <div class="flex items-center gap-4">
          <svg class="w-6 h-6 stroke-second_accent group-hover/add:hidden block" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 5V19" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M5 12H19" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg class="w-6 h-6 stroke-second_accent group-hover/add:block hidden" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 8V16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8 12H16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <p class="text-sm text-second_accent">Aufgabe hinzufügen</p>
        </div>
      </button>

      <!-- Kategorien -->
      <button
          @click="changeView('heute')"
          :class="[
          'flex flex-row gap-2 items-center w-full text-left px-4 py-2 rounded-window transition-colors',
          activeView === 'heute'
            ? 'bg-second_accent text-white stroke-white'
            : 'hover:bg-gray-100 stroke-icon_color'
        ]"
      >
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M12 6V12L16 14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Heute</span>
      </button>
      <button
          @click="changeView('demnächst')"
          :class="[
          'flex flex-row gap-2 items-center w-full text-left px-4 py-2 rounded-window transition-colors',
          activeView === 'demnächst'
            ? 'bg-second_accent text-white stroke-white'
            : 'hover:bg-gray-100 stroke-icon_color'
        ]"
      >
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M19 4H5C3.89543 4 3 4.89543 3 6V20C3 21.1046 3.89543 22 5 22H19C20.1046 22 21 21.1046 21 20V6C21 4.89543 20.1046 4 19 4Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M16 2V6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M8 2V6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M3 10H21" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Demnächst</span>
      </button>
      <button
          @click="changeView('eingang')"
          :class="[
          'flex flex-row gap-2 items-center w-full text-left px-4 py-2 rounded-window transition-colors',
          activeView === 'eingang'
            ? 'bg-second_accent text-white stroke-white'
            : 'hover:bg-gray-100 stroke-icon_color'
        ]"
      >
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M22 12H16L14 15H10L8 12H2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M5.45 5.11L2 12V18C2 18.5304 2.21071 19.0391 2.58579 19.4142C2.96086 19.7893 3.46957 20 4 20H20C20.5304 20 21.0391 19.7893 21.4142 19.4142C21.7893 19.0391 22 18.5304 22 18V12L18.55 5.11C18.3844 4.77679 18.1292 4.49637 17.813 4.30028C17.4967 4.10419 17.1321 4.0002 16.76 4H7.24C6.86792 4.0002 6.50326 4.10419 6.18704 4.30028C5.87083 4.49637 5.61558 4.77679 5.45 5.11V5.11Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Eingang</span>
      </button>
      <button
          @click="changeView('suche')"
          :class="[
          'flex flex-row gap-2 items-center w-full text-left px-4 py-2 rounded-window transition-colors',
          activeView === 'suche'
            ? 'bg-second_accent text-white stroke-white'
            : 'hover:bg-gray-100 stroke-icon_color'
        ]"
      >
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M21.0004 21L16.6504 16.65" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Suche</span>
      </button>
      <button
          @click="changeView('erledigt')"
          :class="[
            'flex flex-row gap-2 items-center w-full text-left px-4 py-2 rounded-window transition-colors',
            activeView === 'erledigt'
              ? 'bg-second_accent text-white stroke-white'
              : 'hover:bg-gray-100 stroke-icon_color'
          ]"
      >
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M20 6L9 17L4 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Erledigt</span>
      </button>
    </div>

    <!-- Mobile Header -->
    <div class="block md:hidden space-y-2">
      <button
          @click="isDropdownOpen = !isDropdownOpen"
          class="flex flex-row gap-2 items-center w-full text-left px-4 py-2 rounded-window transition-colors bg-second_accent text-white stroke-white"
      >
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M12 6V12L16 14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="capitalize">{{ activeView }}</span>
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M6 9L12 15L18 9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
      <div v-if="isDropdownOpen" class="space-y-2 z-30 relative top-4 bg-window_bg shadow-2xl rounded-window p-2">
        <button
            @click="changeView('heute'); isDropdownOpen = false"
            :class="[
          'flex felx-row gap-2 items-center w-full text-left px-4 py-2 rounded-window transition-colors',
          activeView === 'heute'
            ? 'bg-second_accent text-white stroke-white'
            : 'hover:bg-gray-100 stroke-icon_color'
        ]"
        >
          <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 6V12L16 14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Heute</span>
        </button>
        <button
            @click="changeView('demnächst'); isDropdownOpen = false"
            :class="[
          'flex felx-row gap-2 items-center w-full text-left px-4 py-2 rounded-window transition-colors',
          activeView === 'demnächst'
            ? 'bg-second_accent text-white stroke-white'
            : 'hover:bg-gray-100 stroke-icon_color'
        ]"
        >
          <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19 4H5C3.89543 4 3 4.89543 3 6V20C3 21.1046 3.89543 22 5 22H19C20.1046 22 21 21.1046 21 20V6C21 4.89543 20.1046 4 19 4Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M16 2V6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8 2V6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M3 10H21" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Demnächst</span>
        </button>
        <button
            @click="changeView('eingang'); isDropdownOpen = false"
            :class="[
          'flex felx-row gap-2 items-center w-full text-left px-4 py-2 rounded-window transition-colors',
          activeView === 'eingang'
            ? 'bg-second_accent text-white stroke-white'
            : 'hover:bg-gray-100 stroke-icon_color'
        ]"
        >
          <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M22 12H16L14 15H10L8 12H2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M5.45 5.11L2 12V18C2 18.5304 2.21071 19.0391 2.58579 19.4142C2.96086 19.7893 3.46957 20 4 20H20C20.5304 20 21.0391 19.7893 21.4142 19.4142C21.7893 19.0391 22 18.5304 22 18V12L18.55 5.11C18.3844 4.77679 18.1292 4.49637 17.813 4.30028C17.4967 4.10419 17.1321 4.0002 16.76 4H7.24C6.86792 4.0002 6.50326 4.10419 6.18704 4.30028C5.87083 4.49637 5.61558 4.77679 5.45 5.11V5.11Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Eingang</span>
        </button>
        <button
            @click="changeView('suche'); isDropdownOpen = false"
            :class="[
          'flex felx-row gap-2 items-center w-full text-left px-4 py-2 rounded-window transition-colors',
          activeView === 'suche'
            ? 'bg-second_accent text-white stroke-white'
            : 'hover:bg-gray-100 stroke-icon_color'
        ]"
        >
          <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M21.0004 21L16.6504 16.65" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Suche</span>
        </button>
        <button
            @click="changeView('erledigt'); isDropdownOpen = false"
            :class="[
              'flex felx-row gap-2 items-center w-full text-left px-4 py-2 rounded-window transition-colors',
              activeView === 'erledigt'
                ? 'bg-second_accent text-white stroke-white'
                : 'hover:bg-gray-100 stroke-icon_color'
            ]"
        >
          <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 6L9 17L4 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Erledigt</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Aufgaben -->
  <div
      ref="aufgabenRef"
      class="absolute bg-window_bg rounded-window shadow-lg md:w-[calc(100%-300px-16px)] w-full md:h-[100%] h-[calc(100%-56px-16px)] right-0 md:top-0 top-[calc(56px+8px)] p-4 overflow-x-hidden overflow-y-auto"
      :class="isOpen ? 'overflow-y-hidden' : 'overflow-y-auto'"
  >
    <!-- gap on both sides -->
    <div class="xl:mx-[10%] mx-auto ">
      <!-- Titel und Anzahl der Todos -->
      <h2 class="text-2xl font-bold mb-2">
        {{ activeView.charAt(0).toUpperCase() + activeView.slice(1) }}
      </h2>
      <p class="flex flex-row items-center text-sm text-gray-400 stroke-gray-400 mb-6">
        <svg class="w-4 h-4 mr-1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M22 11.08V12C21.9988 14.1564 21.3005 16.2547 20.0093 17.9818C18.7182 19.709 16.9033 20.9725 14.8354 21.5839C12.7674 22.1953 10.5573 22.1219 8.53447 21.3746C6.51168 20.6273 4.78465 19.2461 3.61096 17.4371C2.43727 15.628 1.87979 13.4881 2.02168 11.3363C2.16356 9.18455 2.99721 7.13631 4.39828 5.49706C5.79935 3.85781 7.69279 2.71537 9.79619 2.24013C11.8996 1.7649 14.1003 1.98232 16.07 2.85999" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M22 4L12 14.01L9 11.01" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>{{ activeTodosCount }} Aufgaben</span>
      </p>
      <AddTaskModal
        v-if="isOpen"
        @add="handleAddTodo"
        @close="isOpen = false"
        />

        <ViewGrouped
            v-if="activeView === 'demnächst' && groupedTodos"
            :groupedTodos="groupedTodos"
            :activeView="activeView"
            :loadingTodos="loadingTodos"
            @toggleTodoStatus="toggleTodoStatus"
            @handleAddTodo="handleAddTodo"
            @handleUpdateTodo="handleUpdateTodo"
            @handleDeleteTodo="handleDeleteTodo"
        />

        <ViewList
            v-else
            :filteredTodos="filteredTodos"
            :activeView="activeView"
            :loadingTodos="loadingTodos"
            @toggleTodoStatus="toggleTodoStatus"
            @handleAddTodo="handleAddTodo"
            @handleUpdateTodo="handleUpdateTodo"
            @handleDeleteTodo="handleDeleteTodo"
            @updateSearchQuery="updateSearchQuery"
        />
    </div>

    <div v-if="isLoading" class="absolute inset-0 backdrop-blur z-50 flex items-center justify-center">
      <div class="bg-white p-4 rounded-lg shadow-lg flex items-center space-x-2">
        <svg class="animate-spin h-5 w-5 text-second_accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-gray-700">Lade...</span>
      </div>
    </div>
  </div>
</template>