<template>
  <div class="absolute bg-window_bg rounded-window shadow-window w-full h-full left-0 top-0 overflow-auto">
    <!-- Loading Overlay -->
    <div v-if="isLoading" class="absolute inset-0 bg-white bg-opacity-75 z-50 flex items-center justify-center">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-secondmain mx-auto"></div>
        <p class="mt-4 text-gray-600">Wird geladen...</p>
      </div>
    </div>

    <!-- Hero Header -->
    <div class="bg-white border-b border-gray-100 sticky top-0 z-10 rounded-t-window">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3 sm:py-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
          <!-- Session Info -->
          <div>
            <h2 class="text-lg sm:text-xl font-medium text-gray-800">{{ appointment?.title || 'Nachhilfetermin' }}</h2>
            <div class="flex flex-wrap items-center mt-1 gap-2 sm:gap-0 sm:space-x-3">
              <span class="text-xs sm:text-sm text-gray-500">{{ sessionDate }}</span>
              <span class="hidden sm:inline text-gray-300">•</span>
              <span class="text-xs sm:text-sm text-gray-500">{{ sessionTimeRange }}</span>
              <span class="hidden sm:inline text-gray-300">•</span>
              <span class="text-xs sm:text-sm text-gray-500">
                {{ appointment?.room ? `Raum ${appointment.room.roomNumber}` : 'Kein Raum zugewiesen' }}
              </span>
              <span class="hidden sm:inline text-gray-300">•</span>
              <span
                  class="px-2 py-0.5 rounded-full text-xs sm:text-sm"
                  :class="sessionStatusClass"
              >
                {{ sessionStatusText }}
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center space-x-3 mt-3 sm:mt-0">
            <div
                v-if="sessionStatus === 'IN_PROGRESS'"
                class="px-3 sm:px-4 py-1 sm:py-2 bg-white border border-gray-200 rounded-lg text-xs sm:text-sm text-gray-600 flex items-center space-x-2"
            >
              <Clock class="w-3 h-3 sm:w-4 sm:h-4" />
              <span>{{ timeRemaining }}</span>
            </div>

            <button
                v-if="mode === 'edit'"
                @click="saveSession"
                class="px-3 sm:px-4 py-1 sm:py-2 bg-main text-white rounded-lg text-xs sm:text-sm hover:bg-opacity-90 flex items-center space-x-2 transition-colors"
            >
              <Save class="w-3 h-3 sm:w-4 sm:h-4" />
              <span>Speichern</span>
            </button>
            
            <button
                v-if="mode === 'normal'"
                @click="switchToEditMode"
                class="px-3 sm:px-4 py-1 sm:py-2 bg-main text-white rounded-lg text-xs sm:text-sm hover:bg-opacity-90 flex items-center space-x-2 transition-colors"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3 sm:w-4 sm:h-4">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
              <span>Bearbeiten</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-4 sm:py-6">
      <!-- Attendance Section -->
      <div class="bg-white rounded-xl border border-gray-200 mb-4 sm:mb-6">
        <div class="flex items-center justify-between px-3 sm:px-4 py-2 sm:py-3 border-b border-gray-100">
          <div class="flex items-center space-x-2">
            <Users class="w-4 h-4 text-main" />
            <h3 class="font-medium text-gray-700">Anwesenheit</h3>
          </div>
        </div>
        <div class="divide-y divide-gray-100">
          <div
              v-for="student in students"
              :key="student.id"
              class="flex flex-col sm:flex-row sm:items-center sm:justify-between px-3 sm:px-4 py-3 hover:bg-gray-50"
          >
            <div class="flex items-center space-x-3 mb-2 sm:mb-0">
              <div v-if="getUserAvatar(student)" class="w-6 h-6 sm:w-8 sm:h-8 rounded-full overflow-hidden">
                <img
                    :src="getUserAvatar(student)"
                    :alt="student.name"
                    class="w-full h-full object-cover"
                />
              </div>
              <div v-else-if="profilePictureErrors[student.id]" 
                   class="w-6 h-6 sm:w-8 sm:h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-medium text-gray-600">
                {{ getInitialsAvatar(student.firstName, student.lastName) }}
              </div>
              <div v-else class="w-6 h-6 sm:w-8 sm:h-8 rounded-full bg-gray-200 flex items-center justify-center">
                <div class="animate-pulse w-4 h-4 rounded-full bg-gray-300"></div>
              </div>
              <span class="text-xs sm:text-sm font-medium text-gray-700">{{ student.name }}</span>
            </div>
            <div v-if="mode === 'edit'" class="flex flex-wrap gap-2 sm:space-x-2">
              <button
                  @click="updateAttendance(student.id, 'present')"
                  class="px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm"
                  :class="[
                  student.status === 'present'
                    ? 'bg-green-100 text-green-700'
                    : 'bg-gray-100 text-gray-600 hover:bg-green-50 hover:text-green-600'
                ]"
              >
                Anwesend
              </button>
              <button
                  @click="updateAttendance(student.id, 'excused')"
                  class="px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm"
                  :class="[
                  student.status === 'excused'
                    ? 'bg-blue-100 text-blue-700'
                    : 'bg-gray-100 text-gray-600 hover:bg-blue-50 hover:text-blue-600'
                ]"
              >
                Entschuldigt
              </button>
              <button
                  @click="updateAttendance(student.id, 'absent')"
                  class="px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm"
                  :class="[
                  student.status === 'absent'
                    ? 'bg-red-100 text-red-700'
                    : 'bg-gray-100 text-gray-600 hover:bg-red-50 hover:text-red-600'
                ]"
              >
                Fehlt
              </button>
            </div>
            <div v-else class="text-xs sm:text-sm font-medium">
              <span v-if="student.status === 'present'" class="px-2 sm:px-3 py-1 rounded-full bg-green-100 text-green-700">
                Anwesend
              </span>
              <span v-else-if="student.status === 'excused'" class="px-2 sm:px-3 py-1 rounded-full bg-blue-100 text-blue-700">
                Entschuldigt
              </span>
              <span v-else-if="student.status === 'absent'" class="px-2 sm:px-3 py-1 rounded-full bg-red-100 text-red-700">
                Fehlt
              </span>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
        <!-- Left Column - Student Progress -->
        <div class="bg-white rounded-xl border border-gray-200">
          <div class="flex items-center justify-between px-3 sm:px-4 py-2 sm:py-3 border-b border-gray-100">
            <div class="flex items-center space-x-2">
              <BookOpen class="w-4 h-4 text-main" />
              <h3 class="font-medium text-gray-700">Behandelte Themen & Notizen</h3>
            </div>
          </div>
          <div class="divide-y divide-gray-100">
            <div
                v-for="student in students"
                :key="student.id"
                class="p-3 sm:p-4"
            >
              <div class="flex items-center space-x-2 mb-2 sm:mb-3">
                <div v-if="getUserAvatar(student)" class="w-5 h-5 sm:w-6 sm:h-6 rounded-full overflow-hidden">
                  <img
                      :src="getUserAvatar(student)"
                      :alt="student.name"
                      class="w-full h-full object-cover"
                  />
                </div>
                <div v-else-if="profilePictureErrors[student.id]" 
                     class="w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-gray-200 flex items-center justify-center text-xs font-medium text-gray-600">
                  {{ getInitialsAvatar(student.firstName, student.lastName) }}
                </div>
                <div v-else class="w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-gray-200 flex items-center justify-center">
                  <div class="animate-pulse w-3 h-3 rounded-full bg-gray-300"></div>
                </div>
                <span class="text-xs sm:text-sm font-medium text-gray-700">{{ student.name }}</span>
              </div>
              <div class="space-y-2 sm:space-y-3">
                <input
                    v-if="mode === 'edit'"
                    v-model="student.currentTopic"
                    class="w-full px-2 sm:px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg text-xs sm:text-sm"
                    :placeholder="`Behandeltes Thema für ${student.name.split(' ')[0]}...`"
                />
                <div v-else class="text-xs sm:text-sm text-gray-700 mb-2">
                  <div v-if="student.currentTopic" class="mb-2">
                    <span class="font-semibold text-gray-700 inline-block mb-1">Thema:</span>
                    <p class="pl-1">{{ student.currentTopic }}</p>
                  </div>
                  <div v-else class="text-gray-400 mb-2">Kein Thema angegeben</div>
                  
                  <div v-if="student.notes" class="mt-3">
                    <span class="font-semibold text-gray-700 inline-block mb-1">Notizen:</span>
                    <p class="pl-1 whitespace-pre-line">{{ student.notes }}</p>
                  </div>
                  <div v-else class="text-gray-400 mt-3">Keine Notizen vorhanden</div>
                </div>
                
                <textarea
                    v-if="mode === 'edit'"
                    v-model="student.notes"
                    rows="3"
                    class="w-full px-2 sm:px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg text-xs sm:text-sm"
                    :placeholder="`Notizen zu ${student.name.split(' ')[0]}...`"
                ></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Homework -->
        <div class="space-y-4 sm:space-y-6">
          <!-- Previous Homework -->
          <div class="bg-white rounded-xl border border-gray-200">
            <div class="flex items-center justify-between px-3 sm:px-4 py-2 sm:py-3 border-b border-gray-100">
              <div class="flex items-center space-x-2">
                <CheckSquare class="w-4 h-4 text-main" />
                <h3 class="font-medium text-gray-700">Hausaufgaben vom letzten Mal</h3>
              </div>
            </div>
            <div class="p-3 sm:p-4 space-y-3 sm:space-y-4">
              <div
                  v-for="student in students"
                  :key="student.id"
                  class="space-y-2"
              >
                <div class="flex items-center space-x-2">
                  <div v-if="getUserAvatar(student)" class="w-5 h-5 sm:w-6 sm:h-6 rounded-full overflow-hidden">
                    <img
                        :src="getUserAvatar(student)"
                        :alt="student.name"
                        class="w-full h-full object-cover"
                    />
                  </div>
                  <div v-else-if="profilePictureErrors[student.id]" 
                       class="w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-gray-200 flex items-center justify-center text-xs font-medium text-gray-600">
                    {{ getInitialsAvatar(student.firstName, student.lastName) }}
                  </div>
                  <div v-else class="w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-gray-200 flex items-center justify-center">
                    <div class="animate-pulse w-3 h-3 rounded-full bg-gray-300"></div>
                  </div>
                  <span class="text-xs sm:text-sm font-medium text-gray-700">{{ student.name }}</span>
                </div>
                <div
                    v-for="task in previousHomework"
                    :key="task.id"
                    class="flex items-start space-x-2 sm:space-x-3 ml-6 sm:ml-8"
                >
                  <input
                      v-if="mode === 'edit'"
                      type="checkbox"
                      v-model="task.completedBy[student.id]"
                      class="mt-0.5 sm:mt-1 rounded border-gray-300 text-main"
                  />
                  <div v-else-if="task.completedBy[student.id]" class="text-green-500 mt-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                  <div v-else class="text-gray-400 mt-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </div>
                  <div class="text-xs sm:text-sm text-gray-700">{{ task.description }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- New Homework -->
          <div class="bg-white rounded-xl border border-gray-200">
            <div class="flex items-center justify-between px-3 sm:px-4 py-2 sm:py-3 border-b border-gray-100">
              <div class="flex items-center space-x-2">
                <Book class="w-4 h-4 text-main" />
                <h3 class="font-medium text-gray-700">Neue Hausaufgaben</h3>
              </div>
              <button
                  v-if="mode === 'edit'"
                  @click="addNewHomework"
                  class="text-xs sm:text-sm text-main hover:text-opacity-80"
              >
                + Hinzufügen
              </button>
            </div>
            <div class="p-3 sm:p-4 space-y-3">
              <div
                  v-for="(task, index) in newHomework"
                  :key="index"
                  class="space-y-2"
              >
                <div class="flex items-start space-x-2 sm:space-x-3">
                  <input
                      v-if="mode === 'edit'"
                      v-model="task.description"
                      class="flex-1 px-2 sm:px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg text-xs sm:text-sm"
                      placeholder="Neue Hausaufgabe..."
                  />
                  <div v-else class="flex-1 text-xs sm:text-sm text-gray-700">
                    {{ task.description || 'Keine Beschreibung' }}
                  </div>
                  <button
                      v-if="mode === 'edit'"
                      @click="removeNewHomework(index)"
                      class="p-1.5 sm:p-2 text-gray-400 hover:text-red-500"
                  >
                    <X class="w-3 h-3 sm:w-4 sm:h-4" />
                  </button>
                </div>
                <div class="flex flex-wrap gap-1.5 sm:gap-2 ml-1 sm:ml-2">
                  <button
                      v-if="mode === 'edit'"
                      v-for="student in students"
                      :key="student.id"
                      @click="toggleNewHomeworkStudent(index, student.id)"
                      class="px-1.5 sm:px-2 py-0.5 rounded text-xs"
                      :class="[
                      task.assignedTo.includes(student.id)
                        ? 'bg-blue-100 text-blue-700'
                        : 'bg-gray-100 text-gray-600 hover:bg-blue-50'
                    ]"
                  >
                    {{ student.name.split(' ')[0] }}
                  </button>
                  <span 
                      v-else
                      v-for="student in students.filter(s => task.assignedTo.includes(s.id))"
                      :key="student.id"
                      class="px-1.5 sm:px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700"
                  >
                    {{ student.name.split(' ')[0] }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Message -->
    <div 
      v-if="showSuccessMessage" 
      class="fixed top-4 right-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-3 sm:p-4 rounded shadow-md z-50 max-w-[90%] sm:max-w-md"
    >
      <div class="flex items-center">
        <div class="py-1"><svg class="w-5 h-5 sm:w-6 sm:h-6 mr-3 sm:mr-4 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg></div>
        <p class="text-xs sm:text-sm">{{ successMessage }}</p>
      </div>
    </div>

    <!-- Error Message -->
    <div 
      v-if="showErrorMessage" 
      class="fixed top-4 right-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-3 sm:p-4 rounded shadow-md z-50 max-w-[90%] sm:max-w-md"
    >
      <div class="flex items-center">
        <div class="py-1"><svg class="w-5 h-5 sm:w-6 sm:h-6 mr-3 sm:mr-4 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg></div>
        <p class="text-xs sm:text-sm">{{ errorMessage }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import {
  Save,
  Clock,
  Users,
  BookOpen,
  Book,
  CheckSquare,
  X
} from 'lucide-vue-next'
import axios from 'axios'

// Extrahieren der Appointment-ID und des Modus aus der URL
const appointmentId = window.location.pathname.split('/').pop()
const urlParams = new URLSearchParams(window.location.search)
const mode = urlParams.get('mode') === 'edit' ? 'edit' : 'normal' // Default ist 'normal'

// Referenzen für Daten
const appointment = ref(null)
const isLoading = ref(true)
const error = ref(null)

// Terminstatusoptionen
const STATUS_OPTIONS = {
  SCHEDULED: 'geplant',
  IN_PROGRESS: 'läuft',
  COMPLETED: 'abgeschlossen',
  CANCELLED: 'abgesagt'
}

// Computed Properties für Datumsformatierung
const sessionDate = computed(() => {
  if (!appointment.value) return ''
  const date = new Date(appointment.value.startTime)
  return date.toLocaleDateString('de-DE', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
})

const sessionTimeRange = computed(() => {
  if (!appointment.value) return ''
  const startTime = new Date(appointment.value.startTime)
  const endTime = new Date(appointment.value.endTime)
  return `${startTime.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' })} - ${endTime.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' })}`
})

const sessionStatus = ref('SCHEDULED')
const sessionStatusText = computed(() => STATUS_OPTIONS[sessionStatus.value])
const sessionStatusClass = computed(() => {
  switch (sessionStatus.value) {
    case 'SCHEDULED': return 'bg-blue-100 text-blue-800'
    case 'IN_PROGRESS': return 'bg-yellow-100 text-yellow-800'
    case 'COMPLETED': return 'bg-green-100 text-green-800'
    case 'CANCELLED': return 'bg-red-100 text-red-800'
    default: return 'bg-gray-100 text-gray-800'
  }
})

// Timer
const timeRemaining = ref('')
let timerInterval

const updateTimeRemaining = () => {
  const now = new Date()
  const diff = sessionEndTime - now

  if (diff <= 0) {
    timeRemaining.value = '00:00'
    sessionStatus.value = 'completed'
    clearInterval(timerInterval)
    return
  }

  const minutes = Math.floor(diff / 60000)
  const seconds = Math.floor((diff % 60000) / 1000)
  timeRemaining.value = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
}

onMounted(() => {
  if (sessionStatus.value === 'active') {
    updateTimeRemaining()
    timerInterval = setInterval(updateTimeRemaining, 1000)
  }
  loadAppointmentData()
})

onUnmounted(() => {
  if (timerInterval) {
    clearInterval(timerInterval)
  }
})

// Studenten
const students = ref([])

// Hausaufgaben
const previousHomework = ref([])
const newHomework = ref([
  { id: 1, description: '', assignedTo: [] }
])

// Füge diese reaktiven Variablen für Toast-Meldungen hinzu
const successMessage = ref('');
const showSuccessMessage = ref(false);
const errorMessage = ref('');
const showErrorMessage = ref(false);

// Profilbild-Verwaltung hinzufügen
const profilePictures = ref({});
const profilePictureErrors = ref({});

const fetchProfilePicture = async (userId) => {
  try {
    const response = await fetch(`/api/user/${userId}/pfp`);
    
    if (response.ok) {
      const data = await response.json();
      
      // Prüfen, ob ein gültiger Pfad vorhanden ist
      if (data.pfpPath) {
        // Bild-Element erstellen, um zu prüfen, ob das Bild existiert und lädt
        const img = new Image();
        img.onload = () => {
          profilePictures.value[userId] = data.pfpPath.replace('./', '/');
        };
        img.onerror = () => {
          profilePictureErrors.value[userId] = true;
        };
        img.src = data.pfpPath.replace('./', '/');
      } else {
        profilePictureErrors.value[userId] = true;
      }
    } else {
      profilePictureErrors.value[userId] = true;
    }
  } catch (error) {
    console.error('Error fetching profile picture:', error);
    profilePictureErrors.value[userId] = true;
  }
};

// Funktion für Initialen-Avatar als Fallback
const getInitialsAvatar = (firstName, lastName) => {
  if (!firstName || !lastName) return '';
  const firstInitial = firstName.charAt(0).toUpperCase();
  const lastInitial = lastName.charAt(0).toUpperCase();
  return firstInitial + lastInitial;
};

// Laden der Termindaten
const loadAppointmentData = async () => {
  try {
    isLoading.value = true
    const response = await axios.get(`/api/appointment/${appointmentId}`)
    appointment.value = response.data
    
    // Setze den Status entsprechend des Terminstatus
    const now = new Date()
    const startTime = new Date(appointment.value.startTime)
    const endTime = new Date(appointment.value.endTime)
    
    if (now < startTime) {
      sessionStatus.value = 'SCHEDULED'
    } else if (now >= startTime && now <= endTime) {
      sessionStatus.value = 'IN_PROGRESS'
    } else {
      sessionStatus.value = 'COMPLETED'
    }
    
    // Studenten aus den zugeordneten Benutzern extrahieren
    // Filtere den Lehrer aus der Liste der Benutzer heraus
    const teacherId = appointment.value.teacher?.id
    students.value = appointment.value.users
      .filter(user => user.id !== teacherId) // Entferne den Lehrer aus der Liste
      .map(user => {
        // Lade gespeicherte Anwesenheit, wenn vorhanden
        let status = 'present' // Standard-Status
        if (appointment.value.attendance && appointment.value.attendance[user.id]) {
          status = appointment.value.attendance[user.id]
        }
        
        // Lade gespeicherte Notizen, wenn vorhanden
        let currentTopic = ''
        let notes = ''
        if (appointment.value.notes && appointment.value.notes[user.id]) {
          currentTopic = appointment.value.notes[user.id].titel || ''
          notes = appointment.value.notes[user.id].beschreibung || ''
        }
        
        // Profilbild für jeden Benutzer laden
        fetchProfilePicture(user.id);
        
        return {
          id: user.id,
          name: `${user.first_name} ${user.last_name}`,
          firstName: user.first_name,
          lastName: user.last_name,
          status: status,
          currentTopic: currentTopic,
          notes: notes
        }
      })
    
    // Lade vorhandene Hausaufgaben
    if (appointment.value.homework && Array.isArray(appointment.value.homework)) {
      // Setze neue Hausaufgaben auf leer
      newHomework.value = []
      
      // Füge alle Hausaufgaben zu den neuen Hausaufgaben hinzu
      appointment.value.homework.forEach(task => {
        if (task.homework && task.student_ids && Array.isArray(task.student_ids)) {
          newHomework.value.push({
            description: task.homework,
            assignedTo: task.student_ids
          })
        }
      })
      
      // Füge eine leere Hausaufgabe hinzu, wenn keine vorhanden sind
      if (newHomework.value.length === 0) {
        addNewHomework()
      }
      
      // Setze previousHomework auf ein leeres Array
      previousHomework.value = []
    } else {
      // Wenn keine Hausaufgaben vorhanden sind, setze Standard-Werte
      previousHomework.value = []
      newHomework.value = [{
        description: '',
        assignedTo: []
      }]
    }
    
  } catch (err) {
    console.error('Fehler beim Laden des Termins:', err)
    error.value = 'Der Termin konnte nicht geladen werden.'
  } finally {
    isLoading.value = false
  }
}

// Hilfsfunktion für Profilbild oder Fallback
const getUserAvatar = (student) => {
  if (profilePictures.value[student.id]) {
    return profilePictures.value[student.id];
  }
  return null;
}

// Attendance
const updateAttendance = (studentId, status) => {
  const student = students.value.find(s => s.id === studentId)
  if (student) {
    student.status = status
  }
}

// New Homework
const addNewHomework = () => {
  newHomework.value.push({
    description: '',
    assignedTo: []
  })
}

const removeNewHomework = (index) => {
  newHomework.value.splice(index, 1)
  if (newHomework.value.length === 0) {
    addNewHomework()
  }
}

const toggleNewHomeworkStudent = (taskIndex, studentId) => {
  const task = newHomework.value[taskIndex]
  const index = task.assignedTo.indexOf(studentId)
  if (index === -1) {
    task.assignedTo.push(studentId)
  } else {
    task.assignedTo.splice(index, 1)
  }
}

// Save Session
const saveSession = async () => {
  try {
    // Anwesenheit formatieren
    const attendance = {}
    students.value.forEach(student => {
      attendance[student.id] = student.status
    })
    
    // Hausaufgaben im neuen Format formatieren
    const homework = []
    
    // Neue Hausaufgaben hinzufügen
    newHomework.value
      .filter(task => task.description.trim() !== '')
      .forEach(task => {
        if (task.assignedTo.length > 0) {
          homework.push({
            student_ids: task.assignedTo,
            homework: task.description,
            ticked: false // Neue Hausaufgaben sind immer nicht abgehakt
          })
        }
      })
    
    // Vorherige Hausaufgaben hinzufügen (mit ihrem aktuellen Status)
    previousHomework.value.forEach(task => {
      const studentIds = []
      
      // Sammle alle Schüler-IDs, für die die Aufgabe als erledigt markiert ist
      Object.entries(task.completedBy).forEach(([studentId, isCompleted]) => {
        if (isCompleted) {
          studentIds.push(studentId)
        }
      })
      
      // Nur hinzufügen, wenn mindestens ein Schüler die Aufgabe erledigt hat
      if (studentIds.length > 0) {
        homework.push({
          student_ids: studentIds,
          homework: task.description,
          ticked: true // Vorherige Hausaufgaben sind abgehakt
        })
      }
    })
    
    // Notizen formatieren
    const notes = {}
    students.value.forEach(student => {
      if (student.currentTopic || student.notes) {
        notes[student.id] = {
          "titel": student.currentTopic || "",
          "beschreibung": student.notes || ""
        }
      }
    })
    
    // Separate API-Aufrufe für jede Datenart
    const promises = []
    
    // Anwesenheit aktualisieren
    promises.push(
      axios.patch(`/api/appointment/${appointmentId}/attendance`, { attendance })
    )
    
    // Hausaufgaben aktualisieren
    promises.push(
      axios.patch(`/api/appointment/${appointmentId}/homework`, { homework })
    )
    
    // Notizen aktualisieren
    promises.push(
      axios.patch(`/api/appointment/${appointmentId}/note`, { notes })
    )
    
    await Promise.all(promises)
    
    // Erfolgsmeldung als Toast anzeigen
    successMessage.value = "Sitzung erfolgreich gespeichert";
    showSuccessMessage.value = true;
    setTimeout(() => {
      showSuccessMessage.value = false;
    }, 5000);
    
    console.log('Sitzung erfolgreich gespeichert')
  } catch (err) {
    console.error('Fehler beim Speichern der Sitzung:', err)
    
    // Fehlermeldung als Toast anzeigen
    errorMessage.value = "Fehler beim Speichern der Sitzung: " + err.message;
    showErrorMessage.value = true;
    setTimeout(() => {
      showErrorMessage.value = false;
    }, 5000);
  }
}

// Funktion zum Wechseln in den Bearbeitungsmodus
const switchToEditMode = () => {
  window.location.href = `/nachhilfetermin/${appointmentId}?mode=edit`;
}
</script>