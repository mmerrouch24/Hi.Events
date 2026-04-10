import {useMutation} from "@tanstack/react-query";
import {accountClient} from "../api/account.client.ts";
import {AccountCmiConfig, IdParam} from "../types.ts";

export const useUpdateAccountCmiConfig = (accountId: IdParam) => {
    return useMutation({
        mutationFn: ({config}: { config: AccountCmiConfig }) => accountClient.updateCmiConfig(accountId, config),
    });
};
