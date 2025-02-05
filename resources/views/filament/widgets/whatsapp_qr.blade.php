<x-filament-widgets::widget>
    <x-filament::section>
        <h2 class="text-lg font-medium mb-4">Escanear QR de WhatsApp</h2>
        @if ($qrUrl = $this->getQrUrl())
            <img src="{{ $qrUrl }}" alt="WhatsApp QR Code" class="mx-auto">
            <p class="mt-4 text-sm text-gray-600 text-center">
                Escanea este código QR con WhatsApp > Menú > Dispositivos vinculados
            </p>
        @else
            <p class="text-gray-500">No se requiere autenticación en este momento</p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
