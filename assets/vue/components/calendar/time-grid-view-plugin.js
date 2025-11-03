import { sliceEvents, createPlugin, formatDate } from '@fullcalendar/core';
import axios from 'axios';

let CELL_WIDTH;
const PIXELS_PER_MINUTE = () => CELL_WIDTH / 60;
const startHour = 8;
const endHour = 22;
let calendarApi;
let eventClickCallback;
let currentTimeIndicatorInterval;

const ROOM_COLUMN_WIDTH = 150;
const MIN_CELL_WIDTH = 60;

// Speicher für die Räume
let cachedRooms = [];

// Neue Konstanten für die mobile Ansicht
const MOBILE_BREAKPOINT = 768; // md breakpoint in Tailwind
const MAX_ROOMS_PER_GROUP = 6;
const MOBILE_TIME_COLUMN_WIDTH = 50;
const MOBILE_ROOM_COLUMN_WIDTH = 60;

// Funktion zum Prüfen, ob es sich um ein mobiles Gerät handelt
const isMobileDevice = () => {
  return window.innerWidth < MOBILE_BREAKPOINT;
};

// Funktion zum Gruppieren der Räume für die mobile Ansicht
const groupRoomsForMobile = (rooms) => {
  const groups = [];
  for (let i = 0; i < rooms.length; i += MAX_ROOMS_PER_GROUP) {
    groups.push(rooms.slice(i, i + MAX_ROOMS_PER_GROUP));
  }
  return groups;
};

// Aktualisierte Funktion zur Berechnung der Zellbreite
const updateCellWidth = (containerWidth) => {
  if (isMobileDevice()) {
    // Für mobile Geräte: Feste Breite pro Raumzelle
    const availableWidth = containerWidth - MOBILE_TIME_COLUMN_WIDTH;
    const roomsPerRow = Math.min(MAX_ROOMS_PER_GROUP, cachedRooms.length);
    CELL_WIDTH = availableWidth / roomsPerRow;
  } else {
    // Für Desktop: Bisherige Berechnung
    const availableWidth = containerWidth - ROOM_COLUMN_WIDTH;
    const calculatedCellWidth = availableWidth / (endHour - startHour);
    CELL_WIDTH = Math.max(calculatedCellWidth, MIN_CELL_WIDTH);
  }
};

// Helper function to adjust time by adding one hour
const adjustTime = (date) => {
  const adjustedDate = new Date(date);
  adjustedDate.setHours(adjustedDate.getHours() - 1);
  return adjustedDate;
};

const isDateToday = (date) => {
  const today = new Date();
  return (
    date.getDate() === today.getDate() &&
    date.getMonth() === today.getMonth() &&
    date.getFullYear() === today.getFullYear()
  );
};

// Helper function to check if an event is happening right now
const isEventActive = (start, end) => {
  const now = new Date();
  const eventStart = adjustTime(new Date(start)); // Adjust for timezone
  const eventEnd = adjustTime(new Date(end)); // Adjust for timezone

  // Only return true if the current time is between start and end
  return now >= eventStart && now <= eventEnd;
};

const updateCurrentTimeIndicator = (containerEl, currentDate) => {
  if (!isDateToday(currentDate)) {
    const existingIndicator = containerEl.querySelector(
      '.current-time-indicator'
    );
    if (existingIndicator) {
      existingIndicator.remove();
    }
    return;
  }

  const now = new Date();
  const hours = now.getHours();
  const minutes = now.getMinutes();

  // Only show indicator if current time is within the view bounds
  if (hours >= startHour && hours < endHour) {
    const hoursSinceStart = hours - startHour;
    const minutePercentage = minutes / 60;
    const leftPosition = (hoursSinceStart + minutePercentage) * CELL_WIDTH;

    let indicator = containerEl.querySelector('.current-time-indicator');

    if (!indicator) {
      indicator = document.createElement('div');
      indicator.className = 'current-time-indicator';
      const contentArea = containerEl.querySelector('.relative');
      if (contentArea) {
        contentArea.style.position = 'relative'; // Ensure relative positioning
        contentArea.appendChild(indicator);
      }
    }

    // Format current time
    const timeString = now.toLocaleTimeString('de-DE', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    });

    indicator.innerHTML = `
      <div class="absolute z-20" style="left: ${leftPosition}px; top: 0; bottom: 0;">
        <!-- Time Label at Top -->
        <div class="absolute -top-10 left-1/2 -translate-x-1/2">
          <div class="px-1.5 py-0.5 rounded bg-red-500 text-white text-xs font-medium shadow-sm whitespace-nowrap">
            ${timeString}
          </div>
        </div>
        
        <!-- Vertical Line -->
        <div class="absolute top-0 bottom-0 left-1/2 transform -translate-x-1/2" style="width: 2px; background: linear-gradient(180deg, rgb(239 68 68), rgb(239 68 68 / 0.5));">
          <!-- Top Dot -->
          <div class="absolute -top-1 left-1/2 -translate-x-1/2 w-3 h-3 rounded-full bg-red-500"></div>
        </div>
      </div>
    `;
  }
};

// Funktion zum Laden der Räume
const loadRooms = async () => {
  try {
    const response = await axios.get('/api/room');
    const roomsData = Array.isArray(response.data)
      ? response.data
      : response.data['hydra:member'] || [];
    cachedRooms = roomsData.map((room) => ({
      id: room.id,
      name: `Raum ${room.roomNumber}`,
    }));
  } catch (error) {
    console.error('Error fetching rooms:', error);
    cachedRooms = [];
  }
};

// Neue Funktion zur Überprüfung, ob ein Termin ein Nachhilfetermin ist
const isTutoringAppointment = (event) => {
  return event.def.extendedProps && 
         event.def.extendedProps.appointmentCategory && 
         (event.def.extendedProps.appointmentCategory.name === 'Nachhilfetermin' || 
          (typeof event.def.extendedProps.appointmentCategory === 'object' && 
           event.def.extendedProps.appointmentCategory.name === 'Nachhilfetermin'));
};

const timeGridRoomConfig = {
  classNames: ['time-grid-room'],
  content: function (props) {
    const segs = sliceEvents(props, true);
    const hours = Array.from(
      { length: endHour - startHour },
      (_, i) => startHour + i
    );

    if (!CELL_WIDTH) {
      CELL_WIDTH = MIN_CELL_WIDTH;
    }

    // Verwende die zwischengespeicherten Räume
    const rooms = cachedRooms;

    // Wenn keine Räume verfügbar sind, zeige eine Nachricht
    if (rooms.length === 0) {
      return {
        html: `
          <div class="h-full bg-white rounded-lg shadow-sm p-4">
            <p class="text-gray-500">Keine Räume verfügbar</p>
          </div>
        `,
      };
    }

    const calculateEventStyle = (event, isMobile = false, roomIndex = 0) => {
      const startDate = adjustTime(event.instance.range.start);
      const endDate = adjustTime(event.instance.range.end);

      if (isMobile) {
        // Mobile: Position basierend auf Zeit und Raum
        const startHourDecimal = startDate.getHours() + startDate.getMinutes() / 60;
        const endHourDecimal = endDate.getHours() + endDate.getMinutes() / 60;
        
        // Berechne die exakte Position basierend auf der Zeit
        const topPosition = (startHourDecimal - startHour) * 60; // 60px pro Stunde
        const height = (endHourDecimal - startHourDecimal) * 60;
        
        return {
          top: `${topPosition}px`,
          height: `${height}px`,
          left: `${MOBILE_TIME_COLUMN_WIDTH + roomIndex * CELL_WIDTH}px`,
          width: `${CELL_WIDTH}px`,
        };
      } else {
        // Desktop: Bisherige Berechnung
        const startHourDecimal = startDate.getHours() + startDate.getMinutes() / 60;
        const endHourDecimal = endDate.getHours() + endDate.getMinutes() / 60;
        const clampedEndHourDecimal = Math.min(endHourDecimal, endHour);
        const leftOffset = (startHourDecimal - startHour) * CELL_WIDTH;
        const width = (clampedEndHourDecimal - startHourDecimal) * CELL_WIDTH;

        return {
          left: `${leftOffset}px`,
          width: `${width}px`,
        };
      }
    };

    // Prüfen, ob mobile Ansicht verwendet werden soll
    const useMobileView = isMobileDevice();

    if (useMobileView) {
      // MOBILE ANSICHT
      const roomGroups = groupRoomsForMobile(rooms);
      
      let html = `
        <div class="h-full bg-white rounded-lg shadow-sm overflow-x-hidden overflow-y-auto md:overflow-x-auto">
      `;
      
      // Für jede Raumgruppe einen eigenen Zeitplan erstellen
      roomGroups.forEach((roomGroup, groupIndex) => {
        html += `
          <div class="room-group mb-8 ${groupIndex > 0 ? 'mt-8 pt-8 border-t border-gray-200' : ''}">
            <!-- Header für diese Raumgruppe -->
            <div class="sticky top-0 z-20 flex bg-white/95 backdrop-blur-sm border-b border-gray-100">
              <!-- Zeitachse Header -->
              <div class="sticky left-0 z-30 bg-white/95 backdrop-blur-sm flex items-center justify-center" 
                   style="width: ${MOBILE_TIME_COLUMN_WIDTH}px; min-width: ${MOBILE_TIME_COLUMN_WIDTH}px;">
                <span class="font-medium text-gray-700 text-xs">Zeit</span>
              </div>
              
              <!-- Raumüberschriften -->
              ${roomGroup.map((room, index) => `
                <div class="flex-1 text-center py-3 px-1" style="width: ${CELL_WIDTH}px; min-width: ${CELL_WIDTH}px;">
                  <span class="font-medium text-gray-800 text-xs truncate block">${room.name}</span>
                </div>
              `).join('')}
            </div>
            
            <!-- Zeitgitter für diese Raumgruppe -->
            <div class="relative" style="height: ${(endHour - startHour) * 60}px;">
              <!-- Stundenlinien -->
              ${hours.map((hour) => `
                <div class="absolute w-full border-t border-gray-200 flex" 
                     style="top: ${(hour - startHour) * 60}px; left: 0; right: 0;">
                  <!-- Zeitlabel -->
                  <div class="sticky left-0 z-10 bg-white flex items-center justify-end pr-2 text-xs text-gray-500 font-medium" 
                       style="width: ${MOBILE_TIME_COLUMN_WIDTH}px; height: 20px;">
                    ${hour.toString().padStart(2, '0')}:00
                  </div>
                </div>
              `).join('')}
              
              <!-- Halbstundenlinien -->
              ${hours.map((hour) => `
                <div class="absolute w-full border-t border-gray-100" 
                     style="top: ${(hour - startHour) * 60 + 30}px; left: ${MOBILE_TIME_COLUMN_WIDTH}px; right: 0;"></div>
              `).join('')}
              
              <!-- Viertelstundenlinien -->
              ${hours.flatMap((hour) => [15, 45].map((minute) => `
                <div class="absolute w-full border-t border-gray-50" 
                     style="top: ${(hour - startHour) * 60 + minute}px; left: ${MOBILE_TIME_COLUMN_WIDTH}px; right: 0;"></div>
              `)).join('')}
              
              <!-- Vertikale Linien für Raumtrennung -->
              ${roomGroup.map((room, index) => `
                <div class="absolute h-full border-l border-gray-100" 
                     style="left: ${MOBILE_TIME_COLUMN_WIDTH + index * CELL_WIDTH}px; top: 0;"></div>
              `).join('')}
              
              <!-- Events für diese Raumgruppe -->
              ${roomGroup.map((room, roomIndex) => {
                // Filtere Events für diesen Raum
                const roomEvents = segs.filter(
                  (event) =>
                    event.def.extendedProps &&
                    event.def.extendedProps.room &&
                    (event.def.extendedProps.room.id === room.id ||
                     event.def.extendedProps.room === room.id ||
                     parseInt(event.def.extendedProps.room) === room.id)
                );
                
                return roomEvents.map((event) => {
                  const style = calculateEventStyle(event, true, roomIndex);
                  const adjustedStart = adjustTime(event.instance.range.start);
                  const adjustedEnd = adjustTime(event.instance.range.end);
                  const isActive = isEventActive(adjustedStart, adjustedEnd);
                  
                  const startTime = formatDate(adjustedStart, {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false,
                  });
                  const endTime = formatDate(adjustedEnd, {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false,
                  });
                  
                  const eventDetails = {
                    id: event.def.publicId,
                    title: event.def.title,
                    start: event.instance.range.start.toISOString(),
                    end: event.instance.range.end.toISOString(),
                    description: event.def.extendedProps.description || '',
                    color: event.def.ui.backgroundColor || '#3788d8',
                    room: event.def.extendedProps.room,
                  };
                  
                  const isTutoring = event.def.extendedProps && 
                                    event.def.extendedProps.appointmentCategory && 
                                    ((typeof event.def.extendedProps.appointmentCategory === 'object' && 
                                      event.def.extendedProps.appointmentCategory.name === 'Nachhilfetermin') ||
                                     event.def.extendedProps.appointmentCategory === 'Nachhilfetermin');
                  
                  const tooltipContent = `${event.def.title}\n${startTime} - ${endTime}`;
                  
                  const eventColor = eventDetails.color;
                  const darkerColor = eventColor
                    .replace(/^#/, '')
                    .match(/.{2}/g)
                    .map((x) =>
                      Math.max(0, parseInt(x, 16) - 20)
                        .toString(16)
                        .padStart(2, '0')
                    )
                    .join('');
                  
                  const activeClass = isActive
                    ? 'ring-1 ring-yellow-300 shadow-md'
                    : '';
                  
                  return `
                    <div 
                      class="absolute rounded-md shadow-sm cursor-pointer transition-all 
                             hover:shadow-md hover:-translate-y-[1px] event-item group/event ${activeClass}"
                      style="top: ${style.top}; height: ${style.height}; left: ${style.left}; width: ${style.width}; 
                             background: linear-gradient(to right, ${eventColor}, #${darkerColor});"
                      data-event-details='${JSON.stringify(eventDetails)}'
                      data-is-tutoring='${isTutoring}'
                      title="${tooltipContent.replace(/"/g, '&quot;')}"
                    >
                      <div class="relative h-full p-1 flex flex-col justify-center overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-r 
                                    from-white/[0.05] to-transparent opacity-0 group-hover/event:opacity-100 
                                    transition-opacity"></div>
                        <div class="relative">
                          <div class="font-medium text-white text-xs truncate">
                            ${event.def.title}
                          </div>
                          <div class="flex items-center space-x-1 mt-0.5">
                            <span class="text-[10px] text-white/90 truncate">${startTime}-${endTime}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  `;
                }).join('');
              }).join('')}
            </div>
          </div>
        `;
      });
      
      html += `</div>`;
      
      return { html };
    } else {
      // DESKTOP ANSICHT - Bisheriger Code bleibt unverändert
      let html = `
        <div class="h-full bg-white rounded-lg shadow-sm overflow-x-auto">
          <!-- Header Container -->
          <div class="sticky top-0 z-20 flex bg-white/95 backdrop-blur-sm border-b border-gray-100" 
               style="min-width: calc(${ROOM_COLUMN_WIDTH}px + ${MIN_CELL_WIDTH * (endHour - startHour)}px)">
            <!-- Room Header -->
            <div class="sticky left-0 z-30 bg-white/95 backdrop-blur-sm flex items-center px-6 py-4" 
                 style="width: ${ROOM_COLUMN_WIDTH}px; min-width: ${ROOM_COLUMN_WIDTH}px;">
              <div class="flex items-center space-x-2">
              <!--
                <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                -->
                <span class="font-medium text-gray-700">Räume</span>
              </div>
            </div>
            
            <!-- Time Headers -->
            <div class="relative flex-1" style="width: ${CELL_WIDTH * (endHour - startHour)}px">
              ${hours
                .map(
                  (hour) => `
                  <div class="absolute h-full flex items-center" style="left: ${(hour - startHour) * CELL_WIDTH}px">
                    <div class="relative w-full">
                      <!-- Time Label -->
                      <div class="absolute -left-[1px] -top-3 flex items-center">
                        <div class="relative flex items-center">
                          <div class="w-[1px] h-3 bg-gray-300"></div>
                          <span class="ml-1.5 text-xs font-medium text-gray-500">
                            ${hour.toString().padStart(2, '0')}:00
                          </span>
                        </div>
                      </div>
                      <!-- Vertical Line -->
                      <div class="absolute top-4 left-0 w-[1px] h-full bg-gray-100"></div>
                    </div>
                  </div>
                `
                )
                .join('')}
            </div>
          </div>

          <!-- Main Content Area -->
          <div class="relative" style="min-width: calc(${ROOM_COLUMN_WIDTH}px + ${MIN_CELL_WIDTH * (endHour - startHour)}px)">
      `;

      // Generiere Raumzeilen für jeden Raum
      rooms.forEach((room) => {
        html += `
          <div class="flex border-b border-gray-100 group/row">
            <!-- Room Name -->
            <div class="sticky left-0 z-10 bg-white flex items-center px-6 py-5 transition-colors group-hover/row:bg-gray-50/80" 
                 style="width: ${ROOM_COLUMN_WIDTH}px; min-width: ${ROOM_COLUMN_WIDTH}px;">
              <span class="font-medium text-gray-800">${room.name}</span>
            </div>
            
            <!-- Time Grid -->
            <div class="relative flex-1 transition-colors group-hover/row:bg-gray-50/80" 
                 style="height: 100px; width: ${CELL_WIDTH * (endHour - startHour)}px">
              ${hours
                .map(
                  (hour) => `
                  <!-- Full hour line -->
                  <div class="absolute h-full border-l border-gray-100 transition-colors" 
                       style="left: ${(hour - startHour) * CELL_WIDTH}px">
                  </div>
                `
                )
                .join('')}
        `;

        // Filtere Events für diesen Raum
        const roomEvents = segs.filter(
          (event) =>
            event.def.extendedProps &&
            event.def.extendedProps.room &&
            (event.def.extendedProps.room.id === room.id ||
              event.def.extendedProps.room === room.id ||
              parseInt(event.def.extendedProps.room) === room.id)
        );

        roomEvents.forEach((event) => {
          const style = calculateEventStyle(event);
          const adjustedStart = adjustTime(event.instance.range.start);
          const adjustedEnd = adjustTime(event.instance.range.end);
          const isActive = isEventActive(adjustedStart, adjustedEnd);

          const startTime = formatDate(adjustedStart, {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
          });
          const endTime = formatDate(adjustedEnd, {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
          });

          const eventDetails = {
            id: event.def.publicId,
            title: event.def.title,
            start: event.instance.range.start.toISOString(),
            end: event.instance.range.end.toISOString(),
            description: event.def.extendedProps.description || '',
            color: event.def.ui.backgroundColor || '#3788d8',
            room: event.def.extendedProps.room,
          };

          // Prüfen, ob es sich um einen Nachhilfetermin handelt
          const isTutoring = event.def.extendedProps && 
                            event.def.extendedProps.appointmentCategory && 
                            ((typeof event.def.extendedProps.appointmentCategory === 'object' && 
                              event.def.extendedProps.appointmentCategory.name === 'Nachhilfetermin') ||
                             event.def.extendedProps.appointmentCategory === 'Nachhilfetermin');

          // Create tooltip content
          const tooltipContent = `${event.def.title}\n${startTime} - ${endTime}`;

          const eventColor = eventDetails.color;
          const darkerColor = eventColor
            .replace(/^#/, '')
            .match(/.{2}/g)
            .map((x) =>
              Math.max(0, parseInt(x, 16) - 20)
                .toString(16)
                .padStart(2, '0')
            )
            .join('');

          // Subtle highlight for active events
          const activeClass = isActive
            ? 'ring-2 ring-yellow-300 ring-offset-2 shadow-lg'
            : '';

          html += `
            <div 
              class="absolute top-2 bottom-2 rounded-lg shadow-sm cursor-pointer transition-all 
                     hover:shadow-md hover:-translate-y-[1px] event-item group/event ${activeClass}"
              style="left: ${style.left}; width: ${style.width}; 
                     background: linear-gradient(to right, ${eventColor}, #${darkerColor});"
              data-event-details='${JSON.stringify(eventDetails)}'
              data-is-tutoring='${isTutoring}'
              title="${tooltipContent.replace(/"/g, '&quot;')}"
            >
              <div class="relative h-full p-3 flex flex-col justify-center overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-r 
                            from-white/[0.05] to-transparent opacity-0 group-hover/event:opacity-100 
                            transition-opacity"></div>
                <div class="relative">
                  <div class="font-medium text-white text-sm truncate">
                    ${event.def.title}
                  </div>
                  <div class="flex items-center space-x-2 mt-0.5">
                    <div class="flex-shrink-0">
                      <svg class="w-3 h-3 text-white/90" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                      </svg>
                    </div>
                    <span class="text-xs text-white/90 truncate">${startTime} - ${endTime}</span>
                  </div>
                </div>
              </div>
            </div>
          `;
        });

        html += `
            </div>
          </div>
        `;
      });

      html += `
          </div>
        </div>
      `;

      return { html };
    }
  },
  didMount: async function (props) {
    if (!props.el) return;

    // Lade die Räume beim ersten Mounten
    if (cachedRooms.length === 0) {
      await loadRooms();
      // Render erneut nach dem Laden der Räume
      calendarApi?.render();
    }

    const containerElement = props.el.closest('.fc-view');

    if (!containerElement) {
      console.error('Container element not found!');
      return;
    }

    const containerWidth = containerElement.offsetWidth;
    updateCellWidth(containerWidth);

    // Setup current time indicator and update active events
    const updateTimeAndEvents = () => {
      if (isDateToday(props.dateProfile.currentDate)) {
        // Nur in der Desktop-Ansicht den Zeitindikator anzeigen
        if (!isMobileDevice()) {
          updateCurrentTimeIndicator(props.el, props.dateProfile.currentDate);
        }

        // Update active events
        const eventItems = props.el.querySelectorAll('.event-item');
        eventItems.forEach((item) => {
          const eventDetails = JSON.parse(
            item.getAttribute('data-event-details')
          );
          const isActive = isEventActive(
            new Date(eventDetails.start),
            new Date(eventDetails.end)
          );

          // Toggle active class and badge
          if (isActive) {
            if (isMobileDevice()) {
              item.classList.add(
                'ring-1',
                'ring-yellow-300',
                'shadow-md'
              );
            } else {
              item.classList.add(
                'ring-2',
                'ring-yellow-300',
                'ring-offset-2',
                'shadow-lg'
              );
            }
          } else {
            item.classList.remove(
              'ring-1',
              'ring-2',
              'ring-yellow-300',
              'ring-offset-2',
              'shadow-md',
              'shadow-lg'
            );
          }
        });
      }
    };

    // Initial update
    updateTimeAndEvents();

    // Setup interval for updating time indicator and active events
    if (currentTimeIndicatorInterval) {
      clearInterval(currentTimeIndicatorInterval);
    }

    // Update every 30 seconds
    currentTimeIndicatorInterval = setInterval(updateTimeAndEvents, 30000);

    // Event-Delegation: Füge den Click-Listener zum Container hinzu
    props.el.addEventListener('click', (e) => {
      // Finde das nächste Event-Item-Element
      let target = e.target;
      while (target && !target.classList.contains('event-item')) {
        if (target === props.el) return; // Nicht gefunden
        target = target.parentElement;
      }
      
      if (!target) return; // Kein Event-Item gefunden
      
      const eventDetails = JSON.parse(
        target.getAttribute('data-event-details')
      );
      
      // Prüfen, ob es sich um einen Nachhilfetermin handelt
      const isTutoring = target.getAttribute('data-is-tutoring') === 'true';
      
      if (isTutoring) {
        // Wenn es ein Nachhilfetermin ist, zur Detailseite weiterleiten
        window.location.href = `/nachhilfetermin/${eventDetails.id}`;
      } else if (typeof eventClickCallback === 'function') {
        // Sonst den normalen Callback aufrufen
        eventClickCallback(eventDetails);
      }
    });

    // Setup resize observer
    const resizeObserver = new ResizeObserver(() => {
      const containerWidth = containerElement.offsetWidth;
      updateCellWidth(containerWidth);
      calendarApi?.render();
    });

    resizeObserver.observe(containerElement);
    props.el._resizeObserver = resizeObserver;
    
    // Füge einen Event-Listener für Bildschirmgrößenänderungen hinzu
    window.addEventListener('resize', () => {
      updateCellWidth(containerElement.offsetWidth);
      calendarApi?.render();
    });
    props.el._resizeListener = true;
  },
  willUnmount: function (props) {
    if (props.el?._resizeObserver) {
      props.el._resizeObserver.disconnect();
      delete props.el._resizeObserver;
    }
    
    // Entferne den resize-Listener
    if (props.el?._resizeListener) {
      window.removeEventListener('resize', () => {});
      delete props.el._resizeListener;
    }
    
    // Clear the current time indicator interval
    if (currentTimeIndicatorInterval) {
      clearInterval(currentTimeIndicatorInterval);
      currentTimeIndicatorInterval = null;
    }
  },
};

export const setCalendarApi = (api) => {
  calendarApi = api;
};

export const setEventClickCallback = (callback) => {
  eventClickCallback = callback;
};

export default createPlugin({
  views: {
    timeGridRoom: timeGridRoomConfig,
  },
});
