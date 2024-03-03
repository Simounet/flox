require("../resources/sass/app.scss");

import { createApp } from "vue";
import { mapState, mapActions, mapMutations } from "vuex";
import VueHotkey from "v-hotkey";

import { ElCheckbox } from "element-plus";
import 'element-plus/dist/index.css'

import router from "./routes";
import store from "./store";

const app = createApp(App);

app.config.globalProperties.$store = store;

app.use(router);
app.use(store);
app.use(VueHotkey);
app.use(ElCheckbox);

app.mixin({
  created() {
    this.checkForUserColorScheme();
    this.checkForUserFilter();
    this.checkForUserSortDirection();
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
  },
});

app.mount("#app");
