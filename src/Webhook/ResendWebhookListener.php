<?php

namespace App\Webhook;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\Event\Mailer\MailerDeliveryEvent;
use Symfony\Component\RemoteEvent\Event\Mailer\MailerEngagementEvent;
use Symfony\Component\RemoteEvent\RemoteEvent;

#[AsRemoteEventConsumer('mailer_resend')]
readonly class ResendWebhookListener implements ConsumerInterface
{
    private const STORAGE_FILE = '/var/emails.json';

    public function __construct(
        #[Autowire(param: 'kernel.project_dir')] private string $projectDir,
        private LoggerInterface $logger,
    ) {}

    public function consume(RemoteEvent $event): void
    {
        try {
            if ($event instanceof MailerDeliveryEvent) {
                $this->handleMailDelivery($event);
            } elseif ($event instanceof MailerEngagementEvent) {
                $this->handleMailEngagement($event);
            } else {
                $this->logger->warning('Unknown event type received', [
                    'event_name' => $event->getName(),
                ]);
                return;
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to process webhook event', [
                'error' => $e->getMessage(),
                'event_id' => $event->getId(),
            ]);
        }
    }

    private function handleMailDelivery(MailerDeliveryEvent $event): void
    {
        $payload = $event->getPayload();
        $eventType = $payload['type'] ?? 'unknown';

        $emailData = [
            'id' => $event->getId(),
            'date' => $event->getDate()->format('Y-m-d H:i:s'),
            'to' => $event->getRecipientEmail(),
            'event_type' => 'delivery',
            'status' => $eventType,
            'payload' => $payload,
        ];

        $this->storeEvent($emailData);

        $this->logger->info('Email delivery event processed', [
            'event_id' => $event->getId(),
            'status' => $eventType,
            'recipient' => $event->getRecipientEmail(),
        ]);
    }

    private function handleMailEngagement(MailerEngagementEvent $event): void
    {
        $payload = $event->getPayload();
        $eventType = $payload['event'] ?? 'unknown';

        $emailData = [
            'id' => $event->getId(),
            'date' => $event->getDate()->format('Y-m-d H:i:s'),
            'to' => $event->getRecipientEmail(),
            'event_type' => 'engagement',
            'status' => $eventType,
            'payload' => $payload,
        ];

        $this->storeEvent($emailData);

        $this->logger->info('Email engagement event processed', [
            'event_id' => $event->getId(),
            'status' => $eventType,
            'recipient' => $event->getRecipientEmail(),
        ]);
    }

    private function storeEvent(array $emailData): void
    {
        $filePath = $this->projectDir . self::STORAGE_FILE;

        $emails = $this->loadEmails($filePath);
        $emails[] = $emailData;

        file_put_contents($filePath, json_encode($emails, JSON_PRETTY_PRINT));
    }

    private function loadEmails(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }
}
