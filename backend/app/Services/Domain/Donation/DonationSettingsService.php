<?php

namespace HiEvents\Services\Domain\Donation;

use HiEvents\DomainObjects\Enums\PaymentProviders;
use HiEvents\DomainObjects\Enums\QuestionBelongsTo;
use HiEvents\DomainObjects\EventSettingDomainObject;
use HiEvents\Repository\Interfaces\ProductCategoryRepositoryInterface;
use HiEvents\Repository\Interfaces\ProductRepositoryInterface;
use HiEvents\Repository\Interfaces\QuestionRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DonationSettingsService
{
    public const DONOR_TYPE_ALUMNI = 'ALUMNI';
    public const DONOR_TYPE_COMPANY = 'COMPANY';

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductCategoryRepositoryInterface $productCategoryRepository,
        private readonly QuestionRepositoryInterface $questionRepository,
    ) {
    }

    public function getDonationSettings(array|EventSettingDomainObject|null $settings): array
    {
        if ($settings instanceof EventSettingDomainObject) {
            return $settings->getDonationsSettings() ?? [];
        }

        return is_array($settings) ? $settings : [];
    }

    public function isDonationModeEnabled(array|EventSettingDomainObject|null $settings): bool
    {
        $donationSettings = $this->getDonationSettings($settings);

        return (bool)($donationSettings['enabled'] ?? false)
            && !empty($donationSettings['donations_category_name']);
    }

    public function getConfiguredQuestionIds(array|EventSettingDomainObject|null $settings): array
    {
        $donationSettings = $this->getDonationSettings($settings);

        return collect([
            $donationSettings['graduation_year_question_id'] ?? null,
            $donationSettings['alumni_table_number_question_id'] ?? null,
            $donationSettings['company_name_question_id'] ?? null,
            $donationSettings['company_contact_phone_question_id'] ?? null,
            $donationSettings['company_table_number_question_id'] ?? null,
            $donationSettings['support_message_question_id'] ?? null,
        ])->filter()->map(fn($id) => (int)$id)->unique()->values()->all();
    }

    public function validateDonationSettings(int $eventId, ?array $settings): void
    {
        if (!$settings || !($settings['enabled'] ?? false)) {
            return;
        }

        $questionIds = $this->getConfiguredQuestionIds($settings);
        if (empty($questionIds)) {
            return;
        }

        $questions = $this->questionRepository->findWhereIn('id', $questionIds);
        $validQuestionIds = $questions
            ->filter(fn($question) => $question->getEventId() === $eventId
                && $question->getBelongsTo() === QuestionBelongsTo::ORDER->name)
            ->map(fn($question) => $question->getId())
            ->all();

        $invalidQuestionIds = array_values(array_diff($questionIds, $validQuestionIds));

        if (!empty($invalidQuestionIds)) {
            throw ValidationException::withMessages([
                'donations_settings' => __('Donation question mappings must reference order questions from this event.'),
            ]);
        }
    }

    public function isDonationOrder(int $eventId, array $productIds, array|EventSettingDomainObject|null $settings): bool
    {
        if (!$this->isDonationModeEnabled($settings) || empty($productIds)) {
            return false;
        }

        $donationSettings = $this->getDonationSettings($settings);
        $categoryName = trim((string)($donationSettings['donations_category_name'] ?? ''));

        if ($categoryName === '') {
            return false;
        }

        $products = $this->productRepository->findWhereIn('id', array_values(array_unique($productIds)));
        if ($products->isEmpty() || $products->count() !== count(array_unique($productIds))) {
            return false;
        }

        $categoryIds = $products
            ->map(fn($product) => $product->getProductCategoryId())
            ->filter()
            ->unique()
            ->values()
            ->all();
        if (empty($categoryIds)) {
            return false;
        }

        $categories = $this->productCategoryRepository->findWhereIn('id', $categoryIds)->keyBy(fn($category) => $category->getId());

        foreach ($products as $product) {
            $category = $categories->get($product->getProductCategoryId());

            if (!$category || $category->getEventId() !== $eventId || $category->getName() !== $categoryName) {
                return false;
            }
        }

        return true;
    }

    public function getAllowedPaymentProvidersForDonorType(?string $donorType): array
    {
        return match ($donorType) {
            self::DONOR_TYPE_ALUMNI => [PaymentProviders::CMI->value],
            self::DONOR_TYPE_COMPANY => [PaymentProviders::CMI->value, PaymentProviders::OFFLINE->value],
            default => [],
        };
    }

    public function getDonorTypeFromPointInTimeData(null|array|string $pointInTimeData): ?string
    {
        if (!is_array($pointInTimeData)) {
            return null;
        }

        $donorType = $pointInTimeData['donations']['donor_type'] ?? null;

        return in_array($donorType, [self::DONOR_TYPE_ALUMNI, self::DONOR_TYPE_COMPANY], true)
            ? $donorType
            : null;
    }

    public function getOrderQuestionAnswerMap(?Collection $questions): array
    {
        return $questions?->mapWithKeys(fn($question) => [
            $question->question_id => $this->normalizeAnswer($question->response),
        ])->all() ?? [];
    }

    public function normalizeAnswer(mixed $answer): ?string
    {
        if (is_array($answer) && array_key_exists('answer', $answer)) {
            $answer = $answer['answer'];
        }

        if (is_array($answer)) {
            $answer = collect($answer)->filter(fn($value) => $value !== null && $value !== '')->join(', ');
        }

        if ($answer === null) {
            return null;
        }

        $answer = trim((string)$answer);

        return $answer === '' ? null : $answer;
    }
}
