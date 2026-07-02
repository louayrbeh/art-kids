<?php

namespace App\Entity;

use App\Enum\ReservationStatusEnum;
use App\Repository\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
#[ORM\UniqueConstraint(name: 'uniq_reservation_child_activity', columns: ['child_id', 'activity_id'])]
#[ORM\HasLifecycleCallbacks]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull(message: 'La date de reservation est obligatoire.')]
    private \DateTimeImmutable $dateReservation;

    #[ORM\Column(length: 20, enumType: ReservationStatusEnum::class)]
    #[Assert\NotNull(message: 'Le statut est obligatoire.')]
    private ?ReservationStatusEnum $statut = ReservationStatusEnum::EN_ATTENTE;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'L\'enfant est obligatoire.')]
    private ?Child $child = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'L\'activite est obligatoire.')]
    private ?Activity $activity = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->dateReservation = $now;
        $this->createdAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateReservation(): \DateTimeImmutable
    {
        return $this->dateReservation;
    }

    public function setDateReservation(\DateTimeImmutable $dateReservation): self
    {
        $this->dateReservation = $dateReservation;

        return $this;
    }

    public function getStatut(): ?ReservationStatusEnum
    {
        return $this->statut;
    }

    public function setStatut(ReservationStatusEnum $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getChild(): ?Child
    {
        return $this->child;
    }

    public function setChild(?Child $child): self
    {
        $this->child = $child;

        return $this;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function setActivity(?Activity $activity): self
    {
        $this->activity = $activity;

        return $this;
    }

    public function confirmer(): void
    {
        $this->statut = ReservationStatusEnum::CONFIRMEE;
    }

    public function annuler(): void
    {
        $this->statut = ReservationStatusEnum::ANNULEE;
    }

    public function estEnAttente(): bool
    {
        return ReservationStatusEnum::EN_ATTENTE === $this->statut;
    }

    public function estConfirmee(): bool
    {
        return ReservationStatusEnum::CONFIRMEE === $this->statut;
    }

    public function estAnnulee(): bool
    {
        return ReservationStatusEnum::ANNULEE === $this->statut;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->dateReservation ??= new \DateTimeImmutable();
        $this->createdAt ??= new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
