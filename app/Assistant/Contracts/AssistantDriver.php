<?php

namespace App\Assistant\Contracts;

use App\Assistant\AssistantReply;
use App\Models\User;

/**
 * A conversational backend for the shop assistant. Implementations may call
 * an LLM (OpenAI, Anthropic Claude, local model…) — credentials always come
 * from config('assistant.providers'), i.e. the .env file.
 */
interface AssistantDriver
{
    /** @param list<array{role: string, content: string}> $history */
    public function reply(string $message, ?User $user, array $history = []): AssistantReply;
}
