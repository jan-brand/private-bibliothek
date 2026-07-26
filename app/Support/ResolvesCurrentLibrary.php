<?php

namespace App\Support;

use App\Models\Library;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

trait ResolvesCurrentLibrary
{
    protected function currentLibrary(): Library
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $library = Library::query()
            ->accessibleTo($user)
            ->find(session('current_library_id'));

        if ($library === null) {
            $library = Library::query()
                ->accessibleTo($user)
                ->orderBy('type')
                ->orderBy('name')
                ->first();
        }

        abort_if($library === null, 404, 'Keine zugängliche Bibliothek vorhanden.');

        Gate::authorize('view', $library);

        session(['current_library_id' => $library->getKey()]);

        return $library;
    }
}
