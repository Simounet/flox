<template>
  <form v-if="auth" @submit.prevent="sendReview()">
    <label for="review-add" class="review-add-label">{{ lang('Votre avis') }}</label>
    <textarea id="review-add" v-model="review.content" class="review-add" required></textarea>
    <span class="userdata-changed" v-if="log.length > 0"><span>{{ lang(log) }}</span></span>
    <div class="review-add-actions">
        <button type="submit">{{ lang('save button') }}</button>
        <ReviewLink v-if="review.id" :review="review" class="review-add-link" />
    </div>
  </form>
</template>

<script>
  import MiscHelper from '../../helpers/misc';
  import ReviewLink from './ReviewLink.vue';

  import http from 'axios';
  import debounce from 'debounce';

  const debounceMilliseconds = 5000;

  export default {
    mixins: [MiscHelper],

    props: ['itemId', 'review'],

    created() {
      this.clearLogMessage = debounce(this.clearLogMessage, debounceMilliseconds);
    },

    data() {
      return {
        auth: config.auth,
        log: ''
      }
    },

    methods: {
      sendReview() {
        const content = this.review.content;

        if(content !== '') {
          http.post(`${config.api}/review`, {content, itemId: parseInt(this.itemId)}).then(() => {
            this.displayLogMessage('success message');
          })
          .catch(err => {
            console.log(err.response);
            this.displayLogMessage('error message');
          });
        }
      },

      displayLogMessage(message) {
        this.log = message;
        this.clearLogMessage();
      },

      clearLogMessage() {
        this.log = '';
      }
    },

    components: {
      ReviewLink
    }
  }
</script>
