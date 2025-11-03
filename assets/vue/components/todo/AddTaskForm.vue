<script setup>
import {computed, onBeforeUnmount, onMounted, ref, watch} from "vue";

const props = defineProps({
  isEdit: {
    type: Boolean,
    default: false
  },
  todoToEdit: {
    type: Object,
    default: () => null
  },
  date: {
    type: String,
    default: null
  }
})

const emit = defineEmits(['add', 'update', 'close']);

// TODO: dont display time from prop date
const newTodo = ref(
    props.isEdit && props.todoToEdit
        ? {
          ...props.todoToEdit,
          dueDate: props.todoToEdit.dueDate?.date || null,
        }
        : {
          title: '',
          description: '',
          dueDate: props.date
              ? props.date.split('T')[0] + 'T00:00:00.000Z'
              : null,
          userTodos: []
        }
);

//console.log(newTodo.value);

/********** TIME AND DATE PICKER **********/
// Separate date and time refs
const selectedDate = ref('');
const selectedTime = ref('');

// Watch for changes in either date or time to update the dueDate
watch([selectedDate, selectedTime], ([newDate, newTime]) => {
  if (newDate) {
    // Set default time to 01:00 if no time is selected
    const timeToUse = newTime || '01:00';
    // Create date in local timezone to avoid date shift
    const dateObj = new Date(`${newDate}T${timeToUse}`);
    console.log(dateObj);
    console.log(newDate);
    newTodo.value.dueDate = dateObj.toISOString();
  } else {
    newTodo.value.dueDate = null;
  }
});

// Format functions
const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('de-DE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
};
const formatTime = (timeString) => {
  if (!timeString) return '';
  return timeString;
};

// Initialize date/time if dueDate exists
const initializeDateTimeFromDueDate = () => {
  if (newTodo.value.dueDate) {
    // Create date object in local timezone
    const date = new Date(newTodo.value.dueDate);

    // Format date to YYYY-MM-DD considering timezone offset
    selectedDate.value = date.toLocaleDateString('en-CA'); // Uses YYYY-MM-DD format

    // Extract hours and minutes, pad with leading zeros
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    selectedTime.value = `${hours}:${minutes}`;
  }
};

// Lifecycle hooks
onMounted(() => {
  initializeDateTimeFromDueDate();
});
/********** TIME AND DATE PICKER **********/




/********** USER SEARCH AND ASSIGNMENT **********/
const showUserDropdown = ref(false);
const searchQuery = ref('');
const users = ref([]);
const isLoading = ref(false);
const searchTimeout = ref(null);
const me = ref(null);
const isMeAssigned = ref(false);

// Updated computed property to handle new response format
const displayedUsers = computed(() => {
  return users.value.filter(user =>
      !newTodo.value.userTodos.some(todo => todo.user.id === user.id)
  );
});

// Updated format function to handle user object with subject information
const formatUserName = (user) => {
  return `${user.first_name} ${user.last_name}`;
};

// Updated fetch function to handle new response format
const fetchUsers = async () => {
  try {
    isLoading.value = true;
    const params = new URLSearchParams({
      query: searchQuery.value,
      limit: '10',
    });

    const response = await fetch(`/api/user/search/todo?${params}`);
    if (!response.ok) throw new Error('Failed to fetch users');

    const data = await response.json();
    // Update to handle the new response structure
    users.value = data || [];
  } catch (error) {
    console.error('Error fetching users:', error);
  } finally {
    isLoading.value = false;
  }
};

// Debounced search remains the same
watch(searchQuery, (newValue) => {
  if (searchTimeout.value) clearTimeout(searchTimeout.value);
  searchTimeout.value = setTimeout(() => {
    fetchUsers();
  }, 300);
});

// Updated user selection handler to include additional user data
const selectUser = async (user) => {
  if (user === 'me') {
    try {
      const response = await fetch('/api/user/me');
      if (!response.ok) throw new Error('Failed to fetch current user');

      const currentUser = await response.json();
      newTodo.value.userTodos.push({
        user: {
          id: currentUser.id,
          first_name: currentUser.first_name,
          last_name: currentUser.last_name,
          email: currentUser.email
        }
      });
      me.value = currentUser;
      isMeAssigned.value = true;
    } catch (error) {
      console.error('Error fetching current user:', error);
    }
    return;
  }

  newTodo.value.userTodos.push({
    user: {
      id: user.id,
      first_name: user.first_name,
      last_name: user.last_name,
      email: user.email
    }
  });
  showUserDropdown.value = false;
};

const removeUser = (userId) => {
  if (me.value && me.value.id === userId) {
    isMeAssigned.value = false;
  }

  newTodo.value.userTodos = newTodo.value.userTodos.filter(
      todo => todo.user.id !== userId
  );
};

// Click outside handling remains the same
const userDropdownRef = ref(null);

const handleClickOutside = (event) => {
  if (userDropdownRef.value && !userDropdownRef.value.contains(event.target)) {
    showUserDropdown.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  fetchUsers();
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
  if (searchTimeout.value) clearTimeout(searchTimeout.value);
});
/********** USER SEARCH AND ASSIGNMENT **********/





/********** EMITS SUBMISSION **********/
const handleSubmit = async () => {
  if (props.isEdit) {
    emit('update', { ...newTodo.value });
  } else {
    emit('add', { ...newTodo.value });
  }

  newTodo.value = {
    title: '',
    description: '',
    dueDate: null,
    userTodos: []
  };

  emit('close');
};

const closeForm = () => {
  emit('close');

  newTodo.value = {
    title: '',
    description: '',
    dueDate: null,
    userTodos: []
  };

  showUserDropdown.value = false;
  searchQuery.value = '';
};
/********** FORM SUBMISSION **********/



/********** WATCH FOR EDIT **********/
// Watch for changes in todoToEdit prop
watch(() => props.todoToEdit, (newValue) => {
  if (newValue && props.isEdit) {
    newTodo.value = {
      ...newValue,
      dueDate: newValue.dueDate?.date || null,
    };

    if (newTodo.value.dueDate) {
      const date = new Date(newTodo.value.dueDate);
      selectedDate.value = date.toISOString().split('T')[0];
      selectedTime.value = date.toTimeString().slice(0, 5);
    }
  }
}, { immediate: true });
/********** WATCH FOR EDIT **********/
</script>

<template>
  <form @submit.prevent="handleSubmit" class="border border-gray-300 p-2 rounded-window">
    <div>
      <input
          v-model="newTodo.title"
          type="text"
          ref="titleInput"
          class="w-full font-semibold bg-window_bg border-0 border-b border-b-gray-300 focus:border-b-gray-300 focus:ring-0"
          placeholder="Titel"
          required
      />
    </div>
    <div>
            <textarea
                v-model="newTodo.description"
                rows="2"
                class="w-full bg-window_bg border-0 border-b border-b-gray-300 focus:border-b-gray-300 focus:ring-0"
                placeholder="Beschreibung"
            ></textarea>
    </div>

    <!-- Selected Users Display -->
    <div class="flex flex-wrap gap-2 mb-2">
      <div
          v-for="userTodo in newTodo.userTodos"
          :key="userTodo.user.id"
          class="flex items-center gap-2 px-4 py-1 rounded-full text-sm bg-blue-100"
      >
        <span>{{ formatUserName(userTodo.user) }}</span>
        <button
            type="button"
            @click="removeUser(userTodo.user.id)"
            class="hover:text-red-500 text-xl leading-none"
        >
          ×
        </button>
      </div>
    </div>

    <div class="flex flex-row flex-wrap gap-2">
      <!-- Date picker -->
      <div class="relative w-fit">
        <div
            class="flex gap-1 items-center w-fit p-2 border rounded-window"
            :class="selectedDate ? 'border-second_accent hover:border-main stroke-second_accent text-second_accent' : 'border-gray-300 hover:border-gray-400 stroke-secondary_text text-secondary_text'"
            @click="$refs.datePicker.showPicker()"
        >
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19 4H5C3.89543 4 3 4.89543 3 6V20C3 21.1046 3.89543 22 5 22H19C20.1046 22 21 21.1046 21 20V6C21 4.89543 20.1046 4 19 4Z" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M16 2V6" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8 2V6" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M3 10H21" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span class="text-sm">
            {{ selectedDate ? formatDate(selectedDate) : 'Fälligkeitsdatum' }}
          </span>
          <input
              ref="datePicker"
              v-model="selectedDate"
              type="date"
              class="absolute inset-0 opacity-0 cursor-pointer"
          />
        </div>
      </div>

      <!-- Time picker -->
      <div class="relative w-fit">
        <div
            class="flex gap-1 items-center w-fit p-2 border rounded-window"
            :class="[selectedTime ? 'border-second_accent hover:border-main stroke-second_accent text-second_accent' : 'border-gray-300 hover:border-gray-400 stroke-secondary_text text-secondary_text', selectedDate ? '' : 'opacity-50 cursor-not-allowed']"
            @click="selectedDate && $refs.timePicker.showPicker()"
        >
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 8V12L14 14" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2Z" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span class="text-sm">{{ selectedTime ? formatTime(selectedTime) : 'Uhrzeit' }}</span>
          <input
              ref="timePicker"
              v-model="selectedTime"
              type="time"
              class="absolute inset-0 opacity-0 cursor-pointer"
              :disabled="!selectedDate"
          />
        </div>
      </div>

      <!-- Assigne Todo to me -->
      <div class="relative" v-if="!props.isEdit">
        <button
            @click="selectUser('me')"
            type="button"
            class="flex gap-1 items-center w-fit p-2 border rounded-window border-gray-300 hover:border-gray-400"
            :class="[
                newTodo.userTodos.length === 0 ? 'border-error_text hover:border-error_text animate-pulse' : '',
                isMeAssigned ? 'opacity-50 cursor-not-allowed' : ''
                ]"
            :disabled="isMeAssigned"
        >
          <svg
              class="w-4 h-4 stroke-secondary_text"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
          >
            <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9 11C11.2091 11 13 9.20914 13 7C13 4.79086 11.2091 3 9 3C6.79086 3 5 4.79086 5 7C5 9.20914 6.79086 11 9 11Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span class="text-sm text-secondary_text">Mir zuweisen</span>
        </button>
      </div>

      <!-- User Selection System disabled on Edit-->
      <div class="relative" ref="userDropdownRef" v-if="!props.isEdit">
        <button
            type="button"
            @click="showUserDropdown = !showUserDropdown"
            class="flex gap-1 items-center w-fit p-2 border rounded-window"
            :class="[
          showUserDropdown ? 'border-second_accent text-second_accent' : 'border-gray-300 hover:border-gray-400',
          newTodo.userTodos.length === 0 ? 'border-error_text hover:border-error_text animate-pulse' : ''
        ]"
        >
          <svg
              class="w-4 h-4"
              :class="showUserDropdown ? 'stroke-second_accent' : 'stroke-secondary_text'"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
          >
            <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9 11C11.2091 11 13 9.20914 13 7C13 4.79086 11.2091 3 9 3C6.79086 3 5 4.79086 5 7C5 9.20914 6.79086 11 9 11Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>

          <span class="text-sm" :class="showUserDropdown ? 'text-second_accent' : 'text-secondary_text'">
          Person zuweisen
        </span>
        </button>

        <!-- User Search Dropdown -->
        <div
            v-if="showUserDropdown"
            class="absolute left-0 mt-2 p-2 bg-white border rounded-window shadow-lg z-10 w-[300px]"
        >
          <!-- Search Input -->
          <div class="flex flex-row gap-2 items-center mb-2">
            <input
                v-model="searchQuery"
                type="text"
                class="w-full p-2 border rounded-window"
                placeholder="Nach Person suchen..."
            />
            <button
                type="button"
                @click="showUserDropdown = false"
                class="flex-shrink-0"
            >
              <svg
                  class="w-6 h-6 stroke-secondary_text hover:stroke-error_text"
                  viewBox="0 0 24 24"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
              >
                <path d="M18 6L6 18" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M6 6L18 18" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>

          <!-- Updated Users List -->
          <div class="max-h-48 overflow-y-auto">
            <div v-if="isLoading" class="text-center py-2 text-gray-500">
              Laden...
            </div>

            <div v-else>
              <button
                  v-for="user in displayedUsers"
                  :key="user.id"
                  type="button"
                  @click="selectUser(user)"
                  class="w-full text-left px-3 py-2 hover:bg-gray-100 rounded-window flex flex-col"
              >
                <div class="font-medium flex items-center gap-2">
                  <span>{{ user.first_name }} {{ user.last_name }}</span>
                </div>
                <span class="text-sm text-gray-500">{{ user.email }}</span>
              </button>

              <div
                  v-if="displayedUsers.length === 0"
                  class="text-gray-500 text-sm p-2 text-center"
              >
                Keine Personen gefunden
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>




    <!-- Buttons -->
    <div class="flex justify-end gap-2">
      <button
          type="button"
          @click="closeForm"
          class="mt-2 px-6 py-1.5 rounded-window border-icon_color border font-semibold text-primary_text bg-window_bg shadow-none hover:shadow-lg transition-all duration-200"
      >
        Abbrechen
      </button>
      <button
          v-if="isEdit"
          type="submit"
          class="mt-2 px-6 py-1.5 rounded-window font-semibold text-white bg-second_accent shadow-none hover:shadow-lg transition-all duration-200"
          :disabled="!newTodo.title"
          :class="[
            (!newTodo.title) ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'
          ]"
      >
        Update
      </button>
      <button
          v-if="!isEdit"
          type="submit"
          class="mt-2 px-6 py-1.5 rounded-window font-semibold text-white bg-second_accent shadow-none hover:shadow-lg transition-all duration-200"
          :disabled="!newTodo.title || newTodo.userTodos.length === 0"
          :class="[
            (!newTodo.title || newTodo.userTodos.length === 0) ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'
          ]"
      >
        Hinzufügen
      </button>
    </div>
  </form>
</template>

<style scoped>
</style>