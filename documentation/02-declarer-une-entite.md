# Déclarer une entité

## À propos des propriétés publiques

Le concept général `NotifyProperty`/`NotifyPropertyInterface` (propriétés + notification des changements pour l'[`UnitOfWork`](https://gitlab.ccmbg.com/core/ting/-/blob/master/documentation/02-entites.md) de Ting) reste valable dans une application Symfony. Le bundle permet en plus d'utiliser des **propriétés publiques**.

### PHP < 8.4 : setter explicite

```php
namespace App\Entity;

use CCMBenchmark\Ting\Entity\NotifyProperty;
use CCMBenchmark\Ting\Entity\NotifyPropertyInterface;

class City implements NotifyPropertyInterface
{
    use NotifyProperty;

    public string $name;

    public function setName(string $name): void
    {
        $this->propertyChanged('name', $this->name ?? null, $name);
        $this->name = $name;
    }
}
```

### PHP >= 8.4 : property hooks

```php
class City implements NotifyPropertyInterface
{
    use NotifyProperty;

    public string $name {
        set(string $name) {
            $this->propertyChanged('name', $this->name ?? null, $name);
            $this->name = $name;
        }
    };
}
```

Ce hook est contourné par Ting lors de l'hydratation (l'assignation directe ne redéclenche pas de logique supplémentaire côté hydrateur).

### Propriétés typées non initialisées

- Lors de la persistance d'une entité avec une propriété typée non initialisée, la propriété est ignorée par Ting — pensez à définir une valeur par défaut en base pour cette colonne, sous peine d'échec de l'`INSERT`.
- Toute tentative de lecture d'une propriété typée non initialisée déclenche une erreur PHP (comportement natif du langage, pas spécifique à Ting).

## Déclarer les métadonnées avec des attributs

Plutôt que d'écrire `initMetadata()` à la main côté Ting (voir la [doc Ting sur Repository et Metadata](https://gitlab.ccmbg.com/core/ting/-/blob/master/documentation/03-repository-et-metadata.md)), le bundle fournit deux attributs PHP dans `CCMBenchmark\TingBundle\Schema` : `Table` (sur la classe) et `Column` (sur chaque propriété mappée).

### `Schema\Table`

```php
#[\Attribute(\Attribute::TARGET_CLASS)]
class Table
{
    public function __construct(
        public string $name,       // nom de la table
        public string $connection, // nom de connexion déclaré dans ting.connections
        public string $database,   // nom de la base (accepte %env(...)%)
        public string $repository, // classe du Repository associé
    ) {}
}
```

### `Schema\Column`

```php
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        $autoIncrement = false,   // colonne autoincrement (avec primary: true)
        $primary = false,         // fait partie de la clé primaire
        $column = null,           // nom de colonne SQL si différent du nom de la propriété
        $serializer = null,       // classe de serializer personnalisée
        $serializerOptions = [],  // options passées au serializer
    ) {}
}
```

Sans `column`, le nom de colonne est déduit du nom de la propriété. La sérialisation est déduite automatiquement du type PHP de la propriété quand c'est possible (mêmes règles que côté Ting — voir les [types de `Metadata::addField`](https://gitlab.ccmbg.com/core/ting/-/blob/master/documentation/03-repository-et-metadata.md#types-disponibles)).

### Exemple complet

```php
// src/Entity/City.php
namespace App\Entity;

use App\Repository\CityRepository;
use CCMBenchmark\Ting\Entity\NotifyProperty;
use CCMBenchmark\Ting\Entity\NotifyPropertyInterface;
use CCMBenchmark\TingBundle\Schema;

#[Schema\Table('city_table', 'connectionName', '%env(DATABASE_NAME)%', CityRepository::class)]
class City implements NotifyPropertyInterface
{
    use NotifyProperty;

    #[Schema\Column(autoIncrement: true, primary: true)]
    public int $id {
        set(int $id) {
            $this->propertyChanged('id', $this->id ?? null, $id);
            $this->id = $id;
        }
    };

    #[Schema\Column(column: 'field')]
    public string $fieldWithSpecifiedColumnName {
        set (string $fieldWithSpecifiedColumnName) {
            $this->propertyChanged(
                'fieldWithSpecifiedColumnName',
                $this->fieldWithSpecifiedColumnName ?? null,
                $fieldWithSpecifiedColumnName
            );
            $this->fieldWithSpecifiedColumnName = $fieldWithSpecifiedColumnName;
        }
    };
}
```

```php
// src/Repository/CityRepository.php
namespace App\Repository;

class CityRepository extends \CCMBenchmark\Ting\Repository\Repository
{
}
```

Avec cette approche, la clé `ting.repositories` de la configuration YAML n'est plus nécessaire : les métadonnées sont construites directement à partir des attributs à la découverte de la classe.
