<?php

  namespace App\Http\Controllers;

  use App\Enums\StatusEnum;
  use App\Services\Api\Plex;
  use Illuminate\Http\Response;

  class ApiController {

    /**
     * @var Plex
     */
    private $plex;

    public function __construct(Plex $plex)
    {
      $this->plex = $plex;
    }

    public function plex(): Response
    {
      $payload = json_decode(request('payload'), true);

      $result = $this->plex->handle($payload);

      if($result === StatusEnum::NOT_FOUND) {
        return response('Not Found', 404);
      }

      if($result === StatusEnum::NOT_IMPLEMENTED) {
        return response(501, Response::HTTP_NOT_IMPLEMENTED);
      }

      if($result === StatusEnum::UNAUTHORIZED) {
        return response(501, Response::HTTP_UNAUTHORIZED);
      }

      return response('Ok', 200);
    }
  }
