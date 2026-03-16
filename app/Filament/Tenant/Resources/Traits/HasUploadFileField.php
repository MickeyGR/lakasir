<?php

namespace App\Filament\Tenant\Resources\Traits;

use Filament\Forms\Components\BaseFileUpload;
use League\Flysystem\UnableToCheckFileExistence;

trait HasUploadFileField
{
    private function getUploadedFileUsing(BaseFileUpload $component, string $file, string|array|null $storedFileNames)
    {
        /** @var Storage $storage */
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
}
