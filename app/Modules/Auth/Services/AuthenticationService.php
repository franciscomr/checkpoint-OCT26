<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\DTOs\CredentialsDTO;
use App\Modules\Auth\Enums\UserStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
class AuthenticationService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private readonly LoginThrottleService $throttle
    ) {
    }

    public function attempt(CredentialsDTO $credentials, string $ip): void
    {
        $this->throttle->ensureIsNotRateLimited($credentials->email, $ip);
        $authenticated = Auth::attempt([
            'email' => $credentials->email,
            'password' => $credentials->password,
            'status' => UserStatus::ACTIVE,
        ], $credentials->remember);

        if (!$authenticated) {
            $this->throttle->hit($credentials->email, $ip);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $this->throttle->clear($credentials->email, $ip);
    }
}
