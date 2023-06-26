<template>
  <ol
    class="reviews"
  >
    <li
      :key="'review-' + index"
      v-for="(review, index) in reviews"
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
  import ReviewLink from '../Review/ReviewLink.vue';

  export default {
    props: ['reviews'],

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
