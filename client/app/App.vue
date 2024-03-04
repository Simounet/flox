<template>
  <ModalIndex v-if="!isLoginRoute" />
  <Header v-if="!isLoginRoute" />
  <router-view />
  <Footer v-if="!isLoginRoute" />
</template>

<script>
  import { mapActions, mapMutations, mapState } from 'vuex';

  import ModalIndex from './components/Modal/Index.vue';
  import Footer from './components/Footer.vue';
  import Header from './components/Header.vue';

  export default {
    created() {
      this.isLoginRoute = this.checkIsLoginRoute();
      this.checkForUserColorScheme();
      this.checkForUserFilter();
      this.checkForUserSortDirection();
    },

    data() {
      return {
        isLoginRoute: null
      };
    },

    watch: {
      '$route'() {
        this.isLoginRoute = this.checkIsLoginRoute();
      }
    },

    computed: {
      ...mapState({
        colorScheme: (state) => state.colorScheme,
        filters: (state) => state.filters,
        showFilters: (state) => state.showFilters,
      }),
    },

    methods: {
      ...mapActions(["setColorScheme"]),
      ...mapMutations([
        "SET_USER_FILTER",
        "SET_SHOW_FILTERS",
        "SET_USER_SORT_DIRECTION",
      ]),

      checkForUserColorScheme() {
        if (!localStorage.getItem("color")) {
          localStorage.setItem("color", "dark");
        }

        this.setColorScheme(localStorage.getItem("color"));
      },

      checkForUserFilter() {
        let filter = localStorage.getItem("filter");

        if (!filter || !this.filters.includes(filter)) {
          localStorage.setItem("filter", this.filters[0]);
        }

        this.SET_USER_FILTER(localStorage.getItem("filter"));
      },

      checkForUserSortDirection() {
        if (!localStorage.getItem("sort-direction")) {
          localStorage.setItem("sort-direction", "desc");
        }

        this.SET_USER_SORT_DIRECTION(localStorage.getItem("sort-direction"));
      },

      checkIsLoginRoute() {
        return this.$route.path === '/login';
      }
    },

    components: {
      Footer,
      Header,
      ModalIndex
    }
  }
</script>
