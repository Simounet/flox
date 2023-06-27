<?php

namespace App\Services;

use App\Services\Storage;

class VueAppService
{
    public function view()
    {
        $language = (new Storage)->parseLanguage();
        return view('app')
            ->withLang($language);
    }
}
