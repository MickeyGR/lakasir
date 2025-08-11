<?php

namespace App\Filament\Tenant\Resources\ProformaResource\Pages;

use App\Filament\Tenant\Resources\ProformaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProformas extends ListRecords
{
    protected static string $resource = ProformaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
