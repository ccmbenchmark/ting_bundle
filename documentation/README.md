# Documentation Ting Bundle

`ting_bundle` est le bundle Symfony pour [Ting](https://gitlab.ccmbg.com/core/ting), le datamapper PHP maison de CCM Benchmark. Il fournit la configuration, l'injection de dépendances et les intégrations Symfony (User Provider, Value Resolver, Validator, Profiler...) nécessaires pour utiliser Ting dans une application Symfony (4.4 à 7.x).

Cette documentation découpe par sujet le contenu du [README.md](../README.md) du dépôt, en l'enrichissant de détails tirés directement du code source (`src/TingBundle/`) — voir aussi [COREPHP-744](https://gitlab.ccmbg.com/core/ting).

## Sommaire

1. [Installation et configuration](01-installation-et-configuration.md) — installer le bundle, configurer connexions/repositories
2. [Déclarer une entité](02-declarer-une-entite.md) — propriétés publiques, attributs `Schema\Table`/`Schema\Column`
3. [User Provider](03-user-provider.md) — utiliser Ting pour l'authentification Symfony Security
4. [Contrainte d'unicité](04-contrainte-unicite.md) — valider qu'une valeur est unique en base
5. [Value Resolver](05-value-resolver.md) — injecter automatiquement des entités dans les contrôleurs
6. [Profiler et cache des métadonnées](06-profiler-et-cache-metadata.md) — data collectors, warmup/clear
7. [Configuration dynamique et Serializer](07-configuration-dynamique-et-serializer.md) — `ConfigurationResolverInterface`, bridge `symfony/serializer`

## Versions concernées

Décrit l'API du bundle sur la branche `master` (dépend de `ccmbenchmark/ting` ^4.0, Symfony 4.4 à 7.x). Voir aussi la [documentation de Ting](https://gitlab.ccmbg.com/core/ting/-/tree/master/documentation) pour les concepts sous-jacents (Repository, Metadata, UnitOfWork, Hydrateurs...).
