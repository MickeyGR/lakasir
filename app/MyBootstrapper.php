<?php

namespace App;

use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

class MyBootstrapper implements TenancyBootstrapper
{
    public function bootstrap(Tenant $tenant)
    {
        if (! Request::instance()) {
            return;
        }

        $baseUrl = $this->resolveBaseUrl(Request::instance());

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

    private function resolveBaseUrl(HttpRequest $request): string
    {
        foreach ([
            $request->headers->get('origin'),
            $request->headers->get('referer'),
        ] as $headerUrl) {
            $url = $this->normalizeAbsoluteUrl($headerUrl);

            if ($url !== null) {
                return $url;
            }
        }

        $forwardedHost = $this->firstHeaderValue($request->headers->get('x-forwarded-host'));
        $forwardedProto = $this->firstHeaderValue($request->headers->get('x-forwarded-proto'));

        $host = $forwardedHost ?: $request->getHost();
        $scheme = $forwardedProto ?: $request->getScheme();

        return "{$scheme}://{$host}";
    }

    private function normalizeAbsoluteUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);

        if (blank($scheme) || blank($host)) {
            return null;
        }

        return Str::finish("{$scheme}://{$host}".($port ? ":{$port}" : ''), '');
    }

    private function firstHeaderValue(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Str::of($value)
            ->before(',')
            ->trim()
            ->value();
    }
}
