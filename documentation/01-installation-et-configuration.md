# Installation et configuration

## Installation

```bash
composer require ccmbenchmark/ting_bundle
```

Si le bundle n'est pas activé automatiquement (Symfony Flex), l'enregistrer dans `config/bundles.php` :

```php
CCMBenchmark\TingBundle\TingBundle::class => ['all' => true],
```

## Configuration principale

La configuration `ting:` regroupe deux choses : les **connexions** aux bases de données (équivalent Symfony de la config `ConnectionPool` de Ting — voir la [doc Ting, démarrage rapide](https://gitlab.ccmbg.com/core/ting/-/blob/master/documentation/01-demarrage-rapide.md)), et les **repositories** à charger si vous ne déclarez pas vos entités via des attributs.

```yaml
# config/packages/ting.yaml
ting:
    repositories: # Inutile si les entités sont déclarées avec des attributs (voir 02-declarer-une-entite.md)
        Acme:
            namespace: Acme\DemoBundle\Entity
            directory: "@DemoBundle/Entity"
            options:
                # passer des options à votre repository
                Acme\DemoBundle\BazRepository:
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

- `repositories.<alias>` regroupe un ensemble de repositories à scanner (`namespace` + `directory`), avec des `options` par défaut (`default`) ou par classe de repository.
- `connections.<nom>` définit une connexion nommée, réutilisée ensuite dans `initMetadata()` (`setConnectionName`) ou dans les attributs `Schema\Table`.
- `databases_options.<database>` fixe des options par base (ex. `timezone`), transmises telles quelles à `ConnectionPool::setDatabaseOptions()`.

> Si les `options` d'un groupe doivent être calculées dynamiquement plutôt que déclarées statiquement en YAML, voir [Configuration dynamique](07-configuration-dynamique-et-serializer.md#résolution-dynamique-de-configuration).
