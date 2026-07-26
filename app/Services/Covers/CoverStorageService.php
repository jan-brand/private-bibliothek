<?php

namespace App\Services\Covers;

use App\Models\Media;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CoverStorageService
{
    private const MAX_REMOTE_BYTES = 8_388_608;

    /**
     * @var array<string, string>
     */
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function storeUpload(Media $media, UploadedFile $file): string
    {
        $mimeType = $file->getMimeType() ?: $file->getClientMimeType();

        if (! array_key_exists($mimeType, self::EXTENSIONS)) {
            throw new RuntimeException('Das hochgeladene Coverformat wird nicht unterstützt.');
        }

        $path = $this->pathFor($media, self::EXTENSIONS[$mimeType]);

        $stored = Storage::disk('public')->putFileAs(
            dirname($path),
            $file,
            basename($path),
        );

        if ($stored === false) {
            throw new RuntimeException('Das Cover konnte nicht gespeichert werden.');
        }

        $this->replaceLocalCover($media, $path, $mimeType, null);

        return $path;
    }

    public function importRemote(Media $media, string $url): string
    {
        $response = $this->request()->get($url);
        $response->throw();

        $body = $response->body();

        if ($body === '' || strlen($body) > self::MAX_REMOTE_BYTES) {
            throw new RuntimeException('Die entfernte Coverdatei ist leer oder zu groß.');
        }

        $mimeType = strtolower(trim(
            explode(';', $response->header('Content-Type'))[0],
        ));

        if (! array_key_exists($mimeType, self::EXTENSIONS)) {
            throw new RuntimeException('Die entfernte Adresse liefert kein unterstütztes Bildformat.');
        }

        $path = $this->pathFor($media, self::EXTENSIONS[$mimeType]);

        if (! Storage::disk('public')->put($path, $body)) {
            throw new RuntimeException('Das Cover konnte nicht gespeichert werden.');
        }

        $this->replaceLocalCover($media, $path, $mimeType, $url);

        return $path;
    }

    public function removeLocal(Media $media): void
    {
        $oldPath = $this->coverPath($media);

        $media->forceFill([
            'cover_path' => null,
            'cover_mime_type' => null,
            'cover_source_url' => null,
            'cover_updated_at' => null,
        ])->save();

        if ($oldPath !== null) {
            Storage::disk('public')->delete($oldPath);
        }
    }

    public function url(Media $media): ?string
    {
        $path = $this->coverPath($media);

        if ($path !== null && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        $remoteUrl = trim((string) $media->getAttribute('cover_url'));

        return $remoteUrl === '' ? null : $remoteUrl;
    }

    private function request(): PendingRequest
    {
        return Http::accept('image/*')
            ->withUserAgent(config('app.name').'/1.0')
            ->timeout(15)
            ->retry(2, 250);
    }

    private function pathFor(Media $media, string $extension): string
    {
        return sprintf(
            'covers/%s/%s/%s.%s',
            $media->library_id,
            $media->getKey(),
            Str::uuid()->toString(),
            $extension,
        );
    }

    private function replaceLocalCover(
        Media $media,
        string $path,
        string $mimeType,
        ?string $sourceUrl,
    ): void {
        $oldPath = $this->coverPath($media);

        $media->forceFill([
            'cover_path' => $path,
            'cover_mime_type' => $mimeType,
            'cover_source_url' => $sourceUrl,
            'cover_updated_at' => now(),
        ])->save();

        if ($oldPath !== null && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }
    }

    private function coverPath(Media $media): ?string
    {
        $path = trim((string) $media->getAttribute('cover_path'));

        return $path === '' ? null : $path;
    }
}
