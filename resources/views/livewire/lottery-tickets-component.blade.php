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
            <li>
                <div class="flex items-center gap-8">
                    <div style="background-color: rgb(233, 230, 81)" class="w-8 h-8 border border-gray-700 rounded-md">
                    </div>
                    <p>Pendiente</p>
                </div>
            </li>
            <li>
                <div class="flex items-center gap-8">
                    <div style="background-color: rgb(167, 130, 133)" class="w-8 h-8 border border-gray-700 rounded-md">
                    </div>
                    <p>Pago incompleto</p>
                </div>
            </li>
        </ul>
    </section>

    <section class="grid-container">
        @php
            $colors = [
                'not_payed' => 'rgb(167, 130, 133)',
                'success' => 'rgb(248, 174, 174)',
                'pending' => 'rgb(233, 230, 81)',
                'danger' => 'rgb(174, 248, 174)',
            ];
        @endphp
        @foreach ($tickets as $ticket)
            <div wire:click="selectTicket({{ $ticket['id'] }})"
                style="background-color: {{ $colors[$ticket['color']] }};"
                class="w-8 h-8 border border-gray-700 rounded-md inline-flex justify-center items-center text-sm cursor-pointer">
                {{ $ticket['number'] }}
            </div>
        @endforeach
    </section>

    <x-filament::modal wire:model="selectedTicket" id="ticketModal" width="lg">
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
                        <p class="font-bold">Pagos ({{ count($ticketPayment) }}):</p>
                    </div>
                </li>
                @foreach ($ticketPayment as $key => $payment)
                    <li>
                        <p class="ps-4">
                            ~ {{ ++$key . ')' }} {{ $payment->payment_formatted_with_ref }}
                            {{ $payment->created_at->translatedFormat('l, d M Y, h:i a') }}
                        </p>
                    </li>
                @endforeach
            @endif
            @if ($ticketChanges)
                <li>
                    <div class="flex justify-between">
                        <p class="font-bold">Vueltos ({{ count($ticketChanges) }}):</p>
                    </div>
                </li>
                @foreach ($ticketChanges as $key => $change)
                    <li>
                        <p class="ps-4">
                            ~ {{ ++$key . ')' }} {{ $change->change_formatted_with_ref }}
                            {{ $change->created_at->translatedFormat('l, d M Y, h:i a') }}
                        </p>
                    </li>
                @endforeach
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
