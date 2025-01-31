<?php

namespace App\Filament\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

trait HasActivityLogger
{
  protected function afterCreateModel(Model $record): void
  {
    $this->logActivity($record, 'create', 'create');
  }

  protected function afterSaveModel(Model $record): void
  {
    $this->logActivity($record, 'update', 'edit');
  }

  protected function afterDeleteModel(Model $record): void
  {
    $this->logActivity($record, 'delete', 'delete');
  }

  protected function afterBulkDeleteModel(): void
  {
    foreach ($this->getSelectedRecords() as $record) {
      $this->logActivity($record, 'delete', 'bulk_delete');
    }
  }

  public static function logActivity(
    Model | null $record,
    string $action,
    string $interactionType,
    array $additionalData = []
  ): void {
    try {
      $logData = [
        'timestamp' => now()->toISOString(),
        'user_id' => auth()->id(),
        'user_name' => auth()->user()?->name ?? 'System',
        'action' => $action,
        'model' => $record ? get_class($record) : 'Notification',
        'model_id' => $record->id,
        'form_data' => $action === 'create'
          ? request()->except(['password', 'password_confirmation'])
          : $record->getChanges(),
        'interaction_type' => $interactionType,
        'result' => 'success',
        'ip_address' => request()->ip(),
        'user_agent' => request()->userAgent(),
        'additional_data' => $additionalData
      ];

      Log::channel('activity')->info('Lottery Activity', $logData);
    } catch (\Exception $e) {
      Log::channel('daily')->error("Lottery log failed: " . $e->getMessage());
    }
  }
}
