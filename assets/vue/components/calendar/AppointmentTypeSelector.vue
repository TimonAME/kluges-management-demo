<!-- AppointmentTypeSelector.vue -->
<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
    @click="handleBackdropClick"
  >
    <div
      class="bg-white rounded-xl shadow-xl w-full max-w-md m-4 transform transition-all"
      :class="{
        'scale-95 opacity-0': !isVisible,
        'scale-100 opacity-100': isVisible,
      }"
    >
      <div class="p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">
          Termin erstellen
        </h2>
        <p class="text-gray-600 mb-6">Wählen Sie die Art des Termins aus</p>
        <div class="grid grid-cols-1 gap-4">
          <!-- Regulärer Termin -->
          <button
            @click="selectType('regular')"
            class="flex items-center p-4 border rounded-xl hover:border-second_accent hover:bg-blue-50 transition-all group"
          >
            <div
              class="p-3 rounded-lg bg-blue-100 group-hover:bg-blue-200 transition-colors"
            >
              <svg
                class="w-6 h-6 text-blue-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
              </svg>
            </div>
            <div class="ml-4 text-left">
              <h3 class="font-medium text-gray-900">Regulärer Termin</h3>
              <p class="text-sm text-gray-500">
                Erstellen Sie einen normalen Kalendereintrag
              </p>
            </div>
          </button>

          <!-- Nachhilfetermin -->
          <button
            @click="selectType('tutoring')"
            class="flex items-center p-4 border rounded-xl hover:border-second_accent hover:bg-purple-50 transition-all group"
          >
            <div
              class="p-3 rounded-lg bg-purple-100 group-hover:bg-purple-200 transition-colors"
            >
              <svg
                class="w-6 h-6 text-purple-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                />
              </svg>
            </div>
            <div class="ml-4 text-left">
              <h3 class="font-medium text-gray-900">Nachhilfetermin</h3>
              <p class="text-sm text-gray-500">
                Erstellen Sie einen Nachhilfetermin
              </p>
            </div>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "AppointmentTypeSelector",
  props: {
    show: {
      type: Boolean,
      required: true,
    },
    date: {
      type: Object,
      default: null,
    },
  },
  data() {
    return {
      isVisible: false,
    };
  },
  watch: {
    show(newVal) {
      if (newVal) {
        setTimeout(() => {
          this.isVisible = true;
        }, 50);
      } else {
        this.isVisible = false;
      }
    },
  },
  methods: {
    handleBackdropClick(event) {
      if (event.target === event.currentTarget) {
        this.$emit("close");
      }
    },
    selectType(type) {
      if (type === "regular") {
        this.$emit("select-regular", this.date);
      } else if (type === "tutoring") {
        if (this.date) {
          const dateStr = this.date.date.toISOString().split('T')[0];
          window.location.href = `/nachhilfetermin/neu?date=${dateStr}`;
        } else {
          window.location.href = "/nachhilfetermin/neu";
        }
      }
      this.$emit("close");
    },
  },
};
</script>
