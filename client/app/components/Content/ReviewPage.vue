<template>
  <main>
    <div class="wrap-review" v-if=" ! loading">
        <h1>{{ pageTitle }}</h1>
        <p class="review-content">{{ review.content }}</p>
        <div class="review-actions">
            <router-link
              :to="itemLink"
              class="review-back-link"
              :title="item.title">{{ lang('return_item') }}</router-link>
            <button @click="deleteReview">{{ lang('delete item') }}</button>
        </div>
    </div>

    <span class="loader fullsize-loader" v-if="loading"><i></i></span>
  </main>
</template>

<script>
  import { mapActions, mapState, mapMutations } from 'vuex'
  import MiscHelper from '../../helpers/misc';

  import http from 'axios';

  export default {
    mixins: [MiscHelper],

    created() {
      this.fetchData();
    },

    destroyed() {
      this.SET_ITEM_LOADED_SUBPAGE(false);
    },

    data() {
      return {
        item: {},
        review: {},
        user: {}
      }
    },

    computed: {
      ...mapState({
        loading: state => state.loading
      }),

      itemLink() {
        return { name: `subpage-${this.item.media_type}`, params: { tmdbId: this.item.tmdb_id, slug: this.item.slug }};
      }
    },

    methods: {
      ...mapMutations(['SET_LOADING', 'SET_ITEM_LOADED_SUBPAGE']),
      ...mapActions([ 'setPageTitle' ]),

      fetchData() {
        const reviewId = this.$route.params.reviewId;

        this.SET_LOADING(true);
        http(`${config.api}/review/${reviewId}`).then(response => {
          this.review = response.data;
          this.item = this.review.item;
          this.user = this.review.user;
          this.pageTitle = `${this.item.title} ${this.lang('reviewed_by')} ${this.user.username}`;

          this.setPageTitle(this.pageTitle);

          this.disableLoading();
        }, error => {
          this.SET_LOADING(false);
          this.$router.push('/');
        });
      },

      deleteReview() {
        const confirmed = confirm(this.lang('confirm delete'));
        if(confirmed === false) {
          return false;
        }

        http.delete(`${config.api}/review/${this.review.id}`).then(() => {
          this.$router.push(this.itemLink);
        })
        .catch(error => {
            console.log( error );
            console.log( 'try refreshing the page' );
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
      }
    },

    components: {
    },

    watch: {
      $route() {
        this.fetchData();
        this.scrollToTop();
      }
    }
  }
</script>
