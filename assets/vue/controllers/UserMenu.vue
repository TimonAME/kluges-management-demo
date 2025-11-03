<script setup>
import Location from "../components/userMenu/Location.vue";
import ProfileHelp from "../components/userMenu/ProfileHelp.vue";
import MobileNavbar from "../components/userMenu/MobileNavbar.vue";
import {defineProps} from "vue";

const props = defineProps({
  role: {
    type: String,
    required: true,
  },
  userID: {
    type: Number,
    required: true,
  },
});
</script>

<template>
  <div class="flex flex-row sm:gap-4 gap-1 ml-2 select-none">
    <!-- TODO: find spot for location on mobile -->
    <div class="sm:block hidden">
      <Location :role="props.role"/>
    </div>

    <ProfileHelp :userID="props.userID"/>
    <div class="sm:hidden">
      <!-- Mobile navbar hidden for desktop and tablet -->
      <MobileNavbar :role="props.role"/>
    </div>
  </div>
</template>

<style scoped>

</style>