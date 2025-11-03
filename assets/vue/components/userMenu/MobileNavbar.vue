<script setup>
import { ref, computed } from 'vue';
import { defineProps } from 'vue';

// Define props
const props = defineProps({
  role: {
    type: String,
    required: true,
  },
});

// Reactive variable to toggle navbar visibility
const showNavbar = ref(false);

// Function to open/close the navbar
const openNavbar = () => {
  showNavbar.value = !showNavbar.value;
};

// Define allowed pages for each role
const allowedPages = {
  ROLE_MANAGEMENT: ['Dashboard', 'Kalender', 'Todo', 'Lernmaterialien', 'Marketing', 'Benutzerliste', 'Benachrichtigungen', 'Tipps', 'Chat'],
  ROLE_LOCATION_MANAGEMENT: ['Dashboard', 'Kalender', 'Todo', 'Lernmaterialien', 'Marketing', 'Benutzerliste', 'Benachrichtigungen', 'Tipps', 'Chat'],
  ROLE_OFFICE: ['Dashboard', 'Kalender', 'Todo', 'Lernmaterialien', 'Marketing', 'Benutzerliste', 'Benachrichtigungen', 'Tipps', 'Chat'],
  ROLE_TEACHER: ['Dashboard', 'Kalender', 'Todo', 'Lernmaterialien', 'Benutzerliste', 'Benachrichtigungen', 'Tipps', 'Chat'],
  ROLE_STUDENT: ['Dashboard', 'Kalender', 'Lernmaterialien', 'Benachrichtigungen', 'Tipps', 'Chat'],
  ROLE_GUARDIAN: ['Dashboard', 'Kalender', 'Lernmaterialien', 'Benachrichtigungen', 'Tipps', 'Chat'],
  ROLE_MARKETING: ['Dashboard', 'Kalender', 'Todo', 'Lernmaterialien', 'Marketing', 'Benutzerliste', 'Benachrichtigungen', 'Tipps', 'Chat'],
};

// Define all pages with their icons
const pages = [
  { name: 'Dashboard', icon: 'dashboard' },
  { name: 'Kalender', icon: 'calendar' },
  { name: 'Todo', icon: 'list' },
  { name: 'Benutzerliste', icon: 'users' },
  { name: 'Benachrichtigungen', icon: 'bell' },
  { name: 'Chat', icon: 'circle' },
  { name: 'Tipps', icon: 'help' },
  { name: 'Lernmaterialien', icon: 'folder' },
  { name: 'Marketing', icon: 'folder' },
];

// Filter pages based on the role
const filteredPages = computed(() => {
  const allowed = allowedPages[props.role] || [];
  return pages.filter(page => page.name === 'divider' || allowed.includes(page.name));
});

// Get the SVG path for the icon
const getIconPath = (iconName) => {
  return `../../../images/icons/${iconName}.svg`;
};

// go to the page when clicked
const changePage = (page) => {
  if (page === 'Logout') {
    window.location.href = '/logout';
    return;
  }

  if (props.role === 'ROLE_TEACHER' && page === 'benutzerliste') {
    page = 'benutzerliste/schueler';
  }
  window.location.href = `/${page}`;
};

</script>

<template>
  <!-- Menu button -->
  <div @click="openNavbar" class="bg-window_bg px-4 py-2 rounded-full shadow-window">
    <div class="flex flex-row gap-2 items-center cursor-pointer">
      <!-- Menu icon -->
      <svg
          class="transition-transform duration-100 w-6 h-6"
          :class="(showNavbar ? 'z-50 stroke-white rotate-90' : 'stroke-icon_color')"
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
      >
        <path v-if="!showNavbar" d="M3 12H21" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path v-if="!showNavbar" d="M3 6H21" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path v-if="!showNavbar" d="M3 18H21" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path v-if="showNavbar" d="M18 6L6 18" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path v-if="showNavbar" d="M6 6L18 18" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
  </div>

  <!-- Full-screen navbar -->
  <transition name="navbar">
    <div @click="openNavbar" v-if="showNavbar" class="fixed z-40 inset-0 bg-second_accent bg-opacity-90 flex flex-col justify-start items-end text-white">
      <nav class="flex flex-col items-end gap-6 text-2xl mr-8 mt-18">
        <div @click="changePage(page.name.toLowerCase())" v-for="page in filteredPages" :key="page.name" class="flex items-center gap-2 group transition">
          <p class="group-hover:text-main ">{{ page.name }}</p>
          <div v-if="page.icon" class="w-8 h-8 bg-white group-hover:bg-main" :style="{ mask: `url(${getIconPath(page.icon)}) no-repeat center`, 'mask-size': 'contain'}"></div>
        </div>
      </nav>
    </div>
  </transition>
</template>

<style scoped>
.navbar-enter-active, .navbar-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.navbar-enter {
  opacity: 0;
  transform: translateY(-100%);
}
.navbar-leave-to {
  opacity: 0;
}
</style>