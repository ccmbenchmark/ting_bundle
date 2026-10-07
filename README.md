> **⚠️ This repository is no longer actively maintained.**
> The project is now maintained on the internal GitLab of CCM Benchmark, and new versions (4.0.0 and later) are published there only.
> Pull requests are still welcome here: interesting ones will be ported.

Installation
============

1. Installer Ting Bundle avec
    ```composer require ccmbenchmark/ting_bundle```
2. Charger le Bundle dans AppKernel.php

```php
    new CCMBenchmark\TingBundle\TingBundle(),
```

## Sommaire
- [Configuration](#configuration)
    - [Configuration principale](#configuration-principale)
    - [À propos des propriétés publiques](#à-propos-des-propriétés-publiques)
    - [Déclarer les métadonnées avec des attributs](#déclarer-les-métadonnées-avec-des-attributs)
- [Utiliser Ting comme User Provider](#utiliser-ting-comme-user-provider)
- [Déclarer une contrainte d'unicité sur une table](#déclarer-une-contrainte-dunicité-sur-une-table)
- [Utiliser Ting comme Value Resolver](#utiliser-ting-comme-value-resolver)
- [Intégration au Profiler Symfony](#intégration-au-profiler-symfony)
- [Cache warmer/clearer des métadonnées](#cache-warmerclearer-des-métadonnées)
- [Résolution dynamique de configuration](#résolution-dynamique-de-configuration)
- [Bridge vers le Serializer Symfony](#bridge-vers-le-serializer-symfony)

Configuration
=============

## Configuration principale
```
#!yaml

    ting:
        repositories: # Inutile si les entités sont déclarées avec des attributs
            Acme:
                namespace: Acme\DemoBundle\Entity
                directory: "@DemoBundle/Entity"
                options:
                    # passer des options à votre repository
                    Acme\DemoBundle\BazRepository:
                        extra:
                            bar: hello
                            foo: world
                    Acme\DemoBundle\FooRepository:
                        extra:
                            bar: hello
                            foo: world
                    default:
                        connection: main
                        database: baz

        connections:
            main:
                namespace: CCMBenchmark\Ting\Driver\Mysqli
                master:
                    host:     localhost
                    user:     world_sample
                    password: world_sample
                    port:     3306
                slaves:
                    slave1:
                        host:     127.0.0.1
                        user:     world_sample_ro
                        password: world_sample_ro
                        port:     3306
                    slave2:
                        host:     127.0.1.1
                        user:     world_sample_ro
                        password: world_sample_ro
                        port:     3306

        databases_options:
            baz:
                timezone: 'Europe/Paris'
```

## À propos des propriétés publiques
Voir le [README de Ting](https://gitlab.ccmbg.com/core/ting#déclarer-une-entité) pour le concept général `NotifyProperty`/`NotifyPropertyInterface` (propriétés protégées + setters explicites).

Les propriétés publiques peuvent aussi être utilisées dans vos entités, cependant pour PHP < 8.4, vous devez déclarer un setter pour notifier le changement de propriété.

PHP < 8.4 :

```php
<?php

namespace App\Entity;

use CCMBenchmark\Ting\Entity\NotifyProperty;
use CCMBenchmark\Ting\Entity\NotifyPropertyInterface;

class City implements NotifyPropertyInterface {
    use NotifyProperty;
    
    public string $name;
    
    public function setName(string $name): void
    {
        $this->propertyChanged('name', $this->name ?? null, $name);
        $this->name = $name;
    }

}

```

Pour PHP >= 8.4, vous pouvez utiliser un property hook à la place. Ce hook sera contourné par Ting lors de l'hydratation.

```php
<?php

namespace App\Entity;

use CCMBenchmark\Ting\Entity\NotifyProperty;
use CCMBenchmark\Ting\Entity\NotifyPropertyInterface;

class City implements NotifyPropertyInterface {
    use NotifyProperty;
    
    public string $name { 
        set(string $name) {
            $this->propertyChanged('name', $this->name ?? null, $name);
            $this->name = $name;            
        }
    };
}
```

### Remarque sur les propriétés typées non initialisées
- Lors de la persistance d'une entité avec une propriété typée non initialisée, la propriété sera ignorée ; une valeur par défaut doit être définie en base pour cette colonne afin d'éviter un échec.
- Vous ne pouvez pas accéder à une propriété typée non initialisée, PHP déclenchera une erreur.

## Déclarer les métadonnées avec des attributs
Des attributs sont fournis pour déclarer une entité. Les attributs pertinents sont disponibles dans `CCMBenchmark\TingBundle\Schema`.

### Table
- Nom complet : `CCMBenchmark\TingBundle\Schema\Table`
- Cet attribut doit être ajouté à votre classe, avec toutes les options pertinentes (table, connexion, etc.).

### Column
- Nom complet : `CCMBenchmark\TingBundle\Schema\Column`
- Cet attribut doit être ajouté à chaque propriété mappée en base. La sérialisation est déduite du type, si disponible.

### Exemple complet

```php
// src/Entity/City.php
<?php

namespace App\Entity;

namespace tests\fixtures;

use App\Repository\CityRepository;
use Brick\Geo\Point;
use CCMBenchmark\Ting\Entity\NotifyProperty;
use CCMBenchmark\Ting\Entity\NotifyPropertyInterface;
use CCMBenchmark\TingBundle\Schema;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;

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
            $this->propertyChanged('fieldWithSpecifiedColumnName', $this->fieldWithSpecifiedColumnName ?? null, $fieldWithSpecifiedColumnName);
            $this->fieldWithSpecifiedColumnName = $fieldWithSpecifiedColumnName;
        }
    };
}
```
```php
// src/Repository/CityRepository.php
<?php

namespace App\Repository;

class CityRepository extends CCMBenchmark\Ting\Repository\Repository {

}
```

## Utiliser Ting comme User Provider
Les user providers (re)chargent les utilisateurs depuis un stockage à partir d'un « identifiant utilisateur » (extrait de la [documentation Symfony](https://symfony.com/doc/current/security/user_providers.html)).

Ting peut être utilisé comme User Provider, il est automatiquement enregistré par le bundle en tant que provider `ting`. Pour cela, mettez à jour votre configuration de sécurité.

```yaml
security:
  # https://symfony.com/doc/current/security.html#loading-the-user-the-user-provider
  providers:
    app_user_provider:
      ting:
        class: App\Entity\User
        property: email
```

Votre entité devra implémenter les interfaces suivantes : `Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface` (pour les utilisateurs authentifiés par mot de passe) et `Symfony\Component\Security\Core\User\UserInterface` (commune à tous les types d'utilisateurs).
Elle doit aussi implémenter `__serialize`.

## Déclarer une contrainte d'unicité sur une table
Si vous utilisez le composant `symfony/validator`, vous pourriez avoir besoin de garantir qu'une valeur (ou une combinaison de valeurs) est unique dans votre table.

Vous pouvez utiliser la Constraint `CCMBenchmark\TingBundle\Validator\Constraints\UniqueEntity` pour cela. Elle peut être utilisée en annotation ou en attribut.

Exemple :
```php
namespace App\Entity;

use App\Repository\UserRepository;
use CCMBenchmark\Ting\Entity\NotifyProperty;
use CCMBenchmark\Ting\Entity\NotifyPropertyInterface;
use CCMBenchmark\TingBundle\Schema\Column;
use CCMBenchmark\TingBundle\Schema\Table;
use CCMBenchmark\TingBundle\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[Table(name: 'users', connection: 'main', database: '%env(DATABASE_DB_NAME)%', repository: UserRepository::class)]
#[UniqueEntity(options:['repository' => UserRepository::class, 'fields' => ['email']], groups: ['create'])]
class User implements UserInterface, NotifyPropertyInterface {
    #[Column(autoIncrement: true, primary: true)]
    public int $id { set(int $id) {
        $this->propertyChanged('id', $this->id ?? null, $id);
        $this->id = $id;
    }}
    
    #[Column]
    #[Groups(['default', 'create', 'update', 'service_account'])]
    #[Assert\NotBlank(groups: ["default", "create", "update"])]
    #[Assert\Email(groups: ["default", "create", "update"])]
    public string $email { set(string $email) {
        $this->propertyChanged('email', $this->email ?? null, $email);
        $this->email = $email;
    } }
    
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
        
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
        ];
    }
}
```

Avec cet exemple, vous pouvez vérifier, lors de la création d'un nouvel utilisateur, que l'adresse email est unique :

```php
<?php
namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/users', methods:['POST'], format: 'json')]
class createUserController extends AbstractController {
    public function __construct (private readonly ValidatorInterface $validator, private readonly UserRepository $userRepository) { }
    public function __invoke(
        #[MapRequestPayload(serializationContext: ['groups' => ['create']])] User $user
    ) :JsonResponse {
        $violations = $this->validator->validate($user, groups: ['create']);
        if ($violations->count() > 0) {
            return new JsonResponse(['message' => 'Errors...'], 422);
        }
        $this->userRepository->save($user);
        return new JsonResponse(['message' => 'User registered'], 201);
    }
}

```

## Utiliser Ting comme Value Resolver
Ce bundle enregistre automatiquement un [Value Resolver](https://symfony.com/doc/current/controller/value_resolver.html#built-in-value-resolvers).

Vous pouvez mapper automatiquement les paramètres de requête vers des entités :
1. Déclarez un paramètre dans votre route (ex : `/api/users/{userId}`), en utilisant la propriété de votre entité qui servira à récupérer la donnée (ici : `userId`)
2. Mappez-le vers les paramètres de votre action :
   1. Ajoutez-le à la signature : `public function getUser(User $user)`
   2. Mettez à jour la route pour faire le mapping : `/api/users/{userId:user}` (ici : le `User` dont `userId` correspond à la requête sera récupéré et injecté dans votre action via l'argument `$user`)

```php
<?php

namespace App\Controller;

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController {
    #[Route('/api/users/{userId:myUser}', name: 'get_user', methods: ['GET'], format: 'json' )]
    #[IsGranted('ROLE_USER')]
    public function getUser(User $user): JsonResponse
    {
        return new JsonResponse($this->serializer->serialize($user, 'json', ['groups' => 'default']), json: true);
    }
}
```

Pour des cas d'usage plus avancés, vous pouvez utiliser :
- [Le composant Expression Language](https://symfony.com/doc/current/components/expression_language.html)
- L'attribut `CCMBenchmark\TingBundle\Attribute\MapEntity`

Exemple :

```php
<?php

namespace App\Controller;

use CCMBenchmark\TingBundle\Attribute\MapEntity;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController {
    #[Route('/api/users/{firstname}/{lastname}', name: 'get_user', methods: ['GET'], format: 'json' )]
    #[IsGranted('ROLE_USER')]
    public function getUser(#[MapEntity(expr: 'repository.getOneBy({"firstname": firstname, "lastname": lastname}')] User $user): JsonResponse
    {
        return new JsonResponse($this->serializer->serialize($user, 'json', ['groups' => 'default']), json: true);
    }
}
```

## Intégration au Profiler Symfony

Le bundle enregistre automatiquement deux data collectors, visibles dans le Profiler Symfony (environnement de dev) sans aucune configuration :

- **`ting.driver`** (`CCMBenchmark\TingBundle\DataCollector\TingDriverDataCollector`) : chaque requête/exec exécutée sur une connexion Ting, leur temps d'exécution, et les connexions ouvertes.
- **`ting.cache`** (`CCMBenchmark\TingBundle\DataCollector\TingCacheDataCollector`) : opérations de cache (hits/miss, temps total) quand un cache est configuré pour Ting.

Utile pour repérer des requêtes N+1 ou des requêtes lentes directement depuis la toolbar du profiler.

## Cache warmer/clearer des métadonnées

En production, les métadonnées d'entité (construites depuis la config YAML ou les attributs) sont coûteuses à recalculer à chaque requête. Le bundle enregistre :

- **`CCMBenchmark\TingBundle\Cache\MetadataWarmer`** (`CacheWarmerInterface`) : appelé par `bin/console cache:warmup`, il appelle `batchLoadMetadata()` pour chaque groupe de repositories configuré et écrit le résultat dans un fichier de cache via `MetadataCacheGenerator`.
- **`CCMBenchmark\TingBundle\Cache\MetadataClearer`** (`CacheClearerInterface`) : appelé par `bin/console cache:clear`, il supprime ce fichier de cache pour qu'il soit régénéré au prochain warmup/à la prochaine requête.

Les deux sont branchés automatiquement dès que le bundle est activé — aucune configuration manuelle nécessaire.

## Résolution dynamique de configuration

Si les `options` d'un groupe (`ting.repositories.<alias>.options`) doivent être résolues dynamiquement (par exemple calculées à partir de quelque chose qui n'est pas exprimable en YAML statique), enregistrez un service tagué/aliasé `ting.configuration_resolver` implémentant `CCMBenchmark\TingBundle\ConfigurationResolver\ConfigurationResolverInterface` :

```php
<?php
namespace App\Ting;

use CCMBenchmark\TingBundle\ConfigurationResolver\ConfigurationResolverInterface;

class MyConfigurationResolver implements ConfigurationResolverInterface
{
    public function resolveConf($alias, array $configuration)
    {
        // $alias est le nom du groupe de repositories (ex: "Acme" dans l'exemple de configuration principale)
        // retourne le tableau $configuration (potentiellement modifié)
        return $configuration;
    }
}
```

Il est appelé une fois, au chargement des métadonnées (`RepositoryFactory::loadMetadata()`), et il est optionnel — sans lui, la configuration statique est utilisée telle quelle.

## Bridge vers le Serializer Symfony

`CCMBenchmark\TingBundle\Serializer\SymfonySerializer` implémente le `SerializerInterface` de Ting par-dessus `symfony/serializer` (require-dev `symfony/serializer` pour l'utiliser). Il délègue `serialize()`/`unserialize()` au serializer Symfony configuré dans votre application, ce qui permet de réutiliser vos normalizers/encoders existants pour les champs gérés par Ting plutôt qu'un serializer spécifique à Ting.
