<script setup>
import { computed, onMounted, ref, toRaw } from 'vue';
import Stammdaten from "../components/userProfile/Stammdaten.vue";
import Faecher from "../components/userProfile/Faecher.vue";
import TODOs from "../components/userProfile/TODOs.vue";
import Benachrichtigungen from "../components/userProfile/Benachrichtigungen.vue";
import EMailSettings from "../components/userProfile/E-MailSettings.vue";
import BasicUserInformation from "../components/userProfile/BasicUserInformation.vue";
import Tipps from "../components/userProfile/Tipps.vue";
import DeleteUser from "../components/userProfile/DeleteUser.vue";
import Berechtigungen from "../components/userProfile/Berechtigungen.vue";

const props = defineProps({
  user: Object,
  initialCategory: String,
  currentUserRole: {
    type: String,
    required: true,
  },
  currentUserID: {
    type: Number,
    required: true,
  },
});

let categories
const user = ref({});
const currentUserRole = ref ('');
const currentUserID = ref(0);
const selectedCategory = ref('Stammdaten');
const isDropdownOpen = ref(false);

onMounted(() => {
  user.value = props.user;
  currentUserRole.value = props.currentUserRole;
  currentUserID.value = props.currentUserID;
  categories = allCategories[user.value.roles[0]] || [];

  if (props.initialCategory) {
    const availableCategories = visibleCategories.value;
    const lowerCaseCategories = availableCategories.map(category =>
        category.toLowerCase()
    );
    const lowerCaseInitialCategory = props.initialCategory.toLowerCase();

    if (lowerCaseCategories.includes(lowerCaseInitialCategory)) {
      selectedCategory.value = availableCategories.find(
          category => category.toLowerCase() === lowerCaseInitialCategory
      );
    }
  }
});


// TODO: Categories to add: 'E-Mail Einstellungen'

//Roles: ROLE_MANAGEMENT, ROLE_LOCATION_MANAGEMENT, ROLE_OFFICE, ROLE_TEACHER, ROLE_STUDENT, ROLE_GUARDIAN, ROLE_MARKETING
const allCategories = {
  ROLE_MANAGEMENT: ['Stammdaten', 'Fächer', 'Todos', 'Benachrichtigungen', 'Arbeitszeiten', 'Berechtigungen', 'Tipps', 'Account Löschen'],
  ROLE_LOCATION_MANAGEMENT: ['Stammdaten', 'Fächer', 'Todos', 'Benachrichtigungen', 'Arbeitszeiten', 'Berechtigungen', 'Tipps', 'Account Löschen'],
  ROLE_OFFICE: ['Stammdaten', 'Fächer', 'Todos', 'Benachrichtigungen', 'Arbeitszeiten', 'Berechtigungen', 'Tipps', 'Account Löschen'],
  ROLE_TEACHER: ['Stammdaten', 'Fächer', 'Todos', 'Benachrichtigungen', 'Arbeitszeiten', 'Berechtigungen', 'Tipps', 'Account Löschen'],
  ROLE_STUDENT: ['Stammdaten', 'Fächer', 'Benachrichtigungen', 'Berechtigungen', 'Tipps', 'Account Löschen'],
  ROLE_GUARDIAN: ['Stammdaten', 'Fächer', 'Benachrichtigungen', 'Berechtigungen', 'Tipps', 'Account Löschen'],
  ROLE_MARKETING: ['Stammdaten', 'Fächer', 'Todos', 'Benachrichtigungen', 'Berechtigungen', 'Tipps', 'Account Löschen']
};

// Define role hierarchies (higher number = more permissions)
const ROLE_LEVELS = {
  ROLE_MANAGEMENT: 100,
  ROLE_LOCATION_MANAGEMENT: 90,
  ROLE_OFFICE: 80,
  ROLE_TEACHER: 70,
  ROLE_STUDENT: 20,
  ROLE_GUARDIAN: 10,
  ROLE_MARKETING: 60
};

// Define category permissions
const categoryPermissions = {
  Stammdaten: {
    minViewSelfLevel: 0,        // Everyone can view their own
    minViewOthersLevel: 70,     // Teachers and above can view others
    component: Stammdaten
  },
  Fächer: {
    minViewSelfLevel: 0,
    minViewOthersLevel: 70,
    component: Faecher
  },
  Todos: {
    minViewSelfLevel: 90,
    minViewOthersLevel: 90,    // Only office and above can view others' Todos
    component: TODOs
  },
  Benachrichtigungen: {
    minViewSelfLevel: 80,
    minViewOthersLevel: 80,
    component: Benachrichtigungen
  },
  'E-Mail Einstellungen': {
    minViewSelfLevel: 0,
    minViewOthersLevel: 90,
    component: EMailSettings
  },
  'Arbeitszeiten': {
    minViewSelfLevel: 70,      // Nur Lehrer und höher können ihre eigenen Arbeitszeiten sehen
    minViewOthersLevel: 999,   // Extrem hoher Wert, damit niemand die Arbeitszeiten anderer sehen kann
    component: 'div'           // Dummy-Komponente, da wir zu einer anderen Seite weiterleiten
  },
  'Berechtigungen': {
    minViewSelfLevel: 999,     // Niemand kann seine eigenen Berechtigungen bearbeiten
    minViewOthersLevel: 80,    // Nur Office und höher können Berechtigungen bearbeiten
    component: Berechtigungen
  },
  Tipps: {
    minViewSelfLevel: 80,
    minViewOthersLevel: 80,
    component: Tipps
  },
  'Account Löschen': {
    minViewSelfLevel: 0,
    minViewOthersLevel: 90,   // Only management can see delete option for others
    component: DeleteUser
  }
};


// Permission checking functions
const hasPermissionLevel = (userRole, requiredLevel) => {
  return ROLE_LEVELS[userRole] >= requiredLevel;
};

const canAccess = (category, targetUserId, action) => {
  // First check if the category is available for the target user's role
  const targetUserCategories = allCategories[user.value.roles[0]] || [];
  if (!targetUserCategories.includes(category)) {
    return false;
  }

  // Then check permission-based access
  const isSelf = targetUserId === currentUserID.value;
  const permissionConfig = categoryPermissions[category];

  if (!permissionConfig) return false;

  const requiredLevel = isSelf ?
      permissionConfig.minViewSelfLevel :
      permissionConfig.minViewOthersLevel;

  return hasPermissionLevel(currentUserRole.value, requiredLevel);
};




// Get visible categories based on both role and permissions
const visibleCategories = computed(() => {
  // Safety check for user initialization
  if (!user.value || !user.value.roles || !user.value.roles.length) {
    return ['Stammdaten']; // Default fallback category
  }

  // Get categories available for the target user's role
  const targetUserCategories = allCategories[user.value.roles[0]] || [];

  // Filter categories based on permissions
  return targetUserCategories.filter(category =>
      canAccess(
          category,
          props.user.id,
          'view'
      )
  );

});


const selectedCategoryComponent = computed(() => {
  const config = categoryPermissions[selectedCategory.value];
  return config ? config.component : 'Stammdaten';
});


const selectCategory = (category) => {
  // Spezielle Behandlung für Arbeitszeiten
  if (category === 'Arbeitszeiten') {
    window.location.href = '/arbeitsstunden';
    return;
  }

  // go to url user/:id/:category
  window.history.pushState({}, '', `/user/${user.value.id}/${category}`);

  selectedCategory.value = category;
  isDropdownOpen.value = false;
};
</script>

<template>
  <!-- User Basics -->
  <div
    class="md:absolute bg-window_bg rounded-window shadow-window w-full md:w-[calc(256px)] md:h-[100%] md:left-0 md:top-0 p-2 mb-4 md:mb-0 overflow-y-auto">
    <BasicUserInformation :user="user" :currentUserRole="props.currentUserRole" />
  </div>

  <!-- Kategories -->
  <div
    class="md:absolute bg-window_bg rounded-window shadow-window w-full md:w-[calc(100%-256px-16px)] md:h-[56px] md:right-0 md:top-0 p-2 mb-2 md:mb-0 overflow-x-auto overflow-y-hidden select-none">
    <!-- Dropdown for mobile users -->
    <div class="block md:hidden">
      <button @click="isDropdownOpen = !isDropdownOpen"
        class="w-full text-left px-4 py-2 bg-second_accent bg-opacity-50 rounded-window font-semibold text-primary_text flex flex-row">
        <span>{{ selectedCategory }}</span>
        <svg class="w-6 h-6 stroke-primary_text transition" :class="isDropdownOpen ? 'rotate-180' : ''" viewBox="0 0 24 24"
          fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M6 9L12 15L18 9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </button>
      <div v-if="isDropdownOpen" class="mt-2 bg-window_bg rounded-window">
        <ul>
          <li v-for="category in visibleCategories" :key="category" @click="selectCategory(category)"
            class="px-4 py-2 hover:bg-second_accent cursor-pointer whitespace-nowrap">
            {{ category }}
          </li>
        </ul>
      </div>
    </div>

    <!-- List of buttons for desktop users -->
    <div class="hidden md:flex h-full w-full flex-row font-medium text-primary_text items-center">
      <button
          v-for="category in visibleCategories" :key="category" @click="selectCategory(category)"
          :class="[
                'px-4 py-2 rounded-window font-medium transition-all hover:shadow-lg mr-2 whitespace-nowrap',
                selectedCategory === category ? 'bg-second_accent text-white stroke-white' : 'hover:bg-gray-100 bg-gray-50 text-icon_color stroke-icon_color'
              ]"
      >
        {{ category }}
      </button>
    </div>
  </div>

  <!-- Content, depends on the Kategorie -->
  <div
    class="md:absolute bg-window_bg rounded-window shadow-window w-full md:w-[calc(100%-256px-16px)] md:h-[calc(100%-56px-8px)] md:right-0 md:bottom-0">
    <!-- Content, depends on the Kategorie -->
    <div class="flex-grow h-full items-center overflow-y-scroll">
      <component :is="selectedCategoryComponent" :user_id="user.id" />
    </div>
  </div>


</template>
