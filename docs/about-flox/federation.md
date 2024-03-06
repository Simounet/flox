# ActivityPub Federation

## About federation

Thanks to [the ActivityPub protocol](https://en.wikipedia.org/wiki/ActivityPub), Flox users can share theirs activities to other people using platforms that understand ActivityPub.

## Which Flox actions are federated

- Reviews: when a review is created/updated/deleted, the review's content will be delivered to their followers

## Disabling federation

If you want to use Flox in solo, without sharing anything outside of your server, you can disable this feature by adding this configuration to your `backend/.env` file:

```
FEDERATION_ENABLED=false
```
