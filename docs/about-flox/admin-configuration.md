# Admin Configuration

## How can I change Flox behavior?

Edit the `backend/.env` file. `bakend/.env.example` is a sample file with all available variables.

## Variables

- ActivityPub Federation

```
FEDERATION_ENABLED=true|false
```

- TRANSLATION

All titles are in english by default. You can change your language by setting `TRANSLATION` in `backend/.env`. The most commons are `DE`, `IT`, `FR`, `ES` and `RU`. You can try to use your language code.

This will also affect the language of you website. See in `client/resources/languages` if your language is supported. Pull requests are welcome :)

If there isn't a translation for your language, english will be used.

```
TRANSLATION=AR|DE|EL|EN|ES|FR|NL|PT|RU
```
