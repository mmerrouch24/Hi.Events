<?php

namespace HiEvents\Services\Domain\Donation;

use HiEvents\DomainObjects\EventSettingDomainObject;
use HiEvents\DomainObjects\OrderDomainObject;
use HiEvents\Services\Application\Handlers\Order\DTO\CompleteOrderDTO;
use Illuminate\Validation\ValidationException;

class DonationCheckoutValidationService
{
    public function __construct(
        private readonly DonationSettingsService $donationSettingsService,
    ) {
    }

    public function validate(OrderDomainObject $order, EventSettingDomainObject $eventSettings, CompleteOrderDTO $orderData): void
    {
        $productIds = $order->getOrderItems()?->pluck('product_id')->all() ?? [];

        if (!$this->donationSettingsService->isDonationOrder($order->getEventId(), $productIds, $eventSettings)) {
            return;
        }

        $donorType = $orderData->order->donor_type;
        if (!in_array($donorType, [DonationSettingsService::DONOR_TYPE_ALUMNI, DonationSettingsService::DONOR_TYPE_COMPANY], true)) {
            throw ValidationException::withMessages([
                'order.donor_type' => __('Please choose whether this donation is from an alumni donor or a company.'),
            ]);
        }

        $settings = $this->donationSettingsService->getDonationSettings($eventSettings);
        $answers = $this->donationSettingsService->getOrderQuestionAnswerMap($orderData->order->questions);

        $requiredQuestionIds = match ($donorType) {
            DonationSettingsService::DONOR_TYPE_ALUMNI => [
                $settings['graduation_year_question_id'] ?? null,
                $settings['alumni_table_number_question_id'] ?? null,
            ],
            DonationSettingsService::DONOR_TYPE_COMPANY => [
                $settings['company_name_question_id'] ?? null,
                $settings['company_contact_phone_question_id'] ?? null,
                $settings['company_table_number_question_id'] ?? null,
            ],
        };

        $missing = [];
        foreach (array_filter($requiredQuestionIds) as $questionId) {
            if (empty($answers[(int)$questionId] ?? null)) {
                $missing[] = $questionId;
            }
        }

        if (!empty($missing)) {
            throw ValidationException::withMessages([
                'order.questions' => __('Please complete all required donation details for the selected donor type.'),
            ]);
        }
    }
}
