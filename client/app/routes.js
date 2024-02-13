import Vue from 'vue';
import Router from 'vue-router';

import config from './config';

import Content from './components/Content/Content.vue';
import SearchContent from './components/Content/SearchContent.vue';
import TMDBContent from './components/Content/TMDBContent.vue';
import Subpage from './components/Content/Subpage.vue';
import ReviewPage from "./components/Content/ReviewPage.vue";

const Calendar = () =>
  import(
    /* webpackChunkName: "calendar" */ "./components/Content/Calendar.vue"
  );
const Settings = () =>
  import(
    /* webpackChunkName: "settings" */ "./components/Content/Settings/Index.vue"
  );

Vue.use(Router);

export default new Router({
  mode: 'history',
  base: config.uri,
  routes: [
    { path: '/', component: Content, name: 'home' },

    // todo: use props for media type
    { path: '/movies', component: Content, name: 'movie' },
    { path: '/tv', component: Content, name: 'tv' },
    { path: '/watchlist/:type?', component: Content, name: 'watchlist' },

    { path: '/users/:username/review/:reviewId', component: ReviewPage, name: 'user-review' },
    
    { path: '/movies/:tmdbId/:slug?', component: Subpage, name: 'subpage-movie', props: {mediaType: 'movie'} },
    { path: '/tv/:tmdbId/:slug?', component: Subpage, name: 'subpage-tv', props: {mediaType: 'tv'} },

    { path: '/search', component: SearchContent, name: 'search' },
    { path: "/settings", component: Settings, name: "settings" },
    { path: '/suggestions', component: TMDBContent, name: 'suggestions' },
    { path: '/trending', component: TMDBContent, name: 'trending' },
    { path: '/upcoming', component: TMDBContent, name: 'upcoming' },
    { path: '/now-playing', component: TMDBContent, name: 'now-playing' },
    { path: '/genre/:genre', component: TMDBContent, name: 'genre' },
    { path: "/calendar", component: Calendar, name: "calendar" },

    { path: '*', redirect: '/' }
  ]
});
