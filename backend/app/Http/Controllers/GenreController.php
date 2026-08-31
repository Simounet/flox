<?php

  declare(strict_types=1);

  namespace App\Http\Controllers;
  
  use App\Services\Models\GenreService;
  use Illuminate\Http\JsonResponse;

  class GenreController {
    
    private $genreService;

    public function __construct(GenreService $genreService)
    {
      $this->genreService = $genreService;
    }
    
    public function allGenres(): JsonResponse
    {
      return response()->json($this->genreService->all());
    }
  }
