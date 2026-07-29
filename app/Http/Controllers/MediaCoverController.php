<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Services\Covers\CoverStorageService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaCoverController extends Controller
{
    public function __invoke(
        Media $media,
        CoverStorageService $covers,
    ): BinaryFileResponse {
        Gate::authorize('view', $media);

        $absolutePath = $covers->absolutePath($media);

        abort_if($absolutePath === null, 404);

        $mimeType = trim(
            (string) $media->getAttribute('cover_mime_type'),
        );

        return response()->file(
            $absolutePath,
            [
                'Content-Type' => $mimeType !== ''
                    ? $mimeType
                    : 'application/octet-stream',
                'Cache-Control' => 'private, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
