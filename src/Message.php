<?php

namespace Junges\Kafka;

use Illuminate\Contracts\Support\Arrayable;
use JetBrains\PhpStorm\ArrayShape;

class Message implements Arrayable
{
    public function __construct(
        protected array $headers = [],
        protected array $message = [],
        protected ?string $key = null
    ) {
    }

    /**
     * Set a key in the message array.
     *
     * @param string $key
     * @param mixed $message
     * @return $this
     */
    public function withMessageKey(string $key, mixed $message): Message
    {
        $this->message[$key] = $message;

        return $this;
    }

    /**
     * Unset a key in the message array.
     *
     * @param string $key
     * @return $this
     */
    public function forgetMessageKey(string $key): Message
    {
        unset($this->message[$key]);

        return $this;
    }

    /**
     * Set the message headers.
     *
     * @param array $headers
     * @return $this
     */
    public function withHeaders(array $headers): Message
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Set the kafka message key.
     *
     * @param string $key
     * @return $this
     */
    public function withKey(string $key): Message
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get the payload that should be sent to kafka.
     *
     * @return string
     */
    public function getPayload(): string
    {
        return json_encode($this->message);
    }

    /**
     * Get the kafka message key.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Get the message headers.
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    #[ArrayShape(['payload' => "array", 'key' => "null|string", 'headers' => "array"])]
    public function toArray(): array
    {
        return [
            'payload' => $this->message,
            'key' => $this->key,
            'headers' => $this->headers,
        ];
    }
}
