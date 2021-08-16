<?php

namespace Junges\Kafka;

class Message
{
    public array $headers =  [];

    public array $message = [];

    public function setMessageKey(string $key, mixed $message): Message
    {
        $this->message[$key] = $message;

        return $this;
    }

    public function forgetMessageKey(string $key): Message
    {
        unset($this->message[$key]);

        return $this;
    }

    public function getPayload(): string
    {
        return json_encode([
            'body' => $this->message,
            'headers' => $this->headers
        ]);
    }
}