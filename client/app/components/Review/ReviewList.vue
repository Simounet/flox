<template>
  <ol
    v-if="reviewsWithContent.length"
    class="reviews"
  >
    <li
      :key="'review-' + index"
      v-for="(review, index) in reviewsWithContent"
      class="review-item">
      <p v-html="contentFilter(review.content)"></p>
      <ReviewLink
        :review="review"
        class="review-item__link"
      />
    </li>
  </ol>
</template>

<script>
  import ReviewLink from './ReviewLink.vue';

  export default {
    props: ['reviews'],

    computed: {
      reviewsWithContent() {
        return this.reviews.filter(review => review.content !== '');
      }
    },

    methods: {
      contentFilter(content) {
        return content.replace(/\n/g, '<br />');
      }
    },

    components: {
      ReviewLink
    }
  }
</script>
