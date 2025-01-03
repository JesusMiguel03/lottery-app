<?php

namespace App\Filament\Resources\LotteryResource\Pages;

use App\Filament\Resources\LotteryResource;
use App\RedirectTrait;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class CreateLottery extends CreateRecord
{
    use RedirectTrait;
    protected static string $resource = LotteryResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            try {
                $lottery = static::getModel()::create($data);

                Notification::make()
                    ->title('Rifa creada')
                    ->body("La rifa ({$lottery->name}) ha sido registrada")
                    ->success()
                    ->send();

                $orders = range(1, count($data['prizes']));
                $prizesWithOrders = [];

                foreach ($data['prizes'] as $index => $prize) {
                    $prizesWithOrders[] = [
                        ...$prize,
                        'order' => $orders[$index],
                    ];
                }

                $lottery->prizes()->createMany($prizesWithOrders);
                $total_prizes = count($data['prizes']);
                Notification::make()
                    ->title('Premios registrados')
                    ->body("Se han registrado {$total_prizes} premios en la rifa ({$lottery->name})")
                    ->success()
                    ->send();

                $total_tickets = $data['total_tickets'];

                $tickets = array_map(function ($n) use ($lottery) {
                    $now = Carbon::now('utc')->toDateTimeString();
                    return [
                        'number' => $n,
                        'lottery_id' => $lottery->id,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }, range(1, $total_tickets));

                $lottery->tickets()->insert($tickets);

                Notification::make()
                    ->title('Boletos registrados')
                    ->body("Se han registrado {$total_tickets} boletos para la rifa ({$lottery->name})")
                    ->success()
                    ->send();

                return $lottery;
            } catch (\Exception $e) {
                DB::rollBack();
                Notification::make()
                    ->title('Hubo un error')
                    ->body('No se pudo registrar la rifa correctamente, intente de nuevo.')
                    ->danger()
                    ->send();
                throw $e;
            }
        });
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }
}
