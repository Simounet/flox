<template>

  <div class="settings-box" v-if=" ! loading">
    <div class="login-error" v-if="config.env === 'demo'"><span>Data cannot be changed in the demo</span></div>

    <div class="setting-box">
      <Checkbox
        v-model="refresh"
        @update:modelValue="(value) => {
          refresh = value;
          updateRefresh();
        }"
        id="settings-refresh"
        :label="lang('refresh automatically')"
        />
    </div>

    <div class="misc-btn-wrap">
      <button v-show=" ! refreshAllClicked" @click="refreshAll()" class="setting-btn">{{ lang('refresh all infos') }}</button>
      <span v-show="showRefreshAllMessage" class="update-check">{{ lang('refresh all triggered') }}</span>
    </div>

  </div>

</template>

<script>
  import { mapState, mapMutations } from 'vuex';
  import MiscHelper from '../../../helpers/misc';
  import Checkbox from '../Checkbox.vue';

  import http from 'axios';

  export default {
    mixins: [MiscHelper],

    props: ['initial-refresh'],

    components: {
      Checkbox
    },

    data() {
      return {
        config: window.config,
        refresh: this.initialRefresh || false,
        refreshAllClicked: false,
        showRefreshAllMessage: false,
      }
    },

    computed: {
      ...mapState({
        loading: state => state.loading
      })
    },

    methods: {
      ...mapMutations([ 'SET_LOADING' ]),

      updateRefresh() {
        this.SET_LOADING(true);

        http.patch(`${config.api}/settings/refresh`, {refresh: this.refresh}).then(() => {
          this.SET_LOADING(false);
        }, error => {
          alert(error.message);
        })
      },

      refreshAll() {
        this.refreshAllClicked = true;

        http.patch(`${config.api}/refresh-all`).then(() => {
          this.showRefreshAllMessage = true;
        }).catch(error => {
          this.refreshAllClicked = false;
          alert(error.response.data);
        });
      }
    }

  }
</script>
