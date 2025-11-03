<script setup>
import {ref, computed, onMounted, onUnmounted} from 'vue';

let isEmpty = ref(false);
let notifications = ref([]);

const props = defineProps({
  notifications: Object,
});

const displayedNotifications = computed(() => {
  // Check if window width is less than 768px
  const isMobile = window.innerWidth < 768;
  const MAX_NOTIFICATIONS = 4;

  // Only slice the notifications if on mobile
  return isMobile ? notifications.value.slice(0, MAX_NOTIFICATIONS) : notifications.value;
});

const hasMoreNotifications = computed(() => {
  // Check if window width is less than 768px
  const isMobile = window.innerWidth < 768;
  const MAX_NOTIFICATIONS = 4;

  // Only show "more notifications" button if on mobile and exceeding limit
  return isMobile && notifications.value.length > MAX_NOTIFICATIONS;
});


// Track loading states for notifications
const loadingNotifications = ref([]);

onMounted(() => {
  notifications.value = props.notifications;
});

// toggle Check & update isEmpty
const setCheck = async (id) => {
  // Add notification ID to loading state
  loadingNotifications.value.push(id);

  try {
    const response = await fetch('/api/notification/' + id + '/read', {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ isRead: true }) // Changed from is_read to isRead
    });

    if (!response.ok) {
      throw new Error('Network response was not ok');
    }

    // Remove the notification from the list
    notifications.value = notifications.value.filter(notification => notification.id !== id);
    isEmpty.value = notifications.value.length === 0;
  } catch (error) {
    console.error('Error updating notification:', error);
  } finally {
    // Remove notification ID from loading state
    loadingNotifications.value = loadingNotifications.value.filter(
        loadingId => loadingId !== id
    );
  }
};

// Navigate to notifications page
const goToNotificationsPage = () => {
  window.location.href = '/benachrichtigungen';
};

// Function to format notification text (e.g. highlight dates)
const formatNotificationMessage = (message) => {
  const dateRegex = /\b(\d{2})\.(\d{2})\.(\d{4})\b/g;
  return message.replace(dateRegex, '<span class="italic">$&</span>');
};
</script>

<template>
  <div class="sm:h-[100%] max-h-96 lg:max-h-none w-full bg-window_bg rounded-window shadow-window overflow-hidden flex flex-col">
    <!-- Header -->
    <div class="px-4 pt-2 border-b border-gray-100">
      <div class="flex items-center justify-between">
        <h2 class="text-primary_text text-base font-semibold">Benachrichtigungen</h2>
        <div class="flex items-center gap-2">
          <!-- Navigation Icon -->
          <button @click="goToNotificationsPage()"
                  aria-label="Zur Benachrichtigungs Liste navigieren"
                  class="p-1.5 rounded-window hover:bg-gray-100 transition-colors">
            <svg class="w-5 h-5 stroke-secondary_text" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M7 17L17 7M7 7H17V17" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <div class="flex-grow w-full overflow-y-auto">
      <p class="px-4 text-secondary_text text-sm mb-1 mt-1 w-fit">Neuigkeiten und Änderungen und Tipps</p>
      <table class="min-w-full">
        <transition-group name="fade" tag="tbody" class="divide-y divide-gray-200">
          <tr v-for="(notification) in displayedNotifications" :key="notification.id"
              class="hover:bg-first_accent hover:bg-opacity-20" @click="setCheck(notification.id)">
            <td class="w-7 cursor-pointer px-4" :class="[
              loadingNotifications.includes(notification.id) ? 'animate-pulse opacity-50' : ''
            ]">
              <svg
                  class="w-4 h-4 border p-0 rounded-window transition-colors duration-75 ease-in stroke-icon_color border-icon_color"
                  viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path v-if="loadingNotifications.includes(notification.id)" d="M20 6L9 17L4 12" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round" />
              </svg>
            </td>
            <td class="flex flex-row flex-wrap justify-between items-center cursor-pointer  my-1" :class="[
              loadingNotifications.includes(notification.id) ? 'animate-pulse opacity-50' : ''
            ]">
              <div class="text-sm select-none text-secondary_text" v-html="formatNotificationMessage(notification.message)">
              </div>
              <div>
                <span v-for="tag in notification.notificationTags" :key="tag.id"
                      class="w-fit py-1 px-2 rounded-window shadow-xs transition-all  text-xs font-medium mr-1 cursor-pointer select-none"
                      :style="{ backgroundColor: tag.hex_color + '15', color: tag.hex_color }">
                  {{ tag.name }}
                </span>
              </div>
            </td>
          </tr>
        </transition-group>
      </table>

      <!-- Show All button when there are more notifications -->
      <div v-if="hasMoreNotifications" class="text-center py-2">
        <button
            @click="goToNotificationsPage()"
            class="text-sm text-primary hover:underline"
        >
          Alle Benachrichtigungen anzeigen ({{ notifications.length }})
        </button>
      </div>

      <div v-if="notifications.length === 0" class="text-center text-secondary_text py-4">
        Keine Benachrichtigungen vorhanden.
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.fade-enter,
.fade-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}
</style>
