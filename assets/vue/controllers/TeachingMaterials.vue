<script setup>
// get subjects from props
/*
import {onMounted, ref} from "vue";

const props = defineProps({
  subjects: Array,
});

const subjects = ref([]);

// onMounted
onMounted(() => {
  subjects.value = props.subjects;
  console.log('Subjects:', subjects.value);
});
 */
</script>

<template>
  <div class="absolute inset-0 p-4 bg-window_bg rounded-window shadow-window">
    <div class="h-full flex flex-col items-center justify-center gap-6">
      <div class="text-center max-w-2xl">
        <h2 class="text-2xl font-semibold text-primary_text mb-4">Lernmaterialien Verwaltung</h2>
        <p class="text-secondary_text mb-8">Hier können Sie auf die zentrale Datenablage für Lernmaterialien zugreifen und diese verwalten.</p>
      </div>

      <button
          class="flex flex-row gap-2 items-center px-6 py-3 bg-second_accent text-white stroke-white hover:shadow-lg rounded-window transition-all"
      >
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M2 3H8C9.06087 3 10.0783 3.42143 10.8284 4.17157C11.5786 4.92172 12 5.93913 12 7V21C12 20.2044 11.6839 19.4413 11.1213 18.8787C10.5587 18.3161 9.79565 18 9 18H2V3Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M22 3H16C14.9391 3 13.9217 3.42143 13.1716 4.17157C12.4214 4.92172 12 5.93913 12 7V21C12 20.2044 12.3161 19.4413 12.8787 18.8787C13.4413 18.3161 14.2044 18 15 18H22V3Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Datenablage öffnen</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
</style>