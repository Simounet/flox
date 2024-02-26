<template>

  <div class="settings-box element-ui-checkbox no-select" v-if=" ! loading">
    <div class="login-error" v-if="config.env === 'demo'"><span>Data cannot be changed in the demo</span></div>

    <form class="settings-form" @submit.prevent="editSetting()">
      <span class="update-check">{{ lang('reminders send to') }}</span>

      <input type="email" placeholder="E-Mail" v-model="reminders_send_to">
      <div v-if="success" class="userdata-changed">{{ lang('success message') }}</div>
      <input type="submit" :value="lang('save button')">
    </form>

    <div class="setting-box">
      <el-checkbox v-model="daily" @change="updateReminders">{{ lang('daily reminder') }}</el-checkbox>
    </div>
    <div class="setting-box">
      <el-checkbox v-model="weekly" @change="updateReminders">{{ lang('weekly reminder') }}</el-checkbox>
    </div>
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

    props: ['user'],

    created() {
      this.setOptionsFromUser(this.user);
      this.clearSuccessMessage = debounce(this.clearSuccessMessage, debounceMilliseconds);
    },

    data() {
      return {
        config: window.config,
        reminders_send_to: '',
        success: false,
        daily: false,
        weekly: false,
      }
    },

    computed: {
      ...mapState({
        loading: state => state.loading
      })
    },

    methods: {
      ...mapMutations([ 'SET_LOADING' ]),

      setOptionsFromUser(user) {
        this.reminders_send_to = user.reminders_send_to;
        this.daily = user.daily;
        this.weekly = user.weekly;
      },

      editSetting() {
        http.patch(`${config.api}/settings/reminders-send-to`, {reminders_send_to: this.reminders_send_to})
          .then(() => {
            this.success = true;
            this.clearSuccessMessage();
          });
      },

      updateReminders() {
        this.SET_LOADING(true);

        const daily = this.daily;
        const weekly = this.weekly;

        http.patch(`${config.api}/settings/reminder-options`, {daily, weekly}).then(() => {
          this.SET_LOADING(false);
        }, error => {
          alert(error.message);
        })
      },

      clearSuccessMessage() {
        this.success = false;
      }
    }
  }
</script>
