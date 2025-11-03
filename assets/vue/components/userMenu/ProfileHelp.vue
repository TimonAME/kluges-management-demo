<script setup>
import {ref, onMounted, onBeforeUnmount, defineProps} from 'vue';

const showDropdown = ref(false);

const props = defineProps({
  userID: {
    type: Number,
    required: true,
  },
});

const categories = [
  { name: 'Nutzungsbedingungen und Richtlinien', url: 'https://www.klugesmanagement.at/datenschutz/' },
  { name: 'Hilfe', url: '/tipps' },
  { name: 'Feedback senden', url: '/feedback' },
];

const goToCategory = (url) => {
  switch (url) {
    case 'https://www.klugesmanagement.at/datenschutz/':
      window.open(url, '_blank');
      break;
    case '/tipps':
      window.location.href = url;
      break;
    case '/feedback':
      window.location.href = url;
      break;
    default:
        console.log('Invalid URL');
  }
};

const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value;
};

const handleClickOutside = (event) => {
  if (!event.target.closest('.dropdown-container')) {
    showDropdown.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
});

const goToUserPage = () => {
  window.location.href = '/user/' + props.userID;
};
</script>

<template>
  <div class="relative dropdown-container">
    <div
        class="bg-window_bg px-4 py-2 rounded-[1.3rem] cursor-pointer shadow-window"

        :class="(showDropdown ? 'rounded-b-none' : 'rounded-[1.3rem]')"
    >
      <div v-if="showDropdown" class="shadow-cover bg-window_bg z-40"></div>

      <div class="flex flex-row gap-4 items-center">
        <svg @click="toggleDropdown" class="stroke-icon_color hover:stroke-main transition-colors duration-100" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M9.08984 8.99959C9.32495 8.33126 9.789 7.7677 10.3998 7.40873C11.0106 7.04975 11.7287 6.91853 12.427 7.0383C13.1253 7.15808 13.7587 7.52112 14.2149 8.06312C14.6712 8.60512 14.9209 9.29112 14.9198 9.99959C14.9198 11.9996 11.9198 12.9996 11.9198 12.9996" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M12 17H12.01" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <svg @click="goToUserPage" class="stroke-icon_color hover:stroke-main transition-colors duration-100" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
    </div>

    <div v-if="showDropdown" class="z-20 absolute top-full sm:right-0 right-[-64px] mt-0 w-80 bg-window_bg rounded-[1.3rem] sm:rounded-tr-none rounded-tr-[1.3rem] shadow-window">
      <ul>
        <li v-for="category in categories" :key="category.name" @click="goToCategory(category.url)" class="cursor-pointer px-4 py-2 hover:bg-first_accent hover:text-white rounded-3xl transition-colors duration-100">
          <span class="block">{{ category.name }}</span>
        </li>
      </ul>
    </div>
  </div>
</template>

<style scoped>
.shadow-cover {
  position: absolute;
  top: 32px;
  left: 0;
  width: 100%;
  height: 8px;
}
</style>