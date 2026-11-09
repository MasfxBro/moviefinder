<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\AdminUserPolicy;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => AdminUserPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
