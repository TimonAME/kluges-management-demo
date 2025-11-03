<template>
  <div class="h-full flex items-center justify-center">
    <!-- Übergeordnetes Div für einen Schatten -->
    <div
      class="flex-row items-center justify-center w-full max-w-4xl h-full shadow-lg rounded-lg sm:flex hidden"
    >
      <!-- Linke Seite der Login-Box -->
      <LeftSide />
      <!-- Rechte Seite der Login-Box -->
      <RightSide />
    </div>
    <div
      class="flex flex-row items-center justify-center w-full max-w-4xl h-full shadow-lg rounded-lg sm:hidden"
    >
      <!-- Linke Seite der Login-Box -->
      <LeftSide />
    </div>
  </div>
</template>

<script setup>
import LeftSide from "./box/LeftSide.vue";
import RightSide from "./box/RightSide.vue";
</script>

<style scoped></style>
