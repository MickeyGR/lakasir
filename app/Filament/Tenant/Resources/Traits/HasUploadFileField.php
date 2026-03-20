<?php

namespace App\Filament\Tenant\Resources\Traits;

use App\Support\Filament\FileUploadPreviewResolver;
use Filament\Forms\Components\BaseFileUpload;

trait HasUploadFileField
{
    private function getUploadedFileUsing(BaseFileUpload $component, string $file, string|array|null $storedFileNames)
    {
        return FileUploadPreviewResolver::resolve($component, $file, $storedFileNames);
    }
}
