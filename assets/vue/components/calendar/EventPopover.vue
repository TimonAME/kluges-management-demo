<script>
import axios from "axios";
import { debounce } from "lodash";

export default {
  name: "EventPopover",
  props: {
    show: Boolean,
    event: Object,
    position: Object,
    currentName: {
      type: String,
      required: true,
    },
    currentId: {
      type: Number,
      required: true,
    },
    mode: {
      type: String,
      default: "new",
    },
  },
  data() {
    return {
      currentMode: this.mode,
      showAdvancedSettings: false,
      currentUserId: 1, // TODO: should be set from your auth system
      userSearch: "",
      searchResults: [],
      isSearching: false,
      debouncedSearch: null,
      eventData: {
        id: "null",
        title: "",
        startDate: new Date().toISOString().split("T")[0],
        endDate: new Date().toISOString().split("T")[0],
        startTime: "12:00",
        endTime: "13:00",
        allDay: false,
        location: 1,
        description: "",
        color: "#039BE5",
        appointmentCategory: 1,
        room: 1,
        users: [],
      },
      initialClickPosition: null,
      predefinedColors: [
        "#039BE5", // Blau
        "#7986CB", // Indigo
        "#33B679", // Grün
        "#8E24AA", // Lila
        "#E67C73", // Rot
        "#F6BF26", // Gelb
        "#F4511E", // Orange
        "#B39DDB", // Helles Lila
        "#9E9E9E", // Grau
      ],
      isPositioned: false,
      popoverPosition: {
        top: "0px",
        left: "0px",
      },
    };
  },
  computed: {
    isCreator() {
      return this.eventData.creator === this.currentUserId;
    },

    filteredUsers() {
      return this.users.filter((user) =>
        user.name.toLowerCase().includes(this.userSearch.toLowerCase()),
      );
    },
  },
  watch: {
    mode: {
      immediate: true,
      handler(newMode) {
        this.currentMode = newMode;
      },
    },
    event: {
      immediate: true,
      handler(newEvent) {
        if (newEvent) {
          this.initializeEventData(newEvent);
        }
      },
    },
    position: {
      immediate: true,
      handler(newPosition) {
        if (newPosition && this.show) {
          this.calculatePosition(newPosition);
        }
      },
    },
    show(newValue) {
      if (!newValue) {
        this.isPositioned = false;
      }
    },
    eventData: {
      deep: true,
      handler(newData) {
        if (this.mode === "new") {
          this.updateGhostEvent(newData);
        }
      },
    },
    "eventData.allDay": function (newValue) {
      if (!newValue) {
        // Wenn nicht ganztägig, setze Enddatum gleich Startdatum
        this.eventData.endDate = this.eventData.startDate;
      }
    },
    "eventData.startDate": function (newValue) {
      if (!this.eventData.allDay) {
        // Bei Änderung des Startdatums (wenn nicht ganztägig), setze Enddatum gleich Startdatum
        this.eventData.endDate = newValue;
      }
    },
  },
  methods: {
    async fetchCategoriesAndRooms() {
      try {
        const [categoriesResponse, roomsResponse] = await Promise.all([
          axios.get("/api/appointmentCategory"),
          axios.get("/api/room"),
        ]);

        this.categories = categoriesResponse.data;
        this.rooms = roomsResponse.data;
      } catch (error) {
        console.error("Error fetching categories and rooms:", error);
      }
    },
    initializeEventData(event) {
      console.log(event);
      if (!event) return;

      if (this.mode === "new") {
        this.eventData = {
          id: "null",
          title: "",
          startDate: event.startDate || new Date().toISOString().split("T")[0],
          endDate: event.endDate || new Date().toISOString().split("T")[0],
          startTime: "12:00",
          endTime: "13:00",
          allDay: false,
          description: "",
          color: "#039BE5",
          location: 1,
          appointmentCategory: 1,
          room: null,
          users: [],
        };
      } else {
        const startDate = new Date(event.start);
        const endDate = event.end ? new Date(event.end) : new Date(startDate);

        // Determine the correct color
        let eventColor = "#039BE5"; // Default color
        if (event.backgroundColor) {
          eventColor = event.backgroundColor;
        } else if (event.color) {
          eventColor = event.color;
        } else if (event.extendedProps?.color) {
          eventColor = event.extendedProps.color;
        }

        this.eventData = {
          id: event.id,
          title: event.title,
          startDate: this.formatDateForInput(startDate),
          endDate: this.formatDateForInput(endDate),
          startTime: this.formatTimeForInput(startDate),
          endTime: this.formatTimeForInput(endDate),
          allDay: event.allDay,
          description: event.description || "",
          color: eventColor,
          location: event.location,
          appointmentCategory: event.appointmentCategory,
          room: event.room,
          creator: event.todo
            ? "Test User"
            : event.creator.first_name + " " + event.creator.last_name,
          users: event.users || [],
        };
        console.log("Initialized event data:", this.eventData);
      }
    },

    setInitialPosition() {
      // Set initial position before transition starts
      if (this.position) {
        this.calculatePosition(this.position);
        this.$nextTick(() => {
          this.isPositioned = true;
        });
      }
    },

    calculatePosition(info) {
      if (!info || !info.el) return;

      const POPOVER_WIDTH = 384;
      const POPOVER_HEIGHT = 600; // Erhöht für sicheren Puffer
      const MARGIN = 16;
      const BOTTOM_MARGIN = 0; // Extra Margin für unten

      // Get calendar dimensions
      const calendarEl = document.querySelector(".calendar-wrapper");
      if (!calendarEl) return;
      const calendarRect = calendarEl.getBoundingClientRect();

      // Get the date column of the event
      let dateColumnEl;
      if (info.event) {
        const date = info.event.start;
        const dateStr = date.toISOString().split("T")[0];

        if (info.view.type === "dayGridMonth") {
          dateColumnEl = calendarEl.querySelector(
            `.fc-day[data-date="${dateStr}"]`,
          );
        } else if (info.view.type.includes("timeGrid")) {
          dateColumnEl = calendarEl.querySelector(
            `.fc-timegrid-col[data-date="${dateStr}"]`,
          );
        }
      } else {
        dateColumnEl = info.el;
      }

      if (!dateColumnEl) return;

      const dateColumnRect = dateColumnEl.getBoundingClientRect();
      const viewportHeight = window.innerHeight;

      // Calculate horizontal position
      let finalX;
      if (dateColumnRect.right + MARGIN + POPOVER_WIDTH <= calendarRect.right) {
        finalX = dateColumnRect.right + MARGIN;
      } else {
        finalX = dateColumnRect.left - MARGIN - POPOVER_WIDTH;
      }

      // Calculate vertical position
      let finalY;

      // Always check against viewport boundaries
      if (
        dateColumnRect.top + POPOVER_HEIGHT >
        viewportHeight - BOTTOM_MARGIN
      ) {
        // If it would go below viewport, position it at bottom with margin
        finalY = viewportHeight - POPOVER_HEIGHT - BOTTOM_MARGIN;
      } else {
        // Otherwise use the clicked position
        finalY = dateColumnRect.top;
      }

      // Ensure we don't go above the top margin
      finalY = Math.max(MARGIN, finalY);

      // Set final position
      this.popoverPosition = {
        position: "fixed",
        top: `${finalY}px`,
        left: `${finalX}px`,
      };
    },

    handleNewPopover(position) {
      if (!position) return;
      this.calculatePosition(position);
    },

    updateGhostEvent(data) {
      // Emitte die Änderungen an die übergeordnete Komponente
      this.$emit("ghostUpdate", {
        id: "ghost-event",
        title: data.title || "Neuer Termin",
        start: data.allDay
          ? data.startDate
          : `${data.startDate}T${data.startTime}`,
        end: data.allDay ? data.endDate : `${data.endDate}T${data.endTime}`,
        allDay: data.allDay,
        backgroundColor: data.color + "80", // 50% Transparenz
        borderColor: data.color,
        textColor: this.getTextColor(data.color),
        classNames: ["ghost-event", "cursor-pointer"],
        extendedProps: {
          description: data.description,
          location: data.location,
          appointmentCategory: data.appointmentCategory,
          users: data.users,
        },
      });
    },

    getTextColor(backgroundColor) {
      // Berechne Kontrast für optimale Lesbarkeit
      const rgb = this.hexToRgb(backgroundColor);
      const brightness = (rgb.r * 299 + rgb.g * 587 + rgb.b * 114) / 1000;
      return brightness > 128 ? "#000000" : "#FFFFFF";
    },

    hexToRgb(hex) {
      const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
      return result
        ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16),
          }
        : null;
    },

    async searchUsers(query) {
      if (!query) {
        this.searchResults = [];
        this.isSearching = false;
        return;
      }

      try {
        this.isSearching = true;
        const response = await axios.get("/api/users", {
          params: {
            search: query,
          },
        });

        // Filter out users that are already selected and the current user
        this.searchResults = response.data.filter(
          (user) =>
            !this.eventData.users.some(
              (selectedUser) => selectedUser.id === user.id,
            ) && user.id !== this.currentId,
        );
      } catch (error) {
        console.error("Error searching users:", error);
        this.searchResults = [];
      } finally {
        this.isSearching = false;
      }
    },

    handleUserSearch: debounce(async function (event) {
      const query = event.target.value;
      if (!query) {
        this.searchResults = [];
        this.isSearching = false;
        return;
      }

      try {
        this.isSearching = true;
        const response = await axios.get("/api/users", {
          params: {
            search: query,
          },
        });

        // Filter out users that are already selected and the current user from the users array
        this.searchResults = response.data.users.filter(
          (user) =>
            !this.eventData.users.some(
              (selectedUser) => selectedUser.id === user.id,
            ) && user.id !== this.currentId,
        );
      } catch (error) {
        console.error("Error searching users:", error);
        this.searchResults = [];
      } finally {
        this.isSearching = false;
      }
    }, 300),

    addUser(user) {
      if (!this.eventData.users.some((u) => u.id === user.id)) {
        this.eventData.users.push(user);
      }
      this.userSearch = "";
      this.searchResults = [];
    },

    removeUser(user) {
      this.eventData.users = this.eventData.users.filter(
        (u) => u.id !== user.id,
      );
    },

    formatDateTime(dateString, allDay) {
      if (!dateString) return "";
      const date = new Date(dateString);
      const options = {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
        ...(allDay ? {} : { hour: "2-digit", minute: "2-digit" }),
      };
      return date.toLocaleDateString("de-DE", options);
    },

    formatDateForInput(date) {
      if (!date) return "";
      return date.toISOString().split("T")[0];
    },

    formatTimeForInput(date) {
      if (!date) return "";
      return date.toLocaleTimeString("de-DE", {
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
      });
    },

    validateEventData() {
      const errors = [];

      if (!this.eventData.title.trim()) {
        errors.push("Bitte geben Sie einen Titel ein");
      }

      if (!this.eventData.startDate) {
        errors.push("Bitte wählen Sie ein Startdatum");
      }

      if (!this.eventData.endDate) {
        errors.push("Bitte wählen Sie ein Enddatum");
      }

      const startDateTime = new Date(
        `${this.eventData.startDate}T${this.eventData.startTime}`,
      );
      const endDateTime = new Date(
        `${this.eventData.endDate}T${this.eventData.endTime}`,
      );

      if (endDateTime < startDateTime) {
        errors.push("Das Enddatum muss nach dem Startdatum liegen");
      }

      return errors;
    },

    handleSave() {
      const errors = this.validateEventData();

      if (errors.length > 0) {
        console.error("Validation errors:", errors);
        return;
      }

      const eventToSave = {
        id: this.eventData.id,
        title: this.eventData.title || "Neuer Termin",
        startDate: this.eventData.startDate,
        endDate: this.eventData.endDate,
        startTime: this.eventData.startTime,
        endTime: this.eventData.endTime,
        allDay: this.eventData.allDay,
        backgroundColor: this.eventData.color,
        color: this.eventData.color,
        extendedProps: {
          description: this.eventData.description,
          location: this.eventData.location,
          appointmentCategory: this.eventData.appointmentCategory,
          room: this.eventData.room,
          creator: this.eventData.creator,
          users: this.eventData.users.map((user) => user.id),
          color: this.eventData.color,
        },
      };

      console.log("Event to save:", eventToSave);

      this.$emit("save", eventToSave);
      this.closePopover();
    },

    handleDelete() {
      this.$emit("delete", this.eventData);
      this.closePopover();
    },

    toggleAdvancedSettings() {
      this.showAdvancedSettings = !this.showAdvancedSettings;
    },

    closePopover() {
      this.showAdvancedSettings = false;
      this.initialClickPosition = null;
      this.$emit("close");
    },

    handleKeydown(event) {
      if (event.key === "Escape") {
        this.closePopover();
      }
      if (event.key === "Enter" && event.ctrlKey) {
        this.handleSave();
      }
    },
  },

  mounted() {
    document.addEventListener("keydown", this.handleKeydown);
    this.fetchCategoriesAndRooms();
  },

  beforeDestroy() {
    document.removeEventListener("keydown", this.handleKeydown);
  },
};
</script>

<template>
  <Transition name="popover">
    <div v-if="show" class="fixed inset-0 z-40" @click="closePopover">
      <div
        ref="popover"
        class="fixed z-50 bg-window_bg rounded-window shadow-window w-96 flex flex-col max-h-[600px]"
        :style="popoverPosition"
        @click.stop
      >
        <!-- Header -->
        <div
          class="flex-shrink-0 flex items-center justify-between p-4 border-b border-gray-200"
        >
          <div class="flex-grow flex items-center gap-2">
            <div
              v-if="currentMode === 'view'"
              class="w-3 h-3 rounded-full flex-shrink-0"
              :style="{ backgroundColor: eventData.color }"
            ></div>
            <input
              v-if="currentMode !== 'view'"
              type="text"
              v-model="eventData.title"
              placeholder="Titel hinzufügen"
              class="w-full text-lg font-medium focus:outline-none focus:ring-2 focus:ring-second_accent focus:border-transparent rounded-window"
            />
            <h2 v-else class="text-lg font-medium">{{ eventData.title }}</h2>
          </div>
          <div class="flex items-center gap-2">
            <button
              v-if="currentMode === 'view'"
              @click="$emit('update:mode', 'edit')"
              class="p-2 hover:bg-gray-50 rounded-window transition-colors text-gray-500"
            >
              <svg
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <path
                  d="M18.5 2.49998C18.8978 2.10216 19.4374 1.87866 20 1.87866C20.5626 1.87866 21.1022 2.10216 21.5 2.49998C21.8978 2.89781 22.1213 3.43737 22.1213 3.99998C22.1213 4.56259 21.8978 5.10216 21.5 5.49998L12 15L8 16L9 12L18.5 2.49998Z"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
            </button>
            <button
              @click="closePopover"
              class="p-2 hover:bg-gray-50 rounded-window transition-colors text-gray-500"
            >
              <svg
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M18 6L6 18"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <path
                  d="M6 6L18 18"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
            </button>
          </div>
        </div>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto min-h-0">
          <!-- Basic info section -->
          <div class="p-4 space-y-4">
            <!-- Date/Time Section -->
            <div class="flex items-start gap-4">
              <div
                class="flex-shrink-0 mt-1 p-2 bg-gray-50 rounded-window text-gray-500"
              >
                <svg
                  width="20"
                  height="20"
                  viewBox="0 0 24 24"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M19 4H5C3.89543 4 3 4.89543 3 6V20C3 21.1046 3.89543 22 5 22H19C20.1046 22 21 21.1046 21 20V6C21 4.89543 20.1046 4 19 4Z"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                  <path
                    d="M16 2V6"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                  <path
                    d="M8 2V6"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                  <path
                    d="M3 10H21"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
              </div>
              <div class="flex-grow">
                <div class="mb-2" v-if="mode === 'new' || mode === 'edit'">
                  <label class="flex items-center text-sm text-gray-500 mb-2">
                    <input
                      type="checkbox"
                      v-model="eventData.allDay"
                      class="mr-2 rounded border-gray-300 text-second_accent focus:ring-second_accent"
                    />
                    Ganztägig
                  </label>
                </div>
                <div class="space-y-2">
                  <div class="flex items-center gap-2">
                    <template v-if="mode !== 'view'">
                      <input
                        type="date"
                        v-model="eventData.startDate"
                        class="flex-grow border rounded-window px-3 py-2 focus:ring-2 focus:ring-second_accent focus:border-transparent border-gray-300"
                      />
                      <input
                        v-if="!eventData.allDay"
                        type="time"
                        v-model="eventData.startTime"
                        class="border rounded-window px-3 py-2 focus:ring-2 focus:ring-second_accent focus:border-transparent border-gray-300"
                      />
                    </template>
                    <span v-else class="text-gray-600">
                      {{
                        formatDateTime(
                          eventData.startDate + "T" + eventData.startTime,
                          eventData.allDay,
                        )
                      }}
                    </span>
                  </div>
                  <!-- Im date/time section Block des Templates -->
                  <div class="flex items-center gap-2">
                    <template v-if="mode !== 'view'">
                      <input
                        type="date"
                        v-model="eventData.endDate"
                        :disabled="!eventData.allDay"
                        class="flex-grow border rounded-window px-3 py-2 focus:ring-2 focus:ring-second_accent focus:border-transparent border-gray-300"
                        :class="{
                          'opacity-50 cursor-not-allowed': !eventData.allDay,
                        }"
                      />
                      <input
                        v-if="!eventData.allDay"
                        type="time"
                        v-model="eventData.endTime"
                        class="border rounded-window px-3 py-2 focus:ring-2 focus:ring-second_accent focus:border-transparent border-gray-300"
                      />
                    </template>
                    <span v-else class="text-gray-600">
                      {{
                        formatDateTime(
                          eventData.endDate + "T" + eventData.endTime,
                          eventData.allDay,
                        )
                      }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
            <!-- Users Section -->
            <div class="flex items-start gap-4">
              <div
                class="flex-shrink-0 mt-1 p-2 bg-gray-50 rounded-window text-gray-500"
              >
                <svg
                  width="20"
                  height="20"
                  viewBox="0 0 24 24"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                  <path
                    d="M9 11C11.2091 11 13 9.20914 13 7C13 4.79086 11.2091 3 9 3C6.79086 3 5 4.79086 5 7C5 9.20914 6.79086 11 9 11Z"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                  <path
                    d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                  <path
                    d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
              </div>
              <div class="flex-grow">
                <div class="space-y-2">
                  <div v-if="mode !== 'view'" class="relative">
                    <input
                      type="text"
                      v-model="userSearch"
                      @input="handleUserSearch"
                      placeholder="Nach Teilnehmern suchen..."
                      class="w-full border rounded-window px-3 py-2 focus:ring-2 focus:ring-second_accent focus:border-transparent border-gray-300"
                    />
                    <div v-if="isSearching" class="absolute right-3 top-2.5">
                      <svg
                        class="animate-spin h-5 w-5 text-gray-400"
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
                        ></circle>
                        <path
                          class="opacity-75"
                          fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                      </svg>
                    </div>
                    <div
                      v-if="userSearch && searchResults.length > 0"
                      class="absolute z-10 w-full mt-1 bg-window_bg border rounded-window shadow-lg max-h-48 overflow-y-auto border-gray-200"
                    >
                      <div
                        v-for="user in searchResults"
                        :key="user.id"
                        @click="addUser(user)"
                        class="px-3 py-2 hover:bg-gray-50 cursor-pointer transition-colors"
                      >
                        <div class="font-medium text-gray-700">
                          {{ user.first_name }} {{ user.last_name }}
                        </div>
                        <div class="text-sm text-gray-500">
                          {{ user.email }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Selected users display -->
                <div class="flex flex-wrap gap-2 mt-2">
                  <div
                    class="flex items-center gap-1 bg-second_accent rounded-full px-3 py-1 text-sm"
                  >
                    <span class="font-medium text-white">{{
                      mode === "new" ? currentName : eventData.creator
                    }}</span>
                    <span class="text-xs text-white ml-1">(Ersteller)</span>
                  </div>
                  <div
                    v-for="user in eventData.users"
                    :key="user.id"
                    class="flex items-center gap-1 bg-gray-50 rounded-full px-3 py-1 text-sm"
                  >
                    <span class="text-gray-600"
                      >{{ user.first_name }} {{ user.last_name }}</span
                    >
                    <button
                      v-if="mode !== 'view'"
                      @click="removeUser(user)"
                      class="ml-1 text-gray-400 hover:text-gray-600 transition-colors"
                    >
                      ×
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <!-- Description -->
            <div class="flex items-start gap-4">
              <div
                class="flex-shrink-0 mt-1 p-2 bg-gray-50 rounded-window text-gray-500"
              >
                <svg
                  width="20"
                  height="20"
                  viewBox="0 0 24 24"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
              </div>
              <div class="flex-grow">
                <textarea
                  v-if="mode !== 'view'"
                  v-model="eventData.description"
                  placeholder="Beschreibung hinzufügen"
                  class="w-full border rounded-window px-3 py-2 focus:ring-2 focus:ring-second_accent focus:border-transparent resize-none border-gray-300"
                  rows="3"
                ></textarea>
                <p v-else class="text-gray-600">
                  {{ eventData.description || "Keine Beschreibung" }}
                </p>
              </div>
            </div>

            <!-- Advanced Settings Toggle -->
            <button
              @click="toggleAdvancedSettings"
              class="w-full flex items-center justify-between px-3 py-2 text-gray-600 hover:bg-gray-50 rounded-window transition-colors sticky top-0 bg-window_bg z-10"
            >
              <span class="font-medium">Weitere Optionen</span>
              <svg
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                class="transition-transform duration-200"
                :class="{ 'rotate-180': showAdvancedSettings }"
              >
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>

            <!-- Advanced Settings Content -->
            <div
              v-show="showAdvancedSettings"
              class="space-y-4 overflow-y-auto"
            >
              <!-- Location -->
              <div class="flex items-start gap-4">
                <div
                  class="flex-shrink-0 mt-1 p-2 bg-gray-50 rounded-window text-gray-500"
                >
                  <svg
                    width="20"
                    height="20"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    <path
                      d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                    <path
                      d="M16.2398 7.76001L14.1198 14.12L7.75977 16.24L9.87977 9.88001L16.2398 7.76001Z"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>
                </div>
                <div class="flex-grow">
                  <select
                    v-if="mode !== 'view'"
                    v-model="eventData.location"
                    class="w-full border rounded-window px-3 py-2 focus:ring-2 focus:ring-second_accent focus:border-transparent border-gray-300"
                  >
                    <option
                      v-for="room in rooms"
                      :key="room.id"
                      :value="room.id"
                    >
                      {{ room.roomNumber }}
                    </option>
                  </select>
                  <span v-else class="text-gray-600">{{
                    eventData.room.roomNumber
                  }}</span>
                </div>
              </div>

              <!-- Category -->
              <div class="flex items-start gap-4">
                <div
                  class="flex-shrink-0 mt-1 p-2 bg-gray-50 rounded-window text-gray-500"
                >
                  <svg
                    width="20"
                    height="20"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    <path
                      d="M10 3H3V10H10V3Z"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                    <path
                      d="M21 3H14V10H21V3Z"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                    <path
                      d="M21 14H14V21H21V14Z"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                    <path
                      d="M10 14H3V21H10V14Z"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>
                </div>
                <div class="flex-grow">
                  <select
                    v-if="mode !== 'view'"
                    v-model="eventData.appointmentCategory"
                    class="w-full border rounded-window px-3 py-2 focus:ring-2 focus:ring-second_accent focus:border-transparent border-gray-300"
                  >
                    <option
                      v-for="category in categories"
                      :key="category.id"
                      :value="category.id"
                    >
                      {{ category.name }}
                    </option>
                  </select>
                  <span v-else class="text-gray-600">{{
                    eventData.appointmentCategory.name
                  }}</span>
                </div>
              </div>

              <!-- Color -->
              <div class="flex items-center gap-4" v-if="mode !== 'view'">
                <div
                  class="flex-shrink-0 p-2 bg-gray-50 rounded-window text-gray-500"
                >
                  <svg
                    width="20"
                    height="20"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    <path
                      d="M11.9999 2.68994L17.6599 8.34994C18.7792 9.46855 19.5417 10.894 19.8508 12.446C20.1599 13.998 20.0018 15.6068 19.3964 17.0689C18.7911 18.531 17.7657 19.7808 16.45 20.66C15.1343 21.5393 13.5874 22.0086 12.0049 22.0086C10.4224 22.0086 8.87549 21.5393 7.55978 20.66C6.24407 19.7808 5.2187 18.531 4.61335 17.0689C4.008 15.6068 3.84988 13.998 4.15899 12.446C4.46809 10.894 5.23054 9.46855 6.34989 8.34994L11.9999 2.68994Z"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>
                </div>
                <div class="flex items-center gap-2">
                  <div
                    v-for="color in predefinedColors"
                    :key="color"
                    @click="eventData.color = color"
                    class="w-6 h-6 rounded-full cursor-pointer hover:scale-110 transition-transform relative"
                    :style="{ backgroundColor: color }"
                  >
                    <div
                      v-if="eventData.color === color"
                      class="absolute inset-0 rounded-full ring-2 ring-offset-2 ring-second_accent"
                    ></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- Footer -->
        <div
          class="flex-shrink-0 flex justify-end gap-2 p-4 bg-gray-50 border-t border-gray-200"
        >
          <button
            v-if="mode === 'view'"
            @click="handleDelete"
            class="px-4 py-2 text-red-600 hover:bg-red-50 rounded-window transition-colors"
          >
            Löschen
          </button>
          <button
            v-if="mode !== 'view'"
            @click="handleSave"
            class="px-4 py-2 bg-second_accent text-white rounded-window hover:bg-second_accent/90 transition-colors"
          >
            Speichern
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.popover-container {
  will-change: transform, opacity;
}

.popover-enter-active,
.popover-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}

.popover-enter-from,
.popover-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

/* Ensure the transition applies to the container */
.popover-enter-active .popover-container,
.popover-leave-active .popover-container {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}

.tutoring-hint-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}
.tutoring-hint-leave-to {
  opacity: 0;
  transform: translateY(-100%);
}

.overflow-y-auto {
  scrollbar-width: thin;
  scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
}

.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background-color: rgba(0, 0, 0, 0.2);
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background-color: rgba(0, 0, 0, 0.3);
}

.progress-bar {
  width: 100%;
  transform-origin: left;
  animation: shrinkBar 5s linear forwards;
}

@keyframes shrinkBar {
  0% {
    transform: translateX(0%);
  }
  100% {
    transform: translateX(-100%);
  }
}
</style>
