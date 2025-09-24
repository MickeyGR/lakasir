<?php

namespace App\Livewire;

// Importamos las clases necesarias
use Filament\Facades\Filament;
use Livewire\Component;
use Livewire\Attributes\On;

class UserDarkModeToggle extends Component
{
    /**
     * Este método se activa automáticamente cuando Filament dispara
     * el evento 'dark-mode-toggled' desde la interfaz.
     */
    #[On('dark-mode-toggled')]
    public function updateDarkMode($isDarkMode)
    {
        // Usamos Filament::auth()->user() que es seguro aquí porque este
        // código solo se ejecuta en respuesta a una acción del usuario en el navegador.
        /** @var \App\Models\Tenants\User $user */
        $user = Filament::auth()->user();

        // Actualizamos la preferencia del usuario en la base de datos.
        $user->update(['dark_mode_enabled' => $isDarkMode]);
    }

    /**
     * Este componente es invisible, solo contiene lógica.
     * Por eso devolvemos un div vacío y oculto.
     */
    public function render()
    {
        return <<<'blade'
            <div class="hidden"></div>
        blade;
    }
}
