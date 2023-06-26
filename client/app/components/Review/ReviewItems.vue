<template>
  <div>
    <UserReview
      :review="userReview"
      :itemId="itemId"
    />
    <ReviewItem
      v-if="filteredReviews.length > 0"
      :reviews="filteredReviews"
    />
  </div>
</template>

<script>
  import MiscHelper from '../../helpers/misc';
  import ReviewItem from '../Content/ReviewItem.vue';
  import UserReview from './UserReview.vue';

  export default {
    mixins: [MiscHelper],

    props: ['itemId', 'reviews'],

    data() {
      return {
        auth: config.auth
      }
    },

    computed: {
      userReview() {
        const userReviews = this.reviews.filter(review => review.user.user_id === this.auth);
        if(userReviews.length === 1) {
            return userReviews[0];
        }
        if(userReviews.length > 1) {
          console.warn('More than 1 user review detected.');
        }
        return {};
      },

      filteredReviews() {
        return this.auth ?
            this.reviews.filter(review => review.user.user_id !== this.auth) : this.reviews;
      }
    },

    components: {
      ReviewItem,
      UserReview
    }
  }
</script>
