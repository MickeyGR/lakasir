<?php

namespace App\Filament\Tenant\Resources\ProformaResource\Pages;

use App\Filament\Tenant\Resources\ProformaResource;
use App\Models\Tenants\About;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\RawJs;

class ViewProforma extends ViewRecord
{
    protected static string $resource = ProformaResource::class;

    protected static string $view = 'filament.tenant.resources.proforma-resource.pages.view-proforma';

    public ?About $about;

    public function mount($record): void
    {
        parent::mount($record);

        $this->about = About::first();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
            ->label('Imprimir Proforma')
            ->icon('heroicon-o-printer')
            ->color('warning')
            // Usamos Alpine.js para añadir un evento de clic directo al botón.
            // Esto es más robusto.
            ->extraAttributes([
                'x-on:click' => 'window.print()',
            ]),
        ];
    }
}