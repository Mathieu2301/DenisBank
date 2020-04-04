<template>
  <div id="app">
    <waiting text="Chargement..." v-if="page=='waiting'"></waiting>
    <loginpage v-if="page=='login'"></loginpage>
    <dashboard v-if="page=='dashboard'"></dashboard>
  </div>
</template>

<script>
import waiting from './components/waiting.vue';
import loginpage from './components/login.vue';
import dashboard from './components/dashboard.vue';

export default {
  name: 'app',

  components: {
    loginpage,
    waiting,
    dashboard,
  },

  data() {
    return {
      page: 'waiting',
    };
  },

  mounted() {
    this.api.isConnected((connected) => {
      if (connected) {
        this.page = 'dashboard';
      } else {
        localStorage.removeItem('session');
        this.page = 'login';
      }
    });
  },
};
</script>

<style>
:root {
  touch-action: pan-x pan-y;
}

body {
  background-color: #19222D;
  margin: 0;
}

body * {
  box-sizing: border-box;
  color: #e4ebff;
  transition-duration: 0.2s;
}

#app {
  font-family: Helvetica, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  margin-top: 110px;
  margin-bottom: 70px;
}

table {
  border-collapse: collapse;
  width: 100%;
  box-sizing: border-box;
  font-size: 16px;
  line-height: 30px;
}

th, td {
  padding: 7px 2px;
  text-align: left;
}

tr {
  border-bottom: 1px solid #4e5761;
}

.text_green { color: #00e24c }
.text_red { color: #f02f2f }

.bg_green { background-color: #059263 }
.bg_grey { background-color: #293A48 }
.bg_red { background-color: #b93847 }

.noselect * {
  user-select: none;
}

.center { text-align: center }
.BR_TL { border-top-left-radius: 5px }
.BR_TR { border-top-right-radius: 5px }
.BR_BL { border-bottom-left-radius: 5px }
.BR_BR { border-bottom-right-radius: 5px }

</style>
