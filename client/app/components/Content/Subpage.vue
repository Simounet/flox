<template>
  <main>
    <div class="bigsize-header">
      <section class="big-teaser-wrap" :class="{active: itemLoadedSubpage}" v-show=" ! loading">

        <div class="big-teaser-image" :style="backdropImage"></div>

        <div class="wrap">
          <div class="big-teaser-content">
            <div class="subpage-sidebar">
              <div class="subpage-poster-wrap no-select" :class="'show-ratings-' + displayRatings">
                <div class="item-actions">
                  <router-link :title="lang('suggestions')" :to="suggestionsUri(item)" v-if="item.tmdb_id"
                               class="has-suggestion">
                    <i class="icon-suggest"></i>
                  </router-link>
                  <span class="is-on-watchlist" :title="lang('add to watchlist')"
                        v-if="!isLocalContent && auth && ! rated" @click="addToWatchlist(item)">
                    <i class="icon-watchlist"></i>
                  </span>
                  <span class="is-on-watchlist" :title="lang('remove from watchlist')"
                        v-if="item.user_review && item.user_review.watchlist && auth && ! rated" @click="removeItem()">
                    <i class="icon-watchlist-remove"></i>
                  </span>
                  <span :title="lang('episodes')" v-if="displaySeason(item) && latestEpisode" @click="openSeasonModal(item)"
                        class="is-a-show">
                    S{{ season }}E{{ episode }}
                  </span>
                  <span :title="lang('finished')" v-if="displaySeason(item) && !latestEpisode"
                        @click="openSeasonModal(item)" class="is-a-show">
                    <i class="is-finished"></i>
                  </span>
                </div>

                <rating :rated="rated" :item="item" :set-item="setItem" :set-rated="setRated"></rating>
                <img class="real" :src="posterImage" width="272" height="408">

              </div>

              <!-- todo: move to own component -->
              <div class="subpage-sidebar-buttons no-select" v-if="item.user_review && auth">
                <span class="refresh-infos" @click="refreshInfos()">{{ lang('refresh infos') }}</span>
                <span class="remove-item" @click="removeItem()" v-if=" ! item.user_review.watchlist">{{ lang('delete item') }}</span>
              </div>
            </div>

            <div class="big-teaser-data-wrap">

              <!-- todo: move to own component -->
              <div class="big-teaser-item-data">
                <div class="item-year">{{ released }}, <i>{{ lang(item.media_type) }}</i></div>
                <div class="item-title">{{ item.title }}</div>
                <div class="item-genre">
                  <router-link :key="genre.id" :to="'/genre/' + genre.name"
                               v-for="genre in item.genre">{{ genre.name }}</router-link>
                </div>
              </div>
              <div class="big-teaser-buttons no-select" :class="{'without-watchlist': isLocalContent || ! auth}">
                <a v-if="isOn('netflix', item.homepage)" :href="item.homepage" target="_blank" class="button-netflix">
                  Netflix
                </a>
                <a v-if="isOn('amazon', item.homepage)" :href="item.homepage" target="_blank" class="button-amazon">
                  Amazon Prime
                </a>
                <a v-if="isOn('disney', item.homepage)" :href="item.homepage" target="_blank" class="button-disney">
                  Disney+
                </a>
                <a v-if="isOn('apple', item.homepage)" :href="item.homepage" target="_blank" class="button-apple">
                  Apple TV+
                </a>
                <span @click="openTrailer()" v-if="item.youtube_key" class="button-trailer"><i class="icon-trailer"></i> {{ lang('watch trailer') }}</span>
                <!--                <span class="button-watchlist" v-if="item.rating == null && auth && ! rated" @click="addToWatchlist(item)"><i class="icon-watchlist"></i> {{ lang('add to watchlist') }}</span>-->
                <!--                <span class="button-watchlist" v-if="item.watchlist && auth && ! rated" @click="removeItem()"><i class="icon-watchlist-remove"></i> {{ lang('remove from watchlist') }}</span>-->
                <a :href="`https://www.themoviedb.org/${item.media_type}/${item.tmdb_id}`" target="_blank"
                   class="button-tmdb-rating">
                  <i v-if="item.tmdb_rating && item.tmdb_rating != 0"><b>{{ item.tmdb_rating }}</b> TMDb</i>
                  <i v-else>{{ lang('no tmdb rating') }}</i>
                </a>
                <a v-if="item.imdb_id" :href="`http://www.imdb.com/title/${item.imdb_id}`" target="_blank"
                   class="button-imdb-rating">
                  <i v-if="loadingImdb">{{ lang('loading imdb rating') }}</i>
                  <i v-if="item.imdb_rating && ! loadingImdb"><b>{{ item.imdb_rating }}</b> IMDb</i>
                  <i v-if=" ! item.imdb_rating && ! loadingImdb">IMDb</i>
                </a>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>

    <div class="subpage-content" :class="{active: itemLoadedSubpage}" v-show=" ! loading">
      <div class="wrap">
        <div class="subpage-overview">
          <h2>{{ lang('overview') }}</h2>
          <p>{{ overview }}</p>
          <ol
            v-if="creditCast.length"
            class="cast-list"
          >
            <Person
              :item="item"
              :key="'cast-' + index"
              v-for="(item, index) in creditCast"
            />
          </ol>

          <ol
            v-if="creditCrew.length"
            class="cast-list"
          >
            <Person
              :item="item"
              :key="'crew-' + index"
              v-for="(item, index) in creditCrew"
            />
          </ol>
          <ReviewItems
            v-if="item.review && item.review.length > 0"
            :itemId="item.id"
            :reviews="reviews"
          />
        </div>
      </div>
    </div>

    <span class="loader fullsize-loader" v-show="loading"><i></i></span>
  </main>
</template>

<script>
  import Rating from '../Rating.vue';
  import {mapActions, mapMutations, mapState} from 'vuex'
  import MiscHelper from '../../helpers/misc';
  import ItemHelper from '../../helpers/item';
  import Person from './Person.vue';
  import ReviewItems from '../Review/ReviewItems.vue';

  import http from 'axios';

  export default {
    mixins: [MiscHelper, ItemHelper],

    props: ['mediaType'],

    unmounted() {
      document.body.classList.remove('subpage-open');
      this.SET_ITEM_LOADED_SUBPAGE(false);
      this.CLOSE_MODAL();
    },

    created() {
      document.body.classList.add('subpage-open');
      window.scrollTo(0, 0);
      this.fetchSettings();
      this.fetchData();
    },

    data() {
      return {
        item: {},
        isLocalContent: false,
        latestEpisode: null,
        creditCast: [],
        creditCrew: [],
        reviews: [],
        loadingImdb: false,
        auth: config.auth,
        rated: false,
        displayRatings: null
      }
    },

    computed: {
      ...mapState({
        loading: state => state.loading,
        itemLoadedSubpage: state => state.itemLoadedSubpage
      }),

      overview() {
        return this.item.overview ? this.item.overview : '-';
      },

      backdropImage() {
        if(typeof(this.item.backdrop) !== 'string') {
            return '';
        }

        const backdropBaseUrl = this.isLocalContent ?
          config.backdrop : config.backdropTMDB;

        return {
          backgroundImage: `url(${backdropBaseUrl}${this.item.backdrop})`
        }
      },

      posterImage() {
        if (typeof(this.item.poster) !== 'string' || !this.item.poster) {
          return this.noImage;
        }

        const posterBaseUrl = this.isLocalContent ?
          config.posterSubpage : config.posterSubpageTMDB;

        return posterBaseUrl + this.item.poster;
      },

      noImage() {
        return config.url + '/assets/img/no-image-subpage.png';
      },

      released() {
        const released = new Date(this.item.released * 1000);

        return released.getFullYear();
      },
    },

    methods: {
      ...mapMutations(['SET_LOADING', 'SET_ITEM_LOADED_SUBPAGE', 'OPEN_MODAL', 'CLOSE_MODAL', 'SET_RATED']),
      ...mapActions(['setPageTitle', 'fetchEpisodes']),

      openTrailer() {
        this.OPEN_MODAL({
          type: 'trailer',
          data: {
            youtubeKey: this.item.youtube_key,
            title: this.item.title
          }
        });
      },

      fetchImdbRating() {
        if (this.item.imdb_id && this.isLocalContent === false) {
          this.loadingImdb = true;

          http(`${config.api}/imdb-rating/${this.item.imdb_id}`).then(response => {
            const rating = this.intToFloat(response.data);

            this.$set(this.item, 'imdb_rating', rating);
            this.loadingImdb = false;
          }, error => {
            alert(error);
            this.loadingImdb = false;
          });
        }
      },

      fetchSettings() {
        http(`${config.api}/settings`).then(value => {
          this.displayRatings = value.data.ratings;
        });
      },

      fetchData() {
        const tmdbId = this.$route.params.tmdbId;

        this.SET_LOADING(true);
        http(`${config.api}/item/${tmdbId}/${this.mediaType}`).then(response => {
          this.item = response.data;
          this.isLocalContent = !!this.item.user_review;
          this.creditCast = this.item.credit_cast;
          this.creditCrew = this.item.credit_crew;
          this.reviews = this.item.review;
          this.item.tmdb_rating = this.intToFloat(this.item.tmdb_rating);
          this.latestEpisode = this.item.latest_episode;

          this.setPageTitle(this.item.title);

          this.disableLoading();
          // @TODO web scrapping not working anymore, should use Imdb API instead
          //this.fetchImdbRating();
        }, error => {
          console.log(error);
          this.SET_LOADING(false);
          this.$router.push('/');
        });
      },

      disableLoading() {
        setTimeout(() => {
          this.SET_LOADING(false);
          this.displayItem();
        }, 100);
      },

      displayItem() {
        setTimeout(() => {
          this.SET_ITEM_LOADED_SUBPAGE(true);
        }, 50);
      },

      setItem(item) {
        this.item = item;
      },

      setRated(rated) {
        this.rated = rated;
      },

      removeItem() {
        if(!window.confirm(this.lang('Remove my content for this item (rating, review, watchlist…)'))) {
          return false;
        }
        this.rated = true;

        http.delete(`${config.api}/review/${this.item.user_review.id}`).then(response => {
          this.rated = false;
          // @TODO update item.review list
          this.item.user_review = null;
          this.reviews = [];
          this.isLocalContent = false;
        }, error => {
          alert(error);
          this.rated = false;
        });
      },

      refreshInfos() {
        this.SET_LOADING(true);
        this.SET_ITEM_LOADED_SUBPAGE(false);

        http.patch(`${config.api}/refresh/${this.item.id}`).then(response => {
          location.reload();
        }, error => {
          alert(error);
          this.SET_LOADING(false);
        })
      }
    },

    components: {
      Person,
      Rating,
      ReviewItems
    }
  }
</script>
