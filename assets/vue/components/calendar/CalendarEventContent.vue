<!-- CalendarEventContent.vue -->

<template>
  <div v-if="event.extendedProps.todo" class="w-full min-w-0 event-content">
    <div v-if="event.allDay" class="flex items-center min-w-0">
      <todo-check-icon
        class="flex-shrink-0 mx-1 todo-icon"
        :is-completed="event.extendedProps.isCompleted"
      />
      <span class="truncate min-w-0 event-title">{{ event.title }}</span>
    </div>
    <div v-else class="flex items-center min-w-0">
      <todo-check-icon
        class="flex-shrink-0 mx-1 todo-icon"
        :is-completed="event.extendedProps.isCompleted"
      />
      <b class="flex-shrink-0 mx-1 event-time">{{ timeText }}</b>
      <span class="truncate min-w-0 event-title">{{ event.title }}</span>
    </div>
  </div>

  <div v-else class="flex items-center w-full min-w-0 event-content">
    <template v-if="timeText">
      <div
        class="flex-shrink-0 w-1.5 h-1.5 rounded-full sm:ml-1 mr-1"
        :style="{ backgroundColor: backgroundColor, minWidth: '6px' }"
      />
      <b class="flex-shrink-0 mr-1 event-time hide-on-mobile">{{ timeText }}</b>
      <span class="truncate min-w-0 event-title">{{ event.title }}</span>
    </template>
    <template v-else>
      <span class="truncate min-w-0 ml-1 event-title">{{ event.title }}</span>
    </template>
  </div>
</template>

<script setup>
/**
 * Calendar event content component
 * @displayName CalendarEventContent
 */

// Props
const props = defineProps({
  event: {
    type: Object,
    required: true,
  },
  timeText: {
    type: String,
    default: "",
  },
  backgroundColor: {
    type: String,
    default: "#3788d8",
  },
});

// TodoCheckIcon
const TodoCheckIcon = {
  name: "TodoCheckIcon",
  props: {
    isCompleted: Boolean,
  },
  template: `
    <svg
      width="11"
      height="11"
      viewBox="0 0 16 16"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      class="todo-icon"
    >
      <g clip-path="url(#clip0_50_1477)">
        <path
          d="M14.6663 7.38668V8.00001C14.6655 9.43763 14.2 10.8365 13.3392 11.9879C12.4785 13.1393 11.2685 13.9817 9.88991 14.3893C8.5113 14.7969 7.03785 14.7479 5.68932 14.2497C4.3408 13.7515 3.18944 12.8307 2.40698 11.6247C1.62452 10.4187 1.25287 8.99205 1.34746 7.55755C1.44205 6.12305 1.99781 4.75756 2.93186 3.66473C3.86591 2.57189 5.1282 1.81027 6.53047 1.49344C7.93274 1.17662 9.39985 1.32157 10.713 1.90668"
          :stroke="isCompleted ? '#3788d8' : 'white'"
          stroke-width="1.33333"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
        <path
          d="M14.6667 2.66669L8 9.34002L6 7.34002"
          :stroke="isCompleted ? '#3788d8' : 'white'"
          stroke-width="1.33333"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </g>
      <defs>
        <clipPath id="clip0_50_1477">
          <rect width="16" height="16" fill="white"/>
        </clipPath>
      </defs>
    </svg>
  `,
};

// Komponenten registrieren
const components = {
  TodoCheckIcon,
};
</script>

<style scoped>
.event-content {
  font-size: inherit;
  line-height: inherit;
}

/* Standard-Größe für Desktop */
.todo-icon {
  width: 14px;
  height: 14px;
}

@media (max-width: 640px) {
  .event-title {
    font-size: 0.675rem;
    line-height: 0.875rem;
  }
  
  .event-time {
    font-size: 0.625rem;
  }

  /* Kleinere Größe nur für Mobile */
  .todo-icon {
    width: 10px;
    height: 10px;
  }

  .hide-on-mobile {
    display: none !important;
  }
}

.truncate {
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
}
</style>
