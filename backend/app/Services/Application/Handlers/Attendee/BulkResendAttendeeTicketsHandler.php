<?php

namespace HiEvents\Services\Application\Handlers\Attendee;

use HiEvents\Jobs\Attendee\BulkResendAttendeeTicketsJob;
use HiEvents\Services\Application\Handlers\Attendee\DTO\BulkResendAttendeeTicketsDTO;

class BulkResendAttendeeTicketsHandler
{
    public function handle(BulkResendAttendeeTicketsDTO $dto): void
    {
        BulkResendAttendeeTicketsJob::dispatch($dto->eventId);
    }
}
