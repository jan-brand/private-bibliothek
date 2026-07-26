<?php

namespace App\Services\Metadata;

use App\Models\MediaIdentifier;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ZdbMetadataClient
{
    public function __construct(
        private readonly Marc21MetadataParser $parser,
    ) {}

    public function searchByIssn(string $issn): ?MetadataResult
    {
        return $this->search(
            'iss='.MediaIdentifier::normalize($issn),
        );
    }

    public function searchByZdbId(string $zdbId): ?MetadataResult
    {
        return $this->search(
            'dnb.zdbid='.trim($zdbId),
        );
    }

    private function search(string $query): ?MetadataResult
    {
        $response = $this->request()->get(
            (string) config('metadata.zdb_sru_url'),
            [
                'version' => '1.1',
                'operation' => 'searchRetrieve',
                'query' => $query,
                'recordSchema' => 'MARC21-xml',
                'maximumRecords' => 1,
            ],
        );

        $response->throw();

        return $this->parser->parse($response->body(), 'zdb');
    }

    private function request(): PendingRequest
    {
        return Http::accept('application/xml')
            ->withUserAgent(config('app.name').'/1.0')
            ->timeout((int) config('metadata.timeout_seconds', 12))
            ->retry(2, 250);
    }
}
