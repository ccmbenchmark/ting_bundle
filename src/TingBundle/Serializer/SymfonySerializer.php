<?php

namespace CCMBenchmark\TingBundle\Serializer;

use CCMBenchmark\Ting\Serializer\SerializerInterface;

class SymfonySerializer implements SerializerInterface
{
    public function __construct(private readonly ?\Symfony\Component\Serializer\SerializerInterface $serializer = null) {}

    public function serialize($toSerialize, array $options = [])
    {
        $this->throwOnNullSerializer();
        return $this->serializer->serialize($toSerialize, 'json', $options['context'] ?? []);
    }

    public function unserialize($serialized, array $options = [])
    {
        $this->throwOnNullSerializer();
        if (!isset($options['type'])) {
            throw new \RuntimeException('SymfonySerializer requires type option to be set');
        }
        return $this->serializer->deserialize($serialized, $options['type'], 'json', $options['context'] ?? []);
    }

    private function throwOnNullSerializer()
    {
        if ($this->serializer === null) {
            throw new \RuntimeException('SymfonySerializer requires symfony/serializer to be installed. Use composer require symfony/serializer to add it.');
        }
    }
}