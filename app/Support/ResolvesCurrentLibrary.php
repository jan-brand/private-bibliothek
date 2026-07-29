<?php

namespace App\Support;

use App\Models\Library;

trait ResolvesCurrentLibrary
{
    protected function currentLibrary(): Library
    {
        return app(CurrentLibrary::class)->get();
    }
}
