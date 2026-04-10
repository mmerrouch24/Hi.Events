import {useQuery} from "@tanstack/react-query";
import {orderClientPublic} from "../api/order.client.ts";
import {IdParam, Order} from "../types.ts";
import {GET_ORDER_PUBLIC_QUERY_KEY} from "./useGetOrderPublic.ts";
import {isSsr} from "../utilites/helpers.ts";

const getSessionIdentifierFromUrl = (): string | null => {
    if (isSsr()) return null;

    const url = new URL(window.location.href);
    return url.searchParams.get("session_identifier");
};

export const usePollGetOrderPublic = (eventId: IdParam, orderShortId: IdParam, enabled: boolean, includes: string[] = []) => {
    const sessionIdentifier = getSessionIdentifierFromUrl();

    return useQuery<Order>({
        queryKey: [GET_ORDER_PUBLIC_QUERY_KEY, eventId, orderShortId, sessionIdentifier],

        queryFn: async () => {
            const {data} = await orderClientPublic.findByShortId(
                Number(eventId),
                String(orderShortId),
                includes,
                sessionIdentifier ?? undefined,
            );
            return data;
        },

        refetchInterval: 5000,
        enabled: enabled
    });
}
