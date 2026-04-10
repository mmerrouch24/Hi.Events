import {useMemo, useState} from "react";
import {useParams} from "react-router";
import {useGetEvent} from "../../../../../queries/useGetEvent.ts";
import {useGetDonationsReport} from "../../../../../queries/useGetDonationsReport.ts";
import {Card} from "../../../../common/Card";
import {PageTitle} from "../../../../common/PageTitle";
import {formatCurrency} from "../../../../../utilites/currency.ts";
import {Badge, Group, Progress, SimpleGrid, Skeleton, Switch, Table, Text} from "@mantine/core";
import {PieChart} from "@mantine/charts";
import {t} from "@lingui/macro";

const CHART_COLORS = ['#0f766e', '#d97706', '#2563eb', '#be123c', '#7c3aed', '#0891b2'];

const getPercentage = (value: number, total: number) => {
    if (total <= 0) {
        return 0;
    }

    return Number(((value / total) * 100).toFixed(1));
};

const DonationsReport = () => {
    const {eventId} = useParams();
    const [realtimeEnabled, setRealtimeEnabled] = useState(false);
    const eventQuery = useGetEvent(eventId);
    const reportQuery = useGetDonationsReport(eventId, realtimeEnabled);
    const event = eventQuery.data;
    const report = reportQuery.data;

    const graduationChartData = useMemo(() => {
        return report?.by_graduation_year.map((row, index) => ({
            name: row.graduation_year,
            value: row.donation_count,
            color: CHART_COLORS[index % CHART_COLORS.length],
        })) || [];
    }, [report]);

    const graduationTotal = useMemo(
        () => graduationChartData.reduce((total, row) => total + row.value, 0),
        [graduationChartData]
    );

    const tableChartData = useMemo(() => {
        return report?.by_table.map((row, index) => ({
            name: row.table_number,
            value: row.donation_count,
            color: CHART_COLORS[index % CHART_COLORS.length],
        })) || [];
    }, [report]);

    const tableTotal = useMemo(
        () => tableChartData.reduce((total, row) => total + row.value, 0),
        [tableChartData]
    );

    if (!event || eventQuery.isLoading || reportQuery.isLoading || !report) {
        return (
            <>
                <Skeleton height={40} mb="md"/>
                <Skeleton height={180} mb="md"/>
                <Skeleton height={300}/>
            </>
        );
    }

    const summary = report.summary;
    const currency = event.currency;

    return (
        <>
            <Group justify="space-between" align="flex-start" mb="md">
                <PageTitle>{t`Donations Report`}</PageTitle>
                <Switch
                    checked={realtimeEnabled}
                    onChange={(event) => setRealtimeEnabled(event.currentTarget.checked)}
                    label={t`Real-time updates`}
                />
            </Group>

            <Card style={{marginBottom: '1rem'}}>
                <Group justify="space-between" mb="xs">
                    <Text fw={600}>{t`Fundraising progress`}</Text>
                    <Text>
                        {formatCurrency(summary.total_amount, currency)} / {formatCurrency(summary.goal_amount || 0, currency)}
                    </Text>
                </Group>
                <Progress value={Math.min(summary.progress_percentage || 0, 100)} size="xl" radius="xl" mb="xs"/>
                <Text size="sm" c="dimmed">
                    {summary.progress_percentage === null ? t`Set a donation goal in event settings to track progress.` : `${summary.progress_percentage}%`}
                </Text>
            </Card>

            <SimpleGrid cols={{base: 1, md: 3}} style={{marginBottom: '1rem'}}>
                <Card>
                    <Text size="sm" c="dimmed">{t`Total donations`}</Text>
                    <Text fw={700} size="xl">{formatCurrency(summary.total_amount, currency)}</Text>
                </Card>
                <Card>
                    <Text size="sm" c="dimmed">{t`Donation count`}</Text>
                    <Text fw={700} size="xl">{summary.donation_count}</Text>
                </Card>
                <Card>
                    <Text size="sm" c="dimmed">{t`Offline pending amount`}</Text>
                    <Text fw={700} size="xl">{formatCurrency(summary.offline_pending_amount, currency)}</Text>
                </Card>
            </SimpleGrid>

            <SimpleGrid cols={{base: 1, lg: 2}} style={{marginBottom: '1rem'}}>
                <Card>
                    <Text fw={600} mb="md">{t`Donations by graduation year`}</Text>
                    {graduationChartData.length ? (
                        <>
                            <PieChart data={graduationChartData}/>
                            <Table mt="md" striped>
                                <Table.Thead>
                                    <Table.Tr>
                                        <Table.Th>{t`Color`}</Table.Th>
                                        <Table.Th>{t`Graduation Year`}</Table.Th>
                                        <Table.Th>{t`Donations`}</Table.Th>
                                        <Table.Th>{t`Percent`}</Table.Th>
                                    </Table.Tr>
                                </Table.Thead>
                                <Table.Tbody>
                                    {graduationChartData.map((row) => (
                                        <Table.Tr key={row.name}>
                                            <Table.Td>
                                                <div style={{
                                                    width: 14,
                                                    height: 14,
                                                    borderRadius: 999,
                                                    backgroundColor: row.color,
                                                }}/>
                                            </Table.Td>
                                            <Table.Td>{row.name}</Table.Td>
                                            <Table.Td>{row.value}</Table.Td>
                                            <Table.Td>{getPercentage(row.value, graduationTotal)}%</Table.Td>
                                        </Table.Tr>
                                    ))}
                                </Table.Tbody>
                            </Table>
                        </>
                    ) : (
                        <Text c="dimmed">{t`No graduation year donations yet.`}</Text>
                    )}
                </Card>
                <Card>
                    <Text fw={600} mb="md">{t`Donations by table`}</Text>
                    {tableChartData.length ? (
                        <>
                            <PieChart data={tableChartData}/>
                            <Table mt="md" striped>
                                <Table.Thead>
                                    <Table.Tr>
                                        <Table.Th>{t`Color`}</Table.Th>
                                        <Table.Th>{t`Table`}</Table.Th>
                                        <Table.Th>{t`Donations`}</Table.Th>
                                        <Table.Th>{t`Percent`}</Table.Th>
                                    </Table.Tr>
                                </Table.Thead>
                                <Table.Tbody>
                                    {tableChartData.map((row) => (
                                        <Table.Tr key={row.name}>
                                            <Table.Td>
                                                <div style={{
                                                    width: 14,
                                                    height: 14,
                                                    borderRadius: 999,
                                                    backgroundColor: row.color,
                                                }}/>
                                            </Table.Td>
                                            <Table.Td>{row.name}</Table.Td>
                                            <Table.Td>{row.value}</Table.Td>
                                            <Table.Td>{getPercentage(row.value, tableTotal)}%</Table.Td>
                                        </Table.Tr>
                                    ))}
                                </Table.Tbody>
                            </Table>
                        </>
                    ) : (
                        <Text c="dimmed">{t`No table donations yet.`}</Text>
                    )}
                </Card>
            </SimpleGrid>

            <SimpleGrid cols={{base: 1, lg: 2}} style={{marginBottom: '1rem'}}>
                <Card>
                    <Text fw={600} mb="md">{t`Graduation year leaderboard`}</Text>
                    <Table striped highlightOnHover>
                        <Table.Thead>
                            <Table.Tr>
                                <Table.Th>{t`Graduation Year`}</Table.Th>
                                <Table.Th>{t`Donations`}</Table.Th>
                                <Table.Th>{t`Total`}</Table.Th>
                            </Table.Tr>
                        </Table.Thead>
                        <Table.Tbody>
                            {report.by_graduation_year.map((row) => (
                                <Table.Tr key={row.graduation_year}>
                                    <Table.Td>{row.graduation_year}</Table.Td>
                                    <Table.Td>{row.donation_count}</Table.Td>
                                    <Table.Td>{formatCurrency(row.total_amount, currency)}</Table.Td>
                                </Table.Tr>
                            ))}
                        </Table.Tbody>
                    </Table>
                </Card>

                <Card>
                    <Text fw={600} mb="md">{t`Table leaderboard`}</Text>
                    <Table striped highlightOnHover>
                        <Table.Thead>
                            <Table.Tr>
                                <Table.Th>{t`Table`}</Table.Th>
                                <Table.Th>{t`Donations`}</Table.Th>
                                <Table.Th>{t`Total`}</Table.Th>
                                <Table.Th>{t`Completion`}</Table.Th>
                            </Table.Tr>
                        </Table.Thead>
                        <Table.Tbody>
                            {report.by_table.map((row) => (
                                <Table.Tr key={row.table_number}>
                                    <Table.Td>{row.table_number}</Table.Td>
                                    <Table.Td>{row.donation_count}</Table.Td>
                                    <Table.Td>{formatCurrency(row.total_amount, currency)}</Table.Td>
                                    <Table.Td>{row.completion_percentage === null ? '—' : `${row.completion_percentage}%`}</Table.Td>
                                </Table.Tr>
                            ))}
                        </Table.Tbody>
                    </Table>
                </Card>
            </SimpleGrid>

            <Card>
                <Group justify="space-between" mb="md">
                    <Text fw={600}>{t`Messages to the future student`}</Text>
                    <Badge variant="light">{report.messages.length}</Badge>
                </Group>
                {report.messages.length ? (
                    <Table striped highlightOnHover>
                        <Table.Thead>
                            <Table.Tr>
                                <Table.Th>{t`Donor Type`}</Table.Th>
                                <Table.Th>{t`Message`}</Table.Th>
                            </Table.Tr>
                        </Table.Thead>
                        <Table.Tbody>
                            {report.messages.map((message, index) => (
                                <Table.Tr key={`${message.donor_type}-${index}`}>
                                    <Table.Td>
                                        <Badge variant="outline">
                                            {message.donor_type === 'COMPANY' ? t`Company` : t`Alumni`}
                                        </Badge>
                                    </Table.Td>
                                    <Table.Td>{message.message}</Table.Td>
                                </Table.Tr>
                            ))}
                        </Table.Tbody>
                    </Table>
                ) : (
                    <Text c="dimmed">{t`No support messages have been left yet.`}</Text>
                )}
            </Card>
        </>
    );
};

export default DonationsReport;
