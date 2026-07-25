<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Login extends Component
{
    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();

        $email = Str::lower(trim($this->email));
        $rateLimitKey = Str::transliterate($email.'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            $this->addError(
                'email',
                "Zu viele Anmeldeversuche. Bitte in {$seconds} Sekunden erneut versuchen.",
            );

            return;
        }

        if (! Auth::attempt([
            'email' => $email,
            'password' => $this->password,
            'is_active' => true,
        ], $this->remember)) {
            RateLimiter::hit($rateLimitKey, 60);

            $this->addError('email', 'Die Anmeldedaten sind ungültig oder das Konto ist deaktiviert.');

            return;
        }

        RateLimiter::clear($rateLimitKey);
        session()->regenerate();

        $user = Auth::user();

        if ($user instanceof User) {
            $user->forceFill([
                'last_login_at' => now(),
            ])->save();
        }

        $this->redirectIntended(route('dashboard'));
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
