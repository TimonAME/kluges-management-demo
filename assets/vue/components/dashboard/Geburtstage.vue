<script setup>
import { ref, computed, onMounted } from 'vue';

const birthdays = ref([]);
const loading = ref(true);

const props = defineProps({
  birthdays: Object,
});

onMounted(() => {
  // Convert object to array if it's an object
  birthdays.value = Object.values(props.birthdays);
  loading.value = false;

  // Fetch profile pictures for each user
  if (birthdays.value && birthdays.value.length > 0) {
    birthdays.value.forEach(birthday => {
      if (birthday.id) {
        fetchProfilePicture(birthday.id);
      } else {
        console.error('Invalid user ID:', birthday.id);
      }
    });
  }
});

const today = new Date().toISOString().split('T')[0];

const isToday = (date) => {
  return date === today;
};

const calculateAge = (birthDate) => {
  const today = new Date();
  const birthDateObj = new Date(birthDate);
  let age = today.getFullYear() - birthDateObj.getFullYear();
  const monthDiff = today.getMonth() - birthDateObj.getMonth();
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDateObj.getDate())) {
    age--;
  }
  return age;
};

const goToProfile = (birthday) => {
  window.location.href = `/user/${birthday.id}`;
};


/******* User Profile Picture *******/
const profilePictures = ref({});
const profilePictureErrors = ref({});

const fetchProfilePicture = async (userId) => {
  try {
    const response = await fetch(`/api/user/${userId}/pfp`);

    if (response.ok) {
      const data = await response.json();

      // Check if we have a valid path
      if (data.pfpPath) {
        // Create an image element to verify the image exists and loads
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

const getInitialsAvatar = (firstName, lastName) => {
  if (!firstName || !lastName) return '';
  const firstInitial = firstName.charAt(0).toUpperCase();
  const lastInitial = lastName.charAt(0).toUpperCase();
  return `${firstInitial}${lastInitial}`;
};
/******* User Profile Picture *******/
</script>

<template>
  <div class="sm:h-[100%] w-full bg-window_bg rounded-window shadow-window overflow-hidden flex flex-col">
    <!-- Header -->
    <div class="px-4 pt-2 border-b border-gray-100">
      <div class="flex items-center justify-between">
        <h2 class="text-primary_text text-base font-semibold">Geburtstage</h2>
        <div class="flex items-center gap-2">
          <div class="p-1.5">
            <svg class="w-5 h-5 stroke-window_bg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M7 17L17 7M7 7H17V17" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Birthday List -->
    <div class="flex-1 overflow-y-auto">
      <div class="space-y-1.5">
        <div v-if="loading" class="flex justify-center items-center h-32">
          <svg class="animate-spin h-6 w-6 text-second_accent" xmlns="http://www.w3.org/2000/svg" fill="none"
               viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>

        <div v-else-if="birthdays.length === 0" class="text-center text-secondary_text py-4">
          Keine Geburtstage in nächster Zeit.
        </div>

        <div v-else v-for="birthday in birthdays" :key="birthday.first_name + birthday.last_name"
             @click="goToProfile(birthday)"
             class="group relative flex items-center p-3 bg-window_bg hover:bg-first_accent hover:bg-opacity-20 transition-all cursor-pointer select-none">
          <!-- Birthday Info -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center space-x-3">
              <div class="flex-shrink-0">
                <div class="w-10 h-10 rounded-full overflow-hidden">
                  <img
                      v-if="profilePictures[birthday.id]"
                      :src="profilePictures[birthday.id]"
                      class="w-full h-full object-cover"
                      :alt="`${birthday.first_name} ${birthday.last_name}`"
                      @error="profilePictureErrors[birthday.id] = true"
                  />
                  <!-- Simplified fallback div structure -->
                  <div v-else
                       class="flex items-center justify-center w-full h-full"
                       :class="birthday.rolle === 'lehrer' ? 'bg-first_accent bg-opacity-20' : 'bg-second_accent bg-opacity-20'">
                    <span class="text-sm font-bold" :class="birthday.rolle === 'lehrer' ? 'text-first_accent' : 'text-second_accent'">
                      {{ getInitialsAvatar(birthday.first_name, birthday.last_name) }}
                    </span>
                  </div>
                </div>
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-primary_text truncate">
                  {{ birthday.first_name }} {{ birthday.last_name }}
                </p>
                <p class="text-xs text-secondary_text truncate">
                  wird {{ calculateAge(birthday.birthday.date) + 1 }} Jahre alt
                </p>
              </div>
            </div>
          </div>

          <!-- Date -->
          <div class="flex-shrink-0 ml-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="isToday(birthday.birthday.date) ? 'bg-first_accent text-white' : 'bg-gray-100 text-gray-800'">
              {{ new Date(birthday.birthday.date).toLocaleDateString('de-DE', {day: '2-digit', month: '2-digit'}) }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>