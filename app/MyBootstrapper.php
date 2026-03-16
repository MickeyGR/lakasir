<?php

namespace App;

use Illuminate\Support\Facades\Request;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

class MyBootstrapper implements TenancyBootstrapper
{
    public function bootstrap(Tenant $tenant)
    {
        if (! Request::instance()) {
            return;
        }

        $baseUrl = Request::getSchemeAndHttpHost();

        config([
            'app.url' => $baseUrl,
            'filesystems.disks.public.url' => "{$baseUrl}/storage",
            'filesystems.disks.tmp.url' => "{$baseUrl}/tmp",
        ]);
    }

    public function revert()
    {
        config([
            'app.url' => env('APP_URL', 'http://localhost'),
            'filesystems.disks.public.url' => env('APP_URL', 'http://localhost').'/storage',
            'filesystems.disks.tmp.url' => env('APP_URL', 'http://localhost').'/tmp',
        ]);
    }
}
