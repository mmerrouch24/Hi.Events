<?php

namespace Tests\Unit\Services\Domain\Donation;

use HiEvents\DomainObjects\EventSettingDomainObject;
use HiEvents\DomainObjects\OrderDomainObject;
use HiEvents\DomainObjects\OrderItemDomainObject;
use HiEvents\Services\Application\Handlers\Order\DTO\CompleteOrderDTO;
use HiEvents\Services\Application\Handlers\Order\DTO\CompleteOrderOrderDTO;
use HiEvents\Services\Domain\Donation\DonationCheckoutValidationService;
use HiEvents\Services\Domain\Donation\DonationSettingsService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class DonationCheckoutValidationServiceTest extends TestCase
{
    public function testItRequiresMappedFieldsForCompanyDonations(): void
    {
        $donationSettingsService = Mockery::mock(DonationSettingsService::class);
        $service = new DonationCheckoutValidationService($donationSettingsService);

        $order = (new OrderDomainObject())
            ->setEventId(1)
            ->setOrderItems(new Collection([
                (new OrderItemDomainObject())->setProductId(99),
            ]));

        $eventSettings = (new EventSettingDomainObject())->setDonationsSettings([
            'enabled' => true,
            'donations_category_name' => 'Donations',
            'company_name_question_id' => 10,
            'company_contact_phone_question_id' => 11,
            'company_table_number_question_id' => 12,
        ]);

        $orderData = new CompleteOrderDTO(
            order: new CompleteOrderOrderDTO(
                first_name: 'Jane',
                last_name: 'Doe',
                email: 'jane@example.com',
                donor_type: DonationSettingsService::DONOR_TYPE_COMPANY,
                questions: new Collection([
                    (object)['question_id' => 10, 'response' => ['answer' => 'Acme']],
                ]),
            ),
            products: new Collection(),
            event_id: 1,
        );

        $donationSettingsService->shouldReceive('isDonationOrder')->once()->andReturn(true);
        $donationSettingsService->shouldReceive('getDonationSettings')->once()->andReturn($eventSettings->getDonationsSettings());
        $donationSettingsService->shouldReceive('getOrderQuestionAnswerMap')->once()->andReturn([
            10 => 'Acme',
        ]);

        $this->expectException(ValidationException::class);

        $service->validate($order, $eventSettings, $orderData);
    }
}
