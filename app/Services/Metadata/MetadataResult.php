<?php

namespace App\Services\Metadata;

final readonly class MetadataResult
{
    /**
     * @param  list<string>  $creators
     * @param  array<string, string>  $identifiers
     */
    public function __construct(
        public string $source,
        public ?string $sourceRecordId,
        public string $title,
        public ?string $subtitle,
        public array $creators,
        public ?string $publisher,
        public ?string $publicationPlace,
        public ?int $publicationYear,
        public ?string $edition,
        public ?string $languageCode,
        public ?string $description,
        public array $identifiers,
    ) {}

    /**
     * @return array{
     *     source: string,
     *     source_record_id: string|null,
     *     title: string,
     *     subtitle: string|null,
     *     creators: list<string>,
     *     publisher: string|null,
     *     publication_place: string|null,
     *     publication_year: int|null,
     *     edition: string|null,
     *     language_code: string|null,
     *     description: string|null,
     *     identifiers: array<string, string>
     * }
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'source_record_id' => $this->sourceRecordId,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'creators' => $this->creators,
            'publisher' => $this->publisher,
            'publication_place' => $this->publicationPlace,
            'publication_year' => $this->publicationYear,
            'edition' => $this->edition,
            'language_code' => $this->languageCode,
            'description' => $this->description,
            'identifiers' => $this->identifiers,
        ];
    }
}
