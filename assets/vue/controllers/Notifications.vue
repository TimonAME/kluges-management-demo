<script setup>
import { onMounted, ref, computed } from "vue";

const notifications = ref([]);
const role = ref('');
const allTags = ref([]);
const activeView = ref('unread');
const loadingNotifications = ref([]);
const isLoading = ref(false);
const pendingView = ref(null);
const isDropdownOpen = ref(false);

const props = defineProps({
  notifications: {
    type: Array,
    required: true,
  },
  role: {
    type: String,
    required: true,
  },
  allTags: {
    type: Array,
    required: true,
  },
});

onMounted(() => {
  notifications.value = props.notifications;
  role.value = props.role;
  allTags.value = props.allTags;
});

// Modify your fetchNotificationsByCategory function
const fetchNotificationsByCategory = async (category) => {
  // If already loading, don't fetch again
  if (isLoading.value) return;

  isLoading.value = true;
  pendingView.value = category;

  try {
    const response = await fetch(`/api/notifications?category=${category}`);
    const data = await response.json();
    notifications.value = data.notifications;
  } catch (error) {
    console.error('Failed to fetch notifications:', error);
  } finally {
    isLoading.value = false;
    pendingView.value = null;
  }
};

// Modify your setActiveView function
const setActiveView = async (view) => {
  // If already loading or same view, don't change
  if (isLoading.value || activeView.value === view) return;

  activeView.value = view;
  await fetchNotificationsByCategory(view);
};

const checkNotification = async (id) => {
  // if notification is already checked or loading, return
  if (notifications.value.find(notification => notification.id === id).isRead ||
      loadingNotifications.value.includes(id)) {
    return;
  }

  loadingNotifications.value.push(id);

  try {
    const response = await fetch('/api/notification/' + id + '/read', {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ isRead: true }),
    });

    const data = await response.json();

    // Update the notification state
    notifications.value = notifications.value.map(notification => {
      if (notification.id === id) {
        return { ...notification, isRead: true };
      }
      return notification;
    });

    // If we're in unread view, remove the notification after a short delay
    if (activeView.value === 'unread') {
      setTimeout(() => {
        notifications.value = notifications.value.filter(notification => notification.id !== id);
      }, 400); // Match this with the transition duration
    }
  } catch (error) {
    console.error('Failed to update notification:', error);
  } finally {
    loadingNotifications.value = loadingNotifications.value.filter(
        notificationId => notificationId !== id
    );
  }
};
</script>

<template>
  <div class="absolute bg-window_bg rounded-window shadow-lg md:w-[300px] w-full md:h-[100%] h-[56px] left-0 top-0 p-2">
    <!-- Desktop Sidebar -->
    <div class="md:block hidden space-y-2">
      <button
          @click="setActiveView('unread')"
          :class="[
          'flex flex-row gap-2 items-center w-full text-left px-4 py-2 rounded-window transition-colors',
          activeView === 'unread'
            ? 'bg-second_accent text-white stroke-white'
            : 'hover:bg-gray-100 stroke-icon_color'
        ]"
      >
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M18 8C18 6.4087 17.3679 4.88258 16.2426 3.75736C15.1174 2.63214 13.5913 2 12 2C10.4087 2 8.88258 2.63214 7.75736 3.75736C6.63214 4.88258 6 6.4087 6 8C6 15 3 17 3 17H21C21 17 18 15 18 8Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M13.7295 21C13.5537 21.3031 13.3014 21.5547 12.9978 21.7295C12.6941 21.9044 12.3499 21.9965 11.9995 21.9965C11.6492 21.9965 11.3049 21.9044 11.0013 21.7295C10.6977 21.5547 10.4453 21.3031 10.2695 21" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Neu / Ungelesen</span>
      </button>
      <button
          @click="setActiveView('completed')"
          :class="[
          'flex flex-row gap-2 items-center w-full text-left px-4 py-2 rounded-window transition-colors',
          activeView === 'completed'
            ? 'bg-second_accent text-white stroke-white'
            : 'hover:bg-gray-100 stroke-icon_color'
        ]"
      >
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M20 6L9 17L4 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Gelesen</span>
      </button>

      <div class="mt-6 text-lg font-semibold text-second_accent">Tags</div>
      <div class="h-[1px] w-full rounded-full bg-gray-200 mt-1"></div>
      <div v-for="tag in allTags" :key="tag.id" @click="setActiveView(tag.name)"
           class="flex flex-row gap-2 items-center w-full text-left px-4 py-2 rounded-window transition-colors cursor-pointer"
           :class="activeView === tag.name ? 'bg-second_accent text-white stroke-white' : 'hover:bg-gray-100 stroke-icon_color'"
      >
        <span
            class="w-fit py-1 px-2 rounded-lg shadow-xs transition-all text-xs font-medium mr-1 cursor-pointer border-2"
            :class="activeView === tag.name ? 'border-white border-2' : 'border-transparent'"
            :style="{
                backgroundColor: tag.hex_color + '15',
                color: activeView === tag.name ? 'white' : tag.hex_color
              }">
                  {{ tag.name }}
        </span>
      </div>
    </div>
    <!-- Mobile Dropdown -->
    <div class="block md:hidden space-y-2">
      <button
          @click="isDropdownOpen = !isDropdownOpen"
          class="flex flex-row gap-2 items-center w-full text-left px-4 py-2 rounded-window transition-colors bg-second_accent text-white stroke-white"
      >
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M12 6V12L16 14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="capitalize">{{ activeView }}</span>
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M6 9L12 15L18 9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
      <div v-if="isDropdownOpen" class="space-y-2 z-30 relative top-4 bg-window_bg shadow-2xl rounded-window p-2">
        <button
            @click="setActiveView('unread'); isDropdownOpen = false"
            :class="[
          'flex felx-row gap-2 items-center w-full text-left px-4 py-2 rounded-window transition-colors',
          activeView === 'unread'
            ? 'bg-second_accent text-white stroke-white'
            : 'hover:bg-gray-100 stroke-icon_color'
        ]"
        >
          <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 6V12L16 14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Neu / Ungelesen</span>
        </button>
        <button
            @click="setActiveView('completed'); isDropdownOpen = false"
            :class="[
          'flex felx-row gap-2 items-center w-full text-left px-4 py-2 rounded-window transition-colors',
          activeView === 'completed'
            ? 'bg-second_accent text-white stroke-white'
            : 'hover:bg-gray-100 stroke-icon_color'
        ]"
        >
          <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 6L9 17L4 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span>Erledigte</span>
        </button>
        <div class="mt-6 text-lg font-semibold text-second_accent">Tags</div>
        <div class="h-[1px] w-full rounded-full bg-gray-200 mt-1"></div>
        <div v-for="tag in allTags" :key="tag.id" @click="setActiveView(tag.name); isDropdownOpen = false"
             class="flex flex-row gap-2 items-center w-full text-left px-4 py-2 rounded-window transition-colors cursor-pointer"
             :class="activeView === tag.name ? 'bg-second_accent text-white stroke-white' : 'hover:bg-gray-100 stroke-icon_color'"
        >
          <span
              class="w-fit py-1 px-2 rounded-lg shadow-xs transition-all text-xs font-medium mr-1 cursor-pointer border-2"
              :class="activeView === tag.name ? 'border-white border-2' : 'border-transparent'"
              :style="{
                  backgroundColor: tag.hex_color + '15',
                  color: activeView === tag.name ? 'white' : tag.hex_color
                }">
                    {{ tag.name }}
          </span>
        </div>
      </div>
    </div>
  </div>

  <div class="absolute bg-window_bg rounded-window shadow-lg md:w-[calc(100%-300px-16px)] w-full md:h-[100%] h-[calc(100%-56px-16px)] right-0 md:top-0 top-[calc(56px+8px)] p-4 overflow-x-hidden overflow-y-auto">
    <div class="xl:mx-[10%] mx-auto ">
      <h2 class="text-2xl font-bold mb-2">
        {{ activeView === 'unread' ? 'Ungelesen' : activeView === 'completed' ? 'Gelesen' : activeView.charAt(0).toUpperCase() + activeView.slice(1) }}
      </h2>
      <TransitionGroup name="list">
        <div
            v-for="notification in notifications"
            :key="notification.id"
            class="px-4 py-2 border-b border-gray-200"
            :class="[
                      loadingNotifications.includes(notification.id) ? 'opacity-50' : '',
                      notification.isRead && activeView === 'unread' ? 'list-leave-active' : ''
                    ]"
        >
          <!-- Nested Groups for custom hover effects -->
          <div class="flex items-center gap-4 group/item">
            <svg
                @click="checkNotification(notification.id)"
                class="w-4 h-4 border p-0 rounded-window transition-colors duration-75 ease-in stroke-icon_color border-icon_color"
                :class="notification.isRead ? 'cursor-default' : 'cursor-pointer'"
                viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
            >
              <path v-if="notification.isRead || loadingNotifications.includes(notification.id)" d="M20 6L9 17L4 12" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>

            <div class="flex flex-col w-full">
              <div class="flex flex-row flex-wrap justify-between w-full">
                <p>
                  {{ notification.message }}
                </p>
                <div class="flex flex-wrap flex-row gap-2">
                  <span v-for="tag in notification.notificationTags" :key="tag.id"
                        class="w-fit py-1 px-2 rounded-lg shadow-xs transition-all text-xs font-medium mr-1 cursor-pointer"
                        :style="{ backgroundColor: tag.hex_color + '15', color: tag.hex_color }">
                  {{ tag.name }}
                </span>
                </div>
              </div>

              <div class="flex flex-row items-center gap-2">
                <svg class="w-4 h-4 stroke-icon_color" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M12 6V12L16 14" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <div v-if="new Date(notification.date_created.date).toLocaleString() === 'Invalid Date'" class="text-xs text-gray-500 mt-1">{{ new Date(notification.date_created).toLocaleString() }}</div>
                <div v-else class="text-xs text-gray-500 mt-1">{{ new Date(notification.date_created.date).toLocaleString() }}</div>
              </div>
            </div>
          </div>
        </div>
      </TransitionGroup>
    </div>

    <!-- Loading Overlay -->
    <div v-if="isLoading" class="absolute inset-0 backdrop-blur z-50 flex items-center justify-center">
      <div class="bg-white p-4 rounded-lg shadow-lg flex items-center space-x-2">
        <svg class="animate-spin h-5 w-5 text-second_accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-gray-700">Lade...</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.4s ease;
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}
</style>