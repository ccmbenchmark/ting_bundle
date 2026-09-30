# Value Resolver

Le bundle enregistre automatiquement un [Value Resolver](https://symfony.com/doc/current/controller/value_resolver.html#built-in-value-resolvers) (`CCMBenchmark\TingBundle\ArgumentResolver\EntityValueResolver`, fortement inspiré de l'équivalent Doctrine dans Symfony), qui permet de mapper automatiquement des paramètres de route vers des entités Ting.

## Cas simple : mapping implicite par nom

1. Déclarez un paramètre dans la route en utilisant la propriété de l'entité qui sert à la retrouver (ex. `userId`) : `/api/users/{userId}`.
2. Ajoutez le type de l'entité dans la signature de l'action : `getUser(User $user)`.
3. Faites correspondre le paramètre de route au nom de l'argument via la syntaxe `{parametre:argument}` : `/api/users/{userId:user}`.

```php
namespace App\Controller;

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController
{
    #[Route('/api/users/{userId:user}', name: 'get_user', methods: ['GET'], format: 'json')]
    #[IsGranted('ROLE_USER')]
    public function getUser(User $user): JsonResponse
    {
        return new JsonResponse($this->serializer->serialize($user, 'json', ['groups' => 'default']), json: true);
    }
}
```

Le resolver identifie la clé primaire à partir des paramètres de route (par convention, `id` si présent, ou le mapping explicite `{param:argument}`) et appelle `$repository->get($id)`.

## Cas avancé : `#[MapEntity]`

Pour aller au-delà du mapping par clé primaire, l'attribut `CCMBenchmark\TingBundle\Attribute\MapEntity` (posé sur l'argument du contrôleur) donne accès à plusieurs stratégies de résolution.

### Par expression (`expr`)

Utilise le composant [Expression Language](https://symfony.com/doc/current/components/expression_language.html) — tous les attributs de la requête sont disponibles comme variables, ainsi que le repository de l'entité (`repository`) et la requête elle-même (`request`) :

```php
use CCMBenchmark\TingBundle\Attribute\MapEntity;

class UserController
{
    #[Route('/api/users/{firstname}/{lastname}', name: 'get_user', methods: ['GET'], format: 'json')]
    #[IsGranted('ROLE_USER')]
    public function getUser(
        #[MapEntity(expr: 'repository.getOneBy({"firstname": firstname, "lastname": lastname})')] User $user
    ): JsonResponse {
        return new JsonResponse($this->serializer->serialize($user, 'json', ['groups' => 'default']), json: true);
    }
}
```

### Par critères (`mapping` / `exclude` / `stripNull`)

Quand aucune expression n'est fournie, le resolver retombe sur `Repository::getOneBy($criteria)`. Les critères peuvent être précisés explicitement :

- **`mapping`** : tableau `paramètre de route => propriété de l'entité` (ou simple liste si les noms sont identiques). Sans `mapping`, et si l'argument correspond à un attribut de requête qui est lui-même un tableau, ce tableau est utilisé tel quel comme critères.
- **`exclude`** : liste de propriétés à retirer des critères construits (utile pour exclure un attribut de requête qui ne doit pas participer à la recherche).
- **`stripNull`** : si `true`, retire du tableau de critères les valeurs `null` avant l'appel à `getOneBy()` (par défaut `false` : une valeur `null` explicite participe à la recherche).

### Par identifiant explicite (`id`)

- **`id`** (`string` ou tableau de `string`) : si renseigné et que le(s) paramètre(s) de route correspondant(s) existent, le resolver appelle directement `$repository->get($id)` plutôt que `getOneBy()`. Un tableau permet une clé primaire composite ; un `%s` dans un nom de champ est remplacé par le nom de l'argument (ex. `"%s_uuid"` → `"foobar_uuid"` pour l'argument `$foobar`).
- **`id` et `mapping`/`exclude` sont mutuellement exclusifs** — les combiner lève une `\LogicException` au chargement des routes.

### Autres options

- **`class`** : force la classe de l'entité à résoudre (déduite du type de l'argument par défaut).
- **`forcePrimary`** : si `true`, force Ting à lire sur la connexion primaire/master plutôt que sur un slave (utile juste après une écriture, pour être sûr de lire la donnée à jour).
- **`disabled`** : désactive le resolver pour cet argument (utile pour surcharger des valeurs par défaut globales sans mapping automatique).
- **`message`** : message d'erreur personnalisé si l'entité n'est pas trouvée (sinon `NotFoundHttpException` avec un message générique).

### Comportement en cas d'échec

Si aucune entité n'est trouvée :
- si l'argument est **nullable** (`?User $user`), `null` est injecté ;
- sinon, une `Symfony\Component\HttpKernel\Exception\NotFoundHttpException` est levée (404).
