<?php

namespace App\Filament\Tenant\Resources\ProformaResource\Pages;

use App\Filament\Tenant\Resources\ProformaResource;
use App\Services\Tenants\ProformaService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditProforma extends EditRecord
{
    protected static string $resource = ProformaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Usar el ProformaService para calcular los totales
        $proformaService = new ProformaService();
        $calculatedData = $proformaService->calculateTotals($data);

        return array_merge($data, $calculatedData);
    }

    protected function afterSave(): void
    {
        // Recalcular totales después de actualizar los detalles
        $proforma = $this->record;
        $proformaService = new ProformaService();

        $data = [
            'details' => $proforma->details->toArray(),
            'tax' => $proforma->tax,
            'discount_price' => $proforma->discount_price,
        ];

        $calculatedData = $proformaService->calculateTotals($data);
        $proforma->update($calculatedData);
    }
}
