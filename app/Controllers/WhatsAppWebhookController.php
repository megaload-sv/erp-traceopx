<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

class WhatsAppWebhookController extends BaseController
{
    public function verify(): ResponseInterface
    {
        $token = (string) env('WHATSAPP_VERIFY_TOKEN');
        // PHP normalizes dots in query parameter names to underscores.
        $mode = $this->request->getGet('hub_mode');
        $provided = $this->request->getGet('hub_verify_token');
        $challenge = $this->request->getGet('hub_challenge');

        if ($token === '' || $mode !== 'subscribe' || ! is_string($provided)
            || ! hash_equals($token, $provided) || ! is_string($challenge) || $challenge === '') {
            return $this->response->setStatusCode(403);
        }

        return $this->response->setContentType('text/plain')->setBody($challenge);
    }

    public function receive(): ResponseInterface
    {
        $secret = (string) env('WHATSAPP_APP_SECRET');
        $phoneId = (string) env('WHATSAPP_PHONE_NUMBER_ID');
        if ($secret === '' || $phoneId === '') {
            return $this->response->setStatusCode(503);
        }

        $body = $this->request->getBody();
        $signature = $this->request->getHeaderLine('X-Hub-Signature-256');
        if (! hash_equals('sha256=' . hash_hmac('sha256', $body, $secret), $signature)) {
            return $this->response->setStatusCode(403);
        }

        $event = json_decode($body, true);
        if (! is_array($event) || ($event['object'] ?? null) !== 'whatsapp_business_account') {
            return $this->response->setStatusCode(400);
        }

        foreach ($event['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                if (($change['field'] ?? null) !== 'messages'
                    || ($value['metadata']['phone_number_id'] ?? null) !== $phoneId) {
                    continue;
                }

                foreach ($value['messages'] ?? [] as $message) {
                    $this->storeMessage($message);
                }
            }
        }

        return $this->response->setJSON(['received' => true]);
    }

    private function storeMessage(array $message): void
    {
        $messageId = $message['id'] ?? '';
        if (! is_string($messageId) || $messageId === '' || strlen($messageId) > 255) {
            return;
        }

        // Meta can deliver the same event again. The primary key makes retries safe.
        $stored = db_connect()->query(
            'INSERT INTO whatsapp_inbound_messages (message_id, payload, received_at)
             VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE message_id = message_id',
            [$messageId, json_encode($message, JSON_THROW_ON_ERROR), date('Y-m-d H:i:s')]
        );

        if ($stored === false) {
            throw new RuntimeException('Unable to store inbound WhatsApp message.');
        }
    }
}
