<!-- EventList.vue -->

<template>
  <div class="flex flex-col h-full bg-white">
    <!-- Suchleiste und Quick Filters -->
    <div class="flex-shrink-0 border-b border-gray-100">
      <!-- Suchleiste -->
      <div class="p-3">
        <div class="relative">
          <input
            type="text"
            :value="searchQuery"
            @input="debouncedEmitSearch($event.target.value)"
            placeholder="Termine suchen..."
            class="w-full pl-9 pr-3 py-2 rounded-lg bg-gray-50 border-0 text-sm focus:bg-white focus:ring-2 focus:ring-second_accent/20 transition-all"
          />
          <SearchIcon class="absolute left-2.5 top-2.5 h-4 w-4 text-gray-400" />
        </div>
      </div>

      <!-- Quick Filters - Horizontales Scrolling -->
      <div class="relative">
        <div class="flex overflow-x-auto px-3 pb-3 hide-scrollbar">
          <div class="flex gap-1.5 min-w-max">
            <button
              v-for="filter in filters"
              :key="filter.value"
              @click="$emit('filter-change', filter.value)"
              :class="[
                'px-3 py-1.5 rounded-lg text-sm whitespace-nowrap transition-all flex-1',
                currentFilter === filter.value
                  ? 'bg-second_accent text-white shadow-sm'
                  : 'bg-gray-50 text-gray-600 hover:bg-gray-100',
              ]"
            >
              {{ filter.label }}
            </button>
          </div>
        </div>

        <!-- Gradient Für Scroll-Indikator -->
        <div
          class="absolute right-0 top-0 bottom-0 w-8 bg-gradient-to-l from-white to-transparent pointer-events-none"
          :class="{ hidden: !isScrollable }"
        ></div>
      </div>
    </div>

    <!-- Advanced Filters -->
    <div class="border-b border-gray-100">
      <div class="px-4 py-2 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <button
            @click="showAdvancedFilters = !showAdvancedFilters"
            class="text-sm text-gray-600 flex items-center gap-1.5"
          >
            <FilterIcon class="h-4 w-4" />
            Filter
            <ChevronDownIcon
              class="h-4 w-4 transition-transform"
              :class="{ 'rotate-180': showAdvancedFilters }"
            />
          </button>
          <div v-if="selectedCategoryId" class="flex items-center gap-1.5">
            <span class="w-[1px] h-4 bg-gray-200"></span>
            <span class="text-xs text-gray-500">1 aktiv</span>
          </div>
        </div>
        <button
          v-if="selectedCategoryId"
          @click="resetFilters"
          class="text-xs text-gray-500 hover:text-gray-700"
        >
          Zurücksetzen
        </button>
      </div>

      <!-- Advanced Filter Panel -->
      <div v-if="showAdvancedFilters" class="px-4 pb-3 space-y-4 bg-gray-50/50">
        <!-- Kategorien -->
        <div>
          <h3 class="text-xs font-medium text-gray-500 mb-2">Kategorien</h3>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="category in categories"
              :key="category.id"
              @click="toggleCategory(category.id)"
              class="inline-flex items-center gap-1.5 px-2 py-1.5 rounded-lg text-sm transition-all"
              :class="[
                selectedCategoryId === category.id
                  ? 'bg-white shadow-sm ring-1 ring-gray-200'
                  : 'bg-transparent hover:bg-white/50',
              ]"
            >
              <span
                class="w-2.5 h-2.5 rounded-full"
                :style="{ backgroundColor: category.color || '#4884f4' }"
              ></span>
              {{ category.name }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Event Liste -->
    <div class="flex-1 overflow-y-auto">
      <div v-if="isLoading" class="flex items-center justify-center h-32">
        <div
          class="animate-spin rounded-full h-6 w-6 border-2 border-second_accent border-t-transparent"
        ></div>
      </div>

      <div
        v-else-if="!events.length"
        class="flex flex-col items-center justify-center h-32"
      >
        <CalendarIcon class="h-8 w-8 text-gray-300 mb-2" />
        <p class="text-sm text-gray-500">{{ emptyStateMessage }}</p>
      </div>

      <div v-else class="divide-y divide-gray-100">
        <button
          v-for="event in events"
          :key="event.id"
          @click="$emit('event-click', event)"
          class="w-full px-4 py-3 hover:bg-gray-50/50 transition-all text-left group"
        >
          <div class="flex items-start gap-3">
            <div class="flex flex-col items-center min-w-[48px] pt-0.5">
              <span class="text-xs font-medium text-gray-400">
                {{ formatEventDate(event.start, 'weekday') }}
              </span>
              <span class="text-lg font-semibold text-gray-900">
                {{ formatEventDate(event.start, 'day') }}
              </span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-1.5">
                <span
                  class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                  :style="{ backgroundColor: event.color || '#4884f4' }"
                ></span>
                <h3 class="font-medium text-gray-900 truncate">
                  {{ event.title }}
                </h3>
              </div>
              <div class="flex items-center gap-1 mt-1 text-xs text-gray-500">
                <ClockIcon class="h-3.5 w-3.5" />
                <span>{{ formatEventTime(event) }}</span>
              </div>
              <div v-if="event.extendedProps?.description" class="flex items-center gap-1 mt-1 text-xs text-gray-500">
                <AlignLeftIcon class="h-3.5 w-3.5" />
                <span class="line-clamp-2">{{ event.extendedProps.description }}</span>
              </div>
            </div>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import {
  SearchIcon,
  FilterIcon,
  CalendarIcon,
  MapPinIcon,
  ChevronDownIcon,
  ClockIcon,
  AlignLeftIcon
} from 'lucide-vue-next';
import { debounce } from 'lodash';

const props = defineProps({
  events: {
    type: Array,
    required: true,
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  currentFilter: {
    type: String,
    required: true,
  },
  filters: {
    type: Array,
    required: true,
  },
  categories: {
    type: Array,
    default: () => [],
  },
  searchQuery: {
    type: String,
    default: '',
  },
  emptyStateMessage: {
    type: String,
    default: 'Keine Termine gefunden',
  },
});

const emit = defineEmits([
  'update:searchQuery',
  'filter-change',
  'category-filter',
  'event-click',
]);

// State
const showAdvancedFilters = ref(false);
const selectedCategoryId = ref(null);

// Computed
const isScrollable = ref(false);

// Methods
const debouncedEmitSearch = debounce((value) => {
  emit('update:searchQuery', value);
}, 300);

const toggleCategory = (categoryId) => {
  selectedCategoryId.value = categoryId === selectedCategoryId.value ? null : categoryId;
  emit('category-filter', selectedCategoryId.value);
};

const resetFilters = () => {
  selectedCategoryId.value = null;
  emit('category-filter', null);
};

const formatEventDate = (dateString, format) => {
  const date = new Date(dateString);
  if (format === 'weekday') {
    return date.toLocaleDateString('de-DE', { weekday: 'short' });
  }
  if (format === 'day') {
    return date.getDate();
  }
  return date.toLocaleDateString('de-DE');
};

const formatEventTime = (event) => {
  if (event.allDay) return 'Ganztägig';
  const start = new Date(event.start);
  const end = new Date(event.end);
  return `${start.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' })} - ${end.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' })}`;
};

// Scroll-Logik
onMounted(() => {
  const scrollContainer = document.querySelector('.overflow-x-auto');
  if (scrollContainer) {
    const checkScrollable = () => {
      isScrollable.value =
        scrollContainer.scrollWidth > scrollContainer.clientWidth;
    };

    checkScrollable();
    window.addEventListener('resize', checkScrollable);

    onBeforeUnmount(() => {
      window.removeEventListener('resize', checkScrollable);
    });
  }
});
</script>

<style scoped>
.overflow-y-auto {
  scrollbar-width: thin;
  scrollbar-color: theme('colors.gray.200') transparent;
}

.overflow-y-auto::-webkit-scrollbar {
  width: 4px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background-color: theme('colors.gray.200');
  border-radius: 2px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background-color: theme('colors.gray.300');
}

.hide-scrollbar {
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.hide-scrollbar::-webkit-scrollbar {
  display: none;
}

/* Smooth Scroll Verhalten */
.overflow-x-auto {
  scroll-behavior: smooth;
  -webkit-overflow-scrolling: touch;
}
</style>
