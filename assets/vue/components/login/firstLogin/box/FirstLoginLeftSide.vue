<template>
  <div class="w-full md:w-1/2 h-full bg-white md:rounded-l-window rounded-window flex flex-col">
    <div class="flex justify-start items-center">
      <!-- Icon -->
      <div class="ml-8 mt-8">
        <img
            src="../../../../../images/icon.svg"
            alt="Logo"
            class="w-12"
        />
      </div>
      <a
          href="/login"
          class="group mr-8 mt-8 flex flex-col justify-center items-center z-50 hover:cursor-pointer select-none"
      >
      </a>
    </div>
    <div class="flex flex-col items-center justify-center flex-grow -mt-16">
      <form class="w-3/4" @submit.prevent="handleSubmit">
        <!-- Überschrift -->
        <h1 class="text-2xl mb-6 text-center">Passwort ändern</h1>
        <!-- Info-Text -->
        <p class="text-sm text-secondary_text mb-8 text-center">
          Bitte ändern das generierte Passwort welches Ihnen geschickt wurde. Beachten Sie, dass das Passwort mindestens 8 Zeichen lang sein muss.
        </p>
        <!-- Altes-Passwort-Feld -->
        <div class="mb-8">
          <label
              for="password-old"
              class="block text-secondary_text text-sm font-normal mb-2"
          >Altes Password</label
          >
          <input
              type="password"
              id="password-old"
              class="appearance-none border-b border-gray-300 w-full py-2 px-1 text-secondary_text leading-tight focus:outline-none focus:border-blue-500"
              required
          />
        </div>
        <!-- Passwort-Feld -->
        <div class="mb-2">
          <label
              for="password"
              class="block text-secondary_text text-sm font-normal mb-2"
          >Neues Password</label
          >
          <input
              type="password"
              id="password"
              class="appearance-none border-b border-gray-300 w-full py-2 px-1 text-secondary_text leading-tight focus:outline-none focus:border-blue-500"
              required
          />
        </div>
        <!-- Passwort-Bestätigen-Feld -->
        <div class="mb-4">
          <label
              for="password_confirm"
              class="block text-secondary_text text-sm font-normal mb-2"
          >Neues Password wiederholen</label
          >
          <input
              type="password"
              id="password_confirm"
              class="appearance-none border-b border-gray-300 w-full py-2 px-1 text-secondary_text leading-tight focus:outline-none focus:border-blue-500"
              required
          />
        </div>
        <!-- Senden Button -->
        <!--
        <div class="flex flex-col items-center">
          <button
              type="submit"
              class="w-full bg-main hover:bg-second_accent text-white font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-gray-800 focus:ring-offset-2 mb-4"
          >
            Aktualisieren
          </button>
        </div>
        -->
        <div v-if="error" class="text-red-500 text-sm mb-4 text-center">
          {{ error }}
        </div>
        <div class="flex flex-col items-center">
          <button
              type="submit"
              class="w-full bg-main hover:bg-second_accent text-white font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-gray-800 focus:ring-offset-2 mb-4"
              :disabled="loading"
          >
            {{ loading ? 'Updating...' : 'Aktualisieren' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue';

const loading = ref(false);
const error = ref('');

const handleSubmit = async (event) => {
  const oldPassword = document.getElementById('password-old').value;
  const newPassword = document.getElementById('password').value;
  const confirmPassword = document.getElementById('password_confirm').value;

  if (newPassword.length < 8) {
    error.value = 'Das neue Passwort muss mindestens 8 Zeichen lang sein';
    return;
  }

  if (newPassword !== confirmPassword) {
    error.value = 'Die Passwörter stimmen nicht überein';
    return;
  }

  loading.value = true;
  error.value = '';

  try {
    const response = await fetch('/api/first-login/change-password', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        oldPassword,
        newPassword,
        confirmPassword
      })
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.error || 'Ein Fehler ist aufgetreten');
    }

    window.location.href = '/login';
  } catch (err) {
    error.value = err.message;
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped></style>
