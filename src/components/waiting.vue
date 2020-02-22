<template>
  <div class="waiting">
    <logo add="loading" color="#00b176" width="115px"></logo>
    <div class="text">{{ text }}</div>
  </div>
</template>

<script>
import logo from '@/components/logo.vue';

export default {
  components: {
    logo,
  },
  props: {
    text: String,
  },
};
</script>

<style scoped>

.waiting {
  position: fixed;
  top: 30%;
  width: calc(100% - 16px);
}

.text {
  width: 100%;
  font-size: 27px;
}

</style>
