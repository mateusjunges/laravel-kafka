<?php declare(strict_types=1);

namespace Junges\Kafka\Config;

enum SecurityProtocol: string
{
    /** No encryption and no authentication. */
    case PLAINTEXT = 'PLAINTEXT';

    /** TLS encryption, with optional TLS client authentication. */
    case SSL = 'SSL';

    /** SASL authentication, without encryption. */
    case SASL_PLAINTEXT = 'SASL_PLAINTEXT';

    /** SASL authentication over TLS encryption. */
    case SASL_SSL = 'SASL_SSL';

    /**
     * Get the SASL protocol to use instead of the given one when authenticating with SASL. The
     * encryption is kept, so adding SASL credentials never makes a client connect unencrypted.
     */
    public static function forSasl(self|string|null $protocol): self
    {
        $protocol = $protocol instanceof self ? $protocol : self::tryFrom(mb_strtoupper((string) $protocol));

        return in_array($protocol, [self::SSL, self::SASL_SSL], true) ? self::SASL_SSL : self::SASL_PLAINTEXT;
    }
}
