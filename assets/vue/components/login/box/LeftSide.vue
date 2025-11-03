<template>
  <div class="w-full h-full max-w-md bg-white rounded-l-lg flex flex-col">
    <!-- Icon -->
    <div class="w-full flex justify-start ml-8 mt-8">
      <img src="../../../../images/icon.svg" alt="Logo" class="w-12">
    </div>
    <div class="flex flex-col items-center justify-center flex-grow -mt-16">
      <form class="w-3/4">
        <!-- Überschrift -->
        <h1 class="text-2xl mb-6 text-center">Login</h1>
        <!-- Email-Feld -->
        <div class="mb-4">
          <label for="email" class="block text-secondary_text text-sm font-normal mb-2">E-Mail</label>
          <input type="email" id="email" placeholder="E-Mail-Adresse"
                 class="appearance-none border-b border-gray-300 w-full py-2 px-1 text-secondary_text leading-tight focus:outline-none focus:border-blue-500"
                 required>
        </div>
        <!-- Passwort-Feld -->
        <div class="mb-6">
          <label for="password" class="block text-secondary_text text-sm font-normal mb-2">Passwort</label>
          <input type="password" id="password" placeholder="Passwort"
                 class="appearance-none border-b border-gray-300 w-full py-2 px-1 text-secondary_text leading-tight focus:outline-none focus:border-blue-500"
                 required>
        </div>
        <!-- Login Button -->
        <div class="flex flex-col items-center">
          <button type="submit"
                  class="w-full bg-main hover:bg-second_accent text-white font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-gray-800 focus:ring-offset-2 mb-4">
            Login
          </button>
          <!-- "Passwort vergessen?"-Feld -->
          <!-- data-turbo="false" verhindert das Prefetchen der Seite und sorgt, dafür dass keine Fehler
           mehr auftreten wenn man das fetching unterbricht (symfony/ux-turbo) -->
          <a href="/login/forgotPassword" class="text-sm text-main hover:underline">Passwort vergessen?</a>
        </div>
      </form>
    </div>
  </div>
</template>
<script setup>
</script>

<style scoped>
</style>