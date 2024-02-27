<?php

  namespace App\Http\Controllers;

  use App\Services\FileParser;
  use GuzzleHttp\Exception\ConnectException;
  use Illuminate\Http\JsonResponse;
  use Illuminate\Support\Facades\Auth;
  use Symfony\Component\HttpFoundation\Request;
  use Symfony\Component\HttpFoundation\Response;

  class FileParserController {

    private $parser;

    public function __construct(FileParser $parser)
    {
      increaseTimeLimit();

      $this->parser = $parser;
    }

    /**
     * Call flox-file-parser.
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function call()
    {
      try {
        $this->parser->fetch();
      } catch(ConnectException $e) {
        return response("Can't connect to file-parser. Make sure the server is running.", Response::HTTP_NOT_FOUND);
      } catch(\Exception $e) {
        return response("Error in file-parser:" . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
      }
    }

    /**
     * Will be called from flox-file-parser itself.
     */
    public function receive(Request $request): JsonResponse
    {
      logInfo("FileParserController.receive called");

      $user = Auth::user();

      if(!$user) {
        throw new \Exception('User should be logged');
      }

      $content = json_decode($request->getContent());
      if(!$content) {
        return response()->json('', Response::HTTP_NO_CONTENT);
      }

      try {
        return $this->parser->updateDatabase($user->id, $content);
      } catch(\Exception $e) {
        return response()->json($e->getMessage(), Response::HTTP_UNAUTHORIZED);
      }
    }

    /**
     * @return mixed
     */
    public function lastFetched()
    {
      return $this->parser->lastFetched();
    }
  }
