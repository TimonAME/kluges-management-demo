<template>
  <ForgotPasswordLeftSide />
  <div class="h-full w-1/2 rounded-r-window bg-white md:block hidden">
    <RightSide />
  </div>
</template>

<script setup>
import ForgotPasswordLeftSide from "./box/ForgotPasswordLeftSide.vue";
import RightSide from "../box/RightSide.vue";
</script>

<style scoped>
/*
  <div class="h-full flex items-center justify-center">
    <!-- Übergeordnetes Div für einen Schatten -->
    <div
        class="flex-row items-center justify-center w-full max-w-4xl h-full shadow-lg rounded-lg sm:flex hidden"
    >
      <!-- Linke Seite der Login-Box -->
      <ForgotPasswordLeftSide />
      <!-- Rechte Seite der Login-Box -->
      <RightSide />
    </div>
    <div class="w-2/3 lg:w-7/12 h-2/3 flex items-center justify-center shadow-lg rounded-window sm:hidden">
      <!-- Linke Seite der Login-Box -->
      <ForgotPasswordLeftSide />
    </div>
  </div>
 */
</style>