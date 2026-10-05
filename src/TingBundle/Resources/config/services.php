<?php
/***********************************************************************
 *
 * Ting Bundle - Symfony Bundle for Ting
 * ==========================================
 *
 * Copyright (C) 2014 CCM Benchmark Group. (http://www.ccmbenchmark.com)
 *
 ***********************************************************************
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 **********************************************************************/

// PHP configuration: XML service definitions are no longer loadable since Symfony 8.
// References are built with `new Reference()` because the service() helper does not exist in Symfony 4.4.

use CCMBenchmark\Ting\Cache\Cache;
use CCMBenchmark\Ting\ConnectionPool;
use CCMBenchmark\Ting\MetadataRepository;
use CCMBenchmark\Ting\Query\QueryFactory;
use CCMBenchmark\Ting\Repository\CollectionFactory;
use CCMBenchmark\Ting\Repository\Hydrator;
use CCMBenchmark\Ting\Repository\HydratorSingleObject;
use CCMBenchmark\Ting\Serializer\BackedEnum;
use CCMBenchmark\Ting\Serializer\DateTime;
use CCMBenchmark\Ting\Serializer\DateTimeImmutable;
use CCMBenchmark\Ting\Serializer\DateTimeZone;
use CCMBenchmark\Ting\Serializer\Geometry;
use CCMBenchmark\Ting\Serializer\Json;
use CCMBenchmark\Ting\Serializer\SerializerFactoryInterface;
use CCMBenchmark\Ting\Serializer\SerializerInterface;
use CCMBenchmark\Ting\Serializer\Uuid;
use CCMBenchmark\Ting\UnitOfWork;
use CCMBenchmark\TingBundle\ArgumentResolver\EntityValueResolver;
use CCMBenchmark\TingBundle\Cache\MetadataWarmer;
use CCMBenchmark\TingBundle\DataCollector\TingCacheDataCollector;
use CCMBenchmark\TingBundle\DataCollector\TingDriverDataCollector;
use CCMBenchmark\TingBundle\Repository\RepositoryFactory;
use CCMBenchmark\TingBundle\Security\EntityUserProvider;
use CCMBenchmark\TingBundle\Serializer\SerializerFactory;
use CCMBenchmark\TingBundle\Serializer\SymfonySerializer;
use CCMBenchmark\TingBundle\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

return static function (ContainerConfigurator $container): void {
    $nullOnInvalid = ContainerInterface::NULL_ON_INVALID_REFERENCE;
    $services = $container->services();

    // Auto tag all instances of SerializerInterface with "ting.serializer"
    $services->instanceof(SerializerInterface::class)
        ->autowire()
        ->tag('ting.serializer');

    $services->set('ting', RepositoryFactory::class)
        ->public()
        ->args([
            new Reference('ting.connectionpool'),
            new Reference('ting.metadatarepository'),
            new Reference('ting.queryfactory'),
            new Reference('ting.collectionfactory'),
            new Reference('ting.unitofwork'),
            new Reference('ting.cache'),
            new Reference('ting.serializerfactory'),
        ])
        ->call('loadMetadata', [
            '%kernel.cache_dir%',
            '%ting.cache_file%',
            '%ting.repositories%',
            new Reference('file_locator'),
            new Reference('ting.configuration_resolver', $nullOnInvalid),
        ]);

    $services->set('ting.metadatarepository', MetadataRepository::class)
        ->public()
        ->args([
            new Reference('ting.serializerfactory'),
            new Reference('ting.cache.property_access'),
        ]);
    $services->alias(MetadataRepository::class, 'ting.metadatarepository');

    $services->set('ting.serializerfactory', SerializerFactory::class)->public();
    $services->alias(SerializerFactoryInterface::class, 'ting.serializerfactory');

    $services->set('ting.queryfactory', QueryFactory::class)->public();
    $services->alias(QueryFactory::class, 'ting.queryfactory');

    $services->set('ting.driverlogger')
        ->synthetic()
        ->public()
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('ting.connectionpool', ConnectionPool::class)
        ->public()
        ->call('setConfig', ['%ting.connections%'])
        ->call('setDatabaseOptions', ['%ting.database_options%'])
        ->tag('kernel.reset', ['method' => 'reset']);
    $services->alias(ConnectionPool::class, 'ting.connectionpool');

    $services->set('ting.unitofwork', UnitOfWork::class)
        ->public()
        ->args([
            new Reference('ting.connectionpool'),
            new Reference('ting.metadatarepository'),
            new Reference('ting.queryfactory'),
        ])
        ->tag('kernel.reset', ['method' => 'reset']);
    $services->alias(UnitOfWork::class, 'ting.unitofwork');

    foreach (['ting.hydrator' => Hydrator::class, 'ting.hydrator_single_object' => HydratorSingleObject::class] as $id => $class) {
        $services->set($id, $class)
            ->share(false)
            ->public()
            ->call('setMetadataRepository', [new Reference('ting.metadatarepository')])
            ->call('setUnitOfWork', [new Reference('ting.unitofwork')]);
    }

    $services->set('ting.collectionfactory', CollectionFactory::class)
        ->public()
        ->args([
            new Reference('ting.metadatarepository'),
            new Reference('ting.unitofwork'),
            new Reference('ting.hydrator'),
        ]);
    $services->alias(CollectionFactory::class, 'ting.collectionfactory');

    $services->set('ting.cache', Cache::class)->public();
    $services->alias('Doctrine\Common\Cache\Cache', 'ting.cache');

    $services->set('ting.driver_data_collector', TingDriverDataCollector::class)
        ->public()
        ->tag('data_collector', ['template' => '@Ting/Collector/driverCollector.html.twig', 'id' => 'ting.driver'])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('ting.cache_data_collector', TingCacheDataCollector::class)
        ->public()
        ->tag('data_collector', ['template' => '@Ting/Collector/cacheCollector.html.twig', 'id' => 'ting.cache'])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('ting.metadata_warmer', MetadataWarmer::class)
        ->public()
        ->args([
            new Reference('ting.metadatarepository'),
            new Reference('file_locator'),
            '%ting.repositories%',
            '%ting.cache_file%',
        ])
        ->tag('kernel.cache_warmer', ['priority' => 0]);

    $services->set('ting.validator.unique.entity', UniqueEntityValidator::class)
        ->public()
        ->args([new Reference('ting')])
        ->tag('validator.constraint_validator');

    $services->set('ting.security.user_provider', EntityUserProvider::class)
        ->abstract()
        ->args([
            new Reference('ting.metadatarepository'),
            new Reference('ting'),
        ]);

    $services->set('ting.entity_value_resolver', EntityValueResolver::class)
        ->args([
            new Reference('ting.metadatarepository'),
            new Reference('ting'),
            new Reference(ExpressionLanguage::class, $nullOnInvalid),
        ])
        ->tag('controller.argument_value_resolver', ['priority' => 110]);

    foreach ([Json::class, BackedEnum::class, DateTime::class, DateTimeImmutable::class, DateTimeZone::class, Geometry::class, Uuid::class] as $serializer) {
        $services->set($serializer)->tag('ting.serializer');
    }

    $services->set(SymfonySerializer::class)
        ->args([new Reference('Symfony\Component\Serializer\SerializerInterface', $nullOnInvalid)])
        ->tag('ting.serializer');
};
