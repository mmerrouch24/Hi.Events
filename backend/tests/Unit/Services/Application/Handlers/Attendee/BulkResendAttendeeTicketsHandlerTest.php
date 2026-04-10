<?php

namespace Tests\Unit\Services\Application\Handlers\Attendee;

use HiEvents\Jobs\Attendee\BulkResendAttendeeTicketsJob;
use HiEvents\Services\Application\Handlers\Attendee\BulkResendAttendeeTicketsHandler;
use HiEvents\Services\Application\Handlers\Attendee\DTO\BulkResendAttendeeTicketsDTO;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class BulkResendAttendeeTicketsHandlerTest extends TestCase
{
    public function testDispatchesBulkResendJob(): void
    {
        Bus::fake();

        $handler = new BulkResendAttendeeTicketsHandler();

        $handler->handle(new BulkResendAttendeeTicketsDTO(eventId: 123));

        Bus::assertDispatched(BulkResendAttendeeTicketsJob::class, function (BulkResendAttendeeTicketsJob $job) {
            return $job->eventId === 123;
        });
    }
}
