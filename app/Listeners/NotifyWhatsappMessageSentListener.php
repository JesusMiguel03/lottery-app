<?php

namespace App\Listeners;

use App\Events\BotMessageProcessedEvent;

class NotifyWhatsappMessageSentListener
{
    public function handle(BotMessageProcessedEvent $event) {}
}
