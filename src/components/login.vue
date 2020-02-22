<template>
  <div class="login">
    <div class="logo_container">
      <logo color="#00b176" width="115px"></logo>
      <div class="logo_text">enisBank</div>
    </div>
    <div class="tabs">
      <div class="tab left" @click="tab = 0" :class="{ selected: tab==0 }">Connexion</div>
      <div class="tab right" @click="tab = 1" :class="{ selected: tab==1 }">Inscription</div>
    </div>
    <div class="container">
      <form class="content" v-if="tab == 0" @submit="login"> <!-- Connexion -->
        <input type="email"
          placeholder="Email"
          autocomplete="email"
          v-model="form.email"
        >
        <input type="password"
          placeholder="Mot de passe"
          autocomplete="current-password"
          v-model="form.password"
        >
        <input type="submit" value="Connexion">
      </form>

      <form class="content" v-if="tab == 1" @submit="register"> <!-- Inscription -->
        <input type="email"
          placeholder="Email"
          autocomplete="email"
          v-model="form.email"
        >
        <input type="password"
          placeholder="Mot de passe"
          autocomplete="new-password"
          v-model="form.password"
        >
        <input type="password"
          placeholder="Confirmer mot de passe"
          autocomplete="new-password"
          v-model="form.confirm"
        >
        <input type="text"
          placeholder="Nom complet"
          autocomplete="name"
          v-model="form.fullname"
        >
        <input type="submit" value="Inscription">
      </form>
    </div>
  </div>
</template>

<script>
import logo from '@/components/logo.vue';

export default {
  components: {
    logo,
  },
  data() {
    return {
      tab: 0,

      form: {
        email: localStorage.getItem('email'),
        password: '',

        fullname: '',
        confirm: '',
      },
    };
  },

  methods: {
    login(e) {
      e.preventDefault();
      this.api.login({
        email: this.form.email,
        password: this.form.password,
      }, (rs) => {
        if (rs) window.location.reload();
      });
    },

    register(e) {
      e.preventDefault();
      this.api.register({
        fullname: this.form.fullname,
        email: this.form.email,
        password: this.form.password,
        confirm: this.form.confirm,
      }, (rs) => {
        if (rs.success) {
          this.toast.success('Account created ! Please login now.');
          localStorage.setItem('email', this.form.email);
          this.tab = 0;
          this.form.confirm = '';
          this.form.fullname = '';
        } else this.toast.error(rs.message);
      });
    },
  },
};
</script>

<style scoped>

.login {
  max-width: 500px;
  margin: 0 auto;
}

.tabs {
  display: flex;
  margin: 0 20px;
}

.tabs > .tab {
  background-color: #263341;
  padding: 20px 0;
  width: 100%
}

.tab:not(.selected) { cursor: pointer; }
.tab.selected { background-color: #2D4252; }
.tab.left { border-top-left-radius: 5px; }
.tab.right { border-top-right-radius: 5px; }

.container {
  background-color: #2D4252;
  margin: 0 20px;
  padding: 20px 0;
  border-bottom-left-radius: 5px;
  border-bottom-right-radius: 5px;
}

.container > .content {
  display: flex;
  flex-flow: column;
  align-items: center;
}

.container > .content > * {
  width: 80%;
  box-shadow: 1px 2px 2px #263341;
  max-width: 300px;
}

.container > .content > *:hover { box-shadow: 0 0 }

input[type=email],
input[type=text],
input[type=password],
input[type=submit] {
  border: 0;
  border-radius: 3px;
  padding: 7px;
  margin: 10px 0;
  color: #263341;
}

input[type=submit] {
  background-color: #00b176;
  cursor: pointer;
  color: #e4ebff;
}

.logo_container {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
}

.logo_container > .logo_text {
  font-size: 55px;
  padding-top: 20px;
  color: #00b176;
  user-select: none;
}

@media screen and (max-width: 400px) {
  .logo_container > .logo_text { font-size: 0; padding-top: 0; }
}

</style>
