<?php

namespace HiEvents\Services\Domain\Attendee;

use HiEvents\DomainObjects\AttendeeDomainObject;
use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\DomainObjects\Status\AttendeeStatus;
use HiEvents\Exceptions\ResourceConflictException;

class ResendAttendeeTicketService
{
    public function __construct(
        private readonly SendAttendeeTicketService $sendAttendeeTicketService,
    )
    {
    }

    /**
     * @throws ResourceConflictException
     */
    public function resend(AttendeeDomainObject $attendee, EventDomainObject $event): void
    {
        if ($attendee->getStatus() !== AttendeeStatus::ACTIVE->name) {
            throw new ResourceConflictException('You cannot resend the ticket of an inactive attendee');
        }

        $this->sendAttendeeTicketService->send(
            order: $attendee->getOrder(),
            attendee: $attendee,
            event: $event,
            eventSettings: $event->getEventSettings(),
            organizer: $event->getOrganizer(),
        );
    }
}
