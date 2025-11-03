<script setup>
import { ref, onMounted } from 'vue';
import ToastContainer from '../calendar/ToastContainer.vue';

const props = defineProps({
  user_id: {
    type: Number,
    required: true
  }
});

const roles = [
  { value: 'ROLE_STUDENT', label: 'Schüler' },
  { value: 'ROLE_GUARDIAN', label: 'Erziehungsberechtigter' },
  { value: 'ROLE_TEACHER', label: 'Lehrer' },
  { value: 'ROLE_OFFICE', label: 'Büro' },
  { value: 'ROLE_LOCATION_MANAGEMENT', label: 'Standort-Management' },
  { value: 'ROLE_MANAGEMENT', label: 'Management' },
  { value: 'ROLE_MARKETING', label: 'Marketing' }
];

const currentRole = ref('');
const loading = ref(true);
const updating = ref(false);
const error = ref(null);
const toastContainerRef = ref(null);

onMounted(async () => {
  await fetchUserRole();
});

const fetchUserRole = async () => {
  loading.value = true;
  error.value = null;
  
  try {
    const response = await fetch(`/user/role/${props.user_id}`);
    
    if (!response.ok) {
      throw new Error(`Error: ${response.status}`);
    }
    
    const data = await response.json();
    currentRole.value = data.role;
  } catch (err) {
    error.value = err.message || 'Fehler beim Laden der Benutzerrolle';
    toastContainerRef.value.addToast({
      message: `Fehler: ${error.value}`,
      type: 'error',
      timeout: 5000,
    });
  } finally {
    loading.value = false;
  }
};

const updateUserRole = async () => {
  updating.value = true;
  error.value = null;
  
  try {
    const response = await fetch(`/user/role/${props.user_id}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ role: currentRole.value })
    });
    
    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.error || `Error: ${response.status}`);
    }
    
    // Erfolgreiche Aktualisierung
    toastContainerRef.value.addToast({
      message: 'Benutzerrolle erfolgreich aktualisiert',
      type: 'success',
      timeout: 3000,
    });
  } catch (err) {
    error.value = err.message || 'Fehler beim Aktualisieren der Benutzerrolle';
    toastContainerRef.value.addToast({
      message: `Fehler: ${error.value}`,
      type: 'error',
      timeout: 5000,
    });
  } finally {
    updating.value = false;
  }
};
</script>

<template>
  <div class="w-full h-full p-6">
    <h1 class="text-2xl font-bold text-primary_text mb-6">Berechtigungen verwalten</h1>
    
    <!-- Loading -->
    <div v-if="loading" class="flex justify-center">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-second_accent"></div>
    </div>
    
    <!-- Error -->
    <div v-else-if="error" class="bg-red-100 text-red-800 p-4 rounded-lg mb-4">
      {{ error }}
    </div>
    
    <!-- Role Selection -->
    <div v-else class="max-w-xl">
      <div class="mb-6">
        <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
          Benutzerrolle
        </label>
        <select
          id="role"
          v-model="currentRole"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-second_accent focus:ring focus:ring-second_accent focus:ring-opacity-50"
        >
          <option v-for="role in roles" :key="role.value" :value="role.value">
            {{ role.label }}
          </option>
        </select>
      </div>
      
      <div class="mt-8">
        <button
          @click="updateUserRole"
          class="bg-second_accent hover:bg-opacity-90 text-white py-2 px-4 rounded-md shadow-sm disabled:opacity-50"
          :disabled="updating"
        >
          <span v-if="updating">Wird aktualisiert...</span>
          <span v-else>Rolle aktualisieren</span>
        </button>
      </div>
    </div>
    
    <!-- Toast Container für Benachrichtigungen -->
    <ToastContainer ref="toastContainerRef" />
  </div>
</template>

<style scoped>
select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 0.5rem center;
  background-repeat: no-repeat;
  background-size: 1.5em 1.5em;
  padding-right: 2.5rem;
}
</style>