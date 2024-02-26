<template>

  <div class="settings-box" v-if=" ! loading">
    <div class="login-error" v-if="config.env === 'demo'"><span>Data cannot be changed in the demo</span></div>
    <form class="settings-form" @submit.prevent="editUser()">
      <input type="password" :placeholder="lang('password')" v-model="password" :minlength="passwordMinLength" autocomplete="off" required>
      <div v-if="success" class="userdata-changed">{{ lang('success message') }}</div>
      <div v-if="error" class="userdata-changed userdata-changed--error">{{ error }}</div>
      <input type="submit" :value="lang('save button')">
    </form>
  </div>

</template>

<script>
  import { mapState, mapMutations } from 'vuex';
  import MiscHelper from '../../../helpers/misc';

  import http from 'axios';
  import debounce from 'debounce';

  const debounceMilliseconds = 2000;

  export default {
    mixins: [MiscHelper],

    created() {
      this.fetchUserData();
      this.clearSuccessMessage = debounce(this.clearSuccessMessage, debounceMilliseconds);
    },

    data() {
      return {
        config: window.config,
        error: false,
        password: '',
        passwordMinLength: 4,
        success: false
      }
    },

    computed: {
      ...mapState({
        loading: state => state.loading
      })
    },

    methods: {
      ...mapMutations([ 'SET_LOADING' ]),

      fetchUserData() {
        this.SET_LOADING(true);

        http(`${config.api}/settings`).then(response => {
          this.SET_LOADING(false);
          this.passwordMinLength = response.data.password_min_length;
        });
      },

      editUser() {
        const password = this.password;

        http.patch(`${config.api}/userdata`, {password}).then(() => {
          this.success = true;
          this.error = false;
          this.clearSuccessMessage();
        }).catch(error => {
          // @TODO translate error messages
          this.error = error.response.data.message;
        });
      },

      clearSuccessMessage() {
        this.success = false;
      }
    }
  }
</script>
