<?php

namespace App\Filament\Tenant\Resources\ProformaResource\Pages;

use App\Filament\Tenant\Resources\ProformaResource;
use App\Services\Tenants\ProformaService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateProforma extends CreateRecord
{
    protected static string $resource = ProformaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Agregar el usuario actual
        $data['user_id'] = Filament::auth()->id();

        // Usar el ProformaService para calcular los totales
        $proformaService = new ProformaService();
        $calculatedData = $proformaService->calculateTotals($data);

        return array_merge($data, $calculatedData);
    }

    protected function afterCreate(): void
    {
        // Recalcular totales después de crear los detalles
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
