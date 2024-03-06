# Troubleshooting

## Import does not work

- Your import file is probably to big. In default php.ini the max upload file is 2MB. Set the number higher and try again.
- Make sure that the queue worker is active! Otherwise flox will tell you the import is running, but nothing happens!

