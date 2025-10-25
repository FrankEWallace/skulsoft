<?php

namespace Io\Billdesk\Client\Hmacsha256;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;

class JWEHS256Helper
{
    private $key;

    private $signAlgoManager;

    private $clientJwk;

    private $jwsBuilder;

    private $jwsVerifier;

    private $jwsSerializer;

    public function __construct($key)
    {
        $this->key = $key;
        $this->signAlgoManager = new AlgorithmManager([
            new HS256,
        ]);

        $this->clientJwk = JWKFactory::createFromSecret($this->key);
        $this->jwsBuilder = new JWSBuilder($this->signAlgoManager);
        $this->jwsVerifier = new JWSVerifier($this->signAlgoManager);
        $this->jwsSerializer = new CompactSerializer;
    }

    public function encryptAndSign($payload, $headers = [])
    {
        $jweHeaders = array_merge($headers, [
            'alg' => 'HS256',
        ]);

        $jws = $this->jwsBuilder
            ->create()
            ->withPayload($payload)
            ->addSignature($this->clientJwk, $jweHeaders)
            ->build();

        return $this->jwsSerializer->serialize($jws, 0);
    }

    public function verifyAndDecrypt($token)
    {
        $jws = $this->jwsSerializer->unserialize($token);

        if (! $this->jwsVerifier->verifyWithKey($jws, $this->clientJwk, 0)) {
            throw new SignatureVerificationException('Failed to verify signature');
        }

        return $jws->getPayload();
    }
}
