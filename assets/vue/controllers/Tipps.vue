<script setup>
import { onMounted, ref, computed } from "vue";

/*
 * TODO: tip can be deleted from the author
 * TODO: tip can be edited from the author
 */

const props = defineProps({
  tipps: {
    type: Array,
    required: true
  },
  role: {
    type: String,
    required: true,
  },
});

const tips = ref([]);
const groupedTips = ref({});
const selectedTip = ref(null);
const expandedCategory = ref('Lernmethoden');
const loadingTips = ref([]);
const isAddingTip = ref(false);
const role = ref('');
const categories = ref([]);

const newTip = ref({
  title: '',
  message: '',
  tipCategory: ''
});

onMounted(async () => {
  tips.value = props.tipps;
  console.log('Tips:', tips.value);
  role.value = props.role;

  await fetchCategories();

  // Group tips by category
  tips.value.forEach(tip => {
    if (!groupedTips.value[tip.category]) {
      groupedTips.value[tip.category] = [];
    }
    groupedTips.value[tip.category].push(tip);
  });
});

const canAddTip = computed(() => {
  return role.value === 'ROLE_MANAGEMENT' || role.value === 'ROLE_LOCATION_MANAGEMENT' || role.value === 'ROLE_OFFICE';
});

const markAsRead = async (tipId) => {
  try {
    loadingTips.value.push(tipId);
    const response = await fetch(`/api/tip/${tipId}/read`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ readAt: new Date().toISOString() })
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    // Update the tip in the tips array
    const index = tips.value.findIndex(tip => tip.id === tipId);
    if (index !== -1) {
      // Keep existing tip data and update readAt
      tips.value[index] = {
        ...tips.value[index],
        readAt: new Date().toISOString()
      };

      // Update grouped tips
      const category = tips.value[index].category;
      const categoryIndex = groupedTips.value[category].findIndex(tip => tip.id === tipId);
      if (categoryIndex !== -1) {
        groupedTips.value[category][categoryIndex] = tips.value[index];
      }
    }
  } catch (error) {
    console.error('Error marking tip as read:', error);
  } finally {
    loadingTips.value = loadingTips.value.filter(id => id !== tipId);
  }
};

const addNewTip = async () => {
  if (!newTip.value.title || !newTip.value.message || !newTip.value.tipCategory) {
    return;
  }
  try {
    const selectedCategory = categories.value.find(cat => cat.name === newTip.value.tipCategory);

    const response = await fetch('/api/tip', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        title: newTip.value.title,
        message: newTip.value.message,
        tipCategory: {
          id: selectedCategory.id,
          name: selectedCategory.name
        }
      })
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    const createdTip = await response.json();

    // Add new tip to lists
    const tipToAdd = {
      id: createdTip.id,
      title: createdTip.title,
      message: newTip.value.message,
      category: selectedCategory.name,
      creationDate: new Date().toISOString(),
      isNew: true // Add this flag
    };

    tips.value.unshift(tipToAdd);
    if (!groupedTips.value[tipToAdd.category]) {
      groupedTips.value[tipToAdd.category] = [];
    }
    groupedTips.value[tipToAdd.category].unshift(tipToAdd);

    // Reset form
    newTip.value = {
      title: '',
      message: '',
      tipCategory: ''
    };
    isAddingTip.value = false;
  } catch (error) {
    console.error('Error creating tip:', error);
  }
};

// Add this to your onMounted function
const fetchCategories = async () => {
  try {
    const response = await fetch('/api/tipCategory');
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    categories.value = await response.json();
  } catch (error) {
    console.error('Error fetching categories:', error);
    categories.value = []; // Fallback to empty array
  }
};
</script>

<template>
  <div class="lg:absolute bg-window_bg rounded-window shadow-window h-[100%] left-0 bottom-0 mt-2 lg:mt-0 w-[100%] lg:w-[calc(100%-450px-16px)]">
    <!-- Tips list -->
    <div class="overflow-y-auto h-full p-2">
      <div v-for="(categoryTips, category) in groupedTips" :key="category" class="mb-4">
        <div
            @click="expandedCategory = expandedCategory === category ? null : category"
            class=" mb-2 group cursor-pointer"
        >
          <div class="flex items-center justify-between bg-window_bg rounded-window shadow-sm border border-gray-100 hover:border-second_accent transition-all duration-200">
            <div class="flex items-center flex-grow p-3">
              <!-- Icon basierend auf Kategorie -->
              <div class="p-2 rounded-window bg-second_accent bg-opacity-10 mr-3">
                <svg
                    v-if="category === 'Lernmethoden'"
                    class="w-5 h-5 stroke-second_accent"
                    viewBox="0 0 24 24"
                    fill="none"
                >
                  <path d="M22 19C22 19.5304 21.7893 20.0391 21.4142 20.4142C21.0391 20.7893 20.5304 21 20 21H4C3.46957 21 2.96086 20.7893 2.58579 20.4142C2.21071 20.0391 2 19.5304 2 19V5C2 4.46957 2.21071 3.96086 2.58579 3.58579C2.96086 3.21071 3.46957 3 4 3H9L11 6H20C20.5304 6 21.0391 6.21071 21.4142 6.58579C21.7893 6.96086 22 7.46957 22 8V19Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <svg
                    v-else-if="category === 'Buchempfehlungen'"
                    class="w-5 h-5 stroke-second_accent"
                    viewBox="0 0 24 24"
                    fill="none"
                >
                  <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <svg
                    v-else-if="category === 'Einführungen'"
                    class="w-5 h-5 stroke-second_accent"
                    viewBox="0 0 24 24"
                    fill="none"
                >
                  <path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <svg
                    v-else
                    class="w-5 h-5 stroke-second_accent"
                    viewBox="0 0 24 24"
                    fill="none"
                >
                  <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>

              <div class="flex-grow">
                <h2 class="text-lg font-semibold text-gray-800">{{ category }}</h2>
                <span class="text-sm text-gray-500">{{ categoryTips.length }} Tipps</span>
              </div>
            </div>

            <div class="pr-4">
              <svg
                  :class="{'rotate-180 text-second_accent': expandedCategory === category}"
                  class="w-5 h-5 transition-all duration-200 text-gray-400 group-hover:text-second_accent"
                  viewBox="0 0 24 24"
                  fill="none"
              >
                <path d="M19 9l-7 7-7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </div>
        </div>

        <div v-show="expandedCategory === category" class="space-y-2 p-4">
          <div
              v-for="tip in categoryTips"
              :key="tip.id"
              @click="selectedTip = tip"
              class="flex items-center p-4 rounded-window shadow-sm border transition-all duration-200"
              :class="{
                'border-gray-100 hover:shadow-md': !tip.readAt,
                'border-gray-50 bg-gray-50': tip.readAt,
              }"
          >
            <div class="flex-grow">
              <h3 class="font-medium">{{ tip.title }}</h3>
              <p v-if="tip.message.length < 100" class="text-sm text-gray-600 mt-1">{{ tip.message }}</p>
              <p v-else class="text-sm text-gray-600 mt-1">{{ tip.message.substring(0, 100) }} ... </p>
              <div v-if="!tip.isNew" class="flex items-center mt-2 text-sm text-gray-500">
                <span>{{ new Date(tip.creationDate.date).toLocaleDateString() }}</span>
                <span class="mx-2">•</span>
                <span>{{ tip.creatorFirstName }} {{ tip.creatorLastName }}</span>
              </div>
            </div>

            <button
                v-if="!tip.readAt"
                @click.stop="markAsRead(tip.id)"
                class="ml-4 p-2 text-second_accent hover:bg-second_accent hover:bg-opacity-10 rounded-full"
                :disabled="loadingTips.includes(tip.id)"
                title="Gelesen"
            >
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none">
                <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="lg:absolute bg-window_bg rounded-window shadow-window w-[100%] lg:w-[450px] h-[calc(100%)] right-0 bottom-0 mt-4 lg:mt-0 p-2">
    <div v-if="isAddingTip" class="h-full">
      <h2 class="text-xl font-semibold mb-4">Neuen Tipp erstellen</h2>
      <form @submit.prevent="addNewTip" class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">Titel</label>
          <input
              v-model="newTip.title"
              type="text"
              class="w-full p-2 rounded-window border border-gray-300 focus:ring-2 focus:ring-second_accent focus:border-transparent"
              required
          />
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Kategorie</label>
          <select
              v-model="newTip.tipCategory"
              class="w-full p-2 rounded-window border border-gray-300 focus:ring-2 focus:ring-second_accent focus:border-transparent"
              required
          >
            <option value="" disabled selected>Bitte wählen</option>
            <option value="Lernmethoden">Lernmethoden</option>
            <option value="Buchempfehlungen">Buchempfehlungen</option>
            <option value="Einführungen">Einführungen</option>
            <option value="Sonstiges">Sonstiges</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Nachricht</label>
          <textarea
              v-model="newTip.message"
              rows="4"
              class="w-full p-2 rounded-window border border-gray-300 focus:ring-2 focus:ring-second_accent focus:border-transparent"
              required
          ></textarea>
        </div>

        <div class="flex gap-2">
          <button
              type="submit"
              class="flex-1 px-4 py-2 bg-second_accent text-white rounded-window hover:bg-opacity-90"
          >
            Speichern
          </button>
          <button
              type="button"
              @click="isAddingTip = false"
              class="flex-1 px-4 py-2 border border-gray-300 rounded-window hover:bg-gray-50"
          >
            Abbrechen
          </button>
        </div>
      </form>
    </div>

    <div v-else-if="selectedTip" class="h-full">
      <div class="flex flex-row justify-between w-full">
        <h2 class="text-xl font-semibold mb-2">{{ selectedTip.title }}</h2>

        <!-- close tipp button -->
        <button
            @click="selectedTip = null"
            class="p-1.5 rounded-window hover:bg-gray-100 transition-colors"
        >
          <svg class="w-5 h-5 stroke-secondary_text" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M18 6L6 18" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M6 6L18 18" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
      </div>
      <div class="flex items-center text-sm text-gray-500 mb-4">
        <span>{{ new Date(selectedTip.creationDate.date).toLocaleDateString() }}</span>
        <span class="mx-2">•</span>
        <span>{{ selectedTip.creatorFirstName }} {{ selectedTip.creatorLastName }}</span>
      </div>
      <p class="text-gray-700 whitespace-pre-wrap">{{ selectedTip.message }}</p>
    </div>

    <div v-else class="h-full flex flex-col items-center justify-center text-gray-500">
      <div class="flex flex-col justify-between h-full w-full">
        <button
            v-if="canAddTip"
            @click="isAddingTip = true"
            class="w-full self-end flex items-center gap-2 px-4 py-2 bg-second_accent text-white rounded-window hover:shadow-lg transition-shadow"
        >
          <svg class="h-4 w-4 stroke-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 5V19" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M5 12H19" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span class="md:block hidden whitespace-nowrap">Neuer Tipp</span>
        </button>
        <div class="flex flex-col items-center justify-center flex-grow text-center">
          Wählen Sie einen Tipp aus, um Details zu sehen oder erstellen sie einen neuen Tipp.
        </div>
      </div>
    </div>
  </div>
</template>