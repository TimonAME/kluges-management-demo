<template>
  <div class="w-full md:w-1/2 h-full bg-white md:rounded-l-window rounded-window flex flex-col">
        <div class="flex justify-between items-center">
            <!-- Icon -->
            <div class="ml-8 mt-8">
              <img
                  src="../../../../../images/icon.svg"
                  alt="Logo"
                  class="w-12"
              />
            </div>
            <!-- Zurück-Pfeil -->
            <!-- data-turbo="false" verhindert das Prefetchen der Seite und sorgt, dafür dass keine
      Fehler mehr auftreten wenn man das fetching unterbricht (symfony/ux-turbo) -->
            <a
                href="/login"
                class="group mr-8 mt-8 flex flex-col justify-center items-center z-50 hover:cursor-pointer select-none"
            >
                <div
                    class="text-3xl text-main group-hover:text-first_accent"
                >
                    ←
                </div>
                <div
                    class="text-xs text-main group-hover:text-first_accent"
                >
                    Zurück
                </div>
            </a>
        </div>
        <div class="flex flex-col items-center justify-center flex-grow -mt-16">
            <form class="w-3/4">
                <!-- Überschrift -->
                <h1 class="text-2xl mb-6 text-center">Passwort vergessen</h1>
                <!-- Info-Text -->
                <p class="text-sm text-secondary_text mb-4 text-center">
                    Bitte gib unten deine E-Mail-Adresse ein und wir senden dir
                    einen Link mit weiteren Anweisungen.
                </p>
                <!-- Email-Feld -->
                <div class="mb-4">
                    <label
                        for="email"
                        class="block text-secondary_text text-sm font-normal mb-2"
                        >E-Mail</label
                    >
                    <input
                        type="email"
                        id="email"
                        placeholder="E-Mail-Adresse"
                        class="appearance-none border-b border-gray-300 w-full py-2 px-1 text-secondary_text leading-tight focus:outline-none focus:border-blue-500"
                        required
                    />
                </div>
                <!-- Senden Button -->
                <div class="flex flex-col items-center">
                    <button
                        type="submit"
                        class="w-full bg-main hover:bg-second_accent text-white font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-gray-800 focus:ring-offset-2 mb-4"
                    >
                        Senden
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
<script setup></script>

<style scoped></style>
