# Contrainte d'unicité

Si vous utilisez `symfony/validator`, la contrainte `CCMBenchmark\TingBundle\Validator\Constraints\UniqueEntity` permet de garantir qu'une valeur (ou une combinaison de valeurs) est unique dans une table, en s'appuyant sur `Repository::getOneBy()`.

## Options

| Option | Obligatoire | Rôle |
|---|---|---|
| `repository` | oui | classe du Repository Ting à interroger |
| `fields` | oui | propriété(s) de l'entité qui doivent être uniques ensemble |
| `identityFields` | non | propriété(s) identifiant l'entité elle-même (voir plus bas) |
| `message` | non | message par défaut : `Another entity exists for this data: {{ data }}` |

## Exemple : unicité d'un email à la création

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
#[UniqueEntity(options: ['repository' => UserRepository::class, 'fields' => ['email']], groups: ['create'])]
class User implements UserInterface, NotifyPropertyInterface
{
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

    // getRoles(), eraseCredentials(), getUserIdentifier(), __serialize()...
}
```

```php
namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/users', methods: ['POST'], format: 'json')]
class CreateUserController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly UserRepository $userRepository,
    ) {}

    public function __invoke(#[MapRequestPayload(serializationContext: ['groups' => ['create']])] User $user): JsonResponse
    {
        $violations = $this->validator->validate($user, groups: ['create']);
        if ($violations->count() > 0) {
            return new JsonResponse(['message' => 'Errors...'], 422);
        }
        $this->userRepository->save($user);
        return new JsonResponse(['message' => 'User registered'], 201);
    }
}
```

## Autoriser la mise à jour de l'entité elle-même (`identityFields`)

Sans `identityFields`, la contrainte échoue dès qu'une entité correspondant aux `fields` existe déjà — y compris **l'entité qu'on est en train de mettre à jour elle-même** (un utilisateur qui resauvegarde son propre email sans le changer serait rejeté).

`identityFields` corrige ce cas : le validateur ne considère qu'il y a un vrai conflit que si l'entité trouvée en base **diffère** de l'entité validée sur ces champs (typiquement la clé primaire) :

```php
#[UniqueEntity(options: [
    'repository'      => UserRepository::class,
    'fields'          => ['email'],
    'identityFields'  => ['id'],
], groups: ['update'])]
```

Ici, si l'email correspond à une ligne dont l'`id` est le même que celui de l'entité validée, ce n'est pas un doublon (c'est la même ligne) : la validation passe.
