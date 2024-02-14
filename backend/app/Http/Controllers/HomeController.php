<?php

  namespace App\Http\Controllers;

  use App\Services\VueAppService;

  class HomeController {

    public function app()
    {
        return (new VueAppService)->view();
    }
  }
