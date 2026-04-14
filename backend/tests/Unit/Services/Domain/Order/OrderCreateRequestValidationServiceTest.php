<?php

namespace Tests\Unit\Services\Domain\Order;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\DomainObjects\ProductDomainObject;
use HiEvents\DomainObjects\ProductPriceDomainObject;
use HiEvents\Repository\Interfaces\EventRepositoryInterface;
use HiEvents\Repository\Interfaces\ProductRepositoryInterface;
use HiEvents\Repository\Interfaces\PromoCodeRepositoryInterface;
use HiEvents\Services\Domain\Order\OrderCreateRequestValidationService;
use HiEvents\Services\Domain\Product\AvailableProductQuantitiesFetchService;
use HiEvents\Services\Domain\Product\DTO\AvailableProductQuantitiesDTO;
use HiEvents\Services\Domain\Product\DTO\AvailableProductQuantitiesResponseDTO;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class OrderCreateRequestValidationServiceTest extends TestCase
{
    private ProductRepositoryInterface|MockInterface $productRepository;
    private PromoCodeRepositoryInterface|MockInterface $promoCodeRepository;
    private EventRepositoryInterface|MockInterface $eventRepository;
    private AvailableProductQuantitiesFetchService|MockInterface $availableProductQuantitiesFetchService;
    private OrderCreateRequestValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $this->promoCodeRepository = Mockery::mock(PromoCodeRepositoryInterface::class);
        $this->eventRepository = Mockery::mock(EventRepositoryInterface::class);
        $this->availableProductQuantitiesFetchService = Mockery::mock(AvailableProductQuantitiesFetchService::class);

        $this->service = new OrderCreateRequestValidationService(
            $this->productRepository,
            $this->promoCodeRepository,
            $this->eventRepository,
            $this->availableProductQuantitiesFetchService,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testRejectsProductQuantityBelowLinkedTicketQuantity(): void
    {
        $eventId = 1;
        $ticketProduct = $this->makeProduct(id: 10, priceId: 100, title: 'Match Ticket', productType: 'TICKET');
        $hatProduct = $this->makeProduct(
            id: 20,
            priceId: 200,
            title: 'Hat',
            productType: 'GENERAL',
            minPerOrder: 1,
            linkedTicketProductId: 10,
        );

        $this->mockValidationDependencies($eventId, collect([$ticketProduct, $hatProduct]));

        try {
            $this->service->validateRequestData($eventId, [
                'products' => [
                    [
                        'product_id' => 10,
                        'quantities' => [
                            ['price_id' => 100, 'quantity' => 3, 'price' => 10],
                        ],
                    ],
                    [
                        'product_id' => 20,
                        'quantities' => [
                            ['price_id' => 200, 'quantity' => 2, 'price' => 5],
                        ],
                    ],
                ],
            ]);

            $this->fail('Expected validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'You must order at least 3 products for Hat',
                $exception->errors()['products.1'][0],
            );
        }
    }

    public function testAllowsProductQuantityWhenItMatchesLinkedTicketQuantity(): void
    {
        $eventId = 1;
        $ticketProduct = $this->makeProduct(id: 10, priceId: 100, title: 'Match Ticket', productType: 'TICKET');
        $hatProduct = $this->makeProduct(
            id: 20,
            priceId: 200,
            title: 'Hat',
            productType: 'GENERAL',
            minPerOrder: 1,
            linkedTicketProductId: 10,
        );

        $this->mockValidationDependencies($eventId, collect([$ticketProduct, $hatProduct]));

        $this->service->validateRequestData($eventId, [
            'products' => [
                [
                    'product_id' => 10,
                    'quantities' => [
                        ['price_id' => 100, 'quantity' => 3, 'price' => 10],
                    ],
                ],
                [
                    'product_id' => 20,
                    'quantities' => [
                        ['price_id' => 200, 'quantity' => 3, 'price' => 5],
                    ],
                ],
            ],
        ]);

        $this->assertTrue(true);
    }

    private function mockValidationDependencies(int $eventId, Collection $products): void
    {
        $event = (new EventDomainObject())->setId($eventId);

        $this->eventRepository->shouldReceive('findById')
            ->once()
            ->with($eventId)
            ->andReturn($event);

        $this->productRepository->shouldReceive('loadRelation')
            ->once()
            ->andReturnSelf();

        $this->productRepository->shouldReceive('findWhereIn')
            ->once()
            ->andReturn($products);

        $this->availableProductQuantitiesFetchService->shouldReceive('getAvailableProductQuantities')
            ->once()
            ->with($eventId, true)
            ->andReturn(new AvailableProductQuantitiesResponseDTO(
                productQuantities: collect([
                    AvailableProductQuantitiesDTO::fromArray([
                        'product_id' => 10,
                        'price_id' => 100,
                        'product_title' => 'Match Ticket',
                        'price_label' => null,
                        'quantity_available' => 50,
                        'quantity_reserved' => 0,
                        'initial_quantity_available' => 50,
                        'capacities' => collect(),
                    ]),
                    AvailableProductQuantitiesDTO::fromArray([
                        'product_id' => 20,
                        'price_id' => 200,
                        'product_title' => 'Hat',
                        'price_label' => null,
                        'quantity_available' => 50,
                        'quantity_reserved' => 0,
                        'initial_quantity_available' => 50,
                        'capacities' => collect(),
                    ]),
                ]),
            ));
    }

    private function makeProduct(
        int $id,
        int $priceId,
        string $title,
        string $productType,
        int $minPerOrder = 1,
        ?int $linkedTicketProductId = null,
    ): ProductDomainObject {
        $price = (new ProductPriceDomainObject())
            ->setId($priceId)
            ->setPrice(10)
            ->setInitialQuantityAvailable(null)
            ->setQuantitySold(0);

        return (new ProductDomainObject())
            ->setId($id)
            ->setEventId(1)
            ->setTitle($title)
            ->setType('PAID')
            ->setProductType($productType)
            ->setMinPerOrder($minPerOrder)
            ->setMaxPerOrder(100)
            ->setMinPerOrderLinkedTicketProductId($linkedTicketProductId)
            ->setProductPrices(collect([$price]));
    }
}
