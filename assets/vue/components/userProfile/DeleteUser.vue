<script setup>
import { ref, onMounted } from 'vue';
import ToastContainer from '../calendar/ToastContainer.vue';

const props = defineProps({
  user_id: {
    type: Number,
    required: false
  }
});

const isDeleting = ref(false);
const error = ref(null);
const success = ref(false);
const toastContainerRef = ref(null);

const handleDelete = async () => {
  isDeleting.value = true;
  error.value = null;

  try {
    const response = await fetch(`/user/sendDelete/${props.user_id}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      // If you need CSRF protection:
      // credentials: 'same-origin'
    });

    if (!response.ok) {
      throw new Error(`Error: ${response.status}`);
    }

    success.value = true;
    
    // Zeige Toast-Benachrichtigung an
    toastContainerRef.value.addToast({
      message: 'Eine E-Mail zur Bestätigung wurde gesendet.',
      type: 'success',
      timeout: 5000,
    });
    
    // Redirect after successful deletion
    setTimeout(() => {
      window.location.href = '/benutzerliste';
    }, 3000);
  } catch (err) {
    error.value = err.message || 'Failed to delete user';
    
    // Zeige Fehler-Toast an
    toastContainerRef.value.addToast({
      message: `Fehler: ${error.value}`,
      type: 'error',
      timeout: 5000,
    });
  } finally {
    isDeleting.value = false;
  }
};
</script>

<template>
  <div class="w-full h-full flex flex-col items-center justify-center p-6">
    <h1 class="text-2xl font-bold text-error_text mb-4">Account Löschen Beantragen</h1>
    <p class="text-lg text-error_text mb-2 text-center">Sind Sie sicher, dass dieser Account gelöscht werden soll? Diese Aktion kann nicht rückgängig gemacht werden!</p>
    <p class="text-lg text-error_text mb-6 text-center">Alle Personen bezogenen Daten werden mit dieser Aktion gelöscht.</p>
    
    <div v-if="success" class="bg-green-100 text-green-800 p-3 rounded mb-4">
      Löschantrag erfolgreich gesendet. Weiterleitung...
    </div>

    <div v-if="error" class="bg-red-100 text-red-800 p-3 rounded mb-4">
      {{ error }}
    </div>

    <button
      class="bg-error_text text-white py-2 px-4 rounded hover:bg-red-700 disabled:opacity-50"
      @click="handleDelete"
      :disabled="isDeleting"
    >
      {{ isDeleting ? 'Löschen...' : 'Löschen' }}
    </button>
    
    <!-- Toast Container für Benachrichtigungen -->
    <ToastContainer ref="toastContainerRef" />
  </div>
</template>