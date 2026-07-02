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
        return $this->countByRole(UserRole::ROLE_PARENT->value);
    }

    public function countAdmins(): int
    {
        return $this->countByRole(UserRole::ROLE_ADMIN->value);
    }

    public function countActiveAdmins(): int
    {
        return count(array_filter(
            $this->findAll(),
            static fn (User $user): bool => $user->isAdmin() && $user->isActive()
        ));
    }

    public function countDisabled(): int
    {
        return count(array_filter(
            $this->findAll(),
            static fn (User $user): bool => !$user->isActive()
        ));
    }

    public function countByRole(string $role): int
    {
        return count(array_filter(
            $this->findAll(),
            static fn (User $user): bool => in_array($role, $user->getRoles(), true)
        ));
    }

    /**
     * @return array<int, array{label: string, count: int}>
     */
    public function getUsersByRole(): array
    {
        return [
            ['label' => 'Parents', 'count' => $this->countParents()],
            ['label' => 'Administrateurs', 'count' => $this->countAdmins()],
        ];
    }

    /**
     * @return list<User>
     */
    public function findDisabledUsers(): array
    {
        return array_values(array_filter(
            $this->findBy([], ['nom' => 'ASC', 'prenom' => 'ASC']),
            static fn (User $user): bool => !$user->isActive()
        ));
    }
}
