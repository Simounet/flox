<template>
  <div class="all-modals">
    <transition name="fade" mode="out-in" appear>
      <template v-if="modalType === 'season'">
        <season></season>
      </template>
      <template v-else-if="modalType === 'trailer'">
        <trailer></trailer>
      </template>
    </transition>
    <span class="overlay" v-if="overlay" @click="CLOSE_MODAL()"></span>
  </div>
</template>

<script>
  import Season from './Season.vue';
  import Trailer from './Trailer.vue';

  import { mapState, mapMutations } from 'vuex';

  export default {
    computed: {
      ...mapState({
        overlay: state => state.overlay,
        modalType: state => state.modalType
      })
    },

    methods: {
      ...mapMutations([ 'CLOSE_MODAL' ])
    },

    components: {
      Season, Trailer
    }
  }
</script>
