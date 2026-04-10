<?php

namespace HiEvents\Http\Actions\Reports;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Services\Domain\Donation\DonationsReportService;
use Illuminate\Http\JsonResponse;

class GetDonationsReportAction extends BaseAction
{
    public function __construct(
        private readonly DonationsReportService $donationsReportService,
    ) {
    }

    public function __invoke(int $eventId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        return $this->jsonResponse(
            $this->donationsReportService->generate($eventId),
            wrapInData: true,
        );
    }
}
