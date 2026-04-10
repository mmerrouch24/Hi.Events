<?php

namespace HiEvents\Services\Application\Handlers\Attendee\DTO;

use HiEvents\DataTransferObjects\BaseDTO;

class BulkResendAttendeeTicketsDTO extends BaseDTO
{
    public function __construct(
        public int $eventId,
    )
    {
    }
}
