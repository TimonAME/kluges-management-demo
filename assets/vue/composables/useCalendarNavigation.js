import { ref } from "vue";
import { updateUrlWithView } from "../utils/calendarUtils";
import { parseMonthString } from "../utils/calendarUtils";

/**
 * Composable for handling calendar navigation and view management
 * @param {Ref} calendarWrapperRef - Reference to calendar wrapper component
 * @param {Ref} currentView - Reference to current view state
 * @param {Function} loadEvents - Function to load events
 * @returns {Object} Navigation methods and state
 */
export default function useCalendarNavigation(calendarWrapperRef, currentView, loadEvents) {
  const scrollCooldown = ref(false);

  /**
   * Handles navigation to different dates
   * @param {string|Object} action - Navigation action ('prev', 'next', 'today') or date object
   */
  const handleDateNavigation = (action) => {
    const calendarApi = calendarWrapperRef.value?.getApi();
    if (!calendarApi) return;

    // Wenn action ein Objekt mit type und date ist (vom mobilen Date Picker)
    if (typeof action === 'object' && action.type === 'date' && action.date) {
      calendarApi.gotoDate(action.date);
      
      // URL mit aktuellem Datum aktualisieren
      const url = new URL(window.location);
      const dateStr = action.date.toISOString().split('T')[0];
      url.searchParams.set('date', dateStr);
      window.history.replaceState({}, '', url);
      
      // Events nach Datumsänderung neu laden
      loadEvents();
      
      return;
    }

    // Bestehende Navigation
    switch (action) {
      case "prev":
        calendarApi.prev();
        break;
      case "next":
        calendarApi.next();
        break;
      case "today":
        calendarApi.today();
        break;
    }
    
    // Events nach Datumsänderung neu laden
    loadEvents();
  };

  /**
   * Handles view changes (month, week, day, room)
   * @param {Object} event - View change event
   */
  const handleViewChange = (event) => {
    const view = event.target.value;
    const calendarApi = calendarWrapperRef.value?.getApi();
    if (!calendarApi) return;

    calendarApi.changeView(view);
    currentView.value = view;
  };

  /**
   * Handles date selection from mini calendar
   * @param {Date} date - Selected date
   */
  const handleDateSelection = (date) => {
    const calendarApi = calendarWrapperRef.value?.getApi();
    if (!calendarApi) return;

    console.log(date);

    calendarApi.gotoDate(date);

    // Ensure calendar size is updated after view change
    requestAnimationFrame(() => {
      calendarApi.updateSize();
    });
  };

  /**
   * Handles mouse wheel scrolling for month navigation
   * @param {WheelEvent} event - Wheel event
   */
  const handleScroll = (event) => {
    if (scrollCooldown.value) return;

    const calendarApi = calendarWrapperRef.value?.getApi();
    if (!calendarApi || calendarApi.view.type !== "dayGridMonth") return;

    if (event.deltaY < 0) {
      handleDateNavigation("prev");
    } else if (event.deltaY > 0) {
      handleDateNavigation("next");
    }

    // Implement scroll cooldown to prevent rapid navigation
    scrollCooldown.value = true;
    setTimeout(() => {
      scrollCooldown.value = false;
    }, 500);
  };

  /**
   * Handles navigation through day clicks
   * @param {Date} date - Clicked date
   * @param {Event} jsEvent - Click event
   */
  const handleNavLinkDayClick = (date, jsEvent) => {
    const calendarApi = calendarWrapperRef.value?.getApi();
    if (!calendarApi) return;

    const url = new URL(window.location);
    url.searchParams.set("view", "timeGridDay");
    url.searchParams.set("date", date.toISOString().split("T")[0]);
    window.history.pushState({}, "", url);

    calendarApi.changeView("timeGridDay", date);
    currentView.value = "timeGridDay";
  };

  /**
   * Initializes calendar view from URL parameters
   */
  const initializeFromUrl = () => {
    const calendarApi = calendarWrapperRef.value?.getApi();
    if (!calendarApi) return;

    const urlParams = new URLSearchParams(window.location.search);
    const viewParam = urlParams.get("view") || "dayGridMonth";
    const dateParam = urlParams.get("date");

    currentView.value = viewParam;
    calendarApi.changeView(viewParam);

    if (dateParam) {
      const date = parseMonthString(dateParam, viewParam);
      if (date) {
        calendarApi.gotoDate(date);
      }
    }
  };

  /**
   * Sets up scroll event listeners
   */
  const setupScrollListener = () => {
    const calendarElement = calendarWrapperRef.value?.$refs?.calendar?.$el;
    if (!calendarElement) return;

    calendarElement.addEventListener("wheel", handleScroll);

    // Return cleanup function
    return () => {
      calendarElement.removeEventListener("wheel", handleScroll);
    };
  };

  return {
    handleDateNavigation,
    handleViewChange,
    handleDateSelection,
    handleNavLinkDayClick,
    initializeFromUrl,
    setupScrollListener,
  };
}
