import {t} from "@lingui/macro";
import {Button, Group, Select, Stack, Switch, Text, TextInput, Title} from "@mantine/core";
import {useForm} from "@mantine/form";
import {useEffect} from "react";
import {Card} from "../../../../../common/Card";
import {showSuccess} from "../../../../../../utilites/notifications.tsx";
import {useFormErrorResponseHandler} from "../../../../../../hooks/useFormErrorResponseHandler.tsx";
import {Account, AccountCmiConfig} from "../../../../../../types.ts";
import {useGetAccountCmiConfig} from "../../../../../../queries/useGetAccountCmiConfig.ts";
import {useUpdateAccountCmiConfig} from "../../../../../../mutations/useUpdateAccountCmiConfig.ts";

export const CmiSettings = ({account}: { account: Account }) => {
    const cmiConfigQuery = useGetAccountCmiConfig(account.id);
    const updateMutation = useUpdateAccountCmiConfig(account.id);
    const formErrorHandler = useFormErrorResponseHandler();

    const form = useForm<AccountCmiConfig>({
        initialValues: {
            merchant_id: '',
            store_key: '',
            gateway_url: 'https://payment.cmi.co.ma/fim/est3Dgate',
            currency_numeric_code: '504',
            language: 'fr',
            transaction_type: 'PreAuth',
            store_type: '3D_PAY_HOSTING',
            hash_algorithm: 'ver3',
            auto_redirect: true,
            is_enabled: true,
            mode: 'live',
            bill_to_company: '',
            bill_to_street1: '',
            bill_to_city: '',
            bill_to_state_prov: '',
            bill_to_postal_code: '',
            bill_to_country: '504',
        },
    });

    useEffect(() => {
        if (cmiConfigQuery.data) {
            form.setValues({
                ...form.values,
                ...cmiConfigQuery.data,
                store_key: '',
            });
        }
    }, [cmiConfigQuery.data]);

    const handleSubmit = (values: AccountCmiConfig) => {
        updateMutation.mutate({
            config: values,
        }, {
            onSuccess: () => {
                showSuccess(t`CMI settings updated successfully`);
                cmiConfigQuery.refetch();
                form.setFieldValue('store_key', '');
            },
            onError: (error) => {
                formErrorHandler(form as any, error);
            }
        });
    };

    return (
        <Card variant="lightGray">
            <Title order={3} mb="xs">{t`CMI`}</Title>
            <Text size="sm" c="dimmed" mb="lg">
                {t`Configure your hosted CMI checkout credentials. Buyers will be redirected to CMI to complete payment.`}
            </Text>
            <form onSubmit={form.onSubmit(handleSubmit)}>
                <Stack>
                    <TextInput label={t`Merchant ID`} {...form.getInputProps('merchant_id')} />
                    <TextInput
                        label={t`Store Key`}
                        description={cmiConfigQuery.data ? t`Leave blank to keep your existing stored key.` : undefined}
                        {...form.getInputProps('store_key')}
                    />
                    <TextInput label={t`Gateway URL`} {...form.getInputProps('gateway_url')} />
                    <Group grow align="start">
                        <TextInput label={t`Currency Code`} {...form.getInputProps('currency_numeric_code')} />
                        <TextInput label={t`Language`} {...form.getInputProps('language')} />
                        <Select
                            label={t`Mode`}
                            data={[
                                {value: 'live', label: t`Live`},
                                {value: 'test', label: t`Test`},
                            ]}
                            value={form.values.mode}
                            error={form.errors.mode}
                            onChange={(value) => form.setFieldValue('mode', (value || 'live') as 'test' | 'live')}
                        />
                    </Group>
                    <Group grow align="start">
                        <TextInput label={t`Transaction Type`} {...form.getInputProps('transaction_type')} />
                        <TextInput label={t`Store Type`} {...form.getInputProps('store_type')} />
                        <TextInput label={t`Hash Algorithm`} {...form.getInputProps('hash_algorithm')} />
                    </Group>
                    <Group grow align="start">
                        <TextInput label={t`Billing Company`} {...form.getInputProps('bill_to_company')} />
                        <TextInput label={t`Billing Street`} {...form.getInputProps('bill_to_street1')} />
                    </Group>
                    <Group grow align="start">
                        <TextInput label={t`Billing City`} {...form.getInputProps('bill_to_city')} />
                        <TextInput label={t`Billing State/Province`} {...form.getInputProps('bill_to_state_prov')} />
                    </Group>
                    <Group grow align="start">
                        <TextInput label={t`Billing Postal Code`} {...form.getInputProps('bill_to_postal_code')} />
                        <TextInput label={t`Billing Country Code`} {...form.getInputProps('bill_to_country')} />
                    </Group>
                    <Switch
                        label={t`Auto-redirect buyers back to checkout`}
                        checked={form.values.auto_redirect}
                        {...form.getInputProps('auto_redirect', {type: 'checkbox'})}
                    />
                    <Switch
                        label={t`Enable CMI`}
                        checked={form.values.is_enabled}
                        {...form.getInputProps('is_enabled', {type: 'checkbox'})}
                    />
                    <Button type="submit" loading={updateMutation.isPending}>
                        {t`Save CMI Settings`}
                    </Button>
                </Stack>
            </form>
        </Card>
    );
};
