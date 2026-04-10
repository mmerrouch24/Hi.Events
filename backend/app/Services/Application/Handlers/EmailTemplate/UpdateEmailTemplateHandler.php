<?php

namespace HiEvents\Services\Application\Handlers\EmailTemplate;

use HiEvents\DomainObjects\EmailTemplateDomainObject;
use HiEvents\DomainObjects\Enums\EmailTemplateType;
use HiEvents\Exceptions\EmailTemplateNotFoundException;
use HiEvents\Exceptions\EmailTemplateValidationException;
use HiEvents\Exceptions\InvalidEmailTemplateException;
use HiEvents\Repository\Interfaces\EmailTemplateRepositoryInterface;
use HiEvents\Services\Application\Handlers\EmailTemplate\DTO\UpsertEmailTemplateDTO;
use HiEvents\Services\Domain\Email\EmailTemplateService;

class UpdateEmailTemplateHandler
{
    public function __construct(
        private readonly EmailTemplateRepositoryInterface $emailTemplateRepository,
        private readonly EmailTemplateService $emailTemplateService
    ) {
    }

    /**
     * @throws EmailTemplateValidationException
     * @throws EmailTemplateNotFoundException
     * @throws InvalidEmailTemplateException
     */
    public function handle(UpsertEmailTemplateDTO $dto): EmailTemplateDomainObject
    {
        if (!$dto->id) {
            throw new InvalidEmailTemplateException('Template ID is required for update');
        }

        $validation = $this->emailTemplateService->validateTemplate($dto->subject, $dto->body);
        if (!$validation['valid']) {
            $exception = new EmailTemplateValidationException('Template validation failed');
            $exception->validationErrors = $validation['errors'];
            throw $exception;
        }

        $template = $this->emailTemplateRepository->findFirstWhere([
            'id' => $dto->id,
            'account_id' => $dto->account_id,
        ]);

        if (!$template) {
            throw new EmailTemplateNotFoundException('Email template not found');
        }

        $cta = $dto->cta;

        if (is_array($cta) && isset($cta['label'])) {
            $cta['url_token'] = match (EmailTemplateType::from($template->getTemplateType())) {
                EmailTemplateType::ORDER_CONFIRMATION => 'order.url',
                EmailTemplateType::ATTENDEE_TICKET => 'ticket.url',
            };
        }

        return $this->emailTemplateRepository->updateFromArray($template->getId(), [
            'subject' => $dto->subject,
            'body' => $dto->body,
            'cta' => $cta,
            'engine' => $dto->engine->value,
            'is_active' => $dto->is_active,
        ]);
    }
}
