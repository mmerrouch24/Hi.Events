import {api} from "./client.ts";
import {Account, AccountCmiConfig, GenericDataResponse, IdParam, User, StripeConnectAccountsResponse} from "../types.ts";

interface CreateAccountRequest {
    first_name: string;
    last_name: string;
    email: string;
    password?: string;
}

export const accountClient = {
    create: async (account: CreateAccountRequest) => {
        const response = await api.post<GenericDataResponse<User>>('accounts', account);
        return response.data;
    },
    getAccount: async () => {
        const response = await api.get<GenericDataResponse<Account>>('accounts');
        return response.data;
    },
    updateAccount: async (account: Account) => {
        const response = await api.put<GenericDataResponse<Account>>('accounts', account);
        return response.data;
    },
    getStripeConnectDetails: async (accountId: IdParam, platform?: string) => {
        const response = await api.post<GenericDataResponse<any>>(`accounts/${accountId}/stripe/connect`, {
            platform
        });
        return response.data;
    },
    getStripeConnectAccounts: async (accountId: IdParam) => {
        const response = await api.get<GenericDataResponse<StripeConnectAccountsResponse>>(`accounts/${accountId}/stripe/connect_accounts`);
        return response.data;
    },
    getCmiConfig: async (accountId: IdParam) => {
        const response = await api.get<GenericDataResponse<AccountCmiConfig | null>>(`accounts/${accountId}/cmi-config`);
        return response.data;
    },
    updateCmiConfig: async (accountId: IdParam, config: AccountCmiConfig) => {
        const response = await api.put<GenericDataResponse<AccountCmiConfig>>(`accounts/${accountId}/cmi-config`, config);
        return response.data;
    },
}
