<template>
  <div
    class="fixed bottom-4 right-4 z-50 flex flex-col gap-2"
    style="min-width: 250px"
  >
    <TransitionGroup name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="[
          'p-4 rounded-lg shadow-lg text-white',
          toast.type === 'success' ? 'bg-green-500' : 'bg-red-500',
        ]"
      >
        {{ toast.message }}
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { useToastContainer } from '@/composables/useToastContainer';

const { toasts } = useToastContainer();
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateY(30px);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100px);
}
</style>
