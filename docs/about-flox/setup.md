# Setup

## Requirements

* PHP >=8.1
* Database (MySQL or [other](https://laravel.com/docs/database))
* [Composer](https://getcomposer.org/)
* The Movie Database Account for the free [API-Key](https://www.themoviedb.org/faq/api)

## Install

```bash
git clone https://github.com/simounet/flox
cd flox/backend
composer install --no-dev -o --prefer-dist
php artisan flox:init # Enter here your database credentials
```

Enter your TMDb API-Key in `backend/.env`. Then run:
```bash
php artisan flox:db # Running migrations and enter your admin credentials for the site
```

* Give `backend/storage`, `public/assets` and `public/exports` recursive write access.
* Set the correct `APP_URL` in `backend/.env`.
* Set your `CLIENT_URI` in `backend/.env`.
```bash
# CLIENT_URI=/flox/public
https://localhost:8888/flox/public

# CLIENT_URI=/subfolder/for/flox/public
https://mydomain.com/subfolder/for/flox/public

# CLIENT_URI=/
https://mydomain.com
```

## Queues

### Federation

ActivityPub implementation for Flox uses queues to asynchronously send activities to the federated servers without blocking user's actions. Don't forget to [setup the cronjob](#cron-job).

### Flox content

To import or refresh any of your entries you need to have at least one worker running.

```bash
# spawn a single worker
php artisan queue:work --daemon --tries=3

# Alternatively install it as a systemctl service:

# The script uses the current directory as Flox root path. To override
# use the first argument and set a new absolute Flox (root) path.
# A second argument takes the php path (defaults to /usr/bin)
bash ./bin/install_worker_service.sh
# bash ./bin/install_worker_service.sh $HOME/flox /custom/path/to/php/
```

The default queue driver is set to `database`. All your jobs will be stored in the `jobs` table. If you need some better performance and more reliability, consider to choose redis.

Check the [documentation](https://laravel.com/docs/queues) for more informations.

## Cron Job

To utilize the queues to update automatically you have to set up a cron task once manually on your server.

```
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

You can use ```crontab -e``` to add this new Cron task.

Make sure Cron is running and you are good to go.
```
sudo service cron status
```

This Cron will call the Laravel command scheduler every minute. Then, Laravel evaluates your scheduled tasks and runs the tasks that are due.

Currently in Flox defined tasks (which you can activate in the settings):

| When   | Description                     |
| ------ | ------------------------------- |
| Daily  | Update all Entities from TMDb   |
| Daily  | Send a daily reminder via mail   |
| Weekly  | Send a weekly summary via mail   |

You can change the time for daily and weekly reminder in your `.env`.

## Export / Import

Also you can make a backup of all your movies and shows in the settings page. If you click the `EXPORT` button, there will be an download for an `json` file. This file contains all your movies and shows from your database. This backup file will also be automatically saved in your `public/exports` folder.

If you import an backup, all movies and shows in your database will be deleted and replaced. Be sure to make an current backup before you import.
The import will download all poster images.

## Refresh data

To keep your entries up to date (e.g. ratings, episodes, images) you need to refresh them. In the settings there is the possibility to refresh the data manually or via a cron job (you need the queue worker for this). If you want to refresh only a single entry, there is a button on the subpage of this item.

## Reminders

Flox can send you a daily reminder of episodes or movies coming out today via mail. Or a weekly summary of episodes and movies coming out in the last 7 days. There are options in the settings page for this.

Make sure you tweak the `DATE_FORMAT_PATTERN` config in your `.env` file.

## Development

* Run `composer install` in your `/backend` folder.
* Run `npm install` or `yarn` in your `/client` folder.
* Run `npm run dev`.


