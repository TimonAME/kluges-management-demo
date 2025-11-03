<script setup>
import AddTaskForm from "./AddTaskForm.vue";

const emit = defineEmits(['close', 'add']);

const closeModal = () => {
  // Emit the close event
  emit('close');
};

const handleAddTodo = (newTodo) => {
  // Emit the add event
  emit('add', newTodo);
};
</script>

<template>
  <div class="fixed inset-0 z-40" @click="closeModal">
    <!-- Modal backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50"></div>

    <!-- Modal wrapper -->
    <div class="fixed inset-0 overflow-y-auto">
      <div class="flex min-h-screen items-center justify-center p-4">
        <!-- Modal content -->
        <div class="relative bg-window_bg rounded-lg shadow-xl w-full max-w-xl" @click.stop>
          <div class="p-6">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-semibold">Neue Aufgabe</h2>
              <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
            </div>

            <AddTaskForm
                :is-edit="false"
                @add="handleAddTodo"
                @close="closeModal"
            />

          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Remove any overflow restrictions that might clip the dropdown */
.modal-content {
  overflow: visible !important;
}
</style>