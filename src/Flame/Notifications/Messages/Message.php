<?php

declare(strict_types=1);

namespace Flame\Notifications\Messages;

class Message
{
    private string $from = '';

    private string $to = '';

    private string $title;

    private array|string $body;

    private array $config = [];

    public function __construct($body = '')
    {
        $this->body = $body;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function setFrom(string $from): void
    {
        $this->from = $from;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function setTo(string $to): void
    {
        $this->to = $to;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getBody(): array|string
    {
        return $this->body;
    }

    public function setBody(array|string $body): void
    {
        $this->body = $body;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }
}
