<template>
  <div>
    <span v-if="review && review.rating != null && ! item.watchlist" :class="'item-rating rating-' + review.rating" @click="changeRating()">
      <i class="icon-rating"></i>
    </span>
    <span v-if="review == null && ! item.watchlist && item.tmdb_id && auth && ! localRated" class="item-rating item-new" @click="addNewItem()">
      <i class="icon-add"></i>
    </span>
    <span v-if="item.watchlist" class="item-rating item-new" @click="changeRating()">
      <i class="icon-add"></i>
    </span>
    <span v-if="review == null && item.tmdb_id && localRated" class="item-rating item-new item-rating-loader">
      <span class="loader smallsize-loader"></span>
    </span>
  </div>
</template>

<script>
  import debounce from 'debounce';
  import http from 'axios';

  const ratingMilliseconds = 700;
  const newItemMilliseconds = 200;

  export default {
    props: ['item', 'set-item', 'rated', 'set-rated'],

    data() {
      return {
        auth: config.auth,
      }
    },

    computed: {
      localRated() {
        return this.rated;
      },

      review() {
        return this.item.review && this.item.review.length
          ? this.item.review[0]
          : null;
      }
    },

    created() {
      this.saveNewRating = debounce(this.saveNewRating, ratingMilliseconds);
      this.addNewItem = debounce(this.addNewItem, newItemMilliseconds, true);
    },

    methods: {
      changeRating() {
        if(this.auth) {
          if(this.item.watchlist) {
            this.review.rating = 0;
          } else {
            this.prevRating = this.review.rating;
            this.review.rating = this.prevRating == 3
              ? 1
              : +this.prevRating + 1;
          }
          
          this.item.watchlist = false;

          this.saveNewRating();
        }
      },

      saveNewRating() {
        http.patch(`${config.api}/review/change-rating/${this.review.id}`, {rating: this.review.rating}).catch(error => {
          this.review.rating = this.prevRating;
          alert('Error in saveNewRating()');
        });
      },

      addNewItem() {
        if(this.auth) {
          //this.rated = true;
          this.setRated(true);

          http.post(`${config.api}/add`, {item: this.item}).then(response => {
            this.setItem(response.data);
            //this.rated = false;
            this.setRated(false);
          }, error => {
            if(error.status == 409) {
              alert(this.item.title + ' already exists!');
            }
          });
        }
      }
    }
  }
</script>
