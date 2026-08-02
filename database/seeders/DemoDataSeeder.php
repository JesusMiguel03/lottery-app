<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Currency;
use App\Models\Lottery;
use App\Models\Payment;
use App\Models\Prize;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed realistic demo data covering every domain entity the UI can show.
     *
     * Idempotent: currencies are keyed by their created_at date, clients by their
     * document, lotteries by their name, tickets by (lottery_id, number) and
     * payments by their full set of attributes.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $rate = $this->seedCurrencies();
            $clients = $this->seedClients();

            $this->seedActiveLottery($clients, $rate);
            $this->seedFinishedLottery($clients, $rate);
            $this->seedUpcomingLottery($clients, $rate);
        });
    }

    private function seedCurrencies(): Currency
    {
        $today = Carbon::today();
        $this->seedCurrency(36.50, $today);
        $this->seedCurrency(36.20, $today->copy()->subDay());

        return Currency::whereDate('created_at', $today)->first();
    }

    private function seedCurrency(float $value, Carbon $date): void
    {
        $currency = Currency::whereDate('created_at', $date)->first();

        if ($currency) {
            $currency->update(['value' => $value, 'updated_at' => now()]);

            return;
        }

        Currency::create([
            'value' => $value,
            'created_at' => $date,
            'updated_at' => now(),
        ]);
    }

    private function seedClients(): \Illuminate\Support\Collection
    {
        $clients = [
            ['name' => 'Jose', 'last_name' => 'Rodriguez', 'doc_type' => 'V', 'doc' => '12451248', 'code' => '0414', 'phone' => '5551234'],
            ['name' => 'Maria', 'last_name' => 'Gomez', 'doc_type' => 'V', 'doc' => '18452365', 'code' => '0416', 'phone' => '1234567'],
            ['name' => 'Luis', 'last_name' => 'Perez', 'doc_type' => 'V', 'doc' => '26587410', 'code' => '0424', 'phone' => '9876543'],
            ['name' => 'Carmen', 'last_name' => 'Martinez', 'doc_type' => 'V', 'doc' => '12345678', 'code' => '0412', 'phone' => '5557890'],
            ['name' => 'Pedro', 'last_name' => 'Gonzalez', 'doc_type' => 'E', 'doc' => '84123456', 'code' => '0416', 'phone' => '5554321'],
            ['name' => 'Ana', 'last_name' => 'Hernandez', 'doc_type' => 'V', 'doc' => '15247896', 'code' => '0414', 'phone' => '7778899'],
            ['name' => 'Carlos', 'last_name' => 'Ramirez', 'doc_type' => 'V', 'doc' => '98765432', 'code' => '0426', 'phone' => '3332211'],
            ['name' => 'Gabriela', 'last_name' => 'Diaz', 'doc_type' => 'J', 'doc' => '30567901', 'code' => '0412', 'phone' => '1112233'],
        ];

        return collect($clients)->map(function (array $row) {
            return Client::updateOrCreate(['doc' => $row['doc']], $row);
        });
    }

    private function seedActiveLottery(\Illuminate\Support\Collection $clients, Currency $rate): void
    {
        $lottery = $this->createLottery([
            'name' => 'Demo Sorteo',
            'description' => 'Rifa demo para presentación del sistema: participa hoy mismo por grandes premios.',
            'total_winners' => 3,
            'total_tickets' => 100,
            'ticket_price' => 5,
            'total_price' => 500,
            'initial_date' => Carbon::today()->subDays(10)->format('d/m/Y'),
            'final_date' => Carbon::today()->addDays(7)->format('d/m/Y'),
        ]);

        $this->createPrizes($lottery, [
            ['name' => 'Primer Premio', 'quantity' => 1, 'value' => 150],
            ['name' => 'Segundo Premio', 'quantity' => 1, 'value' => 100],
            ['name' => 'Tercer Premio', 'quantity' => 1, 'value' => 50],
        ]);

        $this->createTickets($lottery);

        $this->sellTicket($lottery, $clients[0], 1, 'usd', 5, null, $rate);
        $this->sellTicket($lottery, $clients[0], 2, 'usd', 5, null, $rate);
        $this->sellTicket($lottery, $clients[1], 3, 'usd', 5, null, $rate);
        $this->sellTicket($lottery, $clients[1], 4, 'usd', 2, null, $rate);
        $this->sellTicket($lottery, $clients[2], 5, 'usd', 5, null, $rate);
        $this->sellTicket($lottery, $clients[2], 6, 'usd', 5, null, $rate);
        $this->sellTicket($lottery, $clients[2], 7, 'usd', 5, null, $rate);
        $this->sellTicket($lottery, $clients[3], 8, 'bs', 182.5, null, $rate);
        $this->sellTicket($lottery, $clients[3], 9, 'bs', 182.5, null, $rate);
        $this->sellTicket($lottery, $clients[4], 10, 'payment', 182.5, '1009988776', $rate);
        $this->sellTicket($lottery, $clients[4], 11, 'payment', 182.5, '1009988777', $rate);
        $this->sellTicket($lottery, $clients[4], 12, 'payment', 182.5, '1009988778', $rate);
        $this->sellTicket($lottery, $clients[5], 13, 'usd', 5, null, $rate);
        $this->sellTicket($lottery, $clients[5], 14, 'usd', 5, null, $rate);
        $this->sellTicket($lottery, $clients[5], 15, 'usd', 5, null, $rate);
        $this->sellTicket($lottery, $clients[5], 16, 'usd', 3, null, $rate);
        $this->sellTicket($lottery, $clients[6], 17, 'usd', 2, null, $rate);
        $this->sellTicket($lottery, $clients[6], 18, 'usd', 2, null, $rate);
        $this->sellTicket($lottery, $clients[7], 19, null, null, null, $rate);
        $this->sellTicket($lottery, $clients[7], 20, null, null, null, $rate);
        $this->sellTicket($lottery, $clients[7], 21, null, null, null, $rate);
    }

    private function seedFinishedLottery(\Illuminate\Support\Collection $clients, Currency $rate): void
    {
        $lottery = $this->createLottery([
            'name' => 'Demo Finalizado',
            'description' => 'Rifa finalizada para mostrar el proceso de ganadores y notificaciones',
            'total_winners' => 3,
            'total_tickets' => 50,
            'ticket_price' => 10,
            'total_price' => 500,
            'initial_date' => Carbon::today()->subDays(30)->format('d/m/Y'),
            'final_date' => Carbon::today()->subDays(20)->format('d/m/Y'),
            'finished_at' => Carbon::now()->subDays(18),
        ]);

        $prizes = $this->createPrizes($lottery, [
            ['name' => 'Primer Premio', 'quantity' => 1, 'value' => 200],
            ['name' => 'Segundo Premio', 'quantity' => 1, 'value' => 150],
            ['name' => 'Tercer Premio', 'quantity' => 1, 'value' => 100],
        ]);

        $this->createTickets($lottery);

        $sold = [
            [1, $clients[0], 10, 'usd'],
            [2, $clients[1], 10, 'usd'],
            [3, $clients[1], 10, 'usd'],
            [4, $clients[2], 10, 'usd'],
            [5, $clients[0], 10, 'usd'],
            [6, $clients[0], 10, 'usd'],
            [7, $clients[2], 10, 'usd'],
            [8, $clients[2], 10, 'usd'],
            [9, $clients[3], 10, 'usd'],
            [10, $clients[4], 10, 'usd'],
            [11, $clients[4], 10, 'usd'],
            [12, $clients[3], 10, 'usd'],
            [13, $clients[3], 10, 'usd'],
            [14, $clients[5], 10, 'usd'],
            [27, $clients[5], 10, 'usd'],
        ];

        foreach ($sold as [$number, $client, $amount, $type]) {
            $this->sellTicket($lottery, $client, $number, $type, $amount, null, $rate);
        }

        $this->setWinner($lottery, $prizes[0], 5, 1);
        $this->setWinner($lottery, $prizes[1], 12, 2);
        $this->setWinner($lottery, $prizes[2], 27, 3);
    }

    private function seedUpcomingLottery(\Illuminate\Support\Collection $clients, Currency $rate): void
    {
        $lottery = $this->createLottery([
            'name' => 'Demo Proximo',
            'description' => 'Rifa próxima para mostrar la disponibilidad de boletos futuros',
            'total_winners' => 5,
            'total_tickets' => 200,
            'ticket_price' => 2,
            'total_price' => 400,
            'initial_date' => Carbon::today()->addDays(5)->format('d/m/Y'),
            'final_date' => Carbon::today()->addDays(20)->format('d/m/Y'),
        ]);

        $this->createPrizes($lottery, [
            ['name' => 'Primer Premio', 'quantity' => 1, 'value' => 100],
            ['name' => 'Segundo Premio', 'quantity' => 1, 'value' => 80],
            ['name' => 'Tercer Premio', 'quantity' => 1, 'value' => 60],
            ['name' => 'Cuarto Premio', 'quantity' => 1, 'value' => 40],
            ['name' => 'Quinto Premio', 'quantity' => 1, 'value' => 30],
        ]);

        $this->createTickets($lottery);

        $this->sellTicket($lottery, $clients[1], 1, 'usd', 2, null, $rate);
        $this->sellTicket($lottery, $clients[4], 2, 'usd', 2, null, $rate);
        $this->sellTicket($lottery, $clients[6], 3, null, null, null, $rate);
    }

    private function createLottery(array $data): Lottery
    {
        return Lottery::updateOrCreate(['name' => $data['name']], $data);
    }

    private function createPrizes(Lottery $lottery, array $prizes): \Illuminate\Support\Collection
    {
        return collect($prizes)->map(function (array $prize, int $index) use ($lottery) {
            return Prize::updateOrCreate(
                [
                    'lottery_id' => $lottery->id,
                    'order' => $index + 1,
                ],
                [
                    'name' => $prize['name'],
                    'quantity' => $prize['quantity'],
                    'value' => $prize['value'],
                ]
            );
        });
    }

    private function createTickets(Lottery $lottery): void
    {
        $existing = $lottery->tickets()->pluck('number')->map(fn($n) => (int) $n)->all();
        $missing = array_values(array_diff(range(1, $lottery->total_tickets), $existing));

        if (empty($missing)) {
            return;
        }

        $now = now()->toDateTimeString();
        $rows = collect($missing)->map(fn(int $number) => [
            'number' => $number,
            'lottery_id' => $lottery->id,
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        $lottery->tickets()->insert($rows);
    }

    private function sellTicket(
        Lottery $lottery,
        Client $client,
        int $number,
        ?string $type,
        ?float $amount,
        ?string $ref,
        Currency $rate
    ): void {
        $ticket = $lottery->tickets()->firstOrCreate(['number' => $number]);
        $ticket->update(['client_id' => $client->id]);

        if ($type === null || $amount === null) {
            return;
        }

        Payment::firstOrCreate([
            'ticket_id' => $ticket->id,
            'amount' => $amount,
            'type' => $type,
            'ref' => $ref,
            'currency_id' => $rate->id,
        ]);
    }

    private function setWinner(Lottery $lottery, Prize $prize, int $number, int $order): void
    {
        $lottery->tickets()->where('number', $number)->update([
            'winner' => true,
            'order' => $order,
            'prize_id' => $prize->id,
        ]);
    }
}
