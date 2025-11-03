<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
  user_id: {
    type: Number,
    required: true
  }
});

const subjects = ref([]);
const userSubjects = ref([]);
const loading = ref(true);
const showForm = ref(false);
const newSubject = ref({
  name: '',
  color_hex_code: '#6B7280'
});

onMounted(fetchData);

async function fetchData() {
  try {
    const [allSubjectsResponse, userSubjectsResponse] = await Promise.all([
      fetch('/api/subject/'),
      fetch(`/api/user/${props.user_id}/subjects`)
    ]);

    subjects.value = await allSubjectsResponse.json();

    // Get user subjects directly from the response array
    userSubjects.value = await userSubjectsResponse.json();
  } catch (error) {
    console.error('Error fetching data:', error);
  } finally {
    loading.value = false;
  }
}

const isUserSubject = (subjectId) => {
  return userSubjects.value.some(subject => subject.id === subjectId);
};

const getAvailableSubjects = () => {
  // Filter out subjects that the user already has
  return subjects.value.filter(subject =>
      !userSubjects.value.some(userSubject =>
          userSubject.id === subject.id
      )
  );
};

// Function to generate a lighter version of a color for background
const getLighterColor = (hexColor) => {
  // Add 15% opacity to the hex color (similar to adding "15" in the user list component)
  return `${hexColor}15`;
};

async function toggleSubject(subjectId) {
  const isEnrolled = isUserSubject(subjectId);

  try {
    const response = await fetch(`/api/user/${props.user_id}/subjects`, {
      method: isEnrolled ? 'DELETE' : 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        subjectId: subjectId
      })
    });

    if (!response.ok) throw new Error(`Failed to ${isEnrolled ? 'remove' : 'add'} subject`);

    // Update local state
    if (isEnrolled) {
      userSubjects.value = userSubjects.value.filter(subject => subject.id !== subjectId);
    } else {
      const subject = subjects.value.find(s => s.id === subjectId);
      if (subject) userSubjects.value.push(subject);
    }
  } catch (error) {
    console.error('Error updating subject:', error);
  }
}

async function handleAddSubject() {
  if (!newSubject.value.name) return;

  try {
    const response = await fetch('/api/subjects', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        name: newSubject.value.name,
        color_hex_code: newSubject.value.color_hex_code
      })
    });

    if (!response.ok) {
      throw new Error('Failed to create subject');
    }

    const subject = await response.json();
    subjects.value.push(subject);

    // Reset form
    showForm.value = false;
    newSubject.value = {
      name: '',
      color_hex_code: '#6B7280'
    };

  } catch (error) {
    console.error('Error creating subject:', error);
  }
}
</script>

<template>
  <div class="h-full p-2">
    <h2 class="text-primary_text text-lg font-semibold mb-1 select-none px-2">Meine Fächer</h2>

    <div class="flex-grow w-full overflow-y-auto select-none p-2 pb-10">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-4">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-second_accent mx-auto"></div>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <!-- User Subjects with updated styling -->
        <div v-for="subject in userSubjects" :key="subject.id"
             class="group relative flex items-center justify-between p-3 rounded-lg shadow-sm hover:shadow-xl cursor-pointer"
             :style="{
                backgroundColor: getLighterColor(subject.color_hex_code),
                color: subject.color_hex_code
             }"
             @click="toggleSubject(subject.id)">
          <span class="text-sm font-semibold">{{ subject.name }}</span>
          <div class="bg-white bg-opacity-20 rounded-full p-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="absolute inset-0 bg-black opacity-0  rounded-lg transition-opacity"/>
        </div>

        <!-- Available Subjects (not enrolled) with updated styling -->
        <div v-for="subject in getAvailableSubjects()" :key="subject.id"
             class="group relative flex items-center justify-between p-3 rounded-lg shadow-sm hover:shadow-xl cursor-pointer opacity-30 "
             :style="{
                backgroundColor: getLighterColor(subject.color_hex_code),
                color: subject.color_hex_code
             }"
             @click="toggleSubject(subject.id)">
          <span class="text-sm font-semibold">{{ subject.name }}</span>
          <div class="bg-white bg-opacity-20 rounded-full p-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="absolute inset-0 bg-black opacity-0  rounded-lg transition-opacity"/>
        </div>

        <!-- Add Subject Button -->
        <button v-if="!showForm" @click="showForm = true"
                class="group flex items-center justify-center p-3 rounded-lg shadow-sm hover:shadow-xl bg-gray-100 border-2 border-dashed border-gray-300">
          <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span class="text-gray-500 text-sm font-medium ml-2">Neues Fach erstellen</span>
        </button>
      </div>

      <!-- Add Subject Form with updated preview -->
      <form v-if="showForm" @submit.prevent="handleAddSubject" class="mt-8 pb-10">
        <h2 class="text-primary_text text-lg font-semibold">Neues Fach hinzufügen</h2>
        <p class="text-primary_text mb-4">Gehen Sie sicher, dass die Schrift gut lesbar ist.</p>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input v-model="newSubject.name" type="text" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                   placeholder="Fachname eingeben"/>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Farbe</label>
            <input v-model="newSubject.color_hex_code" type="color"
                   class="w-full h-10 px-1 py-1 border border-gray-300 rounded-md cursor-pointer"/>
          </div>

          <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Vorschau</label>
            <!-- Updated preview to match new styling -->
            <div class="flex flex-col space-y-3">
              <!-- Preview as it will appear in user subjects list -->
              <div :style="{
                    backgroundColor: getLighterColor(newSubject.color_hex_code),
                    color: newSubject.color_hex_code
                  }"
                   class="flex items-center justify-between p-3 rounded-lg shadow-sm">
                <span class="text-sm font-semibold">{{ newSubject.name || 'Fachname' }}</span>
              </div>

              <!-- Preview as it will appear in user list -->
              <p class="text-xs text-gray-500 mt-1">So wird es in der Benutzerliste angezeigt:</p>
              <span class="px-2 py-1 rounded-lg text-sm font-medium self-start"
                    :style="{
                      backgroundColor: getLighterColor(newSubject.color_hex_code),
                      color: newSubject.color_hex_code
                    }">
                {{ newSubject.name || 'Fachname' }}
              </span>
            </div>
          </div>

          <div class="flex justify-end space-x-3 mt-4">
            <button type="button" @click="showForm = false"
                    class="mt-2 px-6 py-3 w-full rounded-window border-icon_color border font-semibold text-primary_text bg-window_bg opacity-80 hover:opacity-100 shadow-none hover:shadow-xl transition-all duration-200">
              Abbrechen
            </button>
            <button type="submit"
                    class="mt-2 px-6 py-3 w-full rounded-window font-semibold text-white bg-main opacity-80 hover:opacity-100 shadow-none hover:shadow-xl transition-all duration-200"
                    :disabled="!newSubject.name">
              Hinzufügen
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>