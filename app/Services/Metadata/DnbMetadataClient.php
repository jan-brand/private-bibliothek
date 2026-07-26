<?php

namespace App\Services\Metadata;

use App\Models\MediaIdentifier;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class DnbMetadataClient
{
    public function __construct(
        private readonly Marc21MetadataParser $parser,
    ) {}

    public function searchByIsbn(string $isbn): ?MetadataResult
    {
        $normalized = MediaIdentifier::normalize($isbn);

        $response = $this->request()->get(
            (string) config('metadata.dnb_sru_url'),
            [
                'version' => '1.1',
                'operation' => 'searchRetrieve',
                'query' => 'isbn='.$normalized,
                'recordSchema' => 'MARC21-xml',
                'maximumRecords' => 1,
            ],
        );

        $response->throw();

        return $this->parser->parse($response->body(), 'dnb');
    }

    private function request(): PendingRequest
    {
        return Http::accept('application/xml')
            ->withUserAgent(config('app.name').'/1.0')
            ->timeout((int) config('metadata.timeout_seconds', 12))
            ->retry(2, 250);
    }
}
