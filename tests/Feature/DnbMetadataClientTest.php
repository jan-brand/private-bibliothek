<?php

namespace Tests\Feature;

use App\Services\Metadata\DnbMetadataClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DnbMetadataClientTest extends TestCase
{
    public function test_it_fetches_and_parses_dnb_metadata_by_isbn(): void
    {
        Http::fake([
            'services.dnb.de/sru/dnb*' => Http::response(
                $this->marcResponse(),
                200,
                ['Content-Type' => 'application/xml'],
            ),
        ]);

        $result = app(DnbMetadataClient::class)
            ->searchByIsbn('978-3-16-148410-0');

        $this->assertNotNull($result);
        $this->assertSame('dnb', $result->source);
        $this->assertSame('Die Testpublikation', $result->title);
        $this->assertSame('Ein Untertitel', $result->subtitle);
        $this->assertSame(['Beispiel, Erika'], $result->creators);
        $this->assertSame('Testverlag', $result->publisher);
        $this->assertSame(2026, $result->publicationYear);
        $this->assertSame(
            '978-3-16-148410-0',
            $result->identifiers['isbn'],
        );

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), 'services.dnb.de/sru/dnb')
                && $request['query'] === 'isbn=9783161484100'
                && $request['recordSchema'] === 'MARC21-xml';
        });
    }

    private function marcResponse(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<searchRetrieveResponse xmlns="http://www.loc.gov/zing/srw/">
  <numberOfRecords>1</numberOfRecords>
  <records>
    <record>
      <recordData>
        <record xmlns="http://www.loc.gov/MARC21/slim">
          <controlfield tag="001">123456789</controlfield>
          <datafield tag="020" ind1=" " ind2=" ">
            <subfield code="a">978-3-16-148410-0</subfield>
          </datafield>
          <datafield tag="100" ind1="1" ind2=" ">
            <subfield code="a">Beispiel, Erika</subfield>
          </datafield>
          <datafield tag="245" ind1="1" ind2="0">
            <subfield code="a">Die Testpublikation :</subfield>
            <subfield code="b">Ein Untertitel /</subfield>
          </datafield>
          <datafield tag="250" ind1=" " ind2=" ">
            <subfield code="a">2. Auflage</subfield>
          </datafield>
          <datafield tag="264" ind1=" " ind2="1">
            <subfield code="a">Berlin :</subfield>
            <subfield code="b">Testverlag,</subfield>
            <subfield code="c">2026.</subfield>
          </datafield>
          <datafield tag="041" ind1=" " ind2=" ">
            <subfield code="a">ger</subfield>
          </datafield>
        </record>
      </recordData>
    </record>
  </records>
</searchRetrieveResponse>
XML;
    }
}
