<?php

namespace HiEvents\Jobs\Attendee;

use HiEvents\DomainObjects\EventSettingDomainObject;
use HiEvents\DomainObjects\OrderDomainObject;
use HiEvents\DomainObjects\OrderItemDomainObject;
use HiEvents\DomainObjects\OrganizerDomainObject;
use HiEvents\DomainObjects\Status\AttendeeStatus;
use HiEvents\Repository\Eloquent\Value\Relationship;
use HiEvents\Repository\Interfaces\AttendeeRepositoryInterface;
use HiEvents\Repository\Interfaces\EventRepositoryInterface;
use HiEvents\Services\Domain\Attendee\ResendAttendeeTicketService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;
use Throwable;

class BulkResendAttendeeTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $eventId,
    )
    {
    }

    public function handle(
        AttendeeRepositoryInterface $attendeeRepository,
        EventRepositoryInterface $eventRepository,
        ResendAttendeeTicketService $resendAttendeeTicketService,
        LoggerInterface $logger,
    ): void
    {
        $event = $eventRepository
            ->loadRelation(new Relationship(OrganizerDomainObject::class, name: 'organizer'))
            ->loadRelation(EventSettingDomainObject::class)
            ->findById($this->eventId);

        if (!$event) {
            $logger->error('Failed to bulk resend attendee tickets because the event was not found', [
                'eventId' => $this->eventId,
            ]);
            return;
        }

        $attendees = $attendeeRepository
            ->loadRelation(new Relationship(OrderDomainObject::class, nested: [
                new Relationship(OrderItemDomainObject::class),
            ], name: 'order'))
            ->findWhere([
                'event_id' => $this->eventId,
                'status' => AttendeeStatus::ACTIVE->name,
            ]);

        foreach ($attendees as $attendee) {
            try {
                $resendAttendeeTicketService->resend($attendee, $event);
            } catch (Throwable $exception) {
                $logger->error('Failed to resend attendee ticket during bulk resend', [
                    'eventId' => $this->eventId,
                    'attendeeId' => $attendee->getId(),
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}
