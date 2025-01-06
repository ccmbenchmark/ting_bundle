<?php

namespace tests\units\CCMBenchmark\TingBundle\ArgumentResolver;

use atoum;
use CCMBenchmark\Ting\ConnectionPool;
use CCMBenchmark\Ting\Query\QueryFactory;
use CCMBenchmark\Ting\Repository\CollectionFactory;
use CCMBenchmark\Ting\Repository\Hydrator;
use CCMBenchmark\Ting\Serializer\SerializerFactory;
use CCMBenchmark\Ting\UnitOfWork;
use CCMBenchmark\TingBundle\Attribute\MapEntity;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EntityValueResolver extends atoum
{
    private $metadataRepository;
    private $repositoryFactory;
    private $expressionLanguage;
    private $resolver;

    public function beforeTestMethod($method)
    {
        $this->metadataRepository = new \mock\CCMBenchmark\Ting\MetadataRepository(new SerializerFactory());
        $connectionPool = new ConnectionPool();
        $queryFactory = new QueryFactory();
        $unitOfWork = new UnitOfWork($connectionPool, $this->metadataRepository, $queryFactory);
        $hydrator = new Hydrator();
        $this->repositoryFactory = new \mock\CCMBenchmark\TingBundle\Repository\RepositoryFactory(
            $connectionPool,
            $this->metadataRepository,
            new QueryFactory(),
            new CollectionFactory($this->metadataRepository, $unitOfWork, $hydrator),
            $unitOfWork,
            new \CCMBenchmark\Ting\Cache\Cache(),
            new SerializerFactory()
        );
        $this->expressionLanguage = new \mock\Symfony\Component\ExpressionLanguage\ExpressionLanguage();

        $this->resolver = new \CCMBenchmark\TingBundle\ArgumentResolver\EntityValueResolver(
            $this->metadataRepository,
            $this->repositoryFactory,
            $this->expressionLanguage
        );
    }

    public function testReturnsEmptyArrayWhenArgumentIsAlreadyObject(): void
    {
        $this
            ->given($request = new Request(['argumentName' => new \stdClass()]))
            ->and($argument = new ArgumentMetadata('argumentName', null, false, false, null))

            ->when($result = $this->resolver->resolve($request, $argument))

            ->then
            ->array($result)
            ->isEmpty();
    }

    public function testReturnsEmptyArrayWhenMapEntityIsDisabled(): void
    {
        $this
            ->given($mapEntity = new MapEntity(class: 'TestEntity', disabled: true))
            ->and($argument = $this->createArgumentMetadataForAttributes($mapEntity))
            ->and($request = new Request())

            ->when($result = $this->resolver->resolve($request, $argument))

            ->then
            ->array($result)
            ->isEmpty();
    }
    
    public function testWithRouteMapping(): void
    {
        $this
            ->given($argumentCity = $this->createArgumentMetadataForAttributes(new MapEntity(class: 'City'), 'city'))
            ->and($argumentCountry = $this->createArgumentMetadataForAttributes(new MapEntity(class: 'Country'), 'country'))
            ->and($request = new Request(attributes: ['city' => 'Paris', 'country' => 'France', '_route_mapping' => ['slug' => 'city', 'country' => 'country']]))
            ->and($city = new \stdClass())
            ->and($country = new \stdClass())
            
            ->and($repository = new \mock\tests\fixtures\SimpleRepository())
            ->and($this->calling($repository)->getOneBy = static fn($criteria) => match($criteria) {
                ['slug' => 'Paris'] => $city,
                ['country' => 'France'] => $country,
            } )
            ->and($this->mockRepositoryFactoryAndMetadata($repository))

            ->when($result = $this->resolver->resolve($request, $argumentCity))
            ->then
                ->array($result)
                ->hasSize(1)
                ->contains($city)
            ->when($result = $this->resolver->resolve($request, $argumentCountry))
            ->then
                ->array($result)
                ->hasSize(1)
                ->contains($country)
        ;
    }

    public function testThrowsNotFoundHttpExceptionWhenObjectCannotBeFound(): void
    {
        $simpleRepository = new \mock\tests\fixtures\SimpleRepository();
        $this->calling($simpleRepository)->get = null;
        $this
            ->given($mapEntity = new MapEntity(class: 'TestEntity', id: 'id'))
            ->and($argument = $this->createArgumentMetadataForAttributes($mapEntity))
            ->and($request = new Request(attributes: ['id' => 123]))

            ->and($this->calling($this->metadataRepository)->findMetadataForEntity =
                fn($class, $success) => $success(new \mock\CCMBenchmark\Ting\Repository\Metadata(new SerializerFactory())))

            ->and($this->calling($this->repositoryFactory)->get = $simpleRepository)

            ->exception(function () use ($request, $argument) {
                $this->resolver->resolve($request, $argument);
            })
            ->isInstanceOf(NotFoundHttpException::class);
    }

    public function testReturnsObjectWhenFoundInRepository()
    {
        $this
            ->given($mapEntity = new MapEntity(class: 'TestEntity', id: 'id'))
            ->and($argument = $this->createArgumentMetadataForAttributes($mapEntity))
            ->and($request = new Request(attributes: ['id' => 123]))

            ->and($repository = new \mock\tests\fixtures\SimpleRepository())
            ->and($this->calling($repository)->get = $object = new \stdClass())
            ->and($this->mockRepositoryFactoryAndMetadata($repository))

            ->when($result = $this->resolver->resolve($request, $argument))

            ->then
            ->array($result)
            ->hasSize(1)
            ->contains($object);
    }

    public function testEvaluatesExpressionWhenProvided()
    {
        $this
            ->given($mapEntity = new MapEntity(class: 'TestEntity', expr: 'repository.find(id)'))
            ->and($argument = $this->createArgumentMetadataForAttributes($mapEntity))
            ->and($request = new Request(attributes: ['id' => 123]))

            ->and($repository = new \mock\tests\fixtures\SimpleRepository())
            ->and($this->mockRepositoryFactoryAndMetadata($repository))
            ->and($this->calling($this->expressionLanguage)->evaluate = $object = new \stdClass())

            ->when($result = $this->resolver->resolve($request, $argument))

            ->then
            ->array($result)
            ->hasSize(1)
            ->contains($object);
    }
    
    public function testExpressionFailureReturns404()
    {
        $this
            ->given($mapEntity = new MapEntity(class: 'TestEntity', expr: 'repository.find(id)'))
            ->and($argument = $this->createArgumentMetadataForAttributes($mapEntity))
            ->and($request = new Request(attributes: ['id' => 123]))

            ->and($repository = new \mock\tests\fixtures\SimpleRepository())
            ->and($this->mockRepositoryFactoryAndMetadata($repository))
            ->and($this->calling($this->expressionLanguage)->evaluate = null)

            ->exception(function () use ($request, $argument) {
                $this->resolver->resolve($request, $argument);
            })
            ->isInstanceOf(NotFoundHttpException::class);
    }

    public function testReturnsEmptyArrayWhenCriteriaCannotBeDetermined()
    {
        $this
            ->given($mapEntity = new MapEntity(class: 'TestEntity', mapping: []))
            ->and($argument = $this->createArgumentMetadataForAttributes($mapEntity))
            ->and($request = new Request())

            ->and($repository = new \mock\tests\fixtures\SimpleRepository())
            ->and($this->calling($repository)->getOneBy->doesNothing())
            ->and($this->mockRepositoryFactoryAndMetadata($repository))

            ->when($result = $this->resolver->resolve($request, $argument))

            ->then
            ->array($result)
            ->isEmpty();
    }
    
    public function testReturnsObjectWhenCriteriaCanBeDetermined()
    {
        $this
            ->given($mapEntity = new MapEntity(class: 'TestEntity', mapping: ['name' => 'name', 'id' => 'id']))
            ->and($argument = $this->createArgumentMetadataForAttributes($mapEntity))
            ->and($request = new Request(attributes: ['id' => 123, 'name' => 'foo']))

            ->and($repository = new \mock\tests\fixtures\SimpleRepository())
            ->and($this->calling($repository)->getOneBy = $object = new \stdClass())
            ->and($this->mockRepositoryFactoryAndMetadata($repository))

            ->when($result = $this->resolver->resolve($request, $argument))

            ->then
            ->array($result)
            ->hasSize(1)
            ->contains($object);
    }

    private function createArgumentMetadataForAttributes(MapEntity $attribute, string $argumentName = 'argumentName'): ArgumentMetadata
    {
        return new ArgumentMetadata($argumentName, $attribute->class, false, false, null, false, [$attribute]);
    }

    private function mockRepositoryFactoryAndMetadata($repository): void
    {
        $metadata = new \mock\CCMBenchmark\Ting\Repository\Metadata(new SerializerFactory());
        $this->calling($metadata)->getRepository = 'RepositoryClass';

        $this->calling($this->metadataRepository)->findMetadataForEntity =
            fn($class, $success) => $success($metadata);

        $this->calling($this->repositoryFactory)->get = $repository;
    }
}