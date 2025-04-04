<!doctype html>
<html>
<head>

  <meta charset="utf-8">
  <meta id="token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Flox</title>
  <link rel="stylesheet" href="{{ url('assets/app.css') }}">
  <link href="{{ url('assets/favicon.ico?v=3') }}" rel="icon" type="image/x-icon">
  <link rel="manifest" href="{{ url('manifest.json') }}">

</head>
<body
  data-env="{{ config('app.env') }}"
  data-url="{{ url('/') }}"
  data-uri="{{ config('app.CLIENT_URI') }}"
  data-poster-tmdb="{{ config('services.tmdb.poster') }}"
  data-poster-subpage-tmdb="{{ config('services.tmdb.poster_subpage') }}"
  data-backdrop-tmdb="{{ config('services.tmdb.backdrop') }}"
  data-auth="{{ Auth::id() }}"
  data-language="{{ $lang }}"
  class="{{ Auth::check() ? 'logged' : 'guest' }}"
>

  <div id="app"></div>

  <script src="{{ url('assets/vendor.js') }}"></script>
  <script src="{{ url('assets/app.js') }}"></script>

</body>
</html>
