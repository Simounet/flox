<template>
  <main>
    <div class="wrap-content" v-if=" ! loading">
      <Item v-for="(item, index) in floxItems"
            :item="item"
            :key="'items-' + index"
            :genre="true"
            :date="true"
      ></Item>
      <Item v-for="(item, index) in tmdbItems"
            :item="item"
            :key="'tmdb-' + index"
            :genre="true"
            :date="true"
      ></Item>

      <span class="nothing-found" v-if=" ! floxItems.length && ! tmdbItems.length">{{ lang('nothing found') }}</span>
    </div>

    <span class="loader fullsize-loader" v-if="loading"><i></i></span>
  </main>
</template>

<script>
  import Item from './Item.vue';
  import MiscHelper from '../../helpers/misc';

  import http from 'axios';

  import { mapState, mapMutations, mapActions } from 'vuex'

  export default {
    mixins: [MiscHelper],

    created() {
      this.initSearch();
    },

    data() {
      return {
        floxItems: [],
        tmdbItems: []
      }
    },

    computed: {
      ...mapState({
        searchTitle: state => state.searchTitle,
        loading: state => state.loading
      })
    },

    methods: {
      ...mapMutations([ 'SET_SEARCH_TITLE', 'SET_LOADING' ]),
      ...mapActions([ 'setPageTitle' ]),

      initSearch() {
        this.SET_SEARCH_TITLE(this.$route.query.q);
        this.SET_LOADING(true);
        this.setPageTitle(this.lang('search for') + ' ' + this.$route.query.q);
        this.searchFlox();
        this.searchTMDB().then(() => {
          setTimeout(() => {
            this.SET_LOADING(false);
          }, 500);
        });
      },

      searchFlox() {
        http(`${config.api}/search-items?q=${this.searchTitle}`).then(value => {
          this.floxItems = value.data;
        }, error => {
          console.log(error);
        });
      },

      async searchTMDB() {
        if(config.auth) {
          await http(`${config.api}/search-tmdb?q=${this.searchTitle}`).then(value => {
            const floxItems = this.floxItems.map(item => item.tmdb_id);
            this.tmdbItems = value.data.filter(item => ! floxItems.includes(item.tmdb_id));
          }).catch(error => {
            alert('Error in searchTMDB(): ' + error);
          });
        }
      }
    },

    components: {
      Item
    },

    watch: {
      $route() {
        this.scrollToTop();
        this.initSearch();
      }
    }
  }
</script>