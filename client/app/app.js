require("../resources/sass/app.scss");

import { createApp } from "vue";
import VueHotkey from "v-hotkey3";

import App from "./App.vue";
import router from "./routes";
import store from "./store";

const app = createApp(App);

app.config.globalProperties.$store = store;

app.use(router);
app.use(store);
app.use(VueHotkey);

app.mount("#app");
