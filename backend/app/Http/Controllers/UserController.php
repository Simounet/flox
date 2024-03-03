<?php

  namespace App\Http\Controllers;

  use App\Services\Models\UserService;
  use Illuminate\Http\RedirectResponse;
  use Illuminate\Support\Facades\Auth;
  use Illuminate\Support\Facades\Request;
  use Symfony\Component\HttpFoundation\Response;

  class UserController {

    private UserService $userService;

    public function __construct(UserService $userService)
    {
      $this->userService = $userService;
    }

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

      Request::validate([
        'password' => 'required|min:' . UserService::PASSWORD_MIN_LENGTH
      ]);

      $password = (string) Request::input('password');
      $this->userService->changePassword($password);

      return response('Success', Response::HTTP_OK);
    }

    public function logout(): RedirectResponse
    {
      Auth::logout();
      Request::session()->invalidate();
      Request::session()->regenerateToken();

      return redirect('/');
    }
  }
