import {useQuery} from "@tanstack/react-query";
import {IdParam} from "../types.ts";
import {eventsClient} from "../api/event.client.ts";

export const GET_DONATIONS_REPORT_QUERY_KEY = 'getDonationsReport';

export const useGetDonationsReport = (
    eventId: IdParam,
    realtimeEnabled: boolean,
) => {
    return useQuery({
        queryKey: [GET_DONATIONS_REPORT_QUERY_KEY, eventId],
        queryFn: async () => {
            const response = await eventsClient.getDonationsReport(eventId);
            return response.data;
        },
        enabled: !!eventId,
        refetchInterval: realtimeEnabled ? 10000 : false,
    });
};
