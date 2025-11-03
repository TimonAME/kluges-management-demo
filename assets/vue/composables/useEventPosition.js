import { ref, onMounted, onUnmounted } from "vue";

// Constants for positioning
const POPOVER_WIDTH = 384;
const POPOVER_HEIGHT = 600;
const MARGIN = 16;
const BOTTOM_MARGIN = 0;

/**
 * Calculate initial position for the modal
 */
export function calculateInitialPosition(info) {
  if (!info?.el) return { top: "0px", left: "0px" };

  const calendarEl = document.querySelector(".calendar-wrapper");
  if (!calendarEl) return { top: "0px", left: "0px" };

  const calendarRect = calendarEl.getBoundingClientRect();

  // Get date column element
  let dateColumnEl = info.el;
  if (info.event) {
    const date = info.event.start;
    // Korrektur für AllDay Events
    const adjustedDate = new Date(date);
    if (info.event.allDay) {
      // Eine Stunde addieren für AllDay Events
      adjustedDate.setHours(adjustedDate.getHours() + 1);
    }
    const dateStr = adjustedDate.toISOString().split("T")[0];

    if (info.view.type === "dayGridMonth") {
      dateColumnEl = calendarEl.querySelector(
        `.fc-day[data-date="${dateStr}"]`,
      );
    } else if (info.view.type.includes("timeGrid")) {
      dateColumnEl = calendarEl.querySelector(
        `.fc-timegrid-col[data-date="${dateStr}"]`,
      );
    }
  }

  if (!dateColumnEl) return { top: "0px", left: "0px" };

  const dateColumnRect = dateColumnEl.getBoundingClientRect();
  const viewportHeight = window.innerHeight;

  // Calculate horizontal position
  let x;
  if (dateColumnRect.right + MARGIN + POPOVER_WIDTH <= calendarRect.right) {
    x = dateColumnRect.right + MARGIN;
  } else {
    x = dateColumnRect.left - MARGIN - POPOVER_WIDTH;
  }

  // Calculate vertical position
  let y;
  if (dateColumnRect.top + POPOVER_HEIGHT > viewportHeight - BOTTOM_MARGIN) {
    y = viewportHeight - POPOVER_HEIGHT - BOTTOM_MARGIN;
  } else {
    y = dateColumnRect.top;
  }

  // Ensure minimum top margin
  y = Math.max(MARGIN, y);

  return {
    position: "fixed",
    top: `${y}px`,
    left: `${x}px`,
  };
}

/**
 * Composable for managing event popover positioning
 */
export default function useEventPosition() {
  const modalPosition = ref({ top: "0px", left: "0px" });
  const isPositioned = ref(false);

  const calculatePosition = (info) => {
    if (!info?.el) return;
    modalPosition.value = calculateInitialPosition(info);
    isPositioned.value = true;
  };

  /**
   * Update position when window is resized
   */
  const handleResize = () => {
    requestAnimationFrame(() => {
      const popoverEl = document.querySelector(".event-popover");
      if (popoverEl && isPositioned.value) {
        calculatePosition({ el: popoverEl });
      }
    });
  };

  // Lifecycle hooks
  onMounted(() => {
    window.addEventListener("resize", handleResize);
  });

  onUnmounted(() => {
    window.removeEventListener("resize", handleResize);
  });

  return {
    modalPosition,
    isPositioned,
    calculatePosition,
  };
}
