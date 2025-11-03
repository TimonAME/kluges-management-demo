<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import NavItem from "../components/navbar/NavItem.vue";
import { defineProps, computed } from 'vue';

const props = defineProps({
  role: {
    type: String,
    required: true,
  },
});

const role = props.role

const activePage = ref(window.location.pathname.split('/')[1]);
const confirmLogout = ref(false);
const logoutButtonRef = ref(null);

// Array of all Rolles with their allowed pages
// Roles: ROLE_MANAGEMENT, ROLE_LOCATION_MANAGEMENT, ROLE_OFFICE, ROLE_TEACHER, ROLE_STUDENT, ROLE_GUARDIAN, ROLE_MARKETING
const allowedPages = {
  ROLE_MANAGEMENT: ['Dashboard', 'Kalender', 'Todo', 'Lernmaterialien', 'Marketing', 'Benutzerliste', 'Benachrichtigungen', 'Tipps', 'Chat'],
  ROLE_LOCATION_MANAGEMENT: ['Dashboard', 'Kalender', 'Todo', 'Lernmaterialien', 'Marketing', 'Benutzerliste', 'Benachrichtigungen', 'Tipps', 'Chat'],
  ROLE_OFFICE: ['Dashboard', 'Kalender', 'Todo', 'Lernmaterialien', 'Marketing', 'Benutzerliste', 'Benachrichtigungen', 'Tipps', 'Chat'],
  ROLE_TEACHER: ['Dashboard', 'Kalender', 'Todo', 'Lernmaterialien', 'Benutzerliste', 'Benachrichtigungen', 'Tipps', 'Chat'],
  ROLE_STUDENT: ['Dashboard', 'Kalender', 'Lernmaterialien', 'Benachrichtigungen', 'Tipps', 'Chat'],
  ROLE_GUARDIAN: ['Dashboard', 'Kalender', 'Lernmaterialien', 'Benachrichtigungen', 'Tipps', 'Chat'],
  ROLE_MARKETING: ['Dashboard', 'Kalender', 'Todo', 'Lernmaterialien', 'Marketing', 'Benutzerliste', 'Benachrichtigungen', 'Tipps', 'Chat'],
};

// all Pages + their Icons
const pages = [
  { name: 'Dashboard', icon: 'dashboard' },
  { name: 'Kalender', icon: 'calendar' },
  { name: 'Todo', icon: 'list' },
  { name: 'divider', icon: ''},
  { name: 'Benutzerliste', icon: 'users' },
  { name: 'Benachrichtigungen', icon: 'bell' },
  { name: 'Chat', icon: 'circle' },
  { name: 'Tipps', icon: 'help' },
  { name: 'Lernmaterialien', icon: 'bookopen' },
  { name: 'Marketing', icon: 'folder' },
];

// Filter pages based on the role
const filteredPages = computed(() => {
  const allowed = allowedPages[role] || [];
  return pages.filter(page => page.name === 'divider' || allowed.includes(page.name));
});

// Expand/collapse state
const isExpanded = ref(localStorage.getItem('isExpanded') === 'true');

const toggleNavbar = () => {
  isExpanded.value = !isExpanded.value;
  localStorage.setItem('isExpanded', isExpanded.value);
};

// change page function
const changePage = (page) => {
  window.location.href = `/${page.toLowerCase()}`;
};



/************ LOGOUT ************/
//logout function
const logout = () => {
  if (confirmLogout.value) {
    window.location.href = '/logout';
  } else {
    confirmLogout.value = true;
  }
};

// Handle click outside logout button
const handleClickOutside = (event) => {
  if (logoutButtonRef.value && !logoutButtonRef.value.contains(event.target)) {
    confirmLogout.value = false;
  }
};
/************ LOGOUT ************/



/************ NAV SHADOW ************/
const navContainer = ref(null);
const hasTopShadow = ref(false);
const hasBottomShadow = ref(false);

// Add this function to check scroll position and update shadows
const updateScrollShadows = () => {
  //console.log('updateScrollShadows');
  if (!navContainer.value) return;

  const { scrollTop, scrollHeight, clientHeight } = navContainer.value;

  // Show top shadow if we've scrolled down
  hasTopShadow.value = scrollTop > 0;

  // Show bottom shadow if we can scroll further down
  hasBottomShadow.value = scrollTop + clientHeight < scrollHeight -1;
};
/************ NAV SHADOW ************/


/************ MOUNTED ************/
onMounted(() => {
  // Handle click outside
  document.addEventListener('click', handleClickOutside);

  // Handle scroll shadows
  if (navContainer.value) {
    navContainer.value.addEventListener('scroll', updateScrollShadows);
    // Initial check for shadows
    updateScrollShadows();
  }
});

onUnmounted(() => {
  // Remove click outside listener
  document.removeEventListener('click', handleClickOutside);

  // Remove scroll shadow listener
  if (navContainer.value) {
    navContainer.value.removeEventListener('scroll', updateScrollShadows);
  }
});
/************ MOUNTED ************/
</script>

<template>
  <nav class="hidden sm:flex h-svh bg-window_bg shadow-window min-h-screen overflow-hidden flex-col justify-between transition-all duration-300 select-none" :class="[isExpanded ? 'w-52' : 'w-18']">
    <div class="h-[calc(100%-56px-56px-16px)] flex flex-col justify-start relative">
      <!-- logo -->
      <div
          @click="changePage('dashboard')"
          class="w-full h-20 flex items-center justify-start cursor-pointer"
      >
        <img src="../../images/icon.svg" alt="Logo Icon" title="Company Logo" class="w-12 m-3">
        <span class="text-main transition-all font-semibold"
              :class="isExpanded ? 'opacity-100 w-auto duration-150 delay-75 text-md' : 'text-xs opacity-0 w-0 duration-0'"
        >
          Your Company
        </span>
      </div>

      <!-- shadow indicators -->
      <div
          class="absolute top-[72px] left-0 right-0 h-4 bg-gradient-to-b from-gray-300 to-transparent pointer-events-none transition-opacity duration-200"
          :class="{'opacity-0': !hasTopShadow, 'opacity-100': hasTopShadow}"
      ></div>

      <!-- navigation icons -->
      <div
          ref="navContainer"
          class="flex flex-col flex-grow pt-4 overflow-x-hidden overflow-y-auto scrollbar-hidden relative"
      >
        <NavItem
            v-for="page in filteredPages"
            :key="page.name"
            :activePage="activePage"
            :isExpanded="isExpanded"
            :navName="page.name"
            :iconName="page.icon"
            :role="role"
        />
      </div>

      <!-- bottom shadow -->
      <div
          class="absolute bottom-0 left-0 right-0 h-4 bg-gradient-to-t from-gray-300 to-transparent pointer-events-none transition-opacity duration-200"
          :class="{'opacity-0': !hasBottomShadow, 'opacity-100': hasBottomShadow}"
      ></div>
    </div>
    <!-- bottom icons -->
    <div class="flex flex-col items-center justify-center mb-4">
      <div class="w-full group flex items-center justify-start border-r-2 border-transparent hover:border-main transition-colors duration-100 cursor-pointer"
           @click="toggleNavbar"
      >
        <!-- Todo: change icon to single arrow -->
        <svg v-if="!isExpanded" class="w-6 h-6 my-4 ml-6  delay-100	 stroke-icon_color group-hover:stroke-main transition-colors duration-100 rotate-45" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M15 3H21V9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M9 21H3V15" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M21 3L14 10" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M3 21L10 14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <svg v-else class="w-6 h-6 my-4 ml-6  delay-100	 stroke-icon_color group-hover:stroke-main transition-colors duration-100 rotate-45" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M4 14H10V20" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M20 10H14V4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M14 10L21 3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M3 21L10 14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>

        <span v-if="!isExpanded" class="absolute ml-20 hidden group-hover:block duration-100 bg-window_bg text-primary_text px-2 py-1 rounded-window shadow-lg">Ausklappen</span>
        <span class="ml-4 group-hover:text-main  transition-opacity text-primary_text"
              :class="isExpanded ? 'opacity-100 w-[128px] duration-150 delay-100' : 'opacity-0 w-[0px] duration-0 '"
        >
            Einklappen
          </span>
      </div>

      <div ref="logoutButtonRef" @click="logout" :class="(confirmLogout ? 'hover:border-error_text' : 'hover:border-main')" class="w-full cursor-pointer group flex items-center justify-start border-r-2 border-transparent transition-colors duration-100">
        <svg :class="(confirmLogout ? 'stroke-error_text' : 'stroke-icon_color group-hover:stroke-main')" class="w-6 h-6 my-4 ml-6 delay-100 transition-colors duration-100" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M9 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path :class="{'animate-arrow': confirmLogout}" d="M16 17L21 12L16 7" stroke-linecap="round" stroke-linejoin="round"/>
          <path :class="{'animate-arrow': confirmLogout}" d="M21 12H9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>

        <span v-if="!isExpanded && !confirmLogout" class="absolute ml-20 hidden group-hover:block duration-100 bg-window_bg text-primary_text px-2 py-1 rounded-window shadow-lg">Abmelden</span>
        <span v-if="confirmLogout" @click="logout"
              class="absolute block duration-100 bg-error_text hover:bg-red-700 text-white px-2 py-1 rounded-window shadow-lg cursor-pointer animate-pulse"
              :class="isExpanded ? 'ml-56' : 'ml-20'"
        >
          Bestätigen
        </span>

        <span class="ml-4 transition-all "
              :class="[isExpanded ? 'opacity-100 w-[128px] duration-150 delay-100' : 'opacity-0 w-[0px] duration-0 ', confirmLogout ? 'text-error_text font-semibold' : 'text-primary_text group-hover:text-main']"
        >
            Abmelden
        </span>
      </div>
    </div>
  </nav>
</template>

<style scoped>
/* Sidebar width transition */
nav {
  transition: width 0.15s ease;
}

/* Arrow animation */
@keyframes arrowMove {
  0% {
    transform: translateX(0);
  }
  50% {
    transform: translateX(+2px);
  }
  100% {
    transform: translateX(0);
  }
}

.animate-arrow {
  animation: arrowMove 1s forwards infinite;
}

/* Hide scrollbar by default */
.scrollbar-hidden::-webkit-scrollbar {
  display: none;
}
.scrollbar-hidden:hover::-webkit-scrollbar {
  display: block;
}


/* Customize scrollbar */
.scrollbar-hidden::-webkit-scrollbar {
  width: 4px;
  height: 40px;
}

.scrollbar-hidden::-webkit-scrollbar-track {
  background: transparent;
}

.scrollbar-hidden::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
  height: 40px;
}

.scrollbar-hidden:hover::-webkit-scrollbar-thumb {
  background: #666;
}
</style>