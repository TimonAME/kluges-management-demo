<script setup>
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
  user_id: {
    type: Number,
    required: true
  }
});

const notifications = ref([]);
const loading = ref(true);
const activeView = ref('unread'); // 'unread' or 'read'

onMounted(async () => {
  await fetchNotifications();
});

const fetchNotifications = async () => {
  loading.value = true;
  try {
    const category = activeView.value;
    const response = await fetch(`/api/user/${props.user_id}/notifications?category=${category}`);
    const data = await response.json();
    notifications.value = data;
  } catch (error) {
    console.error('Error fetching notifications:', error);
  } finally {
    loading.value = false;
  }
};

// Watch for activeView changes to refetch notifications
watch(activeView, async () => {
  await fetchNotifications();
});

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleString('de-DE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>

<template>
  <div class="h-full p-2">
    <!-- Category Buttons -->
    <div class="flex space-x-2 mb-4">
      <button
          @click="activeView = 'unread'"
          class="px-4 py-2 rounded-lg transition-colors flex items-center gap-2"
          :class="activeView === 'unread' ? 'bg-second_accent text-white' : 'hover:bg-gray-100 text-gray-700'"
      >
        <svg class="w-5 h-5" :class="activeView === 'unread' ? 'stroke-white' : 'stroke-gray-600'"
             viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M22 6L12 13L2 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Ungelesen
      </button>
      <button
          @click="activeView = 'read'"
          class="px-4 py-2 rounded-lg transition-colors flex items-center gap-2"
          :class="activeView === 'read' ? 'bg-second_accent text-white' : 'hover:bg-gray-100 text-gray-700'"
      >
        <svg class="w-5 h-5" :class="activeView === 'read' ? 'stroke-white' : 'stroke-gray-600'"
             viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M20 6L9 17L4 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Gelesen
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-4">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-second_accent mx-auto"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="notifications.length === 0" class="text-center text-gray-500 py-4">
      Keine Benachrichtigungen verfügbar
    </div>

    <!-- Notifications List -->
    <div v-else class="space-y-2">
      <TransitionGroup name="list" tag="div">
        <div
            v-for="notification in notifications"
            :key="notification.id"
            class="flex items-start gap-3 bg-window_bg p-3 border-b border-gray-200 transition-all duration-200"
        >
          <div class="flex-grow">
            <p class="text-sm text-primary_text">{{ notification.message }}</p>
            <div class="flex items-center mt-2 text-xs text-secondary_text">
              <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              {{ formatDate(notification.date_created) }}
            </div>
          </div>
        </div>
      </TransitionGroup>
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