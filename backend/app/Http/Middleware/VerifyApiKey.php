<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{

  /**
   * @var User
   */
  private $user;

  public function __construct(User $user)
  {
    $this->user = $user;
  }

  /**
   * Handle an incoming request.
   *
   * @param \Illuminate\Http\Request $request
   * @param \Closure $next
   * @return mixed
   */
  public function handle($request, Closure $next)
  {
    if (!$request->token) {
      return response(['message' => 'No token provided'], Response::HTTP_UNAUTHORIZED);
    }

    $user = $this->user->findByApiKey($request->token)->first();
    if (!$user) {
      return response(['message' => 'No valid token provided'], Response::HTTP_UNAUTHORIZED);
    }

    Auth::login($user);

    return $next($request);
  }
}
