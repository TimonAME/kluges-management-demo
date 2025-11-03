<template>
  <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-4xl mx-8 my-8">
    <div class="flex flex-row items-center gap-4 mb-6">
      <img src="../../../images/icon.svg" alt="Logo" class="w-14">
      <div class="text-4xl text-center font-semibold">Nachhilfeinstitut ...</div>
    </div>
    <div class="text-2xl mb-6">Anmeldung - Schuljahr 2024/25</div>
    <form class="grid grid-cols-2 gap-6">
      <!-- Persönliche Daten -->
      <PersonalData/>
      <!-- Tabelle für Kurspreise für Gruppenkurse -->
      <TableGroupCourses/>
      <!-- Kurspreise Einzeltraining -->
      <TableSingleCourses/>
      <!-- Schularbeits-Intensivkurs -->
      <TableExamCourses/>
      <!-- Workshops -->
      <TableWorkshops/>
      <!-- Maturaintensivkurs -->
      <TableMatura/>
      <!-- Anti-Prüfungsangst -->
      <TableAntiExamAnxiety/>
      <!-- Lernen lernen -->
      <TableLearnLearn/>
      <!-- TODO: Fragen ob wir Bankdaten speichern dürfen -->
      <div class="col-span-2">
        <button
            type="submit"
            class="w-full bg-main hover:bg-second_accent text-white font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-gray-800 focus:ring-offset-2"
        >
          Registrieren
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import TableGroupCourses from "./tables/TableGroupCourses.vue";
import TableSingleCourses from "./tables/TableSingleCourses.vue";
import TableExamCourses from "./tables/TableExamCourses.vue";
import TableWorkshops from "./tables/TableWorkshops.vue";
import TableMatura from "./tables/TableMatura.vue";
import TableAntiExamAnxiety from "./tables/TableAntiExamAnxiety.vue";
import TableLearnLearn from "./tables/TableLearnLearn.vue";
import PersonalData from "./tables/PersonalData.vue";
</script>

