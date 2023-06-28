<template>
  <form v-if="auth" @submit.prevent="sendReview()">
    <label for="review-add" class="review-add-label">{{ lang('Votre avis') }}</label>
    <textarea id="review-add" v-model="review.content" class="review-add" required></textarea>
    <div class="review-add-actions">
        <button type="submit">{{ lang('save button') }}</button>
        <ReviewLink v-if="review.id" :review="review" class="review-add-link" />
    </div>
    <div v-if="logMsg.length > 0" :class="['userdata-changed', hasError ? 'userdata-changed--error' : '']">{{ lang(logMsg) }}</div>
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
        logMsg: '',
        hasError: false
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
            this.hasError = true;
            this.displayLogMessage('error message');
          });
        }
      },

      displayLogMessage(message) {
        this.logMsg = message;
        this.clearLogMessage();
      },

      clearLogMessage() {
        this.logMsg = '';
        this.hasError = false;
      }
    },

    components: {
      ReviewLink
    }
  }
</script>
