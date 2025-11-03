<script setup>
import {ref, watch} from 'vue';

const allowEmails = ref(false);
const emailSettings = ref({
  new_appointment: false,
  new_todo: false,
  appointment_change: false,
  software_update: false,
});

watch(allowEmails, (newValue) => {
  if (newValue) {
    emailSettings.value = {
      new_appointment: true,
      new_todo: true,
      appointment_change: true,
      software_update: true,
    };
  } else {
    emailSettings.value = {
      new_appointment: false,
      new_todo: false,
      appointment_change: false,
      software_update: false,
    };
  }
});
</script>

<template>
  <div class="p-2 h-full flex flex-col justify-between">
    <div>
      <!-- Explanation -->
      <div class="text-secondary_text text-sm font-normal mb-2">Hier können Sie Ihre E-Mail-Einstellungen anpassen.</div>

      <div class="flex flex-row items-center gap-2">
        <input type="checkbox" v-model="allowEmails" class="w-4 h-4 border p-0 rounded-window transition-colors duration-75 ease-in stroke-icon_color border-icon_color" />
        <div class="text-primary_text font-medium">E-Mails senden erlauben</div>
      </div>

      <!-- E-Mail-Einstellungen -->
      <div class="text-lg font-semibold mt-4 mb-2 transition-colors duration-75 ease-in" :class="{ 'text-gray-200': !allowEmails, 'text-primary_text': allowEmails }">Benachrichtigungs Einstellungen</div>
      <div class="flex flex-col gap-2">
        <div class="flex flex-row items-center gap-2">
          <input type="checkbox" v-model="emailSettings.new_appointment" :disabled="!allowEmails" class="w-4 h-4 border p-0 rounded-window transition-colors duration-75 ease-in stroke-icon_color" :class="{ 'border-gray-200': !allowEmails, 'border-icon_color': allowEmails }"/>
          <div :class="{ 'text-gray-200': !allowEmails, 'text-primary_text': allowEmails }" class="font-medium transition-colors duration-75 ease-in">Neuer Termin</div>
        </div>
        <div class="flex flex-row items-center gap-2">
          <input type="checkbox" v-model="emailSettings.new_todo" :disabled="!allowEmails" class="w-4 h-4 border p-0 rounded-window transition-colors duration-75 ease-in stroke-icon_color" :class="{ 'border-gray-200': !allowEmails, 'border-icon_color': allowEmails }"/>
          <div :class="{ 'text-gray-200': !allowEmails, 'text-primary_text': allowEmails }" class="font-medium transition-colors duration-75 ease-in">Neues TODO</div>
        </div>
        <div class="flex flex-row items-center gap-2">
          <input type="checkbox" v-model="emailSettings.appointment_change" :disabled="!allowEmails" class="w-4 h-4 border p-0 rounded-window transition-colors duration-75 ease-in stroke-icon_color" :class="{ 'border-gray-200': !allowEmails, 'border-icon_color': allowEmails }"/>
          <div :class="{ 'text-gray-200': !allowEmails, 'text-primary_text': allowEmails }" class="font-medium transition-colors duration-75 ease-in">Termin änderung</div>
        </div>
        <div class="flex flex-row items-center gap-2">
          <input type="checkbox" v-model="emailSettings.software_update" :disabled="!allowEmails" class="w-4 h-4 border p-0 rounded-window transition-colors duration-75 ease-in stroke-icon_color" :class="{ 'border-gray-200': !allowEmails, 'border-icon_color': allowEmails }"/>
          <div :class="{ 'text-gray-200': !allowEmails, 'text-primary_text': allowEmails }" class="font-medium transition-colors duration-75 ease-in">Software Update</div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>