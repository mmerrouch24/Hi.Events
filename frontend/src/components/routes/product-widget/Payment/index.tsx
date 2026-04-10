import React, {useState} from "react";
import {useNavigate, useParams} from "react-router";
import {useGetEventPublic} from "../../../../queries/useGetEventPublic.ts";
import {CheckoutContent} from "../../../layouts/Checkout/CheckoutContent";
import {StripePaymentMethod} from "./PaymentMethods/Stripe";
import {OfflinePaymentMethod} from "./PaymentMethods/Offline";
import {CmiPaymentMethod} from "./PaymentMethods/CMI";
import {DonationDonorType, Event} from "../../../../types.ts";
import {Button, Group, Text} from "@mantine/core";
import {IconBuildingBank, IconLock, IconWallet} from "@tabler/icons-react";
import {formatCurrency} from "../../../../utilites/currency.ts";
import {t, Trans} from "@lingui/macro";
import {useGetOrderPublic} from "../../../../queries/useGetOrderPublic.ts";
import {
    useTransitionOrderToOfflinePaymentPublic
} from "../../../../mutations/useTransitionOrderToOfflinePaymentPublic.ts";
import {Card} from "../../../common/Card";
import {InlineOrderSummary} from "../../../common/InlineOrderSummary";
import {showError} from "../../../../utilites/notifications.tsx";
import {getConfig} from "../../../../utilites/config.ts";
import classes from "./Payment.module.scss";
import {trackEvent, AnalyticsEvents} from "../../../../utilites/analytics.ts";
import {orderClientPublic} from "../../../../api/order.client.ts";

const Payment = () => {
    const navigate = useNavigate();
    const {eventId, orderShortId} = useParams();
    const {data: event, isFetched: isEventFetched} = useGetEventPublic(eventId);
    const {data: order, isFetched: isOrderFetched} = useGetOrderPublic(eventId, orderShortId, ['event']);
    const isLoading = !isOrderFetched;
    const [isPaymentLoading, setIsPaymentLoading] = useState(false);
    const [activePaymentMethod, setActivePaymentMethod] = useState<'STRIPE' | 'CMI' | 'OFFLINE' | null>(null);
    const [submitHandler, setSubmitHandler] = useState<(() => Promise<void>) | null>(null);
    const transitionOrderToOfflinePaymentMutation = useTransitionOrderToOfflinePaymentPublic();

    const isStripeEnabled = event?.settings?.payment_providers?.includes('STRIPE');
    const isCmiEnabled = event?.settings?.payment_providers?.includes('CMI');
    const isOfflineEnabled = event?.settings?.payment_providers?.includes('OFFLINE');
    const donorType = order?.point_in_time_data?.donations?.donor_type as DonationDonorType | undefined;
    const donationsCategoryName = event?.settings?.donations_settings?.donations_category_name;
    const donationProductIds = new Set(
        event?.product_categories
            ?.filter((category) => category.name === donationsCategoryName)
            .flatMap((category) => category.products?.map((product) => Number(product.id)) || []) || []
    );
    const isDonationOrder = !!event?.settings?.donations_settings?.enabled
        && !!order?.order_items?.length
        && order.order_items.every((item) => donationProductIds.has(Number(item.product_id)));
    const donationCmiEnabled = isDonationOrder ? isCmiEnabled : false;
    const donationOfflineEnabled = isDonationOrder && donorType === 'COMPANY' ? isOfflineEnabled : false;
    const effectiveStripeEnabled = isDonationOrder ? false : isStripeEnabled;
    const effectiveCmiEnabled = isDonationOrder ? donationCmiEnabled : isCmiEnabled;
    const effectiveOfflineEnabled = isDonationOrder ? donationOfflineEnabled : isOfflineEnabled;

    React.useEffect(() => {
        // Automatically set the first available payment method
        if (effectiveStripeEnabled) {
            setActivePaymentMethod('STRIPE');
        } else if (effectiveCmiEnabled) {
            setActivePaymentMethod('CMI');
        } else if (effectiveOfflineEnabled) {
            setActivePaymentMethod('OFFLINE');
        } else {
            setActivePaymentMethod(null); // No methods available
        }
    }, [effectiveStripeEnabled, effectiveCmiEnabled, effectiveOfflineEnabled]);

    React.useEffect(() => {
        // Scroll to top when payment page loads
        window?.scrollTo(0, 0);
    }, []);

    const handleParentSubmit = () => {
        if (submitHandler) {
            setIsPaymentLoading(true);
            submitHandler().finally(() => setIsPaymentLoading(false));
        }
    };

    const handleSubmit = async () => {
        if (activePaymentMethod === 'STRIPE') {
            handleParentSubmit();
        } else if (activePaymentMethod === 'CMI') {
            setIsPaymentLoading(true);
            try {
                const response = await orderClientPublic.createCmiPaymentRequest(Number(eventId), String(orderShortId));
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = response.gateway_url;

                Object.entries(response.fields).forEach(([key, value]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = String(value);
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            } catch (error: any) {
                setIsPaymentLoading(false);
                showError(error.response?.data?.message || t`Unable to start CMI payment. Please try again.`);
            }
        } else if (activePaymentMethod === 'OFFLINE') {
            setIsPaymentLoading(true);

            await transitionOrderToOfflinePaymentMutation.mutateAsync({
                eventId,
                orderShortId
            }, {
                onSuccess: () => {
                    const totalCents = Math.round((order?.total_gross || 0) * 100);
                    trackEvent(AnalyticsEvents.PURCHASE_COMPLETED_OFFLINE, { value: totalCents });
                    navigate(`/checkout/${eventId}/${orderShortId}/summary`);
                },
                onError: (error: any) => {
                    setIsPaymentLoading(false);
                    showError(error.response?.data?.message || t`Offline payment failed. Please try again or contact the event organizer.`);
                }
            });
        }
    };

    if (!effectiveStripeEnabled && !effectiveCmiEnabled && !effectiveOfflineEnabled && isOrderFetched && isEventFetched) {
        return (
            <CheckoutContent>
                <Card>
                    {t`No payment methods are currently available. Please contact the event organizer for assistance.`}
                </Card>
            </CheckoutContent>
        );
    }

    return (
        <>
            <CheckoutContent>
                {(event && order) && (
                    <InlineOrderSummary event={event} order={order} defaultExpanded={false}/>
                )}
                {effectiveStripeEnabled && (
                    <div style={{display: activePaymentMethod === 'STRIPE' ? 'block' : 'none'}}>
                        <StripePaymentMethod enabled={true} setSubmitHandler={setSubmitHandler}/>
                    </div>
                )}

                {effectiveCmiEnabled && (
                    <div style={{display: activePaymentMethod === 'CMI' ? 'block' : 'none'}}>
                        <CmiPaymentMethod/>
                    </div>
                )}

                {effectiveOfflineEnabled && (
                    <div style={{display: activePaymentMethod === 'OFFLINE' ? 'block' : 'none'}}>
                        <OfflinePaymentMethod event={event as Event}/>
                    </div>
                )}

                {([effectiveStripeEnabled, effectiveCmiEnabled, effectiveOfflineEnabled].filter(Boolean).length > 1) && (
                    <div className={classes.paymentMethodSelector}>
                        <Text size="sm" c="dimmed" className={classes.paymentMethodLabel}>
                            {t`Payment method`}
                        </Text>
                        <div className={classes.paymentMethodTabs}>
                            {effectiveStripeEnabled && (
                                <button
                                    type="button"
                                    className={`${classes.paymentMethodTab} ${activePaymentMethod === 'STRIPE' ? classes.active : ''}`}
                                    onClick={() => setActivePaymentMethod('STRIPE')}
                                >
                                    <IconWallet size={18}/>
                                    <span>{t`Stripe`}</span>
                                </button>
                            )}
                            {effectiveCmiEnabled && (
                                <button
                                    type="button"
                                    className={`${classes.paymentMethodTab} ${activePaymentMethod === 'CMI' ? classes.active : ''}`}
                                    onClick={() => setActivePaymentMethod('CMI')}
                                >
                                    <IconWallet size={18}/>
                                    <span>{t`CMI`}</span>
                                </button>
                            )}
                            {effectiveOfflineEnabled && (
                                <button
                                    type="button"
                                    className={`${classes.paymentMethodTab} ${activePaymentMethod === 'OFFLINE' ? classes.active : ''}`}
                                    onClick={() => setActivePaymentMethod('OFFLINE')}
                                >
                                    <IconBuildingBank size={18}/>
                                    <span>{t`Offline`}</span>
                                </button>
                            )}
                        </div>
                    </div>
                )}

                <div className={classes.checkoutActions}>
                    <Button
                        className={classes.continueButton}
                        loading={isLoading || isPaymentLoading}
                        onClick={handleSubmit}
                    >
                        {order?.is_payment_required ? (
                            <Group gap={8} wrap="nowrap">
                                <IconLock size={16}/>
                                <Text fw={600}>{t`Pay`} {formatCurrency(order.total_gross, order.currency)}</Text>
                            </Group>
                        ) : t`Complete Payment`}
                    </Button>
                    {getConfig('VITE_TOS_URL') && (
                        <p className={classes.tosNotice}>
                            <Trans>
                                By continuing, you agree to the{' '}
                                <a
                                    href={getConfig('VITE_TOS_URL', 'https://hi.events/terms-of-service') as string}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    {getConfig('VITE_APP_NAME', 'Hi.Events')} Terms of Service
                                </a>
                            </Trans>
                        </p>
                    )}
                </div>
            </CheckoutContent>
        </>
    );
}

export default Payment;
