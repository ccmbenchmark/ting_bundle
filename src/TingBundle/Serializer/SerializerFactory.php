<?php

namespace CCMBenchmark\TingBundle\Serializer;

use CCMBenchmark\Ting\Serializer\SerializerFactoryInterface;
use CCMBenchmark\Ting\Serializer\SerializerInterface;

class SerializerFactory implements SerializerFactoryInterface
{
    private array $serializers = [];

    public function add(SerializerInterface $serializer)
    {
        $this->serializers[get_class($serializer)] = $serializer;
    }

    public function get($serializerName)
    {
        if (!isset($this->serializers[$serializerName])) {
            // Support old definition, try to magically instanciate.
            if (class_exists($serializerName) && $this->serializers[$serializerName] = new $serializerName()) {
                return $this->serializers[$serializerName];
            }
            throw new \Exception("Serializer $serializerName not found");
        }
        return $this->serializers[$serializerName];
    }
}