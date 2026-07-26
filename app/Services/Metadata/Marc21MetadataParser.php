<?php

namespace App\Services\Metadata;

use DOMDocument;
use DOMNode;
use DOMXPath;
use RuntimeException;

class Marc21MetadataParser
{
    public function parse(string $xml, string $source): ?MetadataResult
    {
        $document = new DOMDocument;

        $previous = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadXML(
                $xml,
                LIBXML_NONET | LIBXML_NOBLANKS,
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (! $loaded) {
            throw new RuntimeException('Die Metadatenantwort enthält kein gültiges XML.');
        }

        $xpath = new DOMXPath($document);
        $record = $this->firstNode(
            $xpath,
            '//*[local-name()="recordData"]/*[local-name()="record"][1]',
        );

        if ($record === null) {
            $record = $this->firstNode(
                $xpath,
                '/*[local-name()="record"][1]',
            );
        }

        if ($record === null) {
            return null;
        }

        $title = $this->firstValue(
            $xpath,
            $record,
            './*[local-name()="datafield" and @tag="245"]/*[local-name()="subfield" and @code="a"]',
        );

        if ($title === null) {
            return null;
        }

        $publicationField = $this->firstNode(
            $xpath,
            './*[local-name()="datafield" and @tag="264" and @ind2="1"][1]',
            $record,
        ) ?? $this->firstNode(
            $xpath,
            './*[local-name()="datafield" and @tag="260"][1]',
            $record,
        );

        $identifiers = array_filter([
            'isbn' => $this->identifierValue(
                $this->firstValue(
                    $xpath,
                    $record,
                    './*[local-name()="datafield" and @tag="020"]/*[local-name()="subfield" and @code="a"]',
                ),
            ),
            'issn' => $this->identifierValue(
                $this->firstValue(
                    $xpath,
                    $record,
                    './*[local-name()="datafield" and @tag="022"]/*[local-name()="subfield" and @code="a"]',
                ),
            ),
            'zdb' => $source === 'zdb'
                ? $this->firstValue(
                    $xpath,
                    $record,
                    './*[local-name()="datafield" and @tag="016"]/*[local-name()="subfield" and @code="a"]',
                )
                : null,
        ], static fn (?string $value): bool => $value !== null);

        return new MetadataResult(
            source: $source,
            sourceRecordId: $this->firstValue(
                $xpath,
                $record,
                './*[local-name()="controlfield" and @tag="001"]',
                cleanIsbdPunctuation: false,
            ),
            title: $this->cleanIsbdPunctuation($title),
            subtitle: $this->cleanOptional(
                $this->firstValue(
                    $xpath,
                    $record,
                    './*[local-name()="datafield" and @tag="245"]/*[local-name()="subfield" and @code="b"]',
                ),
            ),
            creators: $this->creatorValues($xpath, $record),
            publisher: $this->cleanOptional(
                $publicationField === null
                    ? null
                    : $this->firstValue(
                        $xpath,
                        $publicationField,
                        './*[local-name()="subfield" and @code="b"]',
                    ),
            ),
            publicationPlace: $this->cleanOptional(
                $publicationField === null
                    ? null
                    : $this->firstValue(
                        $xpath,
                        $publicationField,
                        './*[local-name()="subfield" and @code="a"]',
                    ),
            ),
            publicationYear: $this->extractYear(
                $publicationField === null
                    ? null
                    : $this->firstValue(
                        $xpath,
                        $publicationField,
                        './*[local-name()="subfield" and @code="c"]',
                        cleanIsbdPunctuation: false,
                    ),
            ),
            edition: $this->cleanOptional(
                $this->firstValue(
                    $xpath,
                    $record,
                    './*[local-name()="datafield" and @tag="250"]/*[local-name()="subfield" and @code="a"]',
                ),
            ),
            languageCode: $this->cleanOptional(
                $this->firstValue(
                    $xpath,
                    $record,
                    './*[local-name()="datafield" and @tag="041"]/*[local-name()="subfield" and @code="a"]',
                    cleanIsbdPunctuation: false,
                ),
            ),
            description: $this->cleanOptional(
                $this->firstValue(
                    $xpath,
                    $record,
                    './*[local-name()="datafield" and @tag="520"]/*[local-name()="subfield" and @code="a"]',
                ),
            ),
            identifiers: $identifiers,
        );
    }

    /**
     * @return list<string>
     */
    private function creatorValues(DOMXPath $xpath, DOMNode $record): array
    {
        $nodes = $xpath->query(
            './*[local-name()="datafield" and (@tag="100" or @tag="110" or @tag="111" or @tag="700" or @tag="710" or @tag="711")]/*[local-name()="subfield" and @code="a"]',
            $record,
        );

        if ($nodes === false) {
            return [];
        }

        $creators = [];

        foreach ($nodes as $node) {
            $value = $this->cleanIsbdPunctuation($node->textContent);

            if ($value !== '') {
                $creators[] = $value;
            }
        }

        return array_values(array_unique($creators));
    }

    private function firstNode(
        DOMXPath $xpath,
        string $expression,
        ?DOMNode $context = null,
    ): ?DOMNode {
        $nodes = $xpath->query($expression, $context);

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        return $nodes->item(0);
    }

    private function firstValue(
        DOMXPath $xpath,
        DOMNode $context,
        string $expression,
        bool $cleanIsbdPunctuation = true,
    ): ?string {
        $node = $this->firstNode($xpath, $expression, $context);

        if ($node === null) {
            return null;
        }

        $value = trim($node->textContent);

        if ($cleanIsbdPunctuation) {
            $value = $this->cleanIsbdPunctuation($value);
        }

        return $value === '' ? null : $value;
    }

    private function cleanOptional(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = $this->cleanIsbdPunctuation($value);

        return $value === '' ? null : $value;
    }

    private function cleanIsbdPunctuation(string $value): string
    {
        return rtrim(trim($value), " \t\n\r\0\x0B/:;,");
    }

    private function identifierValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/\s+\(.+$/u', '', trim($value)) ?? trim($value);

        return $value === '' ? null : $value;
    }

    private function extractYear(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (preg_match('/\b(1[0-9]{3}|20[0-9]{2}|2100)\b/', $value, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }
}
