import {useMutation} from "@tanstack/react-query";
import {IdParam} from "../types.ts";
import {attendeesClient} from "../api/attendee.client.ts";

export const useResendAllAttendeeTickets = () => {
    return useMutation({
        mutationFn: ({eventId}: {
            eventId: IdParam;
        }) => attendeesClient.resendAllTickets(eventId)
    });
}
