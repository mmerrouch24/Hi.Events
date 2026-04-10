<?php

namespace HiEvents\Services\Application\Handlers\Attendee;

use HiEvents\DomainObjects\EventSettingDomainObject;
use HiEvents\DomainObjects\OrderDomainObject;
use HiEvents\DomainObjects\OrderItemDomainObject;
use HiEvents\DomainObjects\OrganizerDomainObject;
use HiEvents\Repository\Eloquent\Value\Relationship;
use HiEvents\Repository\Interfaces\AttendeeRepositoryInterface;
use HiEvents\Repository\Interfaces\EventRepositoryInterface;
use HiEvents\Services\Application\Handlers\Attendee\DTO\ResendAttendeeTicketDTO;
use HiEvents\Services\Domain\Attendee\ResendAttendeeTicketService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

readonly class ResendAttendeeTicketHandler
{
    public function __construct(
        private ResendAttendeeTicketService $resendAttendeeTicketService,
        private AttendeeRepositoryInterface $attendeeRepository,
        private EventRepositoryInterface    $eventRepository,
        private LoggerInterface             $logger,
    )
    {
    }

    public function handle(ResendAttendeeTicketDTO $resendAttendeeProductDTO): void
    {
        $attendee = $this->attendeeRepository
            ->loadRelation(new Relationship(OrderDomainObject::class, nested: [
                new Relationship(OrderItemDomainObject::class),
            ], name: 'order'))
            ->findFirstWhere([
                'id' => $resendAttendeeProductDTO->attendeeId,
                'event_id' => $resendAttendeeProductDTO->eventId,
            ]);

        if (!$attendee) {
            throw new ResourceNotFoundException();
        }

        $event = $this->eventRepository
            ->loadRelation(new Relationship(OrganizerDomainObject::class, name: 'organizer'))
            ->loadRelation(EventSettingDomainObject::class)
            ->findById($resendAttendeeProductDTO->eventId);

        $this->resendAttendeeTicketService->resend($attendee, $event);

        $this->logger->info('Attendee ticket resent', [
            'attendeeId' => $resendAttendeeProductDTO->attendeeId,
            'eventId' => $resendAttendeeProductDTO->eventId
        ]);
    }
}
