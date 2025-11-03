import { ref } from 'vue';

export function useToastContainer() {
  const toasts = ref([]);

  const showToast = ({ type, message, timeout = 3000 }) => {
    const toast = {
      id: Date.now(),
      type,
      message,
    };

    toasts.value.push(toast);

    if (timeout) {
      setTimeout(() => {
        removeToast(toast.id);
      }, timeout);
    }

    return toast.id;
  };

  const removeToast = (id) => {
    const index = toasts.value.findIndex((t) => t.id === id);
    if (index !== -1) {
      toasts.value.splice(index, 1);
    }
  };

  return {
    toasts,
    showToast,
    removeToast,
  };
} 