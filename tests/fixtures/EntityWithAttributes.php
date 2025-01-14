<?php

namespace tests\fixtures;

use Brick\Geo\Point;
use CCMBenchmark\TingBundle\Schema\Column;
use CCMBenchmark\TingBundle\Schema\Table;

#[Table('entity_with_attributes', 'default', 'default', 'default')]
class EntityWithAttributes
{
    #[Column(autoIncrement: true, primary: true)]
    public int $id;
    
    #[Column(column: 'field')]
    public string $fieldWithSpecifiedColumnName;
    
    #[Column]
    public string $fieldAsCamelCase;
    
    #[Column(serializerOptions: ['format' => 'Y-m-d H:i:s'])]
    public \DateTimeImmutable $dateImmutable;
    
    #[Column]
    public \DateTime $dateMutable;
    
    #[Column]
    public \DateTimeZone $timeZone;
    
    #[Column]
    public array $json;
    
    #[Column]
    public Point $point;
}