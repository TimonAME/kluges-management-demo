<script setup>
import {computed, onBeforeUnmount, onMounted, ref, watch, watchEffect} from "vue";
import debounce from "lodash.debounce";

// Props and initial setup
const props = defineProps({
  users: Object,
  role: {
    type: String,
    required: true,
  },
});

// Individual refs
let users = ref([]);
const filter = ref('');
const sortKey = ref('id');
const sortOrder = ref(1);
const searchQuery = ref('');
const limit = ref(2);
const loading = ref(false);
const offset = ref(0);
const totalUsers = ref(0);
const hasMoreUsers = ref(true);
const isDropdownOpen = ref(false);
const dropdownRef = ref(null);

// Role configurations
const ROLE_CONFIG = {
  views: {
    '1': ['ROLE_MANAGEMENT', 'ROLE_LOCATION_MANAGEMENT', 'ROLE_OFFICE'],
    '2': ['ROLE_MARKETING'],
    '3': ['ROLE_TEACHER']
  },
  mapping: {
    'ROLE_MANAGEMENT': { name: 'Management', color: 'rgba(255,136,107,0.15)' },
    'ROLE_LOCATION_MANAGEMENT': { name: 'Standortleitung', color: 'rgba(255,169,107,0.15)' },
    'ROLE_OFFICE': { name: 'Büro', color: 'rgba(255,213,107,0.15)' },
    'ROLE_TEACHER': { name: 'Lehrer', color: 'rgba(150,205,83,0.15)' },
    'ROLE_STUDENT': { name: 'Schüler', color: 'rgba(76,241,128,0.15)' },
    'ROLE_GUARDIAN': { name: 'Erziehungsberechtigte', color: 'rgba(82,205,227,0.15)' },
    'ROLE_MARKETING': { name: 'Marketing', color: 'rgba(107,149,255,0.15)' }
  },
  additionalRoles: [
    { key: 'ROLE_MANAGEMENT', name: 'Management', color: 'rgba(255,136,107,0.15)' },
    { key: 'ROLE_LOCATION_MANAGEMENT', name: 'Standortleitung', color: 'rgba(255,169,107,0.15)' },
    { key: 'ROLE_OFFICE', name: 'Büro', color: 'rgba(255,213,107,0.15)' },
    { key: 'ROLE_MARKETING', name: 'Marketing', color: 'rgba(107,149,255,0.15)' }
  ]
};

// Computed properties
const activeView = computed(() =>
    Object.keys(ROLE_CONFIG.views).find(key => ROLE_CONFIG.views[key].includes(props.role))
);

//console.log(activeView.value);

const selectedRole = computed(() =>
    filter.value ? ROLE_CONFIG.additionalRoles.find(role =>
        role.key.toLowerCase().includes(filter.value.toLowerCase())
    ) : null
);

// URL management
const urlMapping = {
  '': '/benutzerliste',
  'schueler': '/benutzerliste/student',
  'lehrer': '/benutzerliste/teacher',
  'management': '/benutzerliste/management',
  'standortleitung': '/benutzerliste/location_management',
  'büro': '/benutzerliste/office',
  'marketing': '/benutzerliste/marketing'
};

const goToUrl = (location) => {
  window.location.href = urlMapping[location] || '/benutzerliste';
};

// User management
const addUser = () => window.location.href = '/user/registration';
const goToUser = (id) => window.location.href = `/user/${id}`;
const generateAvatarUrl = () => faker.image.avatar();
const formatRole = (role) => {
  const roleKey = role.replace(/[\[\]"]/g, '');
  return ROLE_CONFIG.mapping[roleKey] || { name: 'Unbekannt', color: 'rgba(0,0,0,0.1)' };
};

// Original fetchUsers implementation
const fetchUsers = async (append = false) => {
  if (sortKey.value === '') {
    sortKey.value = 'id';
  }
  loading.value = true;
  if (!append) {
    offset.value = 0;
  } else {
    offset.value = users.value.length;
  }

  try {
    const response = await fetch(`/api/users?role=${filter.value}&search=${searchQuery.value}&sortBy=${sortKey.value}&sortDirection=${sortOrder.value === 1 ? 'ASC' : 'DESC'}&offset=${offset.value}`);
    if (!response.ok) throw new Error('Failed to fetch users');
    const data = await response.json();

    totalUsers.value = data.total;
    const newUsers = data.users;

    if (append) {
      users.value = [...users.value, ...newUsers];
    } else {
      users.value = newUsers;
    }
    offset.value += limit.value;
    hasMoreUsers.value = users.value.length < totalUsers.value;
  } catch (error) {
    console.error('Error fetching users:', error);
  } finally {
    loading.value = false;
  }
};

// Dropdown management
const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isDropdownOpen.value = false;
  }
};

// Lifecycle hooks and watchers
onMounted(() => {
  users.value = props.users.users;
  totalUsers.value = props.users.total;

  const sentinel = document.querySelector('#sentinel');
  if (sentinel) {
    new IntersectionObserver(
        async (entries) => {
          if (entries[0].isIntersecting && !loading.value && hasMoreUsers.value) {
            await fetchUsers(true);
          }
        },
        { root: null, rootMargin: '0px', threshold: 1.0 }
    ).observe(sentinel);
  }
});

watchEffect(() => {
  const pathMapping = {
    '/benutzerliste/student': 'student',
    '/benutzerliste/teacher': 'teacher',
    '/benutzerliste/management': 'management',
    '/benutzerliste/location_management': 'location_management',
    '/benutzerliste/office': 'office',
    '/benutzerliste/marketing': 'marketing'
  };

  const path = window.location.pathname;
  filter.value = Object.entries(pathMapping)
      .find(([key]) => path.includes(key))?.[1] || '';
});

const debouncedSearch = debounce(fetchUsers, 300);
watch(searchQuery, () => {
  debouncedSearch();
});

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
});



/******* User Profile Picture *******/
const profilePictures = ref({});
const profilePictureErrors = ref({});

const fetchProfilePicture = async (userId) => {
  try {
    const response = await fetch(`/api/user/${userId}/pfp`);

    if (response.ok) {
      const data = await response.json();

      if (data.pfpPath) {
        const img = new Image();
        img.onload = () => {
          profilePictures.value[userId] = data.pfpPath.replace('./', '/');
        };
        img.onerror = () => {
          profilePictureErrors.value[userId] = true;
        };
        img.src = data.pfpPath.replace('./', '/');
      } else {
        profilePictureErrors.value[userId] = true;
      }
    } else {
      profilePictureErrors.value[userId] = true;
    }
  } catch (error) {
    console.error('Error fetching profile picture:', error);
    profilePictureErrors.value[userId] = true;
  }
};

// Add this to your lifecycle hooks
watch(() => users.value, (newUsers) => {
  if (newUsers && newUsers.length) {
    newUsers.forEach(user => {
      if (user.id) {
        fetchProfilePicture(user.id);
      }
    });
  }
}, { immediate: true });

const getInitialsAvatar = (firstName, lastName) => {
  if (!firstName || !lastName) return '';
  const firstInitial = firstName.charAt(0).toUpperCase();
  const lastInitial = lastName.charAt(0).toUpperCase();
  return `${firstInitial}${lastInitial}`;
};
/******* User Profile Picture *******/
</script>

<template>
  <div class="absolute bg-window_bg rounded-window shadow-window lg:w-[calc(100%-300px-16px)] w-[100%] sm:h-[56px] h-[calc(56px*2-8px)] left-0 top-0 p-2">
    <div class="h-full max-w-4xl mx-auto space-y-4">
      <div class="flex sm:flex-nowrap flex-wrap items-center justify-between sm:gap-4 gap-2">
        <div class="flex sm:gap-2 gap-0">
          <button
              v-if="activeView === '1' || activeView === '2'"
              @click="goToUrl('')"
              :class="[
                'px-4 py-2 rounded-window font-medium transition-all hover:shadow-lg',
                filter === '' ? 'bg-second_accent text-white stroke-white' : 'hover:bg-gray-100 bg-gray-50 text-icon_color stroke-icon_color'
              ]"
          >
            Alle
          </button>
          <button
              v-if="activeView === '1' || activeView === '2'"
              @click="goToUrl('lehrer')"
              :class="[
                'px-4 py-2 rounded-window font-medium transition-all hover:shadow-lg',
                filter === 'teacher' ? 'bg-second_accent text-white stroke-white' : 'hover:bg-gray-100 bg-gray-50 text-icon_color stroke-icon_color'
              ]"
          >
            Lehrer
          </button>
          <button
              v-if="activeView === '1' || activeView === '2' || activeView === '3'"
              @click="goToUrl('schueler')"
              :class="[
                'px-4 py-2 rounded-window font-medium transition-all hover:shadow-lg',
                filter === 'student' ? 'bg-second_accent text-white stroke-white' : 'hover:bg-gray-100 bg-gray-50 text-icon_color stroke-icon_color'
              ]"
          >
            Schüler
          </button>
          <!-- "Mehr" Button für die restlichen Rollen zum auswählen (filtern) -->
          <div
              v-if="activeView === '1' || activeView === '2'"
              class="relative"
              ref="dropdownRef"
          >
            <button
                @click="isDropdownOpen = !isDropdownOpen"
                class="px-4 py-2 rounded-window font-medium transition-all hover:shadow-lg flex items-center gap-2"
                :class="selectedRole ? 'bg-second_accent text-white stroke-white' : 'hover:bg-gray-100 bg-gray-50 text-icon_color stroke-icon_color'"
            >
              <span>{{ selectedRole?.name || 'Mehr' }}</span>
              <svg
                  class="w-4 h-4"
                  :class="{ 'rotate-180': isDropdownOpen }"
                  viewBox="0 0 24 24"
                  fill="none"
              >
                <path
                    d="M6 9l6 6 6-6"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
              </svg>
            </button>
            <!-- Dropdown -->
            <div
                v-if="isDropdownOpen"
                class="absolute z-50 mt-2 w-56 rounded-window shadow-lg bg-window_bg border border-gray-100"
            >
              <div class="py-1">
                <button
                    v-for="role in ROLE_CONFIG.additionalRoles"
                    :key="role.key"
                    @click="goToUrl(role.name.toLowerCase()); isDropdownOpen = false"
                    class="w-full text-left px-4 py-2 hover:bg-gray-50 flex items-center gap-2"
                >
                    <span
                        class="w-2 h-2 rounded-full"
                        :style="{ backgroundColor: role.color }"
                    />
                  <span class="text-gray-700">{{ role.name }}</span>
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="flex gap-2 sm:justify-start justify-between sm:w-fit w-full">
          <!-- Search -->
          <div class="relative">
            <svg class="absolute left-3 top-3 h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="none">
              <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input
                v-model="searchQuery"
                type="text"
                placeholder="Suche..."
                class="pl-10 pr-4 py-2 rounded-window bg-window_bg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-second_accent focus:border-transparent"
                style="max-width: 300px; width: 100%; min-width: 150px;"
            />
          </div>

          <!-- Add User Button -->
          <button
              v-if="activeView === '1'"
              @click="addUser"
              class="flex items-center gap-2 px-4 py-2 bg-second_accent text-white rounded-window hover:shadow-lg transition-shadow"
          >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
              <path d="M16 21V19C16 17.9391 15.5786 16.9217 14.8284 16.1716C14.0783 15.4214 13.0609 15 12 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21M20 8V14M23 11H17M8.5 11C10.7091 11 12.5 9.20914 12.5 7C12.5 4.79086 10.7091 3 8.5 3C6.29086 3 4.5 4.79086 4.5 7C4.5 9.20914 6.29086 11 8.5 11Z"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="xl:block hidden whitespace-nowrap">Neuer Benutzer</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="absolute bg-window_bg rounded-window shadow-window lg:w-[calc(100%-300px-16px)] w-[100%] sm:h-[calc(100%-56px-8px)] h-[calc(100%-56px*2-8px+8px)] left-0 bottom-0 px-2 pb-2 overflow-y-auto">
    <div class="h-full max-w-4xl mx-auto space-y-4">
      <!-- List -->
      <div class="divide-y divide-gray-100">
        <div
            v-for="(user, index) in users"
            :key="index"
            @click="goToUser(user.id)"
            class="p-4 hover:bg-gray-50 transition-colors cursor-pointer"
        >
          <div class="flex items-center gap-4">
            <div class="flex-none w-10 h-10 rounded-full overflow-hidden">
              <img
                  v-if="profilePictures[user.id]"
                  :src="profilePictures[user.id]"
                  class="w-full h-full object-cover"
                  :alt="`${user.first_name} ${user.last_name}`"
                  @error="profilePictureErrors[user.id] = true"
              />
              <div v-else class="flex items-center justify-center w-full h-full"
                   :style="{ backgroundColor: formatRole(user.roles).color + '20' }">
                <div class="flex items-center justify-center w-full h-full bg-second_accent bg-opacity-20 rounded-window">
                  <div class="text-sm font-bold text-second_accent w-full h-full flex items-center justify-center">{{ getInitialsAvatar(user.first_name, user.last_name) }}</div>
                </div>
              </div>
            </div>
            <div class="flex-1">
              <div class="font-medium text-gray-900">{{ user.first_name }} {{ user.last_name }}</div>
              <!-- TODO: show email if no phone number is available -->
              <div v-if="user.telefon" class="text-sm text-gray-500">{{ user.telefon }}</div>
              <div v-else class="text-sm text-gray-500">{{ user.email }}</div>
            </div>
            <div
                class="px-3 py-1 rounded-lg text-sm font-medium"
                :style="{ backgroundColor: formatRole(user.roles).color }"
            >
              {{ formatRole(user.roles).name }}
            </div>
            <!-- Devider -->
            <div v-if="user.subject_names" class="h-6 bg-gray-300 w-0.5 md:block hidden"></div>
            <div v-if="user.subject_names" class="gap-2 md:flex hidden">
              <!-- Devider -->
              <span
                  v-for="(subject, i) in user.subject_names.split(',')"
                  :key="i"
                  class="px-2 py-1 rounded-lg text-sm font-medium"
                  :style="{ backgroundColor: user.subject_colors.split(',')[i] + '15', color: user.subject_colors.split(',')[i] }"
              >
                {{ subject }}
              </span>
            </div>
          </div>
        </div>
      </div>
      <div id="sentinel" class="h-1"></div>

      <!-- Loading Indicator -->
      <div v-if="loading && users.length < totalUsers" class="text-center py-4">
        <svg class="spinner mx-auto w-8 h-8" viewBox="0 0 24 24" fill="none">
          <path d="M12 2V6M12 18V22M4.93 4.93L7.76 7.76M16.24 16.24L19.07 19.07M2 12H6M18 12H22M4.93 19.07L7.76 16.24M16.24 7.76L19.07 4.93"
                stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
    </div>
  </div>

  <div class="absolute bg-window_bg rounded-window shadow-window w-[300px] h-[100%] right-0 top-0 lg:block hidden">
    <div class="w-full h-full flex flex-col rounded-window overflow-y-auto">
      <!-- Hauptinhaltsbereich -->
      <div class="flex-1 flex flex-col gap-4">

        <!-- Rechte Spalte - Schnelle Statistiken & Infos -->
        <div class="flex-[2] space-y-4">
          <!-- Schnelle Statistiken -->
          <div class="bg-window_bg shadow-sm p-4">
            <h3 class="font-semibold mb-4">Statistiken</h3>
            <div class="grid grid-cols-2 gap-4">
              <div class="p-4 bg-blue-50 rounded-lg">
                <div class="text-sm text-gray-600">Gesamte Nutzer</div> <!-- alle user in der datenbank -->
                <div class="text-2xl font-bold text-blue-600">2.451</div>
              </div>
              <div class="p-4 bg-green-50 rounded-lg">
                <div class="text-sm text-gray-600">Heute aktiv</div> <!-- user die sich heute eingeloggt haben -->
                <div class="text-2xl font-bold text-green-600">183</div>
              </div>
              <div class="p-4 bg-purple-50 rounded-lg">
                <div class="text-sm text-gray-600">Neu diese Woche</div> <!-- user die diese woche erstellt wurden -->
                <div class="text-2xl font-bold text-purple-600">24</div>
              </div>
              <div class="p-4 bg-orange-50 rounded-lg">
                <div class="text-sm text-gray-600">Unvollständig</div> <!-- user die das standard passwort noch haben -->
                <div class="text-2xl font-bold text-orange-600">12</div>
              </div>
            </div>
          </div>

          <!-- Letzte Aktivitäten -->
          <div class="bg-window_bg shadow-sm p-4">
            <h3 class="font-semibold mb-4">Letzte Aktivitäten</h3>
            <div class="space-y-4">
              <div class="flex items-start gap-3">
                <div class="p-2 w-6 h-6 bg-green-50 rounded-lg">
                </div>
                <div>
                  <div class="text-sm font-medium">Neuer Nutzer registriert</div>
                  <div class="text-xs text-gray-500">Vor 2 Minuten</div>
                </div>
              </div>
              <div class="flex items-start gap-3">
                <div class="p-2 w-6 h-6 bg-purple-50 rounded-lg">
                </div>
                <div>
                  <div class="text-sm font-medium">Telefonnummer aktualisiert</div>
                  <div class="text-xs text-gray-500">Vor 1 Stunde</div>
                </div>
              </div>
              <div class="flex items-start gap-3">
                <div class="p-2 w-6 h-6 bg-blue-50 rounded-lg">
                </div>
                <div>
                  <div class="text-sm font-medium">Adresse geändert</div>
                  <div class="text-xs text-gray-500">Vor 3 Stunden</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Schnelle Aktionen -->
          <div class="bg-window_bg shadow-sm p-4">
            <h3 class="font-semibold mb-4">Aktionen</h3>
            <div class="grid grid-cols-2 gap-3">
              <button class="p-3 text-sm font-medium bg-gray-50 rounded-lg hover:bg-gray-100">
                Nutzerliste exportieren
              </button>
              <button class="p-3 text-sm font-medium bg-gray-50 rounded-lg hover:bg-gray-100">
                Statistiken exportieren
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.spinner {
  animation: spin 3s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>