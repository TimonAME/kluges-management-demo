<script setup>
import { defineProps, computed, ref, onMounted } from "vue";
import UserMenu from "./UserMenu.vue";
import Benachrichtigungen from "../components/dashboard/Benachrichtigungen.vue";
import TODOs from "../components/dashboard/TODOs.vue";
import Schularbeitstermine from "../components/dashboard/Schularbeitstermine.vue";
import Geburtstage from "../components/dashboard/Geburtstage.vue";
import Kalender from "../components/dashboard/Kalender.vue";

const props= defineProps({
  todos: Object,
  notifications: Object,
  birthdays: Object,
  exams: Object,
  role: {
    type: String,
    required: true,
  },
});

// Prüfen, ob es sich um ein mobiles Gerät handelt
const isMobile = ref(false);

onMounted(() => {
  checkMobileDevice();
  window.addEventListener('resize', checkMobileDevice);
});

const checkMobileDevice = () => {
  isMobile.value = window.innerWidth < 768; // md breakpoint in Tailwind
};

// Roles: ROLE_MANAGEMENT, ROLE_LOCATION_MANAGEMENT, ROLE_OFFICE, ROLE_TEACHER, ROLE_STUDENT, ROLE_GUARDIAN, ROLE_MARKETING
// Pages: Benachrichtigungen, TODOs, Kalender, Geburtstage, Schularbeitstermine
// different views to determine which components to show
const views = {
  '5': ['ROLE_MANAGEMENT', 'ROLE_LOCATION_MANAGEMENT', 'ROLE_OFFICE', 'ROLE_TEACHER', 'ROLE_MARKETING'],
  '3': ['ROLE_STUDENT', 'ROLE_GUARDIAN'],
};

const activeView = computed(() => {
  // return the view that contains the current role
  //console.log(Object.keys(views).find((key) => views[key].includes(props.role)));
  return Object.keys(views).find((key) => views[key].includes(props.role));
});
</script>

<template>
  <!-- Mobile Layout -->
  <div v-if="isMobile" class="flex flex-col space-y-4">
    <!-- Benachrichtigungen -->
    <div class="bg-window_bg rounded-window shadow-window w-full h-auto">
      <Benachrichtigungen :notifications="props.notifications" />
    </div>

    <!-- TODOs -->
    <div v-if="activeView === '5'" class="bg-window_bg rounded-window shadow-window w-full h-auto">
      <TODOs :todos="props.todos"/>
    </div>

    <!-- Kalender - Mobile Ansicht mit voller Höhe -->
    <div class="bg-window_bg rounded-window shadow-window w-full h-full overflow-x-hidden">
      <Kalender />
    </div>

    <!-- Schularbeitstermine -->
    <div class="bg-window_bg rounded-window shadow-window w-full h-auto">
      <Schularbeitstermine :exams="props.exams"/>
    </div>

    <!-- Geburtstage -->
    <div v-if="activeView === '5'" class="bg-window_bg rounded-window shadow-window w-full h-auto">
      <Geburtstage :birthdays="props.birthdays" />
    </div>
  </div>

  <!-- Desktop Layout - bleibt unverändert -->
  <template v-else>
    <!-- Benachrichtigungen -->
    <div
        class="md:absolute bg-window_bg rounded-window shadow-window w-full h-auto mb-4 md:mb-0"
        :class="{
          'md:w-[calc((100%-250px-16px-8px)/2)] md:h-[calc(33%-8px)] md:left-0 md:top-0': activeView === '5',
          'md:w-[calc(30%-4px)] md:h-[calc(50%-4px)] md:right-0 md:top-0': activeView === '3'
        }"
    >
      <Benachrichtigungen :notifications="props.notifications" />
    </div>

    <!-- TODOs -->
    <div
        v-if="activeView === '5'"
        class="md:absolute bg-window_bg rounded-window shadow-window w-full md:w-[calc((100%-250px-16px-8px)/2)] h-auto md:h-[calc(33%-8px)] md:left-[calc((100%-250px-16px-8px)/2+8px)] md:top-0 mb-4 md:mb-0"
    >
      <TODOs :todos="props.todos"/>
    </div>

    <!-- Kalender -->
    <div
        class="absolute bg-window_bg rounded-window shadow-window w-full h-auto mb-4 md:mb-0"
        :class="{
          'md:w-[calc(100%-250px-16px)] md:h-[calc(67%-8px)] md:left-0 md:bottom-0': activeView === '5',
          'md:w-[calc(70%-4px)] md:h-[100%] md:left-0 md:bottom-0': activeView === '3'
        }"
    >
      <Kalender />
    </div>

    <!-- Geburtstage -->
    <div
        v-if="activeView === '5'"
        class="md:absolute bg-window_bg rounded-window shadow-window w-full md:w-[250px] h-auto md:h-[calc(50%-4px)] md:right-0 md:top-0 mb-4 md:mb-0"
    >
      <Geburtstage :birthdays="props.birthdays" />
    </div>

    <!-- Schularbeitstermine -->
    <div
        class="md:absolute bg-window_bg rounded-window shadow-window w-full h-auto mb-4 md:mb-0"
        :class="{
          'md:w-[250px] md:h-[calc(50%-4px)] md:right-0 md:bottom-0': activeView === '5',
          'md:w-[calc(30%-4px)] md:h-[calc(50%-4px)] md:right-0 md:bottom-0': activeView === '3'
        }"
    >
      <Schularbeitstermine :exams="props.exams"/>
    </div>
  </template>
</template>