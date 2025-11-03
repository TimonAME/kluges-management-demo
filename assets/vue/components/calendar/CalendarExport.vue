<template>
  <div class="calendar-export">
    <!-- Export Icon Button -->
    <button
      @click="showExportOptions = !showExportOptions"
      class="p-2 text-gray-500 hover:text-secondaccent hover:bg-gray-50 rounded-full transition-colors"
      title="Kalender exportieren"
    >
      <download-icon class="w-5 h-5" />
    </button>

    <!-- Export Options Dropdown -->
    <div
      v-if="showExportOptions"
      class="absolute z-50 right-0 mt-2 w-64 bg-white rounded-md shadow-lg border border-gray-200"
    >
      <div class="p-3 space-y-3">
        <div class="flex items-center justify-between border-b pb-2">
          <span class="text-sm font-medium text-gray-700"
            >Kalender exportieren</span
          >
          <button
            @click="showExportOptions = false"
            class="text-gray-400 hover:text-gray-600"
          >
            <x-icon class="w-4 h-4" />
          </button>
        </div>

        <!-- Zeitraum -->
        <div>
          <label class="block text-xs text-gray-500 mb-1">Zeitraum</label>
          <select
            v-model="exportRange"
            class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-secondaccent focus:ring focus:ring-secondaccent/20"
          >
            <option value="all">Alle Termine</option>
            <option value="future">Zukünftige Termine</option>
            <option value="month">Aktueller Monat</option>
            <option value="custom">Benutzerdefiniert</option>
          </select>
        </div>

        <!-- Custom Date Range -->
        <div v-if="exportRange === 'custom'" class="space-y-2">
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-xs text-gray-500 mb-1">Von</label>
              <input
                type="date"
                v-model="startDate"
                class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-secondaccent focus:ring focus:ring-secondaccent/20"
              />
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">Bis</label>
              <input
                type="date"
                v-model="endDate"
                class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-secondaccent focus:ring focus:ring-secondaccent/20"
              />
            </div>
          </div>
        </div>

        <!-- Kategorien -->
        <div v-if="categories.length">
          <label class="block text-xs text-gray-500 mb-1">Kategorie</label>
          <select
            v-model="selectedCategory"
            class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-secondaccent focus:ring focus:ring-secondaccent/20"
          >
            <option value="">Alle Kategorien</option>
            <option
              v-for="category in categories"
              :key="category.id"
              :value="category.id"
            >
              {{ category.name }}
            </option>
          </select>
        </div>

        <!-- Export Button -->
        <button
          @click="handleExport"
          class="w-full px-3 py-2 text-sm font-medium text-white bg-second_accent rounded-md hover:bg-second_accent/90 transition-colors flex items-center justify-center"
          :disabled="isExporting"
        >
          <loader-icon v-if="isExporting" class="animate-spin w-4 h-4 mr-2" />
          <span>{{ isExporting ? 'Exportiere...' : 'Exportieren' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import {
  DownloadIcon,
  ChevronDownIcon,
  LoaderIcon,
  XIcon,
} from 'lucide-vue-next';
import { useToastContainer } from '../../composables/useToastContainer';
import axios from 'axios';

const props = defineProps({
  categories: {
    type: Array,
    default: () => [],
  },
});

const { showToast } = useToastContainer();
const showExportOptions = ref(false);
const isExporting = ref(false);
const exportRange = ref('all');
const selectedCategory = ref('');
const startDate = ref('');
const endDate = ref('');

// Automatisch das aktuelle Datum als Standard setzen
startDate.value = new Date().toISOString().split('T')[0];
endDate.value = new Date().toISOString().split('T')[0];

const handleExport = async () => {
  try {
    isExporting.value = true;

    const params = new URLSearchParams();

    if (exportRange.value === 'future') {
      params.append('start', new Date().toISOString());
    } else if (exportRange.value === 'month') {
      const now = new Date();
      const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
      const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
      params.append('start', firstDay.toISOString());
      params.append('end', lastDay.toISOString());
    } else if (exportRange.value === 'custom') {
      params.append('start', startDate.value);
      params.append('end', endDate.value);
    }

    if (selectedCategory.value) {
      params.append('category', selectedCategory.value);
    }

    const response = await axios.get(
      `/kalender/api/export?${params.toString()}`,
      {
        responseType: 'blob',
      }
    );

    const blob = new Blob([response.data], { type: 'text/calendar' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'kalender.ics');

    document.body.appendChild(link);
    link.click();
    link.parentNode.removeChild(link);
    window.URL.revokeObjectURL(url);

    showExportOptions.value = false;
    showToast({
      type: 'success',
      message: 'Kalender wurde erfolgreich exportiert',
    });
  } catch (error) {
    console.error('Fehler beim Exportieren:', error);
    showToast({
      type: 'error',
      message: 'Fehler beim Exportieren des Kalenders',
    });
  } finally {
    isExporting.value = false;
  }
};

// Klick außerhalb schließt das Dropdown
const handleClickOutside = (event) => {
  if (!event.target.closest('.calendar-export')) {
    showExportOptions.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.calendar-export {
  position: relative;
}
</style>
