<template>
  <!-- Vorname -->
  <div>
    <label class="block text-secondary_text text-sm font-normal mb-2" for="vorname">Vorname</label>
    <input
        type="text"
        id="vorname"
        class="appearance-none border-b border-gray-300 w-full py-2 px-1 text-secondary_text leading-tight focus:outline-none focus:border-blue-500"
        placeholder="Vorname"
    />
  </div>
  <!-- Nachname -->
  <div>
    <label class="block text-secondary_text text-sm font-normal mb-2" for="nachname">Nachname</label>
    <input
        type="text"
        id="nachname"
        class="appearance-none border-b border-gray-300 w-full py-2 px-1 text-secondary_text leading-tight focus:outline-none focus:border-blue-500"
        placeholder="Nachname"
    />
  </div>
  <!-- Geburtsdatum -->
  <div>
    <label class="block text-secondary_text text-sm font-normal mb-2" for="geburtsdatum">Geburtsdatum</label>
    <input
        type="date"
        id="geburtsdatum"
        class="appearance-none border-b border-gray-300 w-full py-2 px-1 text-secondary_text leading-tight focus:outline-none focus:border-blue-500"
    />
  </div>
  <!-- Adresse -->
  <div>
    <label class="block text-secondary_text text-sm font-normal mb-2" for="adresse">Adresse</label>
    <input
        type="text"
        id="adresse"
        class="appearance-none border-b border-gray-300 w-full py-2 px-1 text-secondary_text leading-tight focus:outline-none focus:border-blue-500"
        placeholder="Adresse"
    />
  </div>
  <div class="col-span-2 mt-5">
    <div class="text-md">Kontaktdaten des Schülers</div>
  </div>
  <!-- Handynummer des Schülers -->
  <div>
    <label class="block text-secondary_text text-sm font-normal mb-2" for="schuelerHandynummer">Handynummer des Schülers</label>
    <input
        type="tel"
        id="schuelerHandynummer"
        class="appearance-none border-b border-gray-300 w-full py-2 px-1 text-secondary_text leading-tight focus:outline-none focus:border-blue-500"
        placeholder="Handynummer des Schülers"
    />
  </div>
  <!-- Email des Schülers -->
  <div>
    <label class="block text-secondary_text text-sm font-normal mb-2" for="schuelerEmail">E-Mail des Schülers</label>
    <input
        type="email"
        id="schuelerEmail"
        class="appearance-none border-b border-gray-300 w-full py-2 px-1 text-secondary_text leading-tight focus:outline-none focus:border-blue-500"
        placeholder="E-Mail des Schülers"
    />
  </div>
  <div class="col-span-2 mt-5">
    <div class="text-md">Kontaktdaten des Anmelders</div>
  </div>
  <!-- Handynummer des Anmelders -->
  <div>
    <label class="block text-secondary_text text-sm font-normal mb-2" for="anmelderHandynummer">Handynummer des Anmelders</label>
    <input
        type="tel"
        id="anmelderHandynummer"
        class="appearance-none border-b border-gray-300 w-full py-2 px-1 text-secondary_text leading-tight focus:outline-none focus:border-blue-500"
        placeholder="Handynummer des Anmelders"
    />
  </div>
  <!-- Email des Anmelders -->
  <div>
    <label class="block text-secondary_text text-sm font-normal mb-2" for="anmelderEmail">E-Mail des Anmelders</label>
    <input
        type="email"
        id="anmelderEmail"
        class="appearance-none border-b border-gray-300 w-full py-2 px-1 text-secondary_text leading-tight focus:outline-none focus:border-blue-500"
        placeholder="E-Mail des Anmelders"
    />
  </div>
  <!-- Schule -->
  <div class="mt-5">
    <label class="block text-secondary_text text-sm font-normal mb-2" for="schule">Schule</label>
    <input
        type="text"
        id="schule"
        class="appearance-none border-b border-gray-300 w-full py-2 px-1 text-secondary_text leading-tight focus:outline-none focus:border-blue-500"
        placeholder="Schule"
    />
  </div>
  <!-- Klasse -->
  <div class="mt-5">
    <label class="block text-secondary_text text-sm font-normal mb-2" for="klasse">Klasse</label>
    <input
        type="text"
        id="klasse"
        class="appearance-none border-b border-gray-300 w-full py-2 px-1 text-secondary_text leading-tight focus:outline-none focus:border-blue-500"
        placeholder="Klasse"
    />
  </div>
</template>

<script setup>

</script>

<style lang="scss" scoped>

</style>