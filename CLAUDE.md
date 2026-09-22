# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Projet Ting Bundle

Bundle Symfony pour [Ting](https://gitlab.ccmbg.com/core/ting), le DataMapper PHP maison de CCMBenchmark. Fournit la configuration, l'injection de dépendances et les intégrations Symfony (User Provider, Value Resolver, Validator, DataCollector...) pour utiliser Ting dans une application Symfony.

**Dépôt** : https://gitlab.ccmbg.com/core/ting_bundle (rapatrié depuis GitHub, historique conservé — voir COREPHP-649)

**Stack technique :**
- PHP >=8.1
- Symfony ^4.4 || ^5.0 || ^6.0 || ^7.0 (validator, http-kernel, dependency-injection, config, stopwatch)
- Dépend de `ccmbenchmark/ting` (^3.11)
- Tests unitaires : [atoum](https://atoum.org/)

## 🏗️ Architecture

```
src/TingBundle/
├── DependencyInjection/    # TingExtension, Configuration (bundle config `ting:`)
├── Schema/                 # Attributs pour déclarer les entités (Table, Column)
├── Repository/             # Extensions Symfony du Repository Ting
├── Security/               # User Provider Ting pour Symfony Security
├── Validator/               # Constraint UniqueEntity
├── ArgumentResolver/        # Value Resolver (mapping paramètres de route -> entités)
├── ConfigurationResolver/   # Résolution de la config (connections, databases_options)
├── Serializer/              # Intégration symfony/serializer
├── Cache/                   # Intégration cache
├── DataCollector/           # Collector pour le profiler Symfony
└── Attribute/               # Ex: MapEntity
```

## 🎯 Concepts clés

Voir le [README.md](README.md) pour le détail (config YAML, attributs `#[Schema\Table]`/`#[Schema\Column]`, User Provider, `UniqueEntity`, Value Resolver `#[MapEntity]`).

- **Déclaration d'entité** : via attributs PHP (`CCMBenchmark\TingBundle\Schema\Table` et `Schema\Column`), plus besoin de config YAML par défaut.
- **User Provider** : provider `ting` enregistré automatiquement, utilisable dans `security.providers`.
- **Value Resolver** : mapping automatique des paramètres de route vers des entités Ting (`{userId:user}`), avec support de l'Expression Language via `#[MapEntity(expr: ...)]`.
- **Profiler** : deux data collectors auto-enregistrés (`ting.driver`, `ting.cache`), visibles dans la toolbar Symfony en dev, sans config.
- **Cache metadata** : `MetadataWarmer`/`MetadataClearer` auto-enregistrés, branchés sur `cache:warmup`/`cache:clear` pour ne pas recalculer les métadonnées à chaque requête en prod.
- **ConfigurationResolver** : point d'extension optionnel (`ting.configuration_resolver`, implémente `ConfigurationResolverInterface`) pour résoudre dynamiquement les `options` d'un groupe de repositories.
- **SymfonySerializer** : bridge vers `symfony/serializer` pour réutiliser les normalizers/encoders de l'app côté Ting.

## 🧪 Tests

Tests unitaires avec [atoum](http://docs.atoum.org/), configuration dans `.atoum.php`.

```bash
# Lancer la suite de tests
vendor/bin/atoum -c .bootstrap.atoum.php --configurations .atoum.php

# Rapport de couverture généré dans tests/coverage/
```

Tests dans `tests/units/TingBundle/`, fixtures dans `tests/fixtures/`. Le suffixe `ArgumentResolver` est automatiquement exclu si la classe `Symfony\Component\HttpKernel\Attribute\ValueResolver` n'existe pas (Symfony < 6).

## ⚙️ Composer

```bash
composer install
composer dump-autoload
```

## 🎓 Bonnes pratiques

- **Compatibilité multi-Symfony** : le bundle supporte Symfony 4.4 à 7.x — vérifier la compatibilité des nouvelles fonctionnalités sur toutes les versions supportées (cf. le pattern `class_exists`/`interface_exists` dans `.atoum.php` pour exclure certains tests).
- **Code style** : PSR-2/PSR-12.

## Git

- Dépôt rapatrié de GitHub vers GitLab (COREPHP-649). L'ancien dépôt GitHub peut être maintenu en miroir en lecture (push mirror) pour ne pas casser les projets consommateurs référençant encore l'URL GitHub dans leur `composer.json`.
