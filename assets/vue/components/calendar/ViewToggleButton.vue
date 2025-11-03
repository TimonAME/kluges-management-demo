<!-- ViewToggleButton.vue -->

<template>
  <button
    :class="[
      'px-4 py-2 rounded-window font-medium transition-all',
      currentView === view
        ? 'bg-second_accent text-white'
        : 'bg-gray-50 text-icon_color hover:bg-gray-100',
    ]"
    @click="$emit('click')"
  >
    {{ label }}
  </button>
</template>

<script setup>
defineProps({
  view: {
    type: String,
    required: true,
  },
  currentView: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
});

defineEmits(["click"]);
</script>
