<template>
  <div class="modal-wrap modal-wrap-big">

    <div class="modal-header">
      <span class="modal-title">{{ lang('trailer for') }} {{ modalData.title }}</span>
      <button class="close-modal" @click="CLOSE_MODAL()">✖</button>
    </div>

    <div class="modal-content">
      <iframe width="100%" height="100%" :src="trailerSrc" frameborder="0" allowfullscreen></iframe>
    </div>

  </div>
</template>

<script>
  import { mapState, mapMutations } from 'vuex';
  import MiscHelper from '../../helpers/misc';

  export default {
    mixins: [MiscHelper],

    computed: {
      ...mapState({
        modalData: state => state.modalData
      }),

      trailerSrc() {
        return `https://www.youtube.com/embed/${this.modalData.youtubeKey}`;
      }
    },

    methods: {
      ...mapMutations([ 'CLOSE_MODAL' ])
    }
  }
</script>
