<?php

namespace App\Ai\Drivers;

use App\Ai\AiException;
use App\Ai\AiRequest;
use App\Ai\AiResponse;
use App\Ai\Contracts\TextGenerator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/** Google Gemini (Generative Language API) with a JSON response schema. */
class GeminiDriver implements TextGenerator
{
    private const BASE = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct(private string $apiKey, private string $model) {}

    public function generate(AiRequest $request): AiResponse
    {
        $body = [
            'systemInstruction' => ['parts' => [['text' => $request->system]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $request->prompt]]]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseJsonSchema' => $request->schema,
                'maxOutputTokens' => $request->maxTokens,
                'temperature' => $request->temperature,
            ],
        ];

        $json = $this->check($this->send(fn (PendingRequest $http) => $http->post(self::BASE.'/models/'.rawurlencode($this->model).':generateContent', $body)))->json();

        if ($blocked = $json['promptFeedback']['blockReason'] ?? null) {
            throw new AiException("Gemini blocked the request ({$blocked}).");
        }
        $candidate = $json['candidates'][0] ?? null;
        $finish = $candidate['finishReason'] ?? 'STOP';
        if ($finish === 'MAX_TOKENS') {
            throw new AiException('The response hit the max-token limit before finishing. Raise "Max output tokens" in AI settings.');
        }
        if ($finish !== 'STOP') {
            throw new AiException("Gemini stopped early ({$finish}).");
        }

        $text = collect($candidate['content']['parts'] ?? [])->reject(fn ($p) => $p['thought'] ?? false)->pluck('text')->implode('');
        $usage = $json['usageMetadata'] ?? [];

        return new AiResponse(
            AiResponse::decode($text),
            (int) ($usage['promptTokenCount'] ?? 0),
            (int) ($usage['candidatesTokenCount'] ?? 0) + (int) ($usage['thoughtsTokenCount'] ?? 0),
            (string) ($json['modelVersion'] ?? $this->model),
        );
    }

    public function test(): string
    {
        return (string) $this->check($this->send(fn (PendingRequest $http) => $http->get(self::BASE.'/models/'.rawurlencode($this->model))))->json('displayName');
    }

    public function models(): array
    {
        return collect($this->check($this->send(fn (PendingRequest $http) => $http->get(self::BASE.'/models', ['pageSize' => 200])))->json('models', []))
            ->filter(fn ($m) => in_array('generateContent', $m['supportedGenerationMethods'] ?? [], true) && str_contains($m['name'], 'gemini'))
            ->mapWithKeys(fn ($m) => [substr($m['name'], 7) => $m['displayName'] ?? $m['name']])
            ->sortKeys()->all();
    }

    private function send(callable $fn): Response
    {
        try {
            // Key goes in a header, not the URL, so it never appears in logged request URLs.
            return $fn(Http::withHeaders(['x-goog-api-key' => $this->apiKey])->acceptJson()->timeout(600)->connectTimeout(15)
                ->retry(2, 2000, fn ($e) => $e instanceof ConnectionException || in_array($e->response?->status(), [429, 500, 503], true), throw: false));
        } catch (ConnectionException $e) {
            throw new AiException('Could not reach the Gemini API: '.$e->getMessage());
        }
    }

    private function check(Response $response): Response
    {
        if ($response->successful()) {
            return $response;
        }
        $message = $response->json('error.message') ?: $response->reason();
        throw new AiException(match ($response->status()) {
            400 => str_contains($message, 'API key') ? 'Google rejected the API key. Check it in AI settings.' : 'Gemini API error: '.$message,
            403 => 'Google rejected the API key or the Generative Language API is not enabled for it.',
            404 => "Gemini doesn't recognise the model \"{$this->model}\".",
            429 => 'Gemini rate limit or quota reached: '.$message,
            default => 'Gemini API error ('.$response->status().'): '.$message,
        });
    }
}
