import {t} from "@lingui/macro";
import {Button, Divider, NumberInput, Select, Switch, TextInput} from "@mantine/core";
import {useForm} from "@mantine/form";
import {useParams} from "react-router";
import {useEffect} from "react";
import {EventSettings, QuestionBelongsToType} from "../../../../../../types.ts";
import {Card} from "../../../../../common/Card";
import {showSuccess} from "../../../../../../utilites/notifications.tsx";
import {useFormErrorResponseHandler} from "../../../../../../hooks/useFormErrorResponseHandler.tsx";
import {useUpdateEventSettings} from "../../../../../../mutations/useUpdateEventSettings.ts";
import {useGetEventSettings} from "../../../../../../queries/useGetEventSettings.ts";
import {HeadingWithDescription} from "../../../../../common/Card/CardHeading";
import {CustomSelect, ItemProps} from "../../../../../common/CustomSelect";
import {IconCoin, IconCoins} from "@tabler/icons-react";
import {SelfServiceSettings} from "../../../../../common/SelfServiceSettings";
import {useGetEventQuestions} from "../../../../../../queries/useGetEventQuestions.ts";

export const MiscSettings = () => {
    const {eventId} = useParams();
    const eventSettingsQuery = useGetEventSettings(eventId);
    const eventQuestionsQuery = useGetEventQuestions(eventId);
    const updateMutation = useUpdateEventSettings();
    const form = useForm({
        initialValues: {
            price_display_mode: 'EXCLUSIVE',
            hide_getting_started_page: false,
            allow_attendee_self_edit: false,
            donations_settings: {
                enabled: false,
                donations_category_name: 'Donations',
                global_goal_amount: 0,
                table_goal_amount: 0,
                graduation_year_question_id: null,
                alumni_table_number_question_id: null,
                company_name_question_id: null,
                company_contact_phone_question_id: null,
                company_table_number_question_id: null,
                support_message_question_id: null,
            },
        }
    });
    const formErrorHandle = useFormErrorResponseHandler();

    useEffect(() => {
        if (eventSettingsQuery?.isFetched && eventSettingsQuery?.data) {
            form.setValues({
                price_display_mode: eventSettingsQuery.data.price_display_mode,
                hide_getting_started_page: eventSettingsQuery.data.hide_getting_started_page,
                allow_attendee_self_edit: eventSettingsQuery.data.allow_attendee_self_edit ?? false,
                donations_settings: {
                    enabled: eventSettingsQuery.data.donations_settings?.enabled ?? false,
                    donations_category_name: eventSettingsQuery.data.donations_settings?.donations_category_name || 'Donations',
                    global_goal_amount: eventSettingsQuery.data.donations_settings?.global_goal_amount ?? 0,
                    table_goal_amount: eventSettingsQuery.data.donations_settings?.table_goal_amount ?? 0,
                    graduation_year_question_id: eventSettingsQuery.data.donations_settings?.graduation_year_question_id ?? null,
                    alumni_table_number_question_id: eventSettingsQuery.data.donations_settings?.alumni_table_number_question_id ?? null,
                    company_name_question_id: eventSettingsQuery.data.donations_settings?.company_name_question_id ?? null,
                    company_contact_phone_question_id: eventSettingsQuery.data.donations_settings?.company_contact_phone_question_id ?? null,
                    company_table_number_question_id: eventSettingsQuery.data.donations_settings?.company_table_number_question_id ?? null,
                    support_message_question_id: eventSettingsQuery.data.donations_settings?.support_message_question_id ?? null,
                },
            });
        }
    }, [eventSettingsQuery.isFetched]);

    const handleSubmit = (values: Partial<EventSettings>) => {
        updateMutation.mutate({
            eventSettings: values,
            eventId: eventId,
        }, {
            onSuccess: () => {
                showSuccess(t`Successfully Updated Misc Settings`);
            },
            onError: (error) => {
                formErrorHandle(form, error);
            }
        });
    }

    const priceOptions: ItemProps[] = [
        {
            icon: <IconCoins/>,
            label: t`Include tax and fees in the price`,
            value: 'INCLUSIVE',
            description: t`The price displayed to the customer will include taxes and fees.`,
        },
        {
            icon: <IconCoin/>,
            label: t`Show tax and fees separately`,
            value: 'EXCLUSIVE',
            description: t`The price displayed to the customer will not include taxes and fees. They will be shown separately`,
        },
    ];

    const orderQuestions = eventQuestionsQuery.data?.filter((question) => question.belongs_to === QuestionBelongsToType.ORDER) || [];
    const questionOptions = orderQuestions.map((question) => ({
        value: String(question.id),
        label: `${question.title} (#${question.id})`,
    }));

    return (
        <Card>
            <HeadingWithDescription
                heading={t`Miscellaneous Settings`}
                description={t`Customize the miscellaneous settings for this event`}
            />
            <form onSubmit={form.onSubmit(handleSubmit as any)}>
                <fieldset disabled={eventSettingsQuery.isLoading || updateMutation.isPending}>
                    <CustomSelect
                        optionList={priceOptions}
                        form={form}
                        name={'price_display_mode'}
                        label={t`Price display mode`}
                        required
                    />
                    {form.errors['price_display_mode'] && (
                        <div style={{color: 'red'}}>
                            {form.errors['price_display_mode']}
                        </div>
                    )}

                    <Switch
                        {...form.getInputProps('hide_getting_started_page', {type: 'checkbox'})}
                        label={t`Hide getting started page`}
                        description={t`Hide the getting started page from the sidebar`}
                    />

                    <SelfServiceSettings
                        value={form.values.allow_attendee_self_edit}
                        onChange={(value) => form.setFieldValue('allow_attendee_self_edit', value)}
                    />

                    <Divider my="lg"/>

                    <HeadingWithDescription
                        heading={t`Donations Settings`}
                        description={t`Configure the fundraising category, goals, and mapped checkout questions for donation reporting.`}
                    />

                    <Switch
                        {...form.getInputProps('donations_settings.enabled', {type: 'checkbox'})}
                        label={t`Enable donations mode`}
                        description={t`When enabled, donation-category carts get the specialized gala checkout and reports.`}
                        mb="md"
                    />

                    <TextInput
                        {...form.getInputProps('donations_settings.donations_category_name')}
                        label={t`Donations category name`}
                        placeholder={t`Donations`}
                        mb="md"
                    />

                    <NumberInput
                        {...form.getInputProps('donations_settings.global_goal_amount')}
                        label={t`Global donations goal`}
                        min={0}
                        decimalScale={2}
                        fixedDecimalScale
                        mb="md"
                    />

                    <NumberInput
                        {...form.getInputProps('donations_settings.table_goal_amount')}
                        label={t`Table goal`}
                        min={0}
                        decimalScale={2}
                        fixedDecimalScale
                        mb="md"
                    />

                    <Select
                        clearable
                        searchable
                        data={questionOptions}
                        label={t`Graduation year question`}
                        value={form.values.donations_settings.graduation_year_question_id?.toString() || null}
                        onChange={(value) => form.setFieldValue('donations_settings.graduation_year_question_id', value ? Number(value) : null)}
                        mb="md"
                    />

                    <Select
                        clearable
                        searchable
                        data={questionOptions}
                        label={t`Alumni table number question`}
                        value={form.values.donations_settings.alumni_table_number_question_id?.toString() || null}
                        onChange={(value) => form.setFieldValue('donations_settings.alumni_table_number_question_id', value ? Number(value) : null)}
                        mb="md"
                    />

                    <Select
                        clearable
                        searchable
                        data={questionOptions}
                        label={t`Company name question`}
                        value={form.values.donations_settings.company_name_question_id?.toString() || null}
                        onChange={(value) => form.setFieldValue('donations_settings.company_name_question_id', value ? Number(value) : null)}
                        mb="md"
                    />

                    <Select
                        clearable
                        searchable
                        data={questionOptions}
                        label={t`Company contact phone question`}
                        value={form.values.donations_settings.company_contact_phone_question_id?.toString() || null}
                        onChange={(value) => form.setFieldValue('donations_settings.company_contact_phone_question_id', value ? Number(value) : null)}
                        mb="md"
                    />

                    <Select
                        clearable
                        searchable
                        data={questionOptions}
                        label={t`Company table number question`}
                        value={form.values.donations_settings.company_table_number_question_id?.toString() || null}
                        onChange={(value) => form.setFieldValue('donations_settings.company_table_number_question_id', value ? Number(value) : null)}
                        mb="md"
                    />

                    <Select
                        clearable
                        searchable
                        data={questionOptions}
                        label={t`Support message question`}
                        value={form.values.donations_settings.support_message_question_id?.toString() || null}
                        onChange={(value) => form.setFieldValue('donations_settings.support_message_question_id', value ? Number(value) : null)}
                        mb="md"
                    />

                    <Button loading={updateMutation.isPending} type={'submit'}>
                        {t`Save`}
                    </Button>
                </fieldset>
            </form>
        </Card>
    );
}
