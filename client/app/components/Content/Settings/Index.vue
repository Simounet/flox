<template>
  <main>
    <div class="wrap-content">

      <div class="navigation-tab no-select">
        <span :class="{active: activeTab == 'misc'}" @click="changeActiveTab('misc')">{{ lang('tab misc') }}</span>
        <span :class="{active: activeTab == 'user'}" @click="changeActiveTab('user')">{{ lang('tab user') }}</span>
        <span :class="{active: activeTab == 'options'}" @click="changeActiveTab('options')">{{ lang('tab options') }}</span>
        <span :class="{active: activeTab == 'backup'}" @click="changeActiveTab('backup')">{{ lang('tab backup') }}</span>
        <span :class="{active: activeTab == 'refresh'}" @click="changeActiveTab('refresh')">{{ lang('refresh') }}</span>
        <span :class="{active: activeTab == 'reminders'}" @click="changeActiveTab('reminders')">{{ lang('reminders') }}</span>
        <span :class="{active: activeTab == 'api_key'}" @click="changeActiveTab('api_key')">API</span>
      </div>

      <span class="loader fullsize-loader" v-if="loading"><i></i></span>

      <User v-if="activeTab == 'user'" :user="user" />
      <Options v-if="activeTab == 'options'" :user="user" />
      <Backup v-if="activeTab == 'backup'" />
      <Misc v-if="activeTab == 'misc'" />
      <Refresh v-if="activeTab == 'refresh'" :initial-refresh="user.refresh" />
      <Reminders v-if="activeTab == 'reminders'" :user="user" />
      <Api v-if="activeTab == 'api_key'" />

    </div>
  </main>
</template>

<script>
  import http from 'axios';

  import User from './User.vue';
  import Options from './Options.vue';
  import Backup from './Backup.vue';
  import Misc from './Misc.vue';
  import Refresh from './Refresh.vue';
  import Reminders from './Reminders.vue';
  import Api from './Api.vue';

  import { mapState, mapMutations, mapActions } from 'vuex';
  import MiscHelper from '../../../helpers/misc';

  export default {
    mixins: [MiscHelper],

    created() {
      this.setPageTitle(this.lang('settings'));
      this.fetchUserData();
    },

    components: {
      User, Options, Backup, Misc, Refresh, Reminders, Api
    },

    data() {
      return {
        activeTab: 'misc',
        user: {}
      }
    },

    computed: {
      ...mapState({
        loading: state => state.loading
      })
    },

    methods: {
      ...mapActions([ 'setPageTitle' ]),

      ...mapMutations([ 'SET_LOADING' ]),

      changeActiveTab(tab) {
        this.activeTab = tab;
      },

      fetchUserData() {
        this.SET_LOADING(true);

        http(`${config.api}/settings`).then(response => {
          this.user = response.data;
          this.SET_LOADING(false);
        });
      }
    }
  }
</script>
