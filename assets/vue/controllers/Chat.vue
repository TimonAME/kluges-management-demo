<script setup>
import {defineProps, ref, onMounted, watch, nextTick} from 'vue';
import debounce from 'lodash.debounce';

const props = defineProps({
  conversations: Array,
  chatID: Number,
  currentUserId: Number,
  otherUser: Array,
  messages: Array
});

const users = ref([]);
const conversations = ref(props.conversations || []);
const searchQuery = ref('');
const showDropdown = ref(false);
const isLoading = ref(false);
const newMessage = ref('');
const chatContainer = ref(null);
const ws = ref(null);
const messages = ref(props.messages || []);

const openChat = (id) => window.location.href = `/chat/${id}`;

const createChat = async (id) => {
  try {
    const response = await fetch('/api/chat/new', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
            recipient: '/api/users/'+id,
          }
      )
    });

    const result = await response.json();
    //console.log(result);

    window.location.href = `/chat/${result.id}`;
  } catch (error) {
    console.error('Error:', error);
  }
};

const searchUsers = async () => {
  if (!searchQuery.value) return;
  isLoading.value = true;
  try {
    const response = await fetch(`/api/user/chat/dropdown-users?searchTerm=${encodeURIComponent(searchQuery.value)}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
      //body: JSON.stringify({searchTerm: searchQuery.value}),
    });
    users.value = (await response.json());
    console.log(users.value);
  } catch (error) {
    console.error("Error:", error);
  } finally {
    isLoading.value = false;
  }
};

const debouncedSearch = debounce(searchUsers, 300);

watch(searchQuery, (newValue) => {
  if (newValue) {
    showDropdown.value = true;
    debouncedSearch();
  } else {
    showDropdown.value = false;
    users.value = [];
  }
});

const sendMessage = async () => {
  console.log(newMessage.value);
  console.log(props.chatID);

  if (!newMessage.value.trim()) return;

  try {
    // Persist the message using the API
    const response = await fetch('/api/chat/send-message', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        conversation: '/api/conversations/' + props.chatID,
        message: newMessage.value,
      }),
    });

    const result = await response.json();
    console.log(result);

    // Format the created_at to match the expected format for formatTime
    if (result.created_at && typeof result.created_at === 'string') {
      result.created_at = {
        date: result.created_at,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
      };
    }

    // Add the message to the local state
    messages.value.push(result);
    console.log('Message sent:', result.content);

    // Send the message through the WebSocket
    if (ws.value && ws.value.readyState === WebSocket.OPEN) {
      ws.value.send(JSON.stringify({
        type: 'chat_message',
        ...result,
        conversation: props.chatID,
        receiverId: props.otherUser[0].id
      }))
    } else {
      console.error('WebSocket connection is not open');
    }

    await nextTick(() => {
      scrollToBottom();
    });
  } catch (error) {
    console.error('Error:', error);
  } finally {
    newMessage.value = ''

    // resize the textarea
    const textarea = document.querySelector('textarea')
    if (textarea) {
      textarea.style.height = 'auto'
    }

    // reload the conversation list
    conversations.value = await fetch('/api/chat/conversations').then(res => res.json())
    //console.log(await fetch('/api/chat/conversations').then(res => res.json()))
  }
}

onMounted(() => {
  // In production:
  /*
  const wsProtocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
  const wsHost = window.location.hostname;
  ws.value = new WebSocket(`${wsProtocol}//${wsHost}/ws/`);
  */

  // In development:
  ws.value = new WebSocket('ws://localhost:2346/');

  ws.value.onopen = () => {
    console.log('WebSocket connection established')

    // Authenticate the user
    ws.value.send(JSON.stringify({
      type: 'authenticate',
      userId: props.currentUserId
    }))

    // Join the current chat
    ws.value.send(JSON.stringify({
      type: 'join_chat',
      chatId: props.chatID
    }))
  }

  ws.value.onmessage = (event) => {
    const message = JSON.parse(event.data)

    // Format the timestamp for received messages
    if (message.created_at && typeof message.created_at === 'string') {
      message.created_at = {
        date: message.created_at,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
      };
    }

    // Only add message if it's for this specific chat
    if (message.conversation === props.chatID) {
      messages.value.push(message)
      nextTick(scrollToBottom)
      console.log('Message received:', message)
    }
  }

  ws.value.onerror = (error) => {
    console.error('WebSocket error:', error);
  };

  ws.value.onclose = () => {
    console.log('WebSocket connection closed');
  };

  if (chatContainer.value && messages.value?.length > 0) {
    nextTick(() => {
      scrollToBottom();
    });
  }
});

// Add a helper function to scroll to bottom
const scrollToBottom = () => {
  if (chatContainer.value) {
    chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
  }
};

// Add a watch for messages
watch(() => messages.value, (newMessages) => {
  if (newMessages?.length > 0) {
    nextTick(() => {
      scrollToBottom();
    });
  }
}, {deep: true});

const formatTime = (dateTimeObj) => {
  if (!dateTimeObj) return '';

  // Handle case where dateTimeObj is a string (direct timestamp)
  let date;
  let timezone;

  if (typeof dateTimeObj === 'string') {
    date = new Date(dateTimeObj);
    timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
  } else if (dateTimeObj && dateTimeObj.date) {
    date = new Date(dateTimeObj.date);
    timezone = dateTimeObj.timezone;
  } else {
    return '';
  }

  const now = new Date();
  const yesterday = new Date(now);
  yesterday.setDate(yesterday.getDate() - 1);

  // Format time
  const timeString = date.toLocaleTimeString('de-DE', {
    hour: '2-digit',
    minute: '2-digit',
    timeZone: timezone
  });

  // If message is from today
  if (date.toDateString() === now.toDateString()) {
    return timeString;
  }

  // If message is from yesterday
  if (date.toDateString() === yesterday.toDateString()) {
    return `Yesterday, ${timeString}`;
  }

  // If message is from this year
  if (date.getFullYear() === now.getFullYear()) {
    return date.toLocaleDateString('de-DE', {
      month: 'short',
      day: 'numeric',
      timeZone: timezone
    }) + `, ${timeString}`;
  }

  // If message is from a different year
  return date.toLocaleDateString('de-DE', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    timeZone: timezone
  }) + `, ${timeString}`;
};

const autoResize = (event) => {
  const textarea = event.target;
  textarea.style.height = 'auto';
  textarea.style.height = textarea.scrollHeight + 'px';
};

const goToUser = (id) => {
  window.location.href = `/user/${id}`;
};


/*********** Profile Picture ***********/

/*********** Profile Picture ***********/

</script>

<template>
  <div
      class="absolute bg-window_bg rounded-window shadow-window md:w-[300px] w-full h-[100%] left-0 bottom-0 z-20
              transition-transform duration-300 ease-in-out"
      :class="chatID ? 'md:block hidden' : ''"
  >
    <div class="h-[100%] overflow-y-auto p-2">
      <!-- Search -->
      <div class="relative">
        <input
            v-model="searchQuery"
            type="text"
            class="w-full pl-10 pr-4 py-2 bg-window_bg border border-gray-200 rounded-window focus:ring-2 focus:ring-first_accent focus:border-transparent"
            placeholder="Benutzer suchen..."
        >
        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" viewBox="0 0 24 24" fill="none">
          <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke="currentColor" stroke-width="2"
                stroke-linecap="round"/>
        </svg>
        <div v-if="isLoading" class="absolute right-3 top-2.5">
          <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
               viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
        </div>
      </div>

      <!-- Search Results -->
      <div v-if="showDropdown && users.length > 0" class="mt-2 space-y-2">
        <div
            v-for="user in users"
            :key="user.id"
            @click="createChat(user.id)"
            class="flex items-center p-3 rounded-window hover:bg-first_accent hover:bg-opacity-20 cursor-pointer"
        >
          <div class="flex items-center justify-center w-10 h-10 bg-second_accent bg-opacity-20 rounded-full">
            <div class="text-sm font-bold text-second_accent">{{ user.first_name[0] }}</div>
          </div>
          <div class="ml-3">
            <p class="font-medium">{{ user.first_name }} {{ user.last_name }}</p>
            <p class="text-sm text-gray-500">{{ user.email }}</p>
          </div>
        </div>
      </div>

      <!-- No Results -->
      <div v-if="showDropdown && searchQuery && !users.length && !isLoading" class="mt-2 p-4 text-center text-gray-500">
        No users found
      </div>

      <!-- Existing Conversations -->
      <div class="mt-4 space-y-2">
        <h3 class="font-medium text-gray-700 px-3">Aktuelle Gespräche</h3>
        <div
            v-for="conv in conversations"
            :key="conv.id"
            @click="openChat(conv[0].id)"
            class="flex items-center p-3 rounded-window cursor-pointer transition-colors duration-200"
            :class="[
              chatID === conv[0].id
                ? 'bg-gray-200'
                : 'hover:bg-gray-100'
            ]"
        >
          <div class="flex items-center justify-center w-10 h-10 bg-second_accent bg-opacity-20 rounded-full">
            <div class="text-sm font-bold text-second_accent">{{ conv.partnerUser[0] }}</div>
          </div>
          <div class="ml-3 min-w-0 flex-1">
            <div class="flex justify-between items-center gap-1">
              <p class="font-medium truncate max-w-[140px]">{{ conv.partnerUser }}</p>
              <span v-if="conv[0].last_message?.created_at" class="text-xs flex-shrink-0 text-gray-500">
                {{ formatTime(conv[0].last_message.created_at) }}
              </span>
            </div>
            <p class="text-sm truncate text-gray-500">
              {{ conv[0].last_message?.content }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="absolute bg-window_bg rounded-window shadow-window md:w-[calc(100%-300px-16px)] w-full h-[100%] right-0 bottom-0 z-10">
    <div v-if="chatID !== null" class="h-full flex flex-col">
      <!-- Chat Header -->
      <div class="p-4 border-b flex justify-between items-center">
        <div class="flex items-center">
          <div class="flex items-center justify-center w-10 h-10 bg-second_accent bg-opacity-20 rounded-full mr-3">
            <div class="text-sm font-bold text-second_accent">{{ otherUser[0]?.first_name[0] }}</div>
          </div>
          <h2 class="font-medium cursor-pointer" @click="goToUser(otherUser[0]?.id)">
            {{ otherUser[0]?.first_name }} {{ otherUser[0]?.last_name }}
          </h2>
        </div>
        <!-- Mobile "close" button to navigate back to chat list "/chat" -->
        <a href="/chat" class="md:hidden">
          <svg class="w-6 h-6 text-gray-500" viewBox="0 0 24 24" fill="none">
            <path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                  stroke-linejoin="round"/>
          </svg>
        </a>
      </div>

      <!-- Messages -->
      <div ref="chatContainer" class="flex-1 overflow-y-auto p-4 space-y-4">
        <div v-for="message in messages" :key="message.id"
             :class="['flex', message.sender?.id === otherUser[0]?.id ? 'justify-start' : 'justify-end']">
          <div class="flex flex-col">
            <div class="flex items-end gap-2" :class="{'flex-row-reverse': message.sender?.id !== otherUser[0]?.id}">
              <div class="flex items-center justify-center w-8 h-8 bg-second_accent bg-opacity-20 rounded-full">
                <div class="text-sm font-bold text-second_accent">{{ message.sender?.first_name[0] }}</div>
              </div>
              <div :class="['max-w-[70%] rounded-lg p-3',
                     message.sender?.id === otherUser[0]?.id ? 'bg-gray-100' : 'bg-main text-white']">
                {{ message.content }}
              </div>
            </div>
            <span class="text-xs text-gray-500 mt-1"
                  :class="{'text-right': message.sender?.id !== otherUser[0]?.id}">
              {{ formatTime(message.created_at) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Message Input -->
      <div class="p-4 border-t">
        <form @submit.prevent="sendMessage" class="flex gap-2">
          <textarea
              v-model="newMessage"
              @input="autoResize"
              @keydown.enter.exact.prevent="sendMessage"
              @keydown.enter.shift.exact.prevent="newMessage += '\n'"
              rows="1"
              class="flex-1 rounded-lg border p-2 focus:ring-2 focus:ring-first_accent focus:outline-none focus:border-transparent resize-none"
              placeholder="Nachricht eingeben"
              style="min-height: 40px; max-height: 200px;"
          ></textarea>
          <button type="submit"
                  class="h-fit bg-main text-white rounded-lg px-4 py-2 hover:first_accent">
            Senden
          </button>
        </form>
      </div>
    </div>

    <div v-else class="h-full flex items-center justify-center text-gray-500">
      Wählen Sie eine Konversation aus oder starten Sie einen neuen Chat
    </div>
  </div>
</template>

<style scoped>
textarea::-webkit-scrollbar {
  display: none; /* Hide the scrollbar for WebKit browsers */
}
</style>