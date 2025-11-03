<script setup>
import {defineProps, ref, watch} from 'vue';

const props = defineProps({
  role: {
    type: String,
    required: true,
  },
});

// Check if user has the right to change location
const canChangeLocation = props.role === "ROLE_MANAGEMENT";

// Reactive variable to store the selected location
const selectedLocation = ref('Baden');

// List of locations
const locations = ref(['Baden', 'Wiener Neustadt', 'Traiskirchen', 'Leopoldsdorf', 'Mariahilf']);

// Reactive variable to control dropdown visibility
const showDropdown = ref(false);

// Method to handle location selection
const selectLocation = (location) => {
  selectedLocation.value = location;
  showDropdown.value = false;
};

// otherLocations, all locations except the selected one updated on selection
const otherLocations = ref(locations.value.filter(location => location !== selectedLocation.value));

// Watcher to update otherLocations when selectedLocation changes
watch(selectedLocation, (newVal) => {
  otherLocations.value = locations.value.filter(location => location !== newVal);
});
</script>

<template>
  <div
      class="bg-window_bg rounded-[1.3rem] shadow-window"
      @mouseenter="showDropdown = true"
      @mouseleave="showDropdown = false"
      :class="(canChangeLocation && showDropdown ? 'absolute top-3 right-32 z-30' : 'relative')"
  >
    <div class="flex flex-col items-center group cursor-pointer">
      <div class="flex flex-row justify-between w-full gap-2 px-4 py-2">
        <span class="text-secondary_text text-md font-semibold group-hover:text-main transition-colors duration-100 w-16 truncate">
          {{ selectedLocation }}
        </span>
        <svg class="stroke-icon_color group-hover:stroke-main transition-colors duration-100" width="24" height="24"
             viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path
              d="M21 10C21 17 12 23 12 23C12 23 3 17 3 10C3 7.61305 3.94821 5.32387 5.63604 3.63604C7.32387 1.94821 9.61305 1 12 1C14.3869 1 16.6761 1.94821 18.364 3.63604C20.0518 5.32387 21 7.61305 21 10Z"
              stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path
              d="M12 13C13.6569 13 15 11.6569 15 10C15 8.34315 13.6569 7 12 7C10.3431 7 9 8.34315 9 10C9 11.6569 10.3431 13 12 13Z"
              stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>

      <div v-if="canChangeLocation && showDropdown" class="w-full">
        <ul>
          <li v-for="location in otherLocations" :key="location" @click="selectLocation(location)"
              class="cursor-pointer w-full hover:bg-first_accent rounded-3xl px-4 py-2 hover:text-white transition-colors duration-100"
          >
            <span class="w-24 truncate block">{{ location }}</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>