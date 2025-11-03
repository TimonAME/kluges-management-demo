<script setup>
import { defineProps, computed } from 'vue';

const props = defineProps({
  activePage: {
    type: String,
    required: true,
  },
  isExpanded: {
    type: Boolean,
    required: true,
  },
  navName: {
    type: String,
    required: true,
  },
  iconName: {
    type: String,
    required: true,
  },
  role: {
    type: String,
    required: true,
  },
});

// get the SVG path
const iconPath = computed(() => {
  return `../../../images/icons/${props.iconName}.svg`;
});

// truncate navName if longer than 14 characters
const truncatedNavName = computed(() => {
  return props.navName.length > 15 ? props.navName.slice(0, 15) + '...' : props.navName;
});

// get the page URL
const pageUrl = computed(() => {
  let page = props.navName.toLowerCase();
  if (props.role === 'ROLE_TEACHER' && page === 'benutzerliste') {
    page = 'benutzerliste/student';
  }
  return `/${page}`;
});
</script>

<template>
  <div v-if="navName === 'divider'" class="flex items-center justify-start w-full m-4">
    <div class="h-0.5 bg-icon_color rounded-full transition-all duration-150" :class="!isExpanded ? 'w-9' : 'w-44'"></div>
  </div>

  <a v-else
     :href="pageUrl"
     :class="activePage.toLowerCase() === navName.toLowerCase() ?
          'w-full flex items-center justify-start border-r-2 border-main relative group cursor-pointer' :
          'w-full group flex items-center justify-start border-r-2 border-transparent hover:border-main transition-colors duration-100 cursor-pointer' "
  >
    <div v-if="activePage.toLowerCase() === navName.toLowerCase()" class="absolute inset-0 bg-main opacity-15 z-10"></div>
    <div class="relative z-20">
      <div
          class="w-6 h-6 my-4 ml-6 delay-100 transition-colors duration-100"
          :class="activePage.toLowerCase() === navName.toLowerCase() ? 'bg-main' : 'bg-icon_color group-hover:bg-main'"
          :style="{ mask: `url(${iconPath}) no-repeat center`, 'mask-size': 'contain' }">
    </div>
    </div>
    <span v-if="!isExpanded" class="fixed left-20 hidden group-hover:block duration-100 bg-window_bg text-primary_text px-2 py-1 rounded-window shadow-lg">{{ navName }}</span>
    <span class="ml-4 transition-all"
          :class="[isExpanded ? 'opacity-100 w-[128px] duration-150 delay-75' : 'opacity-0 w-[0px] duration-0 text',
                        activePage.toLowerCase() === navName.toLowerCase() ? 'text-main' : 'text-primary_text group-hover:text-main']"
    >
        {{ truncatedNavName }}
    </span>
  </a>
</template>

<style scoped>

</style>