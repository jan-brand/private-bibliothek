<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            UPDATE media_identifiers
            SET
                value = upper(regexp_replace(value, '[^A-Za-z0-9]', '', 'g')),
                normalized_value = upper(regexp_replace(value, '[^A-Za-z0-9]', '', 'g'))
            WHERE scheme = 'isbn'
        SQL);

        DB::statement(<<<'SQL'
            UPDATE copies
            SET barcode = upper(regexp_replace(barcode, '[^A-Za-z0-9]', '', 'g'))
            WHERE barcode IS NOT NULL
              AND (
                  upper(regexp_replace(barcode, '[^A-Za-z0-9]', '', 'g'))
                      ~ '^[0-9]{13}$'
                  OR upper(regexp_replace(barcode, '[^A-Za-z0-9]', '', 'g'))
                      ~ '^[0-9]{9}[0-9X]$'
              )
        SQL);
    }

    public function down(): void
    {
        // Die ursprüngliche Trennschreibweise ist nach der Normalisierung
        // nicht eindeutig rekonstruierbar.
    }
};
