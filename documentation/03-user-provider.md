# User Provider

Les user providers Symfony (re)chargent les utilisateurs depuis un stockage à partir d'un « identifiant utilisateur » (voir la [documentation Symfony](https://symfony.com/doc/current/security/user_providers.html)). Ting est automatiquement enregistré comme provider `ting`.

## Configuration

```yaml
security:
  providers:
    app_user_provider:
      ting:
        class: App\Entity\User
        property: email
```

L'entité doit implémenter :
- `Symfony\Component\Security\Core\User\UserInterface` (commune à tous les types d'utilisateurs)
- `Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface` (si authentification par mot de passe)
- `__serialize(): array` — nécessaire pour que Symfony puisse sérialiser l'utilisateur en session ; ne doit contenir que les champs strictement utiles à l'identification (voir l'exemple ci-dessous).

## Deux façons de charger un utilisateur

`CCMBenchmark\TingBundle\Security\EntityUserProvider` charge l'utilisateur de l'une des deux façons suivantes :

1. **Avec `property`** (comme dans l'exemple ci-dessus) : appelle `$repository->getOneBy([$property => $identifiant])`.
2. **Sans `property`** : le Repository de l'entité doit alors implémenter `CCMBenchmark\TingBundle\Security\UserLoaderInterface`, qui expose une seule méthode :

```php
interface UserLoaderInterface
{
    public function loadUserByIdentifier(string $identifier): ?UserInterface;
}
```

Utile quand l'identifiant ne correspond pas directement à une colonne simple (recherche insensible à la casse, sur plusieurs colonnes, etc.).

## Rafraîchissement de session (`refreshUser`)

À chaque requête, Symfony redemande l'utilisateur courant pour éviter de garder des données obsolètes en session. Le provider :
- délègue au Repository si celui-ci implémente lui-même `UserProviderInterface` ;
- sinon, recharge l'utilisateur **par sa clé primaire** (l'entité doit donc avoir sérialisé son identifiant via `__serialize()`).

## Migration de mot de passe (`PasswordUpgraderInterface`)

Si le Repository de l'entité implémente `Symfony\Component\Security\Core\User\PasswordUpgraderInterface`, le provider lui délègue automatiquement `upgradePassword()` — pratique pour migrer un hash de mot de passe vers un algorithme plus récent au moment de la connexion, sans code supplémentaire dans le contrôleur d'authentification.
