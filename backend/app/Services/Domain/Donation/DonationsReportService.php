<?php

namespace HiEvents\Services\Domain\Donation;

use HiEvents\DomainObjects\Status\OrderStatus;
use HiEvents\Models\EventSetting;
use HiEvents\Models\Order;
use HiEvents\Models\Product;
use HiEvents\Models\ProductCategory;
use HiEvents\Models\QuestionAndAnswerView;

class DonationsReportService
{
    public function __construct(
        private readonly DonationSettingsService $donationSettingsService,
    ) {
    }

    public function generate(int $eventId): array
    {
        /** @var EventSetting|null $eventSettings */
        $eventSettings = EventSetting::query()->where('event_id', $eventId)->first();
        $settings = $this->donationSettingsService->getDonationSettings($eventSettings?->donations_settings);

        $globalGoal = (float)($settings['global_goal_amount'] ?? 0);
        $tableGoal = (float)($settings['table_goal_amount'] ?? 0);
        $categoryName = trim((string)($settings['donations_category_name'] ?? ''));

        if (!($settings['enabled'] ?? false) || $categoryName === '') {
            return $this->emptyPayload($globalGoal, $tableGoal);
        }

        $category = ProductCategory::query()
            ->where('event_id', $eventId)
            ->where('name', $categoryName)
            ->first();

        if (!$category instanceof ProductCategory) {
            return $this->emptyPayload($globalGoal, $tableGoal);
        }

        $productIds = Product::query()
            ->where('event_id', $eventId)
            ->where('product_category_id', $category->id)
            ->pluck('id')
            ->all();

        if (empty($productIds)) {
            return $this->emptyPayload($globalGoal, $tableGoal);
        }

        $orders = Order::query()
            ->selectRaw('orders.id, orders.status, orders.point_in_time_data, SUM(order_items.total_gross) as donation_amount')
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.event_id', $eventId)
            ->whereIn('orders.status', [OrderStatus::COMPLETED->name, OrderStatus::AWAITING_OFFLINE_PAYMENT->name])
            ->whereIn('order_items.product_id', $productIds)
            ->whereNull('orders.deleted_at')
            ->whereNull('order_items.deleted_at')
            ->groupBy('orders.id', 'orders.status', 'orders.point_in_time_data')
            ->get();

        if ($orders->isEmpty()) {
            return $this->emptyPayload($globalGoal, $tableGoal);
        }

        $orderIds = $orders->pluck('id')->all();
        $questionIds = $this->donationSettingsService->getConfiguredQuestionIds($settings);

        $answersByOrder = [];
        if (!empty($questionIds)) {
            $answers = QuestionAndAnswerView::query()
                ->whereIn('order_id', $orderIds)
                ->whereIn('question_id', $questionIds)
                ->get(['order_id', 'question_id', 'answer']);

            foreach ($answers as $answer) {
                $answersByOrder[$answer->order_id][$answer->question_id] =
                    $this->donationSettingsService->normalizeAnswer($answer->answer);
            }
        }

        $byGraduationYear = [];
        $byTable = [];
        $messages = [];
        $totalAmount = 0.0;
        $completedAmount = 0.0;
        $offlinePendingAmount = 0.0;
        $donationCount = 0;

        foreach ($orders as $order) {
            $amount = (float)$order->donation_amount;
            $donationCount++;
            $totalAmount += $amount;

            if ($order->status === OrderStatus::COMPLETED->name) {
                $completedAmount += $amount;
            } else {
                $offlinePendingAmount += $amount;
            }

            $orderAnswers = $answersByOrder[$order->id] ?? [];
            $donorType = $this->donationSettingsService->getDonorTypeFromPointInTimeData($order->point_in_time_data);
            if ($donorType === null && !empty($orderAnswers[(int)($settings['company_name_question_id'] ?? 0)] ?? null)) {
                $donorType = DonationSettingsService::DONOR_TYPE_COMPANY;
            }
            $donorType ??= DonationSettingsService::DONOR_TYPE_ALUMNI;

            $graduationYear = $donorType === DonationSettingsService::DONOR_TYPE_ALUMNI
                ? ($orderAnswers[(int)($settings['graduation_year_question_id'] ?? 0)] ?? null)
                : null;
            if ($graduationYear) {
                $byGraduationYear[$graduationYear] ??= ['graduation_year' => $graduationYear, 'donation_count' => 0, 'total_amount' => 0.0];
                $byGraduationYear[$graduationYear]['donation_count']++;
                $byGraduationYear[$graduationYear]['total_amount'] += $amount;
            }

            $tableQuestionId = $donorType === DonationSettingsService::DONOR_TYPE_COMPANY
                ? (int)($settings['company_table_number_question_id'] ?? 0)
                : (int)($settings['alumni_table_number_question_id'] ?? 0);
            $tableNumber = $orderAnswers[$tableQuestionId] ?? null;
            if ($tableNumber) {
                $byTable[$tableNumber] ??= ['table_number' => $tableNumber, 'donation_count' => 0, 'total_amount' => 0.0];
                $byTable[$tableNumber]['donation_count']++;
                $byTable[$tableNumber]['total_amount'] += $amount;
            }

            $message = $orderAnswers[(int)($settings['support_message_question_id'] ?? 0)] ?? null;
            if ($message) {
                $messages[] = [
                    'message' => $message,
                    'donor_type' => $donorType,
                ];
            }
        }

        $graduationRows = array_values($byGraduationYear);
        usort($graduationRows, fn($a, $b) => $b['total_amount'] <=> $a['total_amount']);

        $tableRows = array_values($byTable);
        usort($tableRows, fn($a, $b) => $b['total_amount'] <=> $a['total_amount']);
        $tableRows = array_map(function (array $row) use ($tableGoal) {
            $row['completion_percentage'] = $tableGoal > 0
                ? round(($row['total_amount'] / $tableGoal) * 100, 2)
                : null;

            return $row;
        }, $tableRows);

        return [
            'summary' => [
                'goal_amount' => $globalGoal,
                'table_goal_amount' => $tableGoal,
                'total_amount' => round($totalAmount, 2),
                'progress_percentage' => $globalGoal > 0 ? round(($totalAmount / $globalGoal) * 100, 2) : null,
                'donation_count' => $donationCount,
                'completed_amount' => round($completedAmount, 2),
                'offline_pending_amount' => round($offlinePendingAmount, 2),
            ],
            'by_graduation_year' => array_map(fn($row) => [
                ...$row,
                'total_amount' => round($row['total_amount'], 2),
            ], $graduationRows),
            'by_table' => array_map(fn($row) => [
                ...$row,
                'total_amount' => round($row['total_amount'], 2),
            ], $tableRows),
            'messages' => $messages,
            'updated_at' => now()->toIso8601String(),
        ];
    }

    private function emptyPayload(float $globalGoal, float $tableGoal): array
    {
        return [
            'summary' => [
                'goal_amount' => $globalGoal,
                'table_goal_amount' => $tableGoal,
                'total_amount' => 0,
                'progress_percentage' => $globalGoal > 0 ? 0 : null,
                'donation_count' => 0,
                'completed_amount' => 0,
                'offline_pending_amount' => 0,
            ],
            'by_graduation_year' => [],
            'by_table' => [],
            'messages' => [],
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
