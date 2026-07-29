<?php

namespace App\Support;

use App\Models\Library;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CurrentLibrary
{
    public function get(): Library
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $library = Library::query()
            ->accessibleTo($user)
            ->orderByRaw("CASE WHEN slug = 'shared' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();

        abort_if(
            $library === null,
            404,
            'Keine zugängliche Bibliothek vorhanden.',
        );

        Gate::authorize('view', $library);

        return $library;
    }
}
