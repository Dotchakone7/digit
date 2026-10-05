<?php

namespace App\Assistant;

final readonly class AssistantReply
{
    /** @param list<array{label: string, url: string}> $links */
    public function __construct(public string $text, public array $links = []) {}

    public function toArray(): array
    {
        return ['text' => $this->text, 'links' => $this->links];
    }
}
