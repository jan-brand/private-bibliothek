<?php

namespace Tests\Feature;

use App\Services\Metadata\ZdbMetadataClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZdbMetadataClientTest extends TestCase
{
    public function test_it_fetches_and_parses_zdb_metadata_by_issn(): void
    {
        Http::fake([
            'services.dnb.de/sru/zdb*' => Http::response(
                $this->marcResponse(),
                200,
                ['Content-Type' => 'application/xml'],
            ),
        ]);

        $result = app(ZdbMetadataClient::class)
            ->searchByIssn('0028-0836');

        $this->assertNotNull($result);
        $this->assertSame('zdb', $result->source);
        $this->assertSame('Testzeitschrift', $result->title);
        $this->assertSame('0028-0836', $result->identifiers['issn']);
        $this->assertSame('ZDB-1234567-8', $result->identifiers['zdb']);

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), 'services.dnb.de/sru/zdb')
                && $request['query'] === 'iss=00280836'
                && $request['maximumRecords'] === 1;
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
          <controlfield tag="001">987654321</controlfield>
          <datafield tag="016" ind1="7" ind2=" ">
            <subfield code="a">ZDB-1234567-8</subfield>
            <subfield code="2">zdb</subfield>
          </datafield>
          <datafield tag="022" ind1=" " ind2=" ">
            <subfield code="a">0028-0836</subfield>
          </datafield>
          <datafield tag="245" ind1="0" ind2="0">
            <subfield code="a">Testzeitschrift /</subfield>
          </datafield>
          <datafield tag="264" ind1=" " ind2="1">
            <subfield code="a">Hamburg :</subfield>
            <subfield code="b">Beispielverlag,</subfield>
            <subfield code="c">2020-</subfield>
          </datafield>
        </record>
      </recordData>
    </record>
  </records>
</searchRetrieveResponse>
XML;
    }
}
