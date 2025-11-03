// icons/index.js
export const ChevronLeftIcon = {
  name: "ChevronLeftIcon",
  props: {
    className: {
      type: String,
      default: "",
    },
  },
  template: `
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" :class="[className]">
        <path d="M15 18L9 12L15 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>

  `,
};

export const ChevronRightIcon = {
  name: "ChevronRightIcon",
  props: {
    className: {
      type: String,
      default: "",
    },
  },
  template: `
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" :class="[className]">
      <path d="M9 18L15 12L9 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  `,
};

export const PlusIcon = {
  name: "PlusIcon",
  props: {
    className: {
      type: String,
      default: "",
    },
  },
  template: `
    <svg
        viewBox="0 0 24 24"
        fill="none"
        :class="className"
    >
      <path
          d="M12 5V19M5 12H19"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
      />
    </svg>
  `,
};
