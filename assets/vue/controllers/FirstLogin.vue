<template>
  <div class="h-screen flex justify-center items-center bg-first_bg">
    <div class="w-2/3 lg:w-7/12 h-2/3 flex items-center justify-center shadow-lg rounded-window">
      <FirstLoginForm />
    </div>
  </div>
</template>

<script setup>
import FirstLoginForm from "../components/login/firstLogin/FirstLoginForm.vue";
</script>