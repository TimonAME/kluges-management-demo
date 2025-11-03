<script setup>
import {onMounted, ref, toRaw, watch} from 'vue';

const props = defineProps({
  user: Object,
  currentUserRole: String
});

const copyToClipboard = (text) => {
  navigator.clipboard.writeText(text).then(() => {
    console.log('Copied to clipboard:', text);
  }).catch(err => {
    console.error('Failed to copy:', err);
  });
};

const goToList = () => {
  if (props.currentUserRole === 'ROLE_TEACHER') {
    window.location.href = `/benutzerliste/schueler`;
    return;
  }
  window.location.href = `/benutzerliste`;
};

const formatRole = (roles) => {
  const rawRoles = toRaw(roles);
  if (!rawRoles || rawRoles.length === 0) {
    return 'Unbekannt';
  }
  switch (rawRoles[0]) {
    case 'ROLE_MANAGEMENT':
      return 'Management';
    case 'ROLE_LOCATION_MANAGEMENT':
      return 'Standortleitung';
    case 'ROLE_OFFICE':
      return 'Büro';
    case 'ROLE_TEACHER':
      return 'Lehrer';
    case 'ROLE_STUDENT':
      return 'Schüler';
    case 'ROLE_GUARDIAN':
      return 'Erziehungsberechtigte';
    case 'ROLE_MARKETING':
      return 'Marketing';
    default:
      return 'Unbekannt';
  }
};

const currentUserRole = ref('');
let cantGoBack = ref(false);
const profilePicture = ref(null);
const profilePictureLoaded = ref(false);
const profilePictureError = ref(false);

onMounted(() => {
  currentUserRole.value = props.currentUserRole;
  cantGoBack.value = currentUserRole.value === 'ROLE_STUDENT' || currentUserRole.value === 'ROLE_GUARDIAN';
});

// Watch for changes to the user prop and fetch the profile picture when it becomes available
watch(() => props.user, (newUser) => {
  if (newUser && newUser.id) {
    fetchProfilePicture(newUser.id);
  }
}, {immediate: true}); // immediate: true triggers the watcher immediately

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
          profilePicture.value = data.pfpPath;
          profilePictureLoaded.value = true;
        };
        img.onerror = () => {
          console.error('Failed to load image from path');
          profilePictureError.value = true;
        };
        img.src = data.pfpPath.replace('./', '/');
      } else {
        profilePictureError.value = true;
      }
    } else {
      profilePictureError.value = true;
    }
  } catch (error) {
    console.error('Error fetching profile picture:', error);
    profilePictureError.value = true;
  }
};

const getInitialsAvatar = () => {
  if (!props.user || !props.user.first_name || !props.user.last_name) {
    return renderDefaultAvatar();
  }

  const firstInitial = props.user.first_name.charAt(0).toUpperCase();
  const lastInitial = props.user.last_name.charAt(0).toUpperCase();

  return (
      `<div class="flex items-center justify-center w-full h-full bg-second_accent bg-opacity-20 rounded-window">
      <div class="text-3xl font-bold text-second_accent w-full h-full flex items-center justify-center">${firstInitial}${lastInitial}</div>
    </div>`
  );
};

const renderDefaultAvatar = () => {
  return (
      `<div class="flex items-center justify-center w-full h-full bg-gray-200 rounded-window">
      <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path
            d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21"
            stroke="#5F6368" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path
            d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z"
            stroke="#5F6368" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>`
  );
};
</script>

<template>
  <div class="flex flex-col justify-between h-full">
    <div>
      <!-- Go Back Button -->
      <div v-if="!cantGoBack" class="flex flex-row items-center gap-2 cursor-pointer mt-2 mb-4 group"
           @click="goToList()">
        <svg class="stroke-icon_color w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M10 19L4 12L10 5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M4 12H20" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <div class="text-primary_text font-semibold group-hover:underline">Zurück zur Liste</div>
      </div>

      <!-- Profile Picture -->
      <div class="w-full max-h-64 aspect-square rounded-window overflow-hidden">
        <!-- Real profile picture (if loaded) -->
        <img
            v-if="profilePictureLoaded"
            :src="profilePicture?.replace('./', '/')"
            class="w-full h-full object-cover"
            alt="Profilbild"
            @error="profilePictureError = true"
        />

        <!-- Fallback to initials avatar or default user icon -->
        <div v-else class="w-full h-full">
          <div v-if="profilePictureError" v-html="getInitialsAvatar()" class="w-full h-full"></div>
          <div v-else class="w-full h-full flex items-center justify-center bg-gray-200">
            <div class="animate-pulse w-12 h-12 rounded-full bg-gray-300"></div>
          </div>
        </div>
      </div>

      <!-- personen daten -->
      <div class="mt-2">
        <h2 class="text-xl font-semibold text-second_accent">{{ props.user.first_name }} {{
            props.user.last_name
          }}
        </h2>
        <div class="h-[1px] w-full rounded-full bg-gray-200 mt-1"></div>
        <div class="text-base font-semibold text-secondary_text mt-1">{{ formatRole(props.user.roles) }}</div>
      </div>

      <!-- kontakt daten -->
      <div class="mt-6">
        <h2 class="text-lg font-semibold text-second_accent">Kontaktieren</h2>
        <div class="h-[1px] w-full rounded-full bg-gray-200 mt-1"></div>
        <div
            v-if="user.email"
            class="mt-2 py-2 transition-all duration-100 group cursor-pointer flex flex-row items-center justify-between"
            @click="copyToClipboard(user.email)"
            title="Nummer Kopieren">
          <div class="flex flex-row gap-2 max-w-[calc(100%-32px)] overflow-hidden">
            <svg class="w-6 h-6 stroke-icon_color" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M22 6L12 13L2 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <div class="text-secondary_text group-hover:underline group-hover:text-main truncate">{{ user.email }}</div>
          </div>
          <svg
              class="stroke-icon_color w-6 h-6 group-hover:stroke-main opacity-0 group-hover:opacity-100 transition-opacity duration-100"
              viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
                d="M20 9H11C9.89543 9 9 9.89543 9 11V20C9 21.1046 9.89543 22 11 22H20C21.1046 22 22 21.1046 22 20V11C22 9.89543 21.1046 9 20 9Z"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path
                d="M5 15H4C3.46957 15 2.96086 14.7893 2.58579 14.4142C2.21071 14.0391 2 13.5304 2 13V4C2 3.46957 2.21071 2.96086 2.58579 2.58579C2.96086 2.21071 3.46957 2 4 2H13C13.5304 2 14.0391 2.21071 14.4142 2.58579C14.7893 2.96086 15 3.46957 15 4V5"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div
            v-if="user.telefon"
            class="mt-2 py-2 transition-all duration-100 group cursor-pointer flex flex-row items-center justify-between"
            @click="copyToClipboard(user.telefon)"
            title="Nummer Kopieren">
          <div class="flex flex-row gap-2 max-w-[calc(100%-32px)] overflow-hidden">
            <svg class="w-6 h-6 stroke-icon_color" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path
                  d="M22.0004 16.92V19.92C22.0016 20.1985 21.9445 20.4741 21.8329 20.7293C21.7214 20.9845 21.5577 21.2136 21.3525 21.4018C21.1473 21.5901 20.905 21.7335 20.6412 21.8227C20.3773 21.9119 20.0978 21.945 19.8204 21.92C16.7433 21.5856 13.7874 20.5341 11.1904 18.85C8.77425 17.3146 6.72576 15.2661 5.19042 12.85C3.5004 10.2412 2.44866 7.27097 2.12042 4.17997C2.09543 3.90344 2.1283 3.62474 2.21692 3.3616C2.30555 3.09846 2.44799 2.85666 2.63519 2.6516C2.82238 2.44653 3.05023 2.28268 3.30421 2.1705C3.5582 2.05831 3.83276 2.00024 4.11042 1.99997H7.11042C7.59573 1.9952 8.06621 2.16705 8.43418 2.48351C8.80215 2.79996 9.0425 3.23942 9.11042 3.71997C9.23704 4.68004 9.47187 5.6227 9.81042 6.52997C9.94497 6.8879 9.97408 7.27689 9.89433 7.65086C9.81457 8.02482 9.62928 8.36809 9.36042 8.63998L8.09042 9.90997C9.51398 12.4135 11.5869 14.4864 14.0904 15.91L15.3604 14.64C15.6323 14.3711 15.9756 14.1858 16.3495 14.1061C16.7235 14.0263 17.1125 14.0554 17.4704 14.19C18.3777 14.5285 19.3204 14.7634 20.2804 14.89C20.7662 14.9585 21.2098 15.2032 21.527 15.5775C21.8441 15.9518 22.0126 16.4296 22.0004 16.92Z"
                  stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <div class="text-secondary_text group-hover:underline group-hover:text-main truncate">{{
                user.telefon
              }}
            </div>
          </div>
          <svg
              class="stroke-icon_color w-6 h-6 group-hover:stroke-main opacity-0 group-hover:opacity-100 transition-opacity duration-100"
              viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
                d="M20 9H11C9.89543 9 9 9.89543 9 11V20C9 21.1046 9.89543 22 11 22H20C21.1046 22 22 21.1046 22 20V11C22 9.89543 21.1046 9 20 9Z"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path
                d="M5 15H4C3.46957 15 2.96086 14.7893 2.58579 14.4142C2.21071 14.0391 2 13.5304 2 13V4C2 3.46957 2.21071 2.96086 2.58579 2.58579C2.96086 2.21071 3.46957 2 4 2H13C13.5304 2 14.0391 2.21071 14.4142 2.58579C14.7893 2.96086 15 3.46957 15 4V5"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      </div>

      <div v-if="user.birthday" class="mt-6">
        <div class="text-lg font-semibold text-second_accent">Persönliche Daten</div>
        <div class="h-[1px] w-full rounded-full bg-gray-200 mt-1"></div>
        <div
            class="mt-2 py-2 transition-all duration-100 group flex flex-row items-center justify-between">
          <div class="flex flex-row gap-2 max-w-[calc(100%-32px)] overflow-hidden">
            <svg class="w-6 h-6 stroke-icon_color" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M20 12V22H4V12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M22 7H2V12H22V7Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 22V7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path
                  d="M12 7H7.5C6.83696 7 6.20107 6.73661 5.73223 6.26777C5.26339 5.79893 5 5.16304 5 4.5C5 3.83696 5.26339 3.20107 5.73223 2.73223C6.20107 2.26339 6.83696 2 7.5 2C11 2 12 7 12 7Z"
                  stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path
                  d="M12 7H16.5C17.163 7 17.7989 6.73661 18.2678 6.26777C18.7366 5.79893 19 5.16304 19 4.5C19 3.83696 18.7366 3.20107 18.2678 2.73223C17.7989 2.26339 17.163 2 16.5 2C13 2 12 7 12 7Z"
                  stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <div class="text-secondary_text truncate">{{ user.birthday }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>