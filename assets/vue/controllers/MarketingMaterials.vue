<script setup>
</script>

<template>
  <div class="absolute inset-0 p-4 bg-window_bg rounded-window shadow-window">
    <div class="h-full flex flex-col items-center justify-center gap-6">
      <div class="text-center max-w-2xl">
        <h2 class="text-2xl font-semibold text-primary_text mb-4">Marketingmaterialien Verwaltung</h2>
        <p class="text-secondary_text mb-8">Hier können Sie auf die zentrale Datenablage für Marketingmaterialien zugreifen und diese verwalten.</p>
      </div>

      <button
          class="flex flex-row gap-2 items-center px-6 py-3 bg-second_accent text-white stroke-white hover:shadow-lg rounded-window transition-all"
      >
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M22 19C22 19.5304 21.7893 20.0391 21.4142 20.4142C21.0391 20.7893 20.5304 21 20 21H4C3.46957 21 2.96086 20.7893 2.58579 20.4142C2.21071 20.0391 2 19.5304 2 19V5C2 4.46957 2.21071 3.96086 2.58579 3.58579C2.96086 3.21071 3.46957 3 4 3H9L11 6H20C20.5304 6 21.0391 6.21071 21.4142 6.58579C21.7893 6.96086 22 7.46957 22 8V19Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Datenablage öffnen</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
</style>
