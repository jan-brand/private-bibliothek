<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->foreignId('owner_user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('visibility', 16)
                ->default('shared')
                ->index();
            $table->index(['owner_user_id', 'visibility']);
        });

        DB::transaction(function (): void {
            $this->assertNoCopyConflicts();

            $libraries = DB::table('libraries')
                ->orderBy('id')
                ->get(['id', 'slug', 'type', 'owner_user_id']);

            $canonical = $libraries->firstWhere('slug', 'shared')
                ?? $libraries->firstWhere('type', 'shared')
                ?? $libraries->first();

            if ($canonical === null) {
                $canonicalId = (int) DB::table('libraries')->insertGetId([
                    'name' => 'MiniBib',
                    'slug' => 'shared',
                    'type' => 'shared',
                    'owner_user_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $libraries = DB::table('libraries')
                    ->orderBy('id')
                    ->get(['id', 'slug', 'type', 'owner_user_id']);

                $canonical = $libraries->firstWhere('id', $canonicalId);
            }

            if ($canonical === null) {
                throw new RuntimeException(
                    'Die zentrale Bibliothek konnte nicht angelegt werden.',
                );
            }

            $canonicalId = (int) $canonical->id;

            $libraryMetadata = $libraries->keyBy(
                static fn (object $library): int => (int) $library->id,
            );

            DB::table('media')
                ->orderBy('id')
                ->get(['id', 'library_id', 'created_by_user_id'])
                ->each(function (object $media) use ($canonicalId, $libraryMetadata): void {
                    $sourceLibrary = $libraryMetadata->get((int) $media->library_id);
                    $wasPrivate = $sourceLibrary !== null
                        && (string) $sourceLibrary->type === 'private';

                    $ownerUserId = $wasPrivate
                        && $sourceLibrary->owner_user_id !== null
                            ? (int) $sourceLibrary->owner_user_id
                            : (int) $media->created_by_user_id;

                    DB::table('media')
                        ->where('id', $media->id)
                        ->update([
                            'library_id' => $canonicalId,
                            'owner_user_id' => $ownerUserId,
                            'visibility' => $wasPrivate ? 'private' : 'shared',
                            'updated_at' => now(),
                        ]);
                });

            DB::table('media_lists')
                ->orderBy('id')
                ->get(['id', 'library_id', 'visibility'])
                ->each(function (object $mediaList) use ($canonicalId, $libraryMetadata): void {
                    $sourceLibrary = $libraryMetadata->get((int) $mediaList->library_id);
                    $wasPrivateLibrary = $sourceLibrary !== null
                        && (string) $sourceLibrary->type === 'private';

                    DB::table('media_lists')
                        ->where('id', $mediaList->id)
                        ->update([
                            'library_id' => $canonicalId,
                            'visibility' => $wasPrivateLibrary
                                ? 'private'
                                : (string) $mediaList->visibility,
                            'updated_at' => now(),
                        ]);
                });

            DB::table('copies')->update([
                'library_id' => $canonicalId,
                'updated_at' => now(),
            ]);

            DB::table('locations')->update([
                'library_id' => $canonicalId,
                'updated_at' => now(),
            ]);

            $existingRoles = DB::table('library_memberships')
                ->where('library_id', $canonicalId)
                ->pluck('role', 'user_id');

            DB::table('library_memberships')
                ->where('library_id', '!=', $canonicalId)
                ->delete();

            DB::table('users')
                ->orderBy('id')
                ->get(['id', 'is_admin'])
                ->each(function (object $user) use ($canonicalId, $existingRoles): void {
                    $role = $existingRoles->get((int) $user->id);

                    if (! is_string($role) || $role === '') {
                        $role = (bool) $user->is_admin ? 'admin' : 'member';
                    }

                    DB::table('library_memberships')->updateOrInsert(
                        [
                            'library_id' => $canonicalId,
                            'user_id' => (int) $user->id,
                        ],
                        [
                            'role' => $role,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    );
                });

            DB::table('libraries')
                ->where('id', '!=', $canonicalId)
                ->delete();

            DB::table('libraries')
                ->where('id', $canonicalId)
                ->update([
                    'name' => 'MiniBib',
                    'slug' => 'shared',
                    'type' => 'shared',
                    'owner_user_id' => null,
                    'updated_at' => now(),
                ]);
        });

        DB::statement(
            'ALTER TABLE media ALTER COLUMN owner_user_id SET NOT NULL',
        );
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->dropIndex(['owner_user_id', 'visibility']);
            $table->dropColumn('visibility');
            $table->dropConstrainedForeignId('owner_user_id');
        });
    }

    private function assertNoCopyConflicts(): void
    {
        $inventoryConflicts = DB::table('copies')
            ->select('inventory_code')
            ->whereNotNull('inventory_code')
            ->where('inventory_code', '!=', '')
            ->groupBy('inventory_code')
            ->havingRaw('COUNT(*) > 1')
            ->limit(5)
            ->pluck('inventory_code');

        if ($inventoryConflicts->isNotEmpty()) {
            throw new RuntimeException(
                'Die Bibliotheken können nicht zusammengeführt werden, weil Inventarnummern mehrfach vorkommen: '
                .$inventoryConflicts->implode(', '),
            );
        }

        $barcodeConflicts = DB::table('copies')
            ->select('barcode')
            ->whereNotNull('barcode')
            ->where('barcode', '!=', '')
            ->groupBy('barcode')
            ->havingRaw('COUNT(*) > 1')
            ->limit(5)
            ->pluck('barcode');

        if ($barcodeConflicts->isNotEmpty()) {
            throw new RuntimeException(
                'Die Bibliotheken können nicht zusammengeführt werden, weil Barcodes mehrfach vorkommen: '
                .$barcodeConflicts->implode(', '),
            );
        }
    }
};
