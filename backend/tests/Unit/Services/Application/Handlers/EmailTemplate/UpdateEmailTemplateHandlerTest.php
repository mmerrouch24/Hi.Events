<?php

namespace Tests\Unit\Services\Application\Handlers\EmailTemplate;

use HiEvents\DomainObjects\EmailTemplateDomainObject;
use HiEvents\DomainObjects\Enums\EmailTemplateType;
use HiEvents\Repository\Interfaces\EmailTemplateRepositoryInterface;
use HiEvents\Services\Application\Handlers\EmailTemplate\DTO\UpsertEmailTemplateDTO;
use HiEvents\Services\Application\Handlers\EmailTemplate\UpdateEmailTemplateHandler;
use HiEvents\Services\Domain\Email\EmailTemplateService;
use Mockery;
use Tests\TestCase;

class UpdateEmailTemplateHandlerTest extends TestCase
{
    public function testItForcesTicketUrlTokenForAttendeeTicketTemplatesOnUpdate(): void
    {
        $repository = Mockery::mock(EmailTemplateRepositoryInterface::class);
        $service = Mockery::mock(EmailTemplateService::class);

        $handler = new UpdateEmailTemplateHandler($repository, $service);

        $template = Mockery::mock(EmailTemplateDomainObject::class);
        $template->shouldReceive('getId')->andReturn(15);
        $template->shouldReceive('getTemplateType')->andReturn(EmailTemplateType::ATTENDEE_TICKET->value);

        $service->shouldReceive('validateTemplate')
            ->once()
            ->with('Updated Subject', 'Updated Body')
            ->andReturn([
                'valid' => true,
                'errors' => [],
            ]);

        $repository->shouldReceive('findFirstWhere')
            ->once()
            ->with([
                'id' => 15,
                'account_id' => 3,
            ])
            ->andReturn($template);

        $repository->shouldReceive('updateFromArray')
            ->once()
            ->with(15, Mockery::on(function (array $attributes) {
                return $attributes['cta']['label'] === 'View Ticket'
                    && $attributes['cta']['url_token'] === 'ticket.url'
                    && $attributes['subject'] === 'Updated Subject'
                    && $attributes['body'] === 'Updated Body';
            }))
            ->andReturn($template);

        $result = $handler->handle(new UpsertEmailTemplateDTO(
            account_id: 3,
            template_type: EmailTemplateType::ORDER_CONFIRMATION,
            subject: 'Updated Subject',
            body: 'Updated Body',
            id: 15,
            cta: [
                'label' => 'View Ticket',
                'url_token' => 'order.url',
            ],
        ));

        $this->assertSame($template, $result);
    }

    public function testItKeepsOrderUrlTokenForOrderConfirmationTemplatesOnUpdate(): void
    {
        $repository = Mockery::mock(EmailTemplateRepositoryInterface::class);
        $service = Mockery::mock(EmailTemplateService::class);

        $handler = new UpdateEmailTemplateHandler($repository, $service);

        $template = Mockery::mock(EmailTemplateDomainObject::class);
        $template->shouldReceive('getId')->andReturn(22);
        $template->shouldReceive('getTemplateType')->andReturn(EmailTemplateType::ORDER_CONFIRMATION->value);

        $service->shouldReceive('validateTemplate')
            ->once()
            ->with('Order Subject', 'Order Body')
            ->andReturn([
                'valid' => true,
                'errors' => [],
            ]);

        $repository->shouldReceive('findFirstWhere')
            ->once()
            ->with([
                'id' => 22,
                'account_id' => 8,
            ])
            ->andReturn($template);

        $repository->shouldReceive('updateFromArray')
            ->once()
            ->with(22, Mockery::on(function (array $attributes) {
                return $attributes['cta']['label'] === 'View Order'
                    && $attributes['cta']['url_token'] === 'order.url';
            }))
            ->andReturn($template);

        $result = $handler->handle(new UpsertEmailTemplateDTO(
            account_id: 8,
            template_type: EmailTemplateType::ATTENDEE_TICKET,
            subject: 'Order Subject',
            body: 'Order Body',
            id: 22,
            cta: [
                'label' => 'View Order',
                'url_token' => 'ticket.url',
            ],
        ));

        $this->assertSame($template, $result);
    }
}
