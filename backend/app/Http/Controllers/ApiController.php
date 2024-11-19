<?php

  namespace App\Http\Controllers;

  use App\Enums\StatusEnum;
  use App\Services\Api\Kodi;
  use App\Services\Api\Plex;
  use Illuminate\Http\Request;
  use Illuminate\Http\Response;

  class ApiController {

    public function __construct(
      private Kodi $kodi,
      private Plex $plex
    ) {}

    public function kodi(Request $request): Response
    {
      $payload = $request->all();

      $result = $this->kodi->handle($payload);

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

    public function plex(Request $request): Response
    {
      $inputPayload = $request->input('payload');
      $payload = json_decode($inputPayload, true);

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
