<template>
  <transition mode="out-in" name="fade">
    <li class="review-item" v-html="content"></li>
  </transition>
</template>

<script>
  export default {
    props: ['item'],

    computed: {
      content() {
        return this.item.content.replace(/\n/g, '<br />');
      }
    }
  }
</script>
