<?php

namespace Tests\Unit\Services\Domain\Donation;

use HiEvents\DomainObjects\EventSettingDomainObject;
use HiEvents\DomainObjects\ProductCategoryDomainObject;
use HiEvents\DomainObjects\ProductDomainObject;
use HiEvents\Repository\Interfaces\ProductCategoryRepositoryInterface;
use HiEvents\Repository\Interfaces\ProductRepositoryInterface;
use HiEvents\Repository\Interfaces\QuestionRepositoryInterface;
use HiEvents\Services\Domain\Donation\DonationSettingsService;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DonationSettingsServiceTest extends TestCase
{
    private ProductRepositoryInterface|MockInterface $productRepository;
    private ProductCategoryRepositoryInterface|MockInterface $productCategoryRepository;
    private QuestionRepositoryInterface|MockInterface $questionRepository;
    private DonationSettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $this->productCategoryRepository = Mockery::mock(ProductCategoryRepositoryInterface::class);
        $this->questionRepository = Mockery::mock(QuestionRepositoryInterface::class);

        $this->service = new DonationSettingsService(
            $this->productRepository,
            $this->productCategoryRepository,
            $this->questionRepository,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testItDetectsDonationOrdersWhenAllProductsBelongToConfiguredCategory(): void
    {
        $settings = (new EventSettingDomainObject())->setDonationsSettings([
            'enabled' => true,
            'donations_category_name' => 'Donations',
        ]);

        $product = (new ProductDomainObject())
            ->setId(10)
            ->setProductCategoryId(50)
            ->setEventId(1);

        $category = (new ProductCategoryDomainObject())
            ->setId(50)
            ->setEventId(1)
            ->setName('Donations');

        $this->productRepository
            ->shouldReceive('findWhereIn')
            ->once()
            ->andReturn(new Collection([$product]));

        $this->productCategoryRepository
            ->shouldReceive('findWhereIn')
            ->once()
            ->andReturn(new Collection([$category]));

        $this->assertTrue($this->service->isDonationOrder(1, [10], $settings));
    }

    public function testItRejectsOfflinePaymentsForAlumniAndAllowsThemForCompanies(): void
    {
        $this->assertSame(['CMI'], $this->service->getAllowedPaymentProvidersForDonorType(DonationSettingsService::DONOR_TYPE_ALUMNI));
        $this->assertSame(['CMI', 'OFFLINE'], $this->service->getAllowedPaymentProvidersForDonorType(DonationSettingsService::DONOR_TYPE_COMPANY));
    }
}
