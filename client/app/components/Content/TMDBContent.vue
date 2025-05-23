<template>
  <main :class="{'display-suggestions': path === 'suggestions'}">
    <div class="content-submenu" v-if=" ! loading && items.length">
      <div class="sort-wrap no-select" v-if="isGenrePage">
        <div class="filter-wrap">
          <span class="current-filter" @click="toggleShowGenres()">{{ currentGenre }} <span class="arrow-down"></span></span>
          <ul class="all-filters" :class="{active: showFilters}">
            <router-link :to="'/genre/' + genre.name" v-for="genre in notCurrentGenres" :key="genre.id">{{ genre.name }}</router-link>
          </ul>
        </div>
        <div class="show-watchlist-items">
          <Checkbox
            v-model="showWatchlistItems"
            @update:modelValue="toggleWatchlistItems"
            id="content-tmdbcontent-showwatchlistitems"
            :label="lang('Watchlist')"
            />
        </div>
      </div>
    </div>
    
    <div class="wrap-content" v-if=" ! loading">
      <div class="items-wrap">
        <Item v-for="(item, index) in filteredItems"
              :item="item"
              :key="index"
              :genre="true"
              :date="true"
              :ratings="displayRatings"
        ></Item>
      </div>
    </div>

    <span class="loader fullsize-loader" v-if="loading"><i></i></span>
  </main>
</template>

<script>
  import Checkbox from './Checkbox.vue';
  import Item from './Item.vue';
  import MiscHelper from '../../helpers/misc';

  import http from 'axios';

  import { mapState, mapMutations, mapActions } from 'vuex'

  export default {
    mixins: [MiscHelper],

    created() {
      this.fetchSettings();
      this.init();
    },

    data() {
      return {
        items: [],
        genres: [],
        isGenrePage: false,
        currentGenre: '',
        showWatchlistItems: false,
        path: '',
        displayRatings: null
      }
    },

    computed: {
      ...mapState({
        loading: state => state.loading,
        showFilters: state => state.showFilters
      }),

      filteredItems() {
        return this.items.filter(item => item.user_review === null || (this.showWatchlistItems && item.user_review.watchlist));
      },

      notCurrentGenres() {
        return this.genres.filter(genre => genre !== this.currentGenre);
      }
    },

    methods: {
      ...mapMutations([ 'SET_LOADING', 'SET_SHOW_FILTERS' ]),
      ...mapActions([ 'setPageTitle' ]),

      init() {
        this.SET_LOADING(true);
        this.path = this.$route.name;

        switch(this.path) {
          case 'suggestions':
            return this.initSuggestions();
          case 'genre':
            this.isGenrePage = true;
            return this.initContentByGenre();
          case 'trending':
          case 'upcoming':
          case 'now-playing':
            return this.initContent(this.path);
        }
      },

      toggleWatchlistItems(value) {
        this.showWatchlistItems = value;
      },

      toggleShowGenres() {
        this.SET_SHOW_FILTERS( ! this.showFilters);
      },
      
      initAllGenres() {
        http(`${config.api}/genres`).then(response => {
          this.genres = response.data;
        }, error => {
          console.log(error);
        })
      },
      
      initContentByGenre() {
        this.initAllGenres();
        
        this.currentGenre = this.$route.params.genre;
        
        this.setPageTitle(this.lang('genre'));
        
        http(`${config.api}/genre/${this.currentGenre}`).then(response => {
          this.items = response.data;
          this.SET_LOADING(false);
        }, error => {
          console.log(error);
          this.$router.push('/')
        })
      },

      initSuggestions() {
        const tmdbID = this.$route.query.for;
        const type = this.$route.query.type;

        this.setPageTitle(this.lang('suggestions for') + ' ' + this.$route.query.name);

        http(`${config.api}/suggestions/${tmdbID}/${type}`).then(response => {
          this.items = response.data;
          this.SET_LOADING(false);
        });
      },

      initContent(path) {
        this.items = [];
        this.setPageTitle(this.lang(path));

        http(`${config.api}/${path}`).then(response => {
          this.items = response.data;
          this.SET_LOADING(false); 
        });
      },

      fetchSettings() {
        http(`${config.api}/settings`).then(value => {
          this.displayRatings = value.data.ratings;
        });
      }
    },

    components: {
      Checkbox,
      Item
    },

    watch: {
      $route() {
        this.scrollToTop();
        this.init();
      }
    }
  }
</script>
