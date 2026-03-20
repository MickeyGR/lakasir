<?php

namespace App\Support\Filament;

use Filament\Forms\Components\BaseFileUpload;
use League\Flysystem\UnableToCheckFileExistence;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FileUploadPreviewResolver
{
    public static function resolve(BaseFileUpload $component, string $file, string|array|null $storedFileNames): ?array
    {
        if ($temporaryFileName = self::extractTemporaryUploadFileName($file)) {
            return self::resolveTemporaryUpload($temporaryFileName);
        }

        /** @var \Illuminate\Contracts\Filesystem\Filesystem $storage */
        $storage = $component->getDisk();
        $shouldFetchFileInformation = $component->shouldFetchFileInformation();
        $path = parse_url($file, PHP_URL_PATH) ?: $file;
        $relativePath = ltrim((string) str($path)->after('/storage/')->after('/tmp/'), '/');

        if ($relativePath === '') {
            $relativePath = ltrim((string) str($path)->remove('/storage')->remove('/tmp'), '/');
        }

        if ($shouldFetchFileInformation) {
            try {
                if (! $storage->exists($relativePath)) {
                    return null;
                }
            } catch (UnableToCheckFileExistence) {
                return null;
            }
        }

        return [
            'name' => $relativePath,
            'size' => $shouldFetchFileInformation ? $storage->size($relativePath) : 0,
            'type' => $shouldFetchFileInformation ? $storage->mimeType($relativePath) : null,
            'url' => '/storage/'.$relativePath,
        ];
    }

    private static function extractTemporaryUploadFileName(string $file): ?string
    {
        if ((string) str($file)->startsWith('livewire-file:')) {
            return (string) str($file)->after('livewire-file:');
        }

        $path = parse_url($file, PHP_URL_PATH) ?: $file;
        $candidate = basename($path);

        if (! str($candidate)->contains('-meta')) {
            return null;
        }

        return $candidate;
    }

    private static function resolveTemporaryUpload(string $file): ?array
    {
        try {
            $temporaryFile = TemporaryUploadedFile::createFromLivewire($file);

            if (! $temporaryFile->exists()) {
                return null;
            }

            return [
                'name' => $temporaryFile->getClientOriginalName(),
                'size' => $temporaryFile->getSize(),
                'type' => $temporaryFile->getMimeType(),
                'url' => $temporaryFile->temporaryUrl(),
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
