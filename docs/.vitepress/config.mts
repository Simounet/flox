import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  base: '/flox/',
  title: "Flox Documentation",
  description: "A VitePress Site",
  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    footer: {
      message: 'Feel free to <a href="https://github.com/Simounet/flox/tree/main/docs">improve this doc</a>.'
    },

    nav: [
      { text: 'Home', link: '/' }
    ],

    sidebar: [
      {
        text: 'Pages',
        items: [
          {
            text: 'About Flox',
            items: [
              {
                text: 'Overview',
                link: '/about-flox'
              },
              {
                text: 'Setup',
                link: '/about-flox/setup'
              },
              {
                text: 'Admin Configuration',
                link: '/about-flox/admin-configuration'
              },
              {
                text: 'User Configuration',
                link: '/about-flox/user-configuration'
              },
              {
                text: 'Federation',
                link: '/about-flox/federation'
              },
              {
                text: 'Plex',
                link: '/about-flox/tools'
              },
              {
                text: 'Troubleshooting',
                link: '/about-flox/troubleshooting'
              }
            ]
          },
          { text: 'Future?', link: '/future' }
        ]
      }
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/Simounet/flox/' }
    ]
  }
})
