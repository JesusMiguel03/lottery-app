<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Storage;

class Whatsapp_qr extends Widget
{
    protected static string $view = 'filament.widgets.whatsapp_qr';

    public function getQrUrl()
    {
        $qrPath = 'storage/qr.png';
        if (Storage::disk('public')->exists('qr.png')) {
            $lastModified = Storage::disk('public')->lastModified('qr.png');
            return asset($qrPath) . '?t=' . $lastModified;
        }
        return null;
    }

    public function shouldShow(): bool
    {
        return Storage::disk('public')->exists('qr.png');
    }

    protected function getPollingInterval(): ?string
    {
        return '5s'; // Refresh every 5 seconds
    }
}
