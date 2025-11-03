<template>
  <!-- TODO: responsive design -->
  <div class="w-full h-full rounded-r-lg bg-white">
    <swiper
        :spaceBetween="30"
        :centeredSlides="true"
        :autoplay="{
      delay: 5000,
      disableOnInteraction: false,
    }"
        :pagination="{
      clickable: true,
    }"
        :navigation="false"
        :modules="modules"
        class="w-full h-full rounded-r-lg"
    >
      <!-- Slide 1 -->
      <swiper-slide>
        <img src="../../../../images/slideshow/slide1.jpg" alt="Slide 1"/>
      </swiper-slide>
      <!-- Slide 2 -->
      <swiper-slide>
        <img src="../../../../images/slideshow/slide2.jpg" alt="Slide 2"/>
      </swiper-slide>
      <!-- Slide 3 -->
      <swiper-slide>
        <img src="../../../../images/slideshow/slide3.jpg" alt="Slide 3"/>
      </swiper-slide>
    </swiper>
  </div>
</template>

<script>
// Import Swiper Vue.js components
import {Swiper, SwiperSlide} from 'swiper/vue';

// Import Swiper styles
import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/navigation';

// import required modules
import {Autoplay, Pagination, Navigation} from 'swiper/modules';

export default {
  components: {
    Swiper,
    SwiperSlide,
  },
  setup() {
    return {
      modules: [Autoplay, Pagination, Navigation],
    };
  },
};
</script>

<style scoped>
.swiper-slide {
  text-align: center;
  font-size: 18px;

  /* Center slide text vertically */
  display: flex;
  justify-content: center;
  align-items: center;
}

.swiper-slide img {
  display: block;
  height: 100%;
  object-fit: cover;
}
</style>