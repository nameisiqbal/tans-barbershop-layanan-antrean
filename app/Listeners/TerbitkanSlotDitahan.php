<?php

namespace App\Listeners;

use App\Events\SlotDitahan;
use App\Messaging\RabbitPublisher;

class TerbitkanSlotDitahan
{
    public function __construct(private RabbitPublisher $publisher) {}

    public function handle(SlotDitahan $event): void
    {
        $this->publisher->publish('slot.ditahan', $event->payload(), $event->id, 'SlotDitahan');
    }
}
