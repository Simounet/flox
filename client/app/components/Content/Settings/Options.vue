<template>

  <div class="settings-box no-select" v-if=" ! loading">
    <div class="login-error" v-if="config.env === 'demo'"><span>Data cannot be changed in the demo</span></div>

    <div class="setting-box">
      <Checkbox
        v-model="genre"
        @update:modelValue="(value) => {
          genre = value;
          updateOptions();
        }"
        id="settings-options-genre"
        :label="lang('display genre')"
        />
    </div>
    <div class="setting-box">
      <Checkbox
        v-model="date"
        @update:modelValue="(value) => {
          date = value;
          updateOptions();
        }"
        id="settings-options-date"
        :label="lang('display date')"
        />
    </div>
    <div class="setting-box">
      <Checkbox
        v-model="spoiler"
        @update:modelValue="(value) => {
          spoiler = value;
          updateOptions();
        }"
        id="settings-options-spoiler"
        :label="lang('spoiler')"
        />
    </div>
    <div class="setting-box">
      <Checkbox
        v-model="watchlist"
        @update:modelValue="(value) => {
          watchlist = value;
          updateOptions();
        }"
        id="settings-options-watchlist"
        :label="lang('show watchlist')"
        />
    </div>

    <div class="setting-box select-box">
      <label for="ratings">{{ lang('show own ratings') }}</label>
      <select id="ratings" v-model="ratings" @change="updateOptions">
        <option value="always">{{ lang('always') }}</option>
        <option value="hover">{{ lang('on hover') }}</option>
        <option value="never">{{ lang('never') }}</option>
      </select>
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

    props: ['user'],

    components: {
      Checkbox
    },

    created() {
      this.setOptionsFromUser(this.user);
    },

    data() {
      return {
        config: window.config,
        genre: null,
        date: null,
        spoiler: null,
        watchlist: null,
        ratings: null,
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
        this.genre = user.genre;
        this.date = user.date;
        this.spoiler = user.spoiler;
        this.watchlist = user.watchlist;
        this.ratings = user.ratings;
      },

      updateOptions() {
        this.SET_LOADING(true);

        const {date, genre, ratings, spoiler, watchlist} = this;

        http.patch(`${config.api}/settings`, {date, genre, spoiler, watchlist, ratings}).then(response => {
          this.SET_LOADING(false);
        }, error => {
          alert(error.message);
        })
      },
    }

  }
</script>
