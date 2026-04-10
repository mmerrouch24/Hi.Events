import {useQuery} from "@tanstack/react-query";
import {accountClient} from "../api/account.client.ts";
import {AccountCmiConfig, IdParam} from "../types.ts";

export const GET_ACCOUNT_CMI_CONFIG_QUERY_KEY = 'getAccountCmiConfig';

export const useGetAccountCmiConfig = (accountId: IdParam, enabled = true) => {
    return useQuery<AccountCmiConfig | null>({
        queryKey: [GET_ACCOUNT_CMI_CONFIG_QUERY_KEY, accountId],
        queryFn: async () => {
            const {data} = await accountClient.getCmiConfig(accountId);
            return data;
        },
        enabled: enabled && !!accountId,
    });
};
