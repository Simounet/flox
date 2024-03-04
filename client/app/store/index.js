import { createStore } from "vuex";

import * as actions from "./actions";
import mutations from "./mutations";

export default createStore({
  state: {
    filters: [
      "last seen",
      "own rating",
      "title",
      "release",
      "tmdb rating",
      "imdb rating",
    ],
    showFilters: false,
    items: [],
    searchTitle: "",
    userFilter: "",
    userSortDirection: "",
    loading: false,
    clickedMoreLoading: false,
    paginator: null,
    colorScheme: "",
    overlay: false,
    modalData: {},
    loadingModalData: true,
    seasonActiveModal: 1,
    modalType: "",
    itemLoadedSubpage: false,
  },
  mutations,
  actions,
});
