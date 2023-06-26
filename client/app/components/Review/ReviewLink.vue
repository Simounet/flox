<template>
  <router-link :to="{ name: 'user-review', params: { username: review.user.username, reviewId: review.id }}">{{ lang('go_to_review_page') }}</router-link>
</template>

<script>
  import MiscHelper from '../../helpers/misc';

  export default {
    mixins: [MiscHelper],

    props: ['review']
  }
</script>
