<?php

  namespace App\Http\Controllers;

  use Illuminate\Http\RedirectResponse;
  use Illuminate\Support\Facades\Auth;
  use Illuminate\Support\Facades\Request;
  use Symfony\Component\HttpFoundation\Response;

  class UserController {

    public function login(): Response
    {
      $username = Request::input('username');
      $password = Request::input('password');

      if(Auth::attempt(['username' => $username, 'password' => $password], true)) {
        return response('Success', Response::HTTP_OK);
      }

      return response('Unauthorized', Response::HTTP_UNAUTHORIZED);
    }

    public function getUserData(): array
    {
      return [
        'username' => Auth::user()->username,
      ];
    }

    /**
     * Save new user credentials.
     */
    public function changeUserData(): Response
    {
      if (isDemo()) {
        return response('Success', Response::HTTP_OK);
      }

      $username = Request::input('username');
      $password = Request::input('password');

      $user = Auth::user();
      $user->username = $username;

      if($password != '') {
        $user->password = bcrypt($password);
      }

      if($user->save()) {
        return response('Success', Response::HTTP_OK);
      }

      return response('Server Error', Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function logout(): RedirectResponse
    {
      Auth::logout();
      Request::session()->invalidate();
      Request::session()->regenerateToken();

      return redirect('/');
    }
  }
