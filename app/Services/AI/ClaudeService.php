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
     * Send a single user turn whose content is a pre-built array of content
     * blocks (text, image, and/or document) and return Claude's text response.
     *
     * Use this for multimodal extraction — e.g. attaching a PDF/image invoice
     * alongside a text instruction. Build blocks with documentBlock()/imageBlock()
     * and textBlock(). Falls back to null when the provider is unavailable.
     *
     * @param  array<int, array>  $content  Anthropic content blocks.
     * @return string|null
     */
    public function messageWithContent(array $content, ?string $system = null, array $options = []): ?string
    {
        if (!$this->enabled() || empty($content)) {
            return null;
        }

        $payload = [
            'model'      => $options['model'] ?? config('services.anthropic.model'),
            'max_tokens' => $options['max_tokens'] ?? config('services.anthropic.max_tokens'),
            'messages'   => [
                ['role' => 'user', 'content' => array_values($content)],
            ],
        ];

        if (!empty($system)) {
            $payload['system'] = $system;
        }

        if (isset($options['temperature'])) {
            $payload['temperature'] = $options['temperature'];
        }

        // Keep PHP's execution limit above the HTTP timeout so a slow document
        // call returns a clean error instead of a fatal "max execution time".
        $docTimeout = (int) config('services.anthropic.document_timeout', 90);
        if (function_exists('set_time_limit')) {
            @set_time_limit($docTimeout + 30);
        }

        try {
            $response = Http::withHeaders([
                'x-api-key'         => config('services.anthropic.key'),
                'anthropic-version' => config('services.anthropic.version'),
                'content-type'      => 'application/json',
            ])
                // Documents (PDFs) can be large and slower to process than plain
                // text — allow a longer ceiling than the default chat timeout.
                ->timeout($docTimeout)
                ->post(rtrim((string) config('services.anthropic.base_url'), '/') . '/v1/messages', $payload);

            if ($response->failed()) {
                Log::warning('Claude document request failed', [
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
            Log::warning('Claude document exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Multimodal variant of json(): send content blocks, force a JSON reply and
     * return it decoded.
     *
     * @param  array<int, array>  $content
     * @return array|null
     */
    public function jsonWithContent(array $content, ?string $system = null, array $options = []): ?array
    {
        $jsonDirective = 'Respond with a single valid JSON object and nothing else. '
            . 'Do not wrap it in markdown code fences or add commentary.';

        $system = $system ? ($system . "\n\n" . $jsonDirective) : $jsonDirective;

        $raw = $this->messageWithContent($content, $system, $options);

        if ($raw === null) {
            return null;
        }

        $decoded = $this->decodeJson($raw);

        if ($decoded === null) {
            Log::warning('Claude JSON parse failed (document)', ['raw' => $raw]);
        }

        return $decoded;
    }

    /** Build a plain-text content block. */
    public function textBlock(string $text): array
    {
        return ['type' => 'text', 'text' => $text];
    }

    /** Build a base64 image content block (media_type e.g. image/png, image/jpeg). */
    public function imageBlock(string $mediaType, string $base64): array
    {
        return [
            'type'   => 'image',
            'source' => ['type' => 'base64', 'media_type' => $mediaType, 'data' => $base64],
        ];
    }

    /** Build a base64 PDF document content block. */
    public function documentBlock(string $base64, string $mediaType = 'application/pdf'): array
    {
        return [
            'type'   => 'document',
            'source' => ['type' => 'base64', 'media_type' => $mediaType, 'data' => $base64],
        ];
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

        // Last resort: the reply was cut off mid-object because it hit max_tokens
        // (common for large tables — a 30-row invoice can overflow the budget).
        // Salvage every complete element by closing the still-open brackets, so a
        // truncated response yields the rows that DID come back instead of nothing.
        $repaired = $this->repairTruncatedJson($text);
        if ($repaired !== null) {
            $decoded = json_decode($repaired, true);
            if (is_array($decoded)) {
                Log::warning('Claude JSON was truncated; salvaged complete elements from a partial reply.');
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Repair JSON that was truncated mid-value (e.g. the model hit max_tokens).
     *
     * Walks the string tracking string/escape state and the open-bracket stack,
     * cuts back to the last position where a complete element ended (a closing
     * bracket, or just before a comma), then appends the closing brackets needed
     * to balance whatever is still open. Returns null when nothing can be
     * salvaged (no complete element, or the JSON was already balanced).
     */
    private function repairTruncatedJson(string $text): ?string
    {
        $inString = false;
        $escape   = false;
        $stack    = [];   // currently open brackets, in order
        $cut      = -1;    // byte offset to truncate at
        $cutStack = [];    // open-bracket stack as it stood at $cut

        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            $ch = $text[$i];

            if ($inString) {
                if ($escape) {
                    $escape = false;
                } elseif ($ch === '\\') {
                    $escape = true;
                } elseif ($ch === '"') {
                    $inString = false;
                }
                continue;
            }

            if ($ch === '"') {
                $inString = true;
            } elseif ($ch === '{' || $ch === '[') {
                $stack[] = $ch;
            } elseif ($ch === '}' || $ch === ']') {
                array_pop($stack);
                $cut = $i + 1;          // after a complete container
                $cutStack = $stack;
            } elseif ($ch === ',') {
                $cut = $i;              // before the comma (drop the partial next element)
                $cutStack = $stack;
            }
        }

        // Nothing complete to keep, or the whole thing was already closed.
        if ($cut < 0 || empty($cutStack)) {
            return null;
        }

        $repaired = substr($text, 0, $cut);
        for ($j = count($cutStack) - 1; $j >= 0; $j--) {
            $repaired .= $cutStack[$j] === '{' ? '}' : ']';
        }

        return $repaired;
    }
}
