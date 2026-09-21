<?php
// src/Controller/ChatController.php
declare(strict_types=1);

namespace App\Controller;

use App\Service\ChatbotService;
use Cake\Event\EventInterface;
use Cake\Http\Response;

class ChatController extends AppController
{
    /**
     * Configure access for chatbot endpoints.
     *
     * @param \Cake\Event\EventInterface $event Event instance.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->Authentication->allowUnauthenticated([
            'ask',
            'reset',
        ]);
    }

    /**
     * Handle chatbot requests.
     *
     * @return \Cake\Http\Response
     */
    public function ask(): Response
    {
        $this->request->allowMethod(['post']);
        $this->autoRender = false;

        $message = trim((string)$this->request->getData('message'));

        if ($message === '') {
            return $this->jsonResponse([
                'error' => 'Please type a message.',
            ], 400);
        }

        if (mb_strlen($message) > 500) {
            return $this->jsonResponse([
                'error' => 'Please keep your message under 500 characters.',
            ], 400);
        }

        $session = $this->request->getSession();

        $requests = $session->read('Chatbot.requests');

        if (!is_array($requests)) {
            $requests = [];
        }

        $now = time();

        $requests = array_values(array_filter(
            $requests,
            static fn(mixed $timestamp): bool => is_int($timestamp) && ($now - $timestamp) < 60,
        ));

        if (count($requests) >= 10) {
            return $this->jsonResponse([
                'error' => 'You are sending messages too quickly. Please wait a moment.',
            ], 429);
        }

        $requests[] = $now;

        $session->write('Chatbot.requests', $requests);

        $history = $session->read('Chatbot.history');

        if (!is_array($history)) {
            $history = [];
        }

        $reply = (new ChatbotService())->ask($history, $message);

        $history[] = [
            'role' => 'user',
            'content' => $message,
        ];

        $history[] = [
            'role' => 'assistant',
            'content' => $reply,
        ];

        $history = array_slice($history, -20);

        $session->write('Chatbot.history', $history);

        return $this->jsonResponse([
            'reply' => $reply,
        ]);
    }

    /**
     * Reset chatbot conversation history.
     *
     * @return \Cake\Http\Response
     */
    public function reset(): Response
    {
        $this->request->allowMethod(['post']);
        $this->autoRender = false;

        $this->request->getSession()->delete('Chatbot.history');

        return $this->jsonResponse([
            'ok' => true,
        ]);
    }

    /**
     * Build a JSON response.
     *
     * @param array $payload Response payload.
     * @param int $status HTTP status code.
     * @return \Cake\Http\Response
     */
    private function jsonResponse(array $payload, int $status = 200): Response
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody(
                json_encode(
                    $payload,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                ),
            );
    }
}
