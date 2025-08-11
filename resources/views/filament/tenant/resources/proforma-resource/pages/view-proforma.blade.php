<x-filament-panels::page>

    @php
    // Calculamos los totales aquí para usarlos después
    $subtotal = $record->details->sum(fn ($detail) => $detail->qty * $detail->price);
    $totalDescuento = $record->details->sum('discount_price');
    $granTotal = $subtotal - $totalDescuento;
    @endphp

    <div class="p-6 bg-white rounded-lg shadow-md dark:bg-gray-800 printable-area">

        {{-- SECCIÓN DEL ENCABEZADO --}}
        <div class="grid grid-cols-2 gap-4 pb-6 border-b dark:border-gray-700">
            {{-- Información de la Empresa (Dinámica) --}}
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">{{ $about?->shop_name }}</h2>
                <p class="dark:text-gray-400">{{ $about?->shop_location }}</p>
                <p class="dark:text-gray-400">{{ $about?->shop_phone ?? 'Cel. 305 201 07 17' }}</p>
            </div>

            {{-- Información de la Proforma y Cliente (Dinámica) --}}
            <div class="text-right">
                <h3 class="text-xl font-bold text-gray-800 dark:text-white">PROFORMA</h3>
                <p class="dark:text-gray-400"><strong>No:</strong> {{ $record->number }}</p>
                <p class="dark:text-gray-400"><strong>FECHA:</strong> {{ $record->created_at->format('d/m/Y') }}</p>
                <div class="mt-4">
                    <p class="font-semibold dark:text-gray-300">CLIENTE:</p>
                    <p class="dark:text-gray-400">{{ $record->member->name ?? 'Cliente General' }}</p>
                </div>
            </div>
        </div>

        {{-- SECCIÓN DE LA TABLA DE PRODUCTOS (Dinámica) --}}
        <div class="mt-6">
            <table class="w-full text-left table-auto dark:text-gray-300">
                <thead>
                    <tr class="border-b dark:border-gray-700">
                        <th class="px-4 py-2">REF.</th>
                        <th class="px-4 py-2">DESCRIPCION</th>
                        <th class="px-4 py-2 text-center">CANT.</th>
                        <th class="px-4 py-2 text-right">VR.UNIT.</th>
                        <th class="px-4 py-2 text-right">VR.TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($record->details as $item)
                    <tr class="border-b dark:border-gray-700">
                        <td class="px-4 py-2">{{ $item->product->id }}</td>
                        <td class="px-4 py-2">{{ $item->product->name }}</td>
                        <td class="px-4 py-2 text-center">{{ $item->qty }}</td>
                        {{-- Calculamos el precio unitario dividiendo --}}
                        <td class="px-4 py-2 text-right">${{ number_format($item->price / $item->qty, 2) }}</td>
                        {{-- El precio total ya está en $item->price --}}
                        <td class="px-4 py-2 text-right">${{ number_format($item->price, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- SECCIÓN DE TOTALES (Dinámica) --}}
        <div class="flex justify-end mt-6">
            <div class="w-full max-w-xs space-y-2">
                <div class="flex justify-between">
                    <span class="font-semibold dark:text-gray-300">SUBTOTAL:</span>
                    <span class="dark:text-gray-400">${{ number_format($subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-semibold dark:text-gray-300">DESCUENTO:</span>
                    <span class="dark:text-gray-400">-${{ number_format($totalDescuento, 2) }}</span>
                </div>
                <div class="flex justify-between pt-2 mt-2 border-t dark:border-gray-700">
                    <span class="text-lg font-bold dark:text-white">TOTAL:</span>
                    <span class="text-lg font-bold dark:text-white">${{ number_format($granTotal, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- SECCIÓN DE FIRMA --}}
        <div class="mt-24">
            <div class="w-1/3 pt-2 border-t border-gray-400">
                <p class="text-center dark:text-gray-300">RECIBE</p>
            </div>
        </div>
    </div>

    {{-- El bloque @push DEBE ir DENTRO de la etiqueta principal de la página --}}
    @push('scripts')
    <style>
        @media print {

            /* 1. Oculta todo en la página por defecto */
            body * {
                visibility: hidden;
            }

            /* 2. Muestra únicamente el área que marcamos como imprimible y su contenido */
            .printable-area,
            .printable-area * {
                visibility: visible;
            }

            /* 3. Asegura que el área de impresión ocupe toda la hoja */
            .printable-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
        }
    </style>
    @endpush

</x-filament-panels::page>