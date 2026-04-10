<?php

namespace Tests\Unit\Jobs\Attendee;

use HiEvents\DomainObjects\AttendeeDomainObject;
use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Repository\Interfaces\AttendeeRepositoryInterface;
use HiEvents\Repository\Interfaces\EventRepositoryInterface;
use HiEvents\Jobs\Attendee\BulkResendAttendeeTicketsJob;
use HiEvents\Repository\Eloquent\Value\Relationship;
use HiEvents\Services\Domain\Attendee\ResendAttendeeTicketService;
use Mockery as m;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class BulkResendAttendeeTicketsJobTest extends TestCase
{
    public function testResendsTicketsForAllEligibleAttendeesWithoutDeduplicatingByEmail(): void
    {
        $eventRepository = m::mock(EventRepositoryInterface::class);
        $attendeeRepository = m::mock(AttendeeRepositoryInterface::class);
        $resendService = m::mock(ResendAttendeeTicketService::class);
        $logger = m::mock(LoggerInterface::class);

        $event = new EventDomainObject();

        $attendeeOne = (new AttendeeDomainObject())
            ->setId(1)
            ->setEmail('shared@example.com');
        $attendeeTwo = (new AttendeeDomainObject())
            ->setId(2)
            ->setEmail('shared@example.com');

        $eventRepository->shouldReceive('loadRelation')
            ->twice()
            ->andReturnSelf();
        $eventRepository->shouldReceive('findById')
            ->once()
            ->with(55)
            ->andReturn($event);

        $attendeeRepository->shouldReceive('loadRelation')
            ->once()
            ->with(m::type(Relationship::class))
            ->andReturnSelf();
        $attendeeRepository->shouldReceive('findWhere')
            ->once()
            ->with([
                'event_id' => 55,
                'status' => 'ACTIVE',
            ])
            ->andReturn(collect([$attendeeOne, $attendeeTwo]));

        $resendService->shouldReceive('resend')->once()->with($attendeeOne, $event);
        $resendService->shouldReceive('resend')->once()->with($attendeeTwo, $event);
        $logger->shouldNotReceive('error');

        $job = new BulkResendAttendeeTicketsJob(55);

        $job->handle($attendeeRepository, $eventRepository, $resendService, $logger);
    }

    public function testLogsFailuresAndContinuesProcessingRemainingAttendees(): void
    {
        $eventRepository = m::mock(EventRepositoryInterface::class);
        $attendeeRepository = m::mock(AttendeeRepositoryInterface::class);
        $resendService = m::mock(ResendAttendeeTicketService::class);
        $logger = m::mock(LoggerInterface::class);

        $event = new EventDomainObject();

        $failingAttendee = (new AttendeeDomainObject())
            ->setId(11)
            ->setEmail('fail@example.com');
        $successfulAttendee = (new AttendeeDomainObject())
            ->setId(12)
            ->setEmail('ok@example.com');

        $eventRepository->shouldReceive('loadRelation')
            ->twice()
            ->andReturnSelf();
        $eventRepository->shouldReceive('findById')
            ->once()
            ->with(77)
            ->andReturn($event);

        $attendeeRepository->shouldReceive('loadRelation')
            ->once()
            ->with(m::type(Relationship::class))
            ->andReturnSelf();
        $attendeeRepository->shouldReceive('findWhere')
            ->once()
            ->with([
                'event_id' => 77,
                'status' => 'ACTIVE',
            ])
            ->andReturn(collect([$failingAttendee, $successfulAttendee]));

        $resendService->shouldReceive('resend')
            ->once()
            ->with($failingAttendee, $event)
            ->andThrow(new \RuntimeException('SMTP error'));
        $resendService->shouldReceive('resend')
            ->once()
            ->with($successfulAttendee, $event);

        $logger->shouldReceive('error')
            ->once()
            ->with('Failed to resend attendee ticket during bulk resend', m::on(function (array $context) {
                return $context['eventId'] === 77
                    && $context['attendeeId'] === 11
                    && $context['error'] === 'SMTP error';
            }));

        $job = new BulkResendAttendeeTicketsJob(77);

        $job->handle($attendeeRepository, $eventRepository, $resendService, $logger);
    }
}
