<template>
  <div>
    <span v-if="item.item_user && item.item_user.rating != null && ! item.item_user.watchlist" :class="'item-rating rating-' + item.item_user.rating" @click="changeRating()">
      <i class="icon-rating"></i>
    </span>
    <span v-if="item.item_user === null && item.tmdb_id && auth && ! localRated" class="item-rating item-new" @click="addNewItem()">
      <i class="icon-add"></i>
    </span>
    <span v-if="item.item_user && item.item_user.watchlist" class="item-rating item-new" @click="changeRating()">
      <i class="icon-add"></i>
    </span>
    <span v-if="item.item_user === null && item.tmdb_id && localRated" class="item-rating item-new item-rating-loader">
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
      }
    },

    created() {
      this.saveNewRating = debounce(this.saveNewRating, ratingMilliseconds);
      this.addNewItem = debounce(this.addNewItem, newItemMilliseconds, true);
    },

    methods: {
      changeRating() {
        if(this.auth) {
          if(this.item.item_user.watchlist) {
            this.item.item_user.rating = 0;
          } else {
            this.prevRating = this.item.item_user.rating;
            this.item.item_user.rating = this.prevRating == 3
              ? 1
              : +this.prevRating + 1;
          }
          
          this.item.item_user.watchlist = false;

          this.saveNewRating();
        }
      },

      saveNewRating() {
        http.patch(`${config.api}/item/change-rating/${this.item.item_user.id}`, {rating: this.item.item_user.rating}).catch(error => {
          this.item.item_user.rating = this.prevRating;
          alert('Error in saveNewRating()');
        });
      },

      addNewItem() {
        if(this.auth) {
          //this.rated = true;
          this.setRated(true);

          http.post(`${config.api}/add`, {tmdb_id: this.item.tmdb_id, media_type: this.item.media_type}).then(response => {
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
