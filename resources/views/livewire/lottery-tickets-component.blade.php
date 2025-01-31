<div class="mt-64 flex flex-col gap-3">
    <section>
        <h6>Leyenda</h6>
        <ul>
            <li>
                <div class="flex items-center gap-8">
                    <div style="background-color: rgba(248, 174, 174)" class="w-8 h-8 border border-gray-700 rounded-md">
                    </div>
                    <p>Comprado</p>
                </div>
            </li>
            <li>
                <div class="flex items-center gap-8">
                    <div style="background-color: rgba(174, 248, 174)" class="w-8 h-8 border border-gray-700 rounded-md">
                    </div>
                    <p>Disponible</p>
                </div>
            </li>
        </ul>
    </section>

    <section class="flex gap-3">
        @foreach ($tickets as $ticket)
            <div wire:click="selectTicket({{ $ticket['id'] }})"
                style="background-color: {{ $ticket['color'] === 'success' ? 'rgba(248, 174, 174)' : 'rgba(174, 248, 174)' }};"
                class="w-8 h-8 border border-gray-700 rounded-md inline-flex justify-center items-center text-sm cursor-pointer">
                {{ $ticket['number'] }}
            </div>
        @endforeach
    </section>

    <x-filament::modal wire:model="selectedTicket" id="ticketModal" width="md">
        <x-slot name="heading">
            Detalles del boleto {{ $ticketNumber }}
        </x-slot>

        <ul class="w-full flex flex-col">
            <li>
                <div class="flex justify-between">
                    <p class="font-bold">Cliente:</p>
                    <p>{{ $ticketClientName }}</p>
                </div>
            </li>
            <li>
                <div class="flex justify-between">
                    <p class="font-bold">Precio:</p>
                    <p>{{ $ticketPrice }} $</p>
                </div>
            </li>
            @if ($ticketPayment)
                <li>
                    <div class="flex justify-between">
                        <p class="font-bold">Monto pagado:</p>
                        <p>{{ $ticketPaymentAmount }}</p>
                    </div>
                </li>
                <li>
                    <div class="flex justify-between">
                        <p class="font-bold">Tipo de pago:</p>
                        <p>{{ $ticketPaymentType }}</p>
                    </div>
                </li>
                <li>
                    <div class="flex justify-between">
                        <p class="font-bold">Ref:</p>
                        <p>{{ $ticketPaymentRef }}</p>
                    </div>
                </li>
            @endif
            <li>
                <div class="flex justify-between">
                    <p class="font-bold">Ganador:</p>
                    <p>{{ $ticketWinner ? 'Si' : 'No' }}</p>
                </div>
            </li>
            @if ($ticketWinnerOrder)
                <li>
                    <div class="flex justify-between">
                        <p class="font-bold">Nro:</p>
                        <p>{{ $ticketWinnerOrder }}</p>
                    </div>
                </li>
            @endif
            <li>
                <div class="flex justify-between">
                    <p class="font-bold">Notificado:</p>
                    <p>{{ $ticketNotified }}</p>
                </div>
            </li>
        </ul>

        <x-slot name="footer">
            <x-filament::button wire:click="selectTicket(null)">
                Cerrar
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>
