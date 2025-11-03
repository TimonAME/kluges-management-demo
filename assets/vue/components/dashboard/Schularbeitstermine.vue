<script setup>
import { ref, onMounted } from 'vue';

// Calculate the date 31 days from now
const today = new Date();
const nextMonth = new Date(today);
nextMonth.setDate(today.getDate() + 31);

const exams = ref([]);
const groupedExams = ref({});
const props = defineProps({
  exams: Object,
});

onMounted(() => {
  exams.value = props.exams;

  // Group exams by date and initialize showExams property
  exams.value.forEach(exam => {
    const date = new Date(exam.date.date).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' });
    const examDate = new Date(exam.date.date);
    if (!groupedExams.value[date]) {
      groupedExams.value[date] = {
        exams: [],
        showExams: examDate <= nextMonth
      };
    }
    groupedExams.value[date].exams.push(exam);
  });
});

// Toggle the visibility of exams for a specific date
const toggleExams = (date) => {
  groupedExams.value[date].showExams = !groupedExams.value[date].showExams;
};

// Navigate to user profile
const personSelected = (person) => {
  window.location.href = `/user/${person}`;
};
</script>

<template>
  <div class="sm:h-[100%] w-full bg-window_bg rounded-window shadow-window overflow-hidden flex flex-col">
    <!-- Header -->
    <div class="px-4 pt-2 border-b border-gray-100">
      <div class="flex items-center justify-between">
        <h2 class="text-primary_text text-base font-semibold">Schularbeitstermine</h2>
        <div class="flex items-center gap-2">
          <div class="p-1.5">
            <svg class="w-5 h-5 stroke-window_bg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M7 17L17 7M7 7H17V17" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Exam List -->
    <div class="flex-1 overflow-y-auto select-none">
      <div class="p-2 space-y-2">
        <div v-for="(group, date) in groupedExams" :key="date">
          <!-- Date Header -->
          <div
              @click="toggleExams(date)"
              class="flex items-center justify-between px-2 py-2 cursor-pointer hover:bg-gray-50 transition-colors rounded-window"
          >
            <div class="flex items-center space-x-2">
              <span class="text-sm font-semibold text-primary_text">{{ date }}</span>
              <span class="px-2 py-0.5 text-xs font-medium text-second_accent bg-second_accent bg-opacity-10 rounded-full">
                {{ group.exams.length }} {{ group.exams.length > 1 ? 'Prüfungen' : 'Prüfung' }}
              </span>
            </div>
            <svg
                class="w-4 h-4 stroke-icon_color transition-transform duration-200"
                :class="{ 'rotate-180': !group.showExams }"
                viewBox="0 0 24 24"
                fill="none"
            >
              <path d="M6 9L12 15L18 9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>

          <!-- Exam Details -->
          <div
              v-show="group.showExams"
              class="py-1"
          >
            <div
                v-for="exam in group.exams"
                :key="exam.id"
                class="relative group mb-2 last:mb-0"
            >
              <div
                  class="p-3 rounded-lg transition-all"
                  :style="{ backgroundColor: exam.subject.color_hex_code + '15' }"
              >
                <div class="flex items-center justify-between">
                  <div class="flex flex-col gap-1">
                    <!-- Subject -->
                    <div class="text-sm font-medium" :style="{ color: exam.subject.color_hex_code }">
                      {{ exam.subject.name }}
                    </div>
                    <!-- Student Name -->
                    <button
                        @click.stop="personSelected(exam.user_taking_exam.id)"
                        class="text-xs text-icon_color hover:underline transition-colors flex items-center gap-1"
                    >
                      <svg class="w-3 h-3 stroke-icon_color" viewBox="0 0 24 24" fill="none">
                        <path d="M20 21V19C20 16.7909 18.2091 15 16 15H8C5.79086 15 4 16.7909 4 19V21M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      {{ exam.user_taking_exam.first_name }} {{ exam.user_taking_exam.last_name }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="Object.keys(groupedExams).length === 0" class="text-center text-secondary_text py-4">
          Keine bevorstehenden Schularbeiten oder Prüfungen.
        </div>
      </div>
    </div>
  </div>
</template>


<style scoped>
/* Für das Ausklappen */
.slide-fade-enter-active {
  transition: all 0.5s ease;
}

.slide-fade-enter-from {
  max-height: 0;
  opacity: 0;
  overflow: hidden;
}

.slide-fade-enter-to {
  max-height: 500px;
  /* Schätzwert, kann je nach Inhalt angepasst werden */
  opacity: 1;
}

/* Für das Einklappen */
.slide-fade-leave-active {
  transition: max-height 0.3s ease, opacity 0.3s ease;
  transition-delay: 0s;
  /* Einklappen soll sofort beginnen */
}

.slide-fade-leave-from {
  max-height: 500px;
  opacity: 1;
}

.slide-fade-leave-to {
  max-height: 0;
  opacity: 0;
  overflow: hidden;
}
</style>
