<?php

namespace App\Console\Commands;

use App\Models\Library;
use App\Models\LibraryMembership;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateUserCommand extends Command
{
    protected $signature = 'minibib:user:create
        {email? : E-Mail-Adresse}
        {--name= : Anzeigename}
        {--password= : Passwort mit mindestens 12 Zeichen}
        {--admin : Benutzer als Administrator anlegen}
        {--inactive : Benutzer deaktiviert anlegen}';

    protected $description = 'Legt einen MiniBib-Benutzer an und fügt ihn der gemeinsamen Bibliothek hinzu.';

    public function handle(): int
    {
        $email = Str::lower(trim((string) ($this->argument('email') ?: $this->ask('E-Mail-Adresse'))));
        $name = trim((string) ($this->option('name') ?: $this->ask('Name')));
        $password = (string) ($this->option('password') ?: $this->secret('Passwort (mindestens 12 Zeichen)'));

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $isAdmin = (bool) $this->option('admin');
        $isActive = ! (bool) $this->option('inactive');

        $user = DB::transaction(function () use ($email, $isActive, $isAdmin, $name, $password): User {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'email_verified_at' => now(),
                'password' => $password,
                'is_admin' => $isAdmin,
                'is_active' => $isActive,
            ]);

            $library = Library::query()->firstOrCreate(
                ['slug' => 'shared'],
                [
                    'name' => 'MiniBib',
                    'type' => Library::TYPE_SHARED,
                    'owner_user_id' => null,
                ],
            );

            LibraryMembership::query()->updateOrCreate(
                [
                    'library_id' => $library->getKey(),
                    'user_id' => $user->getKey(),
                ],
                [
                    'role' => $isAdmin
                        ? LibraryMembership::ROLE_ADMIN
                        : LibraryMembership::ROLE_MEMBER,
                ],
            );

            return $user;
        });

        $this->info("Benutzer {$user->email} wurde angelegt.");
        $this->line("Benutzer-ID: {$user->getKey()}");

        return self::SUCCESS;
    }
}
