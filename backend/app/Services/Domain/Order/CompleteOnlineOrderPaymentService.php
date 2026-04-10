<?php

namespace HiEvents\Services\Domain\Order;

use HiEvents\DomainObjects\Enums\PaymentProviders;
use HiEvents\DomainObjects\EventSettingDomainObject;
use HiEvents\DomainObjects\Generated\EventSettingDomainObjectAbstract;
use HiEvents\DomainObjects\Generated\OrderDomainObjectAbstract;
use HiEvents\DomainObjects\OrderDomainObject;
use HiEvents\DomainObjects\OrderItemDomainObject;
use HiEvents\DomainObjects\Status\AttendeeStatus;
use HiEvents\DomainObjects\Status\OrderApplicationFeeStatus;
use HiEvents\DomainObjects\Status\OrderPaymentStatus;
use HiEvents\DomainObjects\Status\OrderStatus;
use HiEvents\Events\OrderStatusChangedEvent;
use HiEvents\Repository\Interfaces\AffiliateRepositoryInterface;
use HiEvents\Repository\Interfaces\AttendeeRepositoryInterface;
use HiEvents\Repository\Interfaces\EventSettingsRepositoryInterface;
use HiEvents\Repository\Interfaces\OrderRepositoryInterface;
use HiEvents\Services\Domain\Product\ProductQuantityUpdateService;
use HiEvents\Services\Infrastructure\DomainEvents\DomainEventDispatcherService;
use HiEvents\Services\Infrastructure\DomainEvents\Enums\DomainEventType;
use HiEvents\Services\Infrastructure\DomainEvents\Events\OrderEvent;

class CompleteOnlineOrderPaymentService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly AffiliateRepositoryInterface $affiliateRepository,
        private readonly AttendeeRepositoryInterface $attendeeRepository,
        private readonly ProductQuantityUpdateService $productQuantityUpdateService,
        private readonly DomainEventDispatcherService $domainEventDispatcherService,
        private readonly EventSettingsRepositoryInterface $eventSettingsRepository,
        private readonly OrderApplicationFeeService $orderApplicationFeeService,
    ) {
    }

    public function complete(
        int $orderId,
        PaymentProviders $paymentProvider,
        ?int $applicationFeeAmountMinorUnit = null,
    ): OrderDomainObject {
        $updatedOrder = $this->orderRepository
            ->loadRelation(OrderItemDomainObject::class)
            ->updateFromArray($orderId, [
                OrderDomainObjectAbstract::PAYMENT_STATUS => OrderPaymentStatus::PAYMENT_RECEIVED->name,
                OrderDomainObjectAbstract::STATUS => OrderStatus::COMPLETED->name,
                OrderDomainObjectAbstract::PAYMENT_PROVIDER => $paymentProvider->value,
            ]);

        if ($updatedOrder->getAffiliateId()) {
            $this->affiliateRepository->incrementSales(
                affiliateId: $updatedOrder->getAffiliateId(),
                amount: $updatedOrder->getTotalGross()
            );
        }

        $this->attendeeRepository->updateWhere(
            attributes: ['status' => AttendeeStatus::ACTIVE->name],
            where: [
                'order_id' => $updatedOrder->getId(),
                'status' => AttendeeStatus::AWAITING_PAYMENT->name,
            ],
        );

        $this->productQuantityUpdateService->updateQuantitiesFromOrder($updatedOrder);

        /** @var EventSettingDomainObject $eventSettings */
        $eventSettings = $this->eventSettingsRepository->findFirstWhere([
            EventSettingDomainObjectAbstract::EVENT_ID => $updatedOrder->getEventId(),
        ]);

        event(new OrderStatusChangedEvent($updatedOrder, createInvoice: $eventSettings->getEnableInvoicing()));

        $this->domainEventDispatcherService->dispatch(
            new OrderEvent(
                type: DomainEventType::ORDER_CREATED,
                orderId: $updatedOrder->getId(),
            ),
        );

        if ($applicationFeeAmountMinorUnit !== null) {
            $this->orderApplicationFeeService->createOrderApplicationFee(
                orderId: $updatedOrder->getId(),
                applicationFeeAmountMinorUnit: $applicationFeeAmountMinorUnit,
                orderApplicationFeeStatus: OrderApplicationFeeStatus::PAID,
                paymentMethod: $paymentProvider,
                currency: $updatedOrder->getCurrency(),
            );
        }

        return $updatedOrder;
    }
}
