<?php

namespace HiEvents\Http\Actions\Attendees;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Services\Application\Handlers\Attendee\BulkResendAttendeeTicketsHandler;
use HiEvents\Services\Application\Handlers\Attendee\DTO\BulkResendAttendeeTicketsDTO;
use Illuminate\Http\Response;

class BulkResendAttendeeTicketsAction extends BaseAction
{
    public function __construct(
        private readonly BulkResendAttendeeTicketsHandler $handler,
    )
    {
    }

    public function __invoke(int $eventId): Response
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $this->handler->handle(new BulkResendAttendeeTicketsDTO(
            eventId: $eventId,
        ));

        return $this->noContentResponse();
    }
}
