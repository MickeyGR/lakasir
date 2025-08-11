<?php

namespace App\Filament\Tenant\Resources\ProformaResource\Pages;

use App\Filament\Tenant\Resources\ProformaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProforma extends EditRecord
{
    protected static string $resource = ProformaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
