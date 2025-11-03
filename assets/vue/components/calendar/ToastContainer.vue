<template>
  <div
    class="fixed top-4 right-4 z-50 flex flex-col gap-2"
    style="min-width: 300px; max-width: 90vw"
  >
    <TransitionGroup name="toast" tag="div" class="flex flex-col gap-2">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="flex items-center gap-3 p-4 bg-white rounded-lg shadow-lg border-l-4"
        :class="{
          'border-blue-500': toast.type === 'loading',
          'border-green-500': toast.type === 'success',
          'border-red-500': toast.type === 'error',
        }"
      >
        <!-- Loading Spinner -->
        <svg
          v-if="toast.type === 'loading'"
          class="animate-spin h-5 w-5 text-blue-500"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          />
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          />
        </svg>

        <!-- Success Icon -->
        <svg
          v-else-if="toast.type === 'success'"
          class="h-5 w-5 text-green-500"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 13l4 4L19 7"
          />
        </svg>

        <!-- Error Icon -->
        <svg
          v-else-if="toast.type === 'error'"
          class="h-5 w-5 text-red-500"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"
          />
        </svg>

        <!-- Toast Message -->
        <span
          class="flex-1 text-sm text-gray-800"
          :class="{
            'text-blue-700': toast.type === 'loading',
            'text-green-700': toast.type === 'success',
            'text-red-700': toast.type === 'error',
          }"
        >
          {{ toast.message }}
        </span>

        <!-- Close Button -->
        <button
          @click="removeToast(toast.id)"
          class="flex-shrink-0 ml-2 text-gray-400 hover:text-gray-600"
        >
          <svg
            class="h-4 w-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { ref } from "vue";

const toasts = ref([]);

const addToast = (toast) => {
  const id = Date.now();
  toasts.value.push({
    id,
    message: toast.message,
    type: toast.type,
    timeout: toast.timeout || 3000,
  });

  if (toast.type !== "loading") {
    setTimeout(() => {
      removeToast(id);
    }, toast.timeout || 3000);
  }

  return id;
};

const removeToast = (id) => {
  toasts.value = toasts.value.filter((toast) => toast.id !== id);
};

const updateToast = (id, newToast) => {
  const index = toasts.value.findIndex((toast) => toast.id === id);
  if (index !== -1) {
    toasts.value[index] = {
      ...toasts.value[index],
      ...newToast,
    };

    if (newToast.type !== "loading") {
      setTimeout(() => {
        removeToast(id);
      }, newToast.timeout || 3000);
    }
  }
};

// Expose methods to parent
defineExpose({
  addToast,
  removeToast,
  updateToast,
});
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  transform: translateX(100%);
  opacity: 0;
}

.toast-leave-to {
  transform: translateX(100%);
  opacity: 0;
}

.toast-move {
  transition: transform 0.3s ease;
}
</style>
