# Profiler et cache des métadonnées

Ces deux fonctionnalités sont enregistrées automatiquement dès que le bundle est activé — aucune configuration nécessaire.

## Intégration au Profiler Symfony

En environnement de dev, deux data collectors sont visibles dans la toolbar et le Profiler Symfony :

- **`ting.driver`** (`CCMBenchmark\TingBundle\DataCollector\TingDriverDataCollector`) — chaque requête/exécution faite sur une connexion Ting, son temps d'exécution, et les connexions ouvertes.
- **`ting.cache`** (`CCMBenchmark\TingBundle\DataCollector\TingCacheDataCollector`) — opérations de cache (hits/miss, temps total), quand un cache est configuré pour Ting (voir [Cache](https://gitlab.ccmbg.com/core/ting/-/blob/master/documentation/05-cache.md) côté Ting).

Utile pour repérer des requêtes N+1 ou des requêtes lentes directement depuis la toolbar, sans instrumentation manuelle.

## Cache warmer/clearer des métadonnées

Les métadonnées d'entité (construites depuis la config YAML ou les [attributs](02-declarer-une-entite.md)) sont coûteuses à recalculer à chaque requête. Le bundle enregistre :

- **`CCMBenchmark\TingBundle\Cache\MetadataWarmer`** (implémente `CacheWarmerInterface`) — appelé par `bin/console cache:warmup`, il appelle `batchLoadMetadata()` pour chaque groupe de repositories configuré et écrit le résultat dans un fichier de cache via `MetadataCacheGenerator` (côté Ting).
- **`CCMBenchmark\TingBundle\Cache\MetadataClearer`** (implémente `CacheClearerInterface`) — appelé par `bin/console cache:clear`, il supprime ce fichier de cache pour qu'il soit régénéré au prochain warmup ou à la prochaine requête.

En pratique : rien à faire manuellement, ces deux services sont branchés sur les commandes standard de Symfony. En production, penser à exécuter `cache:warmup` au déploiement (comme pour le reste du cache Symfony) pour éviter un recalcul des métadonnées sur la première requête après un déploiement.
