<?php

namespace App\Support\Storage;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoredFileUrl
{
    public static function extractPublicPath(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $path = ltrim((string) Str::of($path)->after('/storage/'), '/');

        if ($path === '' || self::isTemporaryUploadPath($path)) {
            return null;
        }

        return $path;
    }

    public static function toCurrentPublicUrl(?string $url): ?string
    {
        $path = self::extractPublicPath($url);

        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public static function isTemporaryUploadPath(?string $path): bool
    {
        if (blank($path)) {
            return false;
        }

        $path = parse_url($path, PHP_URL_PATH) ?: $path;
        $candidate = basename($path);

        return Str::contains($candidate, '-meta') || Str::contains($path, '/tmp/') || Str::startsWith($path, ['tmp/', 'livewire-tmp/']);
    }
}
