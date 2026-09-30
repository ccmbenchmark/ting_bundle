# Configuration dynamique et Serializer

## Résolution dynamique de configuration

Si les `options` d'un groupe de repositories (`ting.repositories.<alias>.options`, voir [Installation et configuration](01-installation-et-configuration.md)) doivent être calculées dynamiquement — par exemple à partir de quelque chose qui n'est pas exprimable en YAML statique — enregistrez un service implémentant `CCMBenchmark\TingBundle\ConfigurationResolver\ConfigurationResolverInterface` :

```php
interface ConfigurationResolverInterface
{
    /**
     * @param string $alias Le nom du groupe de repositories dans la configuration (ex: "Acme")
     * @param array  $configuration Les options de configuration
     * @return array
     */
    public function resolveConf($alias, array $configuration);
}
```

```php
namespace App\Ting;

use CCMBenchmark\TingBundle\ConfigurationResolver\ConfigurationResolverInterface;

class MyConfigurationResolver implements ConfigurationResolverInterface
{
    public function resolveConf($alias, array $configuration)
    {
        // $alias identifie le groupe de repositories concerné
        // retourne le tableau $configuration, potentiellement modifié
        return $configuration;
    }
}
```

Le resolver est appelé une seule fois, au chargement des métadonnées (`RepositoryFactory::loadMetadata()`) — c'est un point d'extension optionnel : sans lui, la configuration statique déclarée en YAML est utilisée telle quelle.

## Bridge vers le Serializer Symfony

`CCMBenchmark\TingBundle\Serializer\SymfonySerializer` implémente le `SerializerInterface` de Ting (voir la [doc Ting sur les Metadata et les serializers](https://gitlab.ccmbg.com/core/ting/-/blob/master/documentation/03-repository-et-metadata.md#types-disponibles)) par-dessus `symfony/serializer` (dépendance dev, à ajouter avec `composer require symfony/serializer` pour l'utiliser).

Il délègue `serialize()` / `unserialize()` au serializer Symfony configuré dans l'application, ce qui permet de réutiliser les normalizers/encoders déjà en place pour les champs gérés par Ting, plutôt que d'écrire un serializer spécifique à la main (voir « Écrire son propre serializer » dans la doc Ting).

```php
$metadata->addField([
    'fieldName'  => 'tags',
    'columnName' => 'tags_name',
    'type'       => 'json',
    'serializer' => \CCMBenchmark\TingBundle\Serializer\SymfonySerializer::class,
]);
```

(Ou, via les attributs du bundle : `#[Schema\Column(serializer: \CCMBenchmark\TingBundle\Serializer\SymfonySerializer::class)]`.)
