<script setup>
import { ref, computed } from "vue";
import axios from "axios";

const selectedRole = ref('');
const subjectSearchQuery = ref('');
const selectedSubjects = ref([]);
const formData = ref({
  firstName: '',
  lastName: '',
  birthDate: '',
  email: '',
  phone: '',
  address: {
    street: '',
    postalCode: '',
    city: ''
  },
  guardian: {
    firstName: '',
    lastName: '',
    birthDate: '',
    email: '',
    phone: '',
    address: {
      street: '',
      postalCode: '',
      city: ''
    }
  },
  school: '',
  class: ''
});

const subjects = [
  { id: 1, name: 'Mathematik' },
  { id: 2, name: 'Deutsch' },
  { id: 3, name: 'Englisch' },
  { id: 4, name: 'Biologie' },
  { id: 5, name: 'Chemie' },
  { id: 6, name: 'Physik' },
];

const roles = [
  { name: 'Management', roleName: 'ROLE_MANAGEMENT' },
  { name: 'Standortleitung', roleName: 'ROLE_LOCATION_MANAGEMENT' },
  { name: 'Bürokraft', roleName: 'ROLE_OFFICE' },
  { name: 'Lehrkraft', roleName: 'ROLE_TEACHER' },
  { name: 'Marketing', roleName: 'ROLE_MARKETING' },
  { name: 'Schüler', roleName: 'ROLE_STUDENT' },
];

const subjectSearchResults = computed(() => {
  if (!subjectSearchQuery.value) return [];
  return subjects.filter(subject =>
      subject.name.toLowerCase().includes(subjectSearchQuery.value.toLowerCase()) &&
      !selectedSubjects.value.some(selected => selected.id === subject.id)
  );
});

const selectSubject = (subject) => {
  selectedSubjects.value.push(subject);
  subjectSearchQuery.value = '';
};

const removeSelectedSubject = (subjectId) => {
  selectedSubjects.value = selectedSubjects.value.filter(subject => subject.id !== subjectId);
};

const handleSubmit = async () => {
  try {
    const payload = {
      ...formData.value,
      role: selectedRole.value,
      subjects: selectedRole.value === 'ROLE_TEACHER' ? selectedSubjects.value : undefined
    };

    console.log(payload)

    const response = await axios.post('/api/register', JSON.stringify(payload));

    if (response.data.success) {
      //window.location.href = '/dashboard';
    }
  } catch (error) {
    console.error('Registration failed:', error);
  }
};
</script>

<template>
  <div class="absolute bg-window_bg rounded-window shadow-window w-[100%] h-[56px] left-0 top-0 select-none p-2">
    <div class="flex items-center justify-left gap-4">
      <p class="text-secondary_text px-2">Wählen Sie die Rolle aus, die der Benutzer haben soll.</p>
      <select v-model="selectedRole" id="role" class="flex flex-row gap-2 items-center text-left px-4 py-2 rounded-window transition-colors bg-second_accent text-white stroke-white w-64 h-full border-none">
        <option class="bg-window_bg text-secondary_text" value="" disabled selected>Bitte wählen...</option>
        <option class="bg-window_bg text-secondary_text" v-for="role in roles" :key="role.roleName" :value="role.roleName">
          {{ role.name }}
        </option>
      </select>
    </div>
  </div>
  <div class="absolute bg-window_bg rounded-window shadow-window w-[100%] h-[calc(100%-56px-8px)] left-0 bottom-0 transition-opacity duration-100" :class="selectedRole === '' ? 'bg-opacity-35' : 'bg-opacity-100'">
    <form @submit.prevent="handleSubmit" class="h-full p-4 overflow-y-auto overflow-x-hidden" v-if="selectedRole !== ''">
      <div class="grid md:grid-cols-2 gap-6 w-full">
        <div class="space-y-4 mb-4">
          <h2 class="text-lg font-semibold text-primary_text mb-4">Persönliche Informationen</h2>
          <div class="form-group">
            <label for="firstName" class="block text-sm font-medium text-primary_text mb-1">Vorname<span class="text-xl">*</span></label>
            <input v-model="formData.firstName" type="text" id="firstName" class="block w-full p-2 border border-gray-300 rounded-window shadow-sm focus:ring-second_accent focus:border-second_accent">
          </div>
          <div class="form-group">
            <label for="lastName" class="block text-sm font-medium text-primary_text mb-1">Nachname<span class="text-xl">*</span></label>
            <input v-model="formData.lastName" type="text" id="lastName" class="block w-full p-2 border border-gray-300 rounded-window shadow-sm focus:ring-second_accent focus:border-second_accent">
          </div>
          <div class="form-group">
            <label for="birthDate" class="block text-sm font-medium text-primary_text mb-1">Geburtsdatum<span class="text-xl">*</span></label>
            <input v-model="formData.birthDate" type="date" id="birthDate" class="block w-full p-2 border border-gray-300 rounded-window shadow-sm focus:ring-second_accent focus:border-second_accent">
          </div>
        </div>
        <div class="space-y-4 mb-4">
          <h2 class="text-lg font-semibold text-primary_text mb-4">Kontaktinformationen</h2>
          <div class="form-group">
            <label for="email" class="block text-sm font-medium text-primary_text mb-1">Email<span class="text-xl">*</span></label>
            <input v-model="formData.email" type="email" id="email" class="block w-full p-2 border border-gray-300 rounded-window shadow-sm focus:ring-second_accent focus:border-second_accent">
          </div>
          <div class="form-group">
            <label for="phone" class="block text-sm font-medium text-primary_text mb-1">Telefonnummer<span class="text-xl">*</span></label>
            <input v-model="formData.phone" type="tel" id="phone" class="block w-full p-2 border border-gray-300 rounded-window shadow-sm focus:ring-second_accent focus:border-second_accent">
          </div>
          <div class="space-y-3">
            <label class="block text-sm font-medium text-primary_text mb-1">Adresse<span class="text-xl">*</span></label>
            <input v-model="formData.address.street" type="text" placeholder="Straße und Hausnummer" class="block w-full p-2 border border-gray-300 rounded-window shadow-sm focus:ring-second_accent focus:border-second_accent mb-2">
            <div class="grid grid-cols-2 gap-2">
              <input v-model="formData.address.postalCode" type="text" placeholder="PLZ" class="block w-full p-2 border border-gray-300 rounded-window shadow-sm focus:ring-second_accent focus:border-second_accent">
              <input v-model="formData.address.city" type="text" placeholder="Stadt" class="block w-full p-2 border border-gray-300 rounded-window shadow-sm focus:ring-second_accent focus:border-second_accent">
            </div>
          </div>
        </div>
        <div v-if="selectedRole === 'ROLE_STUDENT'" class="space-y-4 mb-4">
          <h2 class="text-lg font-semibold text-primary_text mb-4">Erziehungsberechtigte</h2>
          <div class="form-group">
            <label for="guardianFirstName" class="block text-sm font-medium text-primary_text mb-1">Erziehungsberchtigte Vorname<span class="text-xl">*</span></label>
            <input v-model="formData.guardian.firstName" type="text" id="guardianFirstName" class="block w-full p-2 border border-gray-300 rounded-window shadow-sm focus:ring-second_accent focus:border-second_accent">
          </div>
          <div class="form-group">
            <label for="guardianLastName" class="block text-sm font-medium text-primary_text mb-1">Erziehungsberchtigte Nachname<span class="text-xl">*</span></label>
            <input v-model="formData.guardian.lastName" type="text" id="guardianLastName" class="block w-full p-2 border border-gray-300 rounded-window shadow-sm focus:ring-second_accent focus:border-second_accent">
          </div>
          <div class="form-group">
            <label for="guardianBirthDate" class="block text-sm font-medium text-primary_text mb-1">Erziehungsberchtigte Geburtsdatum<span class="text-xl">*</span></label>
            <input v-model="formData.guardian.birthDate" type="date" id="guardianBirthDate" class="block w-full p-2 border border-gray-300 rounded-window shadow-sm focus:ring-second_accent focus:border-second_accent">
          </div>
          <div class="form-group">
            <label for="guardianEmail" class="block text-sm font-medium text-primary_text mb-1">Erziehungsberchtigte Email<span class="text-xl">*</span></label>
            <input v-model="formData.guardian.email" type="email" id="guardianEmail" class="block w-full p-2 border border-gray-300 rounded-window shadow-sm focus:ring-second_accent focus:border-second_accent">
          </div>
          <div class="form-group">
            <label for="guardianPhone" class="block text-sm font-medium text-primary_text mb-1">Erziehungsberchtigte Telefonnummer<span class="text-xl">*</span></label>
            <input v-model="formData.guardian.phone" type="tel" id="guardianPhone" class="block w-full p-2 border border-gray-300 rounded-window shadow-sm focus:ring-second_accent focus:border-second_accent">
          </div>
          <div class="space-y-3">
            <label class="block text-sm font-medium text-primary_text mb-1">Erziehungsberchtigte Adresse<span class="text-xl">*</span></label>
            <input v-model="formData.guardian.address.street" type="text" placeholder="Straße und Hausnummer" class="block w-full p-2 border border-gray-300 rounded-window shadow-sm focus:ring-second_accent focus:border-second_accent mb-2">
            <div class="grid grid-cols-2 gap-2">
              <input v-model="formData.guardian.address.postalCode" type="text" placeholder="PLZ" class="block w-full p-2 border border-gray-300 rounded-window shadow-sm focus:ring-second_accent focus:border-second_accent">
              <input v-model="formData.guardian.address.city" type="text" placeholder="Stadt" class="block w-full p-2 border border-gray-300 rounded-window shadow-sm focus:ring-second_accent focus:border-second_accent">
            </div>
          </div>
        </div>
        <div v-if="selectedRole === 'ROLE_STUDENT'" class="space-y-4 mb-4">
          <h2 class="text-lg font-semibold text-primary_text mb-4">SchülerIn Informationen</h2>
          <div class="form-group">
            <label for="school" class="block text-sm font-medium text-primary_text mb-1">Schule</label>
            <input v-model="formData.school" type="text" id="school" class="block w-full p-2 border border-gray-300 rounded-window shadow-sm focus:ring-second_accent focus:border-second_accent">
          </div>
          <div class="form-group">
            <label for="class" class="block text-sm font-medium text-primary_text mb-1">Klasse</label>
            <input v-model="formData.class" type="text" id="class" class="block w-full p-2 border border-gray-300 rounded-window shadow-sm focus:ring-second_accent focus:border-second_accent">
          </div>
        </div>
        <div v-if="selectedRole === 'ROLE_TEACHER'" class="space-y-4 mb-4">
          <h2 class="text-lg font-semibold text-primary_text mb-4">Unterrichtsfächer</h2>
          <div class="form-group">
            <label for="subjects" class="block text-sm font-medium text-primary_text mb-1">Fächer</label>
            <div class="relative">
              <input type="text" id="subjects" v-model="subjectSearchQuery" placeholder="Fach suchen..." class="block w-full p-2 border border-gray-300 rounded-window shadow-sm focus:ring-second_accent focus:border-second_accent">
              <ul v-if="subjectSearchResults.length" class="absolute z-10 w-full bg-white border border-gray-300 rounded-window shadow-lg mt-1">
                <li v-for="subject in subjectSearchResults" :key="subject.id" @click="selectSubject(subject)" class="p-2 cursor-pointer hover:bg-gray-100">
                  {{ subject.name }}
                </li>
              </ul>
            </div>
            <div v-if="selectedSubjects.length" class="flex flex-row flex-wrap gap-2 mt-2">
              <div v-for="subject in selectedSubjects" :key="subject.id" class="flex items-center gap-1 py-1 px-2 bg-second_accent bg-opacity-20 rounded-window text-sm font-medium">
                <span>{{ subject.name }}</span>
                <button @click="removeSelectedSubject(subject.id)" class="ml-1 text-error_text hover:text-red-700 font-medium text-2xl">×</button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="mt-6 w-full">
        <div class="text-secondary_text">Felder mit einem <span class="text-xl">*</span> müssen ausgefüllt werden</div>
        <div class="mb-2">
          <label class="flex items-center">
            <input type="checkbox" class="mr-2 rounded-window" required>
            Ich akzeptiere die <a href="#" class="text-main underline mx-1">AGB</a> und die <a href="#" class="text-main underline ml-1">Datenschutzrichtlinien</a>.
          </label>
        </div>
        <button type="submit" class="w-full px-6 py-3 rounded-window font-semibold text-white bg-main opacity-80 hover:opacity-100 shadow-none hover:shadow-xl transition-all duration-200">
          Registrieren
        </button>
      </div>
    </form>
  </div>
</template>

<style lang="scss" scoped>
</style>