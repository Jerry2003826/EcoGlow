<?php
// src/Service/ChatbotService.php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Throwable;

class ChatbotService
{
    /**
     * Build the system prompt for the assistant.
     *
     * @return string
     */
    public function buildSystemPrompt(): string
    {
        $sections = [];

        $sections[] = implode("\n", [
            'You are the shopping assistant for Eco Glow Lighting, an Australian lighting store.',
            'Only answer questions about this store, its products, services and website.',
            'Be polite and concise.',
            'Reply in English.',
        ]);

        $productLines = [];

        try {
            $productsTable = TableRegistry::getTableLocator()->get('Products');

            $products = $productsTable
                ->find()
                ->contain([
                    'ProductCategories',
                    'Inventory',
                ])
                ->all();

            foreach ($products as $product) {
                $quantity = (int)($product->inventory->Quantity ?? 0);
                $threshold = (int)($product->inventory->LowStockThreshold ?? 0);

                if ($quantity === 0) {
                    $stockLabel = 'out of stock';
                } elseif ($quantity <= $threshold) {
                    $stockLabel = 'low stock';
                } else {
                    $stockLabel = 'in stock';
                }

                $categoryName = (string)($product->product_category->CategoryName ?? '');
                $description = trim((string)$product->Description ?? '');
                $price = number_format((float)$product->Price, 2, '.', '');

                $productLines[] = sprintf(
                    '- %s (%s) - AUD $%s - %s - %s',
                    (string)$product->ProductName,
                    $categoryName,
                    $price,
                    $description,
                    $stockLabel,
                );
            }
        } catch (Throwable $exception) {
            Log::error(
                'Failed to load chatbot products: ' . $exception->getMessage(),
            );
        }

        if ($productLines !== []) {
            $sections[] = "Product Catalogue:\n" . implode("\n", $productLines);
        }

        $faqLines = [];

        try {
            $faqTable = TableRegistry::getTableLocator()->get('ChatbotFaq');

            $faqs = $faqTable
                ->find('active')
                ->all();

            foreach ($faqs as $faq) {
                $faqLines[] = sprintf(
                    "Q: %s\nA: %s",
                    (string)$faq->Question,
                    (string)$faq->Answer,
                );
            }
        } catch (Throwable $exception) {
            Log::error(
                'Failed to load chatbot FAQs: ' . $exception->getMessage(),
            );
        }

        if ($faqLines !== []) {
            $sections[] = "FAQ:\n" . implode("\n\n", $faqLines);
        }

        $sections[] = implode("\n", [
            'Website Navigation:',
            '- Home: /',
            '- Services: /services',
            '- About Us: /about',
            '- Product Catalogue: /products or /shop',
            '- Contact Us: /enquiries/add',
            '- Login: /login',
            '- Register: /users/add',
            '- Forgot Password: /forgot-password',
            '- Shopping Cart: /cart',
            '- Checkout: /checkout',
            '- Order Receipt: /orders/receipt/{id}',
            '- Recommend products using links such as /products?q=keyword.',
            '- Recommend category links using /products?category=1 for Pendant Lights.',
            '- Recommend category links using /products?category=2 for Wall Lights.',
            '- Contact requests should be directed to /enquiries/add.',
            '- Cart questions should be directed to /cart.',
            '- Checkout questions should be directed to /checkout.',
            '- Never invent URLs such as /products/1, /products/view/1, or any other product detail page.',
        ]);

        $sections[] = implode("\n", [
            'Rules:',
            '- If you do not know the answer, say so and suggest contacting the team via /enquiries/add.',
            '- Do not discuss competitors.',
            '- Do not promise products, stock, prices, or services that are not present in the provided catalogue.',
            '- Reply in plain text only. Do not use Markdown formatting such as **bold**, headings, or tables.',
            '- For lists, start each item on a new line with a simple dash.',
        ]);

        return implode("\n\n", $sections);
    }

    /**
     * Send a chat request to the configured assistant.
     *
     * @param array $history Conversation history.
     * @param string $userMessage Current user message.
     * @return string
     */
    public function ask(array $history, string $userMessage): string
    {
        $apiKey = Configure::read('Chatbot.apiKey');

        if (!is_string($apiKey) || trim($apiKey) === '') {
            return 'The assistant is not configured yet. Please contact us via the Contact Us page.';
        }

        $endpoint = Configure::read(
            'Chatbot.endpoint',
            'https://api.deepseek.com/chat/completions',
        );

        $model = Configure::read(
            'Chatbot.model',
            'deepseek-chat',
        );

        $messages = [
            [
                'role' => 'system',
                'content' => $this->buildSystemPrompt(),
            ],
        ];

        $recentHistory = array_slice($history, -10);

        foreach ($recentHistory as $entry) {
            if (
                !is_array($entry) ||
                !isset($entry['role'], $entry['content'])
            ) {
                continue;
            }

            $role = (string)$entry['role'];

            if (!in_array($role, ['user', 'assistant'], true)) {
                continue;
            }

            $messages[] = [
                'role' => $role,
                'content' => (string)$entry['content'],
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        $payload = [
            'model' => (string)$model,
            'messages' => $messages,
            'temperature' => 0.4,
            'max_tokens' => 600,
        ];

        try {
            $client = new Client([
                'timeout' => 20,
            ]);

            $response = $client->post(
                (string)$endpoint,
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . trim($apiKey),
                        'Content-Type' => 'application/json',
                    ],
                ],
            );

            if (!$response->isOk()) {
                Log::error(
                    'Chatbot request failed with HTTP status ' .
                    $response->getStatusCode(),
                );

                return 'Sorry, I could not reach the assistant right now. Please try again in a moment.';
            }

            $data = json_decode($response->getStringBody(), true);

            if (!is_array($data)) {
                Log::error(
                    'Chatbot response JSON could not be parsed.',
                );

                return 'Sorry, I could not reach the assistant right now. Please try again in a moment.';
            }

            $content = $data['choices'][0]['message']['content'] ?? null;

            if (!is_string($content) || trim($content) === '') {
                Log::error(
                    'Chatbot response content was missing.',
                );

                return 'Sorry, I could not reach the assistant right now. Please try again in a moment.';
            }

            return trim($content);
        } catch (Throwable $exception) {
            Log::error(
                'Chatbot request exception: ' . $exception->getMessage(),
            );

            return 'Sorry, I could not reach the assistant right now. Please try again in a moment.';
        }
    }
}
