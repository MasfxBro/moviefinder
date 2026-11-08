<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('admin.email');
        if ($email === '') {
            $this->command?->error('ADMIN_EMAIL must be configured.');

            return;
        }

        $user = User::firstOrNew(['email' => $email]);
        if ($user->exists) {
            $this->command?->error('Administrator already exists; credentials were not changed.');

            return;
        }

        $password = Str::random(48);
        $recoveryCodes = array_map(fn (): string => Str::upper(Str::random(12)), range(1, 8));
        $secret = app(Google2FA::class)->generateSecretKey();
        $user->name = 'Administrator';
        $user->role = Role::Admin;
        $user->email_verified_at ??= now();
        $user->password = $password;
        $user->admin_totp_secret = $secret;
        $user->admin_recovery_codes = array_map(Hash::make(...), $recoveryCodes);
        $user->save();

        $this->command?->info("Admin password (shown once): {$password}");
        $this->command?->info('TOTP setup URI: '.app(Google2FA::class)->getQRCodeUrl(config('app.name'), $email, $secret));
        $this->command?->info('Recovery codes (shown once): '.implode(' ', $recoveryCodes));
    }
}
