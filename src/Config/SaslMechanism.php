<?php declare(strict_types=1);

namespace Junges\Kafka\Config;

enum SaslMechanism: string
{
    /** Authenticates with a username and a password sent in clear text, so it should be used with SASL_SSL. */
    case PLAIN = 'PLAIN';

    /** Authenticates with a username and a password, using a salted challenge instead of sending the password. */
    case SCRAM_SHA_256 = 'SCRAM-SHA-256';

    /** Authenticates with a username and a password, using a salted challenge instead of sending the password. */
    case SCRAM_SHA_512 = 'SCRAM-SHA-512';

    /** Authenticates with Kerberos. */
    case GSSAPI = 'GSSAPI';

    /** Authenticates with OAuth 2.0 tokens, provided by the OAUTHBEARER token refresh callback. */
    case OAUTHBEARER = 'OAUTHBEARER';
}
