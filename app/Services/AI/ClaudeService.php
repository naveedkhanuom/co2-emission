<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around Anthropic's Claude Messages API.
 *
 * This is the project's primary LLM provider. Inject it wherever an AI
 * capability is needed; keep prompt construction in the calling service so
 * prompts/versioning stay close to the feature that owns them.
 */
class ClaudeService
{
    /**
     * Whether the provider is configured and usable.
     */
    public function enabled(): bool
    {
        return !empty(config('services.anthropic.key'));
    }

    /**
     * Send a single-turn message and return Claude's text response.
     *
     * @param  string       $prompt   The user message.
     * @param  string|null  $system   Optional system prompt.
     * @param  array        $options  Overrides: model, max_tokens, temperature.
     * @return string|null            The assistant text, or null on failure.
     */
    public function message(string $prompt, ?string $system = null, array $options = []): ?string
    {
        if (!$this->enabled()) {
            return null;
        }

        $payload = [
            'model'      => $options['model'] ?? config('services.anthropic.model'),
            'max_tokens' => $options['max_tokens'] ?? config('services.anthropic.max_tokens'),
            'messages'   => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ];

        if (!empty($system)) {
            $payload['system'] = $system;
        }

        if (isset($options['temperature'])) {
            $payload['temperature'] = $options['temperature'];
        }

        try {
            $response = Http::withHeaders([
                'x-api-key'         => config('services.anthropic.key'),
                'anthropic-version' => config('services.anthropic.version'),
                'content-type'      => 'application/json',
            ])
                ->timeout((int) config('services.anthropic.timeout', 30))
                ->post(rtrim((string) config('services.anthropic.base_url'), '/') . '/v1/messages', $payload);

            if ($response->failed()) {
                Log::warning('Claude API request failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            // Messages API returns content as an array of blocks; concatenate text blocks.
            $blocks = $response->json('content', []);
            $text = '';
            foreach ($blocks as $block) {
                if (($block['type'] ?? null) === 'text') {
                    $text .= $block['text'] ?? '';
                }
            }

            return $text !== '' ? $text : null;
        } catch (\Throwable $e) {
            Log::warning('Claude API exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Send a multi-turn conversation and return Claude's text response.
     *
     * Each message is ['role' => 'user'|'assistant', 'content' => string]. Use
     * this when the feature needs conversation continuity (e.g. a chat
     * assistant); for one-shot prompts use message() instead.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  string|null  $system
     * @param  array        $options  Overrides: model, max_tokens, temperature.
     * @return string|null            The assistant text, or null on failure.
     */
    public function chat(array $messages, ?string $system = null, array $options = []): ?string
    {
        if (!$this->enabled() || empty($messages)) {
            return null;
        }

        $payload = [
            'model'      => $options['model'] ?? config('services.anthropic.model'),
            'max_tokens' => $options['max_tokens'] ?? config('services.anthropic.max_tokens'),
            'messages'   => array_values($messages),
        ];

        if (!empty($system)) {
            $payload['system'] = $system;
        }

        if (isset($options['temperature'])) {
            $payload['temperature'] = $options['temperature'];
        }

        try {
            $response = Http::withHeaders([
                'x-api-key'         => config('services.anthropic.key'),
                'anthropic-version' => config('services.anthropic.version'),
                'content-type'      => 'application/json',
            ])
                ->timeout((int) config('services.anthropic.timeout', 30))
                ->post(rtrim((string) config('services.anthropic.base_url'), '/') . '/v1/messages', $payload);

            if ($response->failed()) {
                Log::warning('Claude chat request failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $blocks = $response->json('content', []);
            $text = '';
            foreach ($blocks as $block) {
                if (($block['type'] ?? null) === 'text') {
                    $text .= $block['text'] ?? '';
                }
            }

            return $text !== '' ? $text : null;
        } catch (\Throwable $e) {
            Log::warning('Claude chat exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Send a message expecting a JSON object back and return it decoded.
     *
     * The system prompt is augmented to force a raw-JSON reply, and any
     * markdown code fences are stripped before decoding.
     *
     * @return array|null  The decoded JSON object, or null on failure.
     */
    public function json(string $prompt, ?string $system = null, array $options = []): ?array
    {
        $jsonDirective = 'Respond with a single valid JSON object and nothing else. '
            . 'Do not wrap it in markdown code fences or add commentary.';

        $system = $system ? ($system . "\n\n" . $jsonDirective) : $jsonDirective;

        $raw = $this->message($prompt, $system, $options);

        if ($raw === null) {
            return null;
        }

        $decoded = $this->decodeJson($raw);

        if ($decoded === null) {
            Log::warning('Claude JSON parse failed', ['raw' => $raw]);
        }

        return $decoded;
    }

    /**
     * Best-effort extraction of a JSON object from a model response.
     */
    private function decodeJson(string $raw): ?array
    {
        $text = trim($raw);

        // Strip ```json ... ``` or ``` ... ``` fences if present.
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```[a-zA-Z]*\s*|\s*```$/', '', $text);
            $text = trim((string) $text);
        }

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Fallback: grab the first {...} span and try again.
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
