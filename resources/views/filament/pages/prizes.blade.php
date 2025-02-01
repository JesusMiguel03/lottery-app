<div>
    @php
        $lotteryId = $getRecord()->id;
        $tickets = $getRecord()
            ->tickets()
            ->where('winner', 1)
            ->whereHas('payment')
            ->with(['lottery', 'lottery.prizes'])
            ->paginate(10);
    @endphp
    <ul class='flex flex-wrap justify-center gap-3'>
        @foreach ($tickets as $ticket)
            @php
                $prize = $ticket->lottery->prizes[0];
            @endphp
            <li class='py-2 px-6 border border-neutral-400 rounded-md'>
                <a href="{{ route('filament.admin.resources.lotteries.view', $lotteryId) }}">
                    <div class='flex flex-col justify-center items-center'>
                        <h3 class='font-bold'>Ganador &num;{{ $prize->order }}</h3>
                        <h6 class='text-lg font-semibold'>
                            {{ $ticket->client->full_name }}
                        </h6>
                        <p>Premio: {{ $prize->name }} ({{ $prize->value }}$)</p>
                        <p>Boleto &num;{{ $ticket->number }}</p>
                    </div>
                </a>
            </li>
        @endforeach
    </ul>
    <x-filament::pagination :paginator="$tickets" />
</div>
