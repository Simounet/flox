# Tools you can use Flox with

## Kodi

You can track your activity from Kodi to Flox following these intructions:

1. Download [the last version of the Flox for Kodi addon](https://github.com/simounet/script.flox/releases/latest/download/script.flox.zip)
2. Put the extracted archive content (`script.flox`) inside the `~/.kodi/addons` directory
3. Activate the addon and fill in the requested credentials (url/api token)

## Plex

To enable the sync from Plex to Flox, you first need to generate an API-Key in Flox in the settings page. Then enter the Flox API-URL to the webhooks section in Plex.

```
https://YOUR-FLOX-URL/api/plex?token=YOUR-TOKEN
```

If you start a tv show or movie in Plex, Flox will search the item via the title from TMDb and add them into the Flox database. If you rate a movie or tv show in Plex, Flox will also rate the item. Note that rating for seasons or episodes are not supported in Flox. If you rate an movie or tv show, which is not in the Flox database, Flox will also fetch them from TMDb first. If you complete an episode (passing the 90% mark), Flox will also check this episode as seen.
