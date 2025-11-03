<template>
  <FirstLoginLeftSide />
  <div class="h-full w-1/2 rounded-r-window bg-white md:block hidden">
    <RightSide />
  </div>
</template>

<script setup>
import RightSide from "../box/RightSide.vue";
import FirstLoginLeftSide from "./box/FirstLoginLeftSide.vue";
</script>

<style scoped>
</style>