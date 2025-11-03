<script setup>

import UserMenu from "./UserMenu.vue";
import Benachrichtigungen from "../components/dashboard/Benachrichtigungen.vue";
import TODOs from "../components/dashboard/TODOs.vue";
import Schularbeitstermine from "../components/dashboard/Schularbeitstermine.vue";
import Geburtstage from "../components/dashboard/Geburtstage.vue";
import Kalender from "../components/dashboard/Kalender.vue";
</script>

<template>

  <div class="absolute bg-window_bg rounded-window shadow-window left-0 top-0 w-[calc(50%-8px)] h-full">
    test est tesd
  </div>
  <div class="absolute bg-window_bg rounded-window shadow-window right-0 top-0 w-[calc(50%-8px)] h-full">
    test est tesd
  </div>
  <!--
    <div class="absolute w-[40.5%] h-[33%] left-0 top-0">
      <Benachrichtigungen />
    </div>
    <div class="absolute bg-window_bg rounded-window shadow-window w-[40.5%] h-[33%] left-[41.5%] top-0">
      <TODOs />
    </div>
    <div class="absolute bg-window_bg rounded-window shadow-window w-[82%] h-[65.5%] left-0 bottom-0">
      <Kalender />
    </div>
    <div class="absolute bg-window_bg rounded-window shadow-window w-[17%] h-[49%] right-0 top-0">
      <Geburtstage />
    </div>
   <div class="absolute bg-window_bg rounded-window shadow-window w-[17%] h-[49.5%] right-0 bottom-0">
      <Schularbeitstermine />
    </div>
    -->
</template>