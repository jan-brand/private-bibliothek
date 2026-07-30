<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE media
             ADD COLUMN search_vector tsvector
             NOT NULL DEFAULT ''::tsvector",
        );

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION minibib_update_media_search_vector()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    NEW.search_vector :=
        setweight(to_tsvector('german', coalesce(NEW.title, '')), 'A')
        || setweight(to_tsvector('german', coalesce(NEW.subtitle, '')), 'B')
        || setweight(to_tsvector('german', coalesce(NEW.creators, '')), 'B')
        || setweight(to_tsvector('german', coalesce(NEW.publisher, '')), 'C')
        || setweight(to_tsvector('german', coalesce(NEW.publication_place, '')), 'C')
        || setweight(to_tsvector('german', coalesce(NEW.edition, '')), 'C')
        || setweight(to_tsvector('german', coalesce(NEW.description, '')), 'D')
        || setweight(to_tsvector('simple', coalesce(NEW.publication_year::text, '')), 'D');

    RETURN NEW;
END;
$$
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER media_search_vector_update
BEFORE INSERT OR UPDATE OF
    title,
    subtitle,
    creators,
    publisher,
    publication_place,
    publication_year,
    edition,
    description
ON media
FOR EACH ROW
EXECUTE FUNCTION minibib_update_media_search_vector()
SQL);

        DB::statement(
            "UPDATE media
             SET search_vector =
                setweight(to_tsvector('german', coalesce(title, '')), 'A')
                || setweight(to_tsvector('german', coalesce(subtitle, '')), 'B')
                || setweight(to_tsvector('german', coalesce(creators, '')), 'B')
                || setweight(to_tsvector('german', coalesce(publisher, '')), 'C')
                || setweight(to_tsvector('german', coalesce(publication_place, '')), 'C')
                || setweight(to_tsvector('german', coalesce(edition, '')), 'C')
                || setweight(to_tsvector('german', coalesce(description, '')), 'D')
                || setweight(to_tsvector('simple', coalesce(publication_year::text, '')), 'D')",
        );

        DB::statement(
            'CREATE INDEX media_search_vector_gin_index
             ON media USING GIN (search_vector)',
        );

        DB::statement(
            'CREATE INDEX media_library_visibility_type_index
             ON media (library_id, visibility, type)',
        );

        DB::statement(
            'CREATE INDEX media_library_publication_year_index
             ON media (library_id, publication_year)',
        );
    }

    public function down(): void
    {
        DB::statement(
            'DROP INDEX IF EXISTS media_library_publication_year_index',
        );
        DB::statement(
            'DROP INDEX IF EXISTS media_library_visibility_type_index',
        );
        DB::statement(
            'DROP INDEX IF EXISTS media_search_vector_gin_index',
        );
        DB::statement(
            'DROP TRIGGER IF EXISTS media_search_vector_update ON media',
        );
        DB::statement(
            'DROP FUNCTION IF EXISTS minibib_update_media_search_vector()',
        );
        DB::statement(
            'ALTER TABLE media DROP COLUMN IF EXISTS search_vector',
        );
    }
};
