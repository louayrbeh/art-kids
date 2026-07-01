<?php

namespace App\Repository;

use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return list<User>
     */
    public function findParents(): array
    {
        $users = $this->findBy([], ['nom' => 'ASC', 'prenom' => 'ASC']);

        return array_values(array_filter(
            $users,
            static fn (User $user): bool => in_array(UserRole::ROLE_PARENT->value, $user->getRoles(), true)
        ));
    }

    public function countParents(): int
    {
        return count($this->findParents());
    }
}
