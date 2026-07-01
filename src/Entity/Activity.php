<?php

namespace App\Entity;

use App\Enum\ActivityStatusEnum;
use App\Enum\ReservationStatusEnum;
use App\Repository\ActivityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Activity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(max: 150)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull(message: 'La date de l\'activite est obligatoire.')]
    private ?\DateTimeInterface $dateActivite = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Assert\NotNull(message: 'L\'heure de debut est obligatoire.')]
    private ?\DateTimeInterface $heureDebut = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Assert\NotNull(message: 'L\'heure de fin est obligatoire.')]
    private ?\DateTimeInterface $heureFin = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La capacite maximale est obligatoire.')]
    #[Assert\Positive(message: 'La capacite maximale doit etre superieure a 0.')]
    private ?int $capaciteMax = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'L\'age minimum est obligatoire.')]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'L\'age minimum doit etre superieur ou egal a 0.')]
    private ?int $ageMin = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'L\'age maximum est obligatoire.')]
    private ?int $ageMax = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le prix doit etre positif ou nul.')]
    private ?string $prix = null;

    #[ORM\Column(length: 20, enumType: ActivityStatusEnum::class)]
    #[Assert\NotNull(message: 'Le statut est obligatoire.')]
    private ?ActivityStatusEnum $statut = ActivityStatusEnum::OUVERTE;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $lieu = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'activities')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'La categorie est obligatoire.')]
    private ?Category $category = null;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(mappedBy: 'activity', targetEntity: Reservation::class, orphanRemoval: true)]
    private Collection $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getDateActivite(): ?\DateTimeInterface
    {
        return $this->dateActivite;
    }

    public function setDateActivite(\DateTimeInterface $dateActivite): self
    {
        $this->dateActivite = $dateActivite;

        return $this;
    }

    public function getHeureDebut(): ?\DateTimeInterface
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(\DateTimeInterface $heureDebut): self
    {
        $this->heureDebut = $heureDebut;

        return $this;
    }

    public function getHeureFin(): ?\DateTimeInterface
    {
        return $this->heureFin;
    }

    public function setHeureFin(\DateTimeInterface $heureFin): self
    {
        $this->heureFin = $heureFin;

        return $this;
    }

    public function getCapaciteMax(): ?int
    {
        return $this->capaciteMax;
    }

    public function setCapaciteMax(int $capaciteMax): self
    {
        $this->capaciteMax = $capaciteMax;

        return $this;
    }

    public function getAgeMin(): ?int
    {
        return $this->ageMin;
    }

    public function setAgeMin(int $ageMin): self
    {
        $this->ageMin = $ageMin;

        return $this;
    }

    public function getAgeMax(): ?int
    {
        return $this->ageMax;
    }

    public function setAgeMax(int $ageMax): self
    {
        $this->ageMax = $ageMax;

        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(?string $prix): self
    {
        $this->prix = $prix;

        return $this;
    }

    public function getStatut(): ?ActivityStatusEnum
    {
        return $this->statut;
    }

    public function setStatut(ActivityStatusEnum $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(?string $lieu): self
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setActivity($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->removeElement($reservation) && $reservation->getActivity() === $this) {
            $reservation->setActivity(null);
        }

        return $this;
    }

    public function placesDisponibles(): int
    {
        $reservationsActives = $this->reservations->filter(
            static fn (Reservation $reservation): bool => ReservationStatusEnum::ANNULEE !== $reservation->getStatut()
        )->count();

        return max(0, (int) $this->capaciteMax - $reservationsActives);
    }

    public function estComplete(): bool
    {
        return $this->placesDisponibles() <= 0;
    }

    public function estDisponible(): bool
    {
        $this->updateStatutIfNeeded();

        return ActivityStatusEnum::OUVERTE === $this->statut && $this->estFuture() && !$this->estComplete();
    }

    public function estFuture(): bool
    {
        if (null === $this->dateActivite) {
            return false;
        }

        return $this->dateActivite >= new \DateTimeImmutable('today');
    }

    public function updateStatutIfNeeded(): void
    {
        if (ActivityStatusEnum::ANNULEE === $this->statut) {
            return;
        }

        if (!$this->estFuture()) {
            $this->statut = ActivityStatusEnum::TERMINEE;

            return;
        }

        if ($this->estComplete()) {
            $this->statut = ActivityStatusEnum::COMPLETE;

            return;
        }

        $this->statut = ActivityStatusEnum::OUVERTE;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if (null !== $this->heureDebut && null !== $this->heureFin && $this->heureFin <= $this->heureDebut) {
            $context->buildViolation('L\'heure de fin doit etre superieure a l\'heure de debut.')
                ->atPath('heureFin')
                ->addViolation();
        }

        if (null !== $this->ageMin && null !== $this->ageMax && $this->ageMax < $this->ageMin) {
            $context->buildViolation('L\'age maximum doit etre superieur ou egal a l\'age minimum.')
                ->atPath('ageMax')
                ->addViolation();
        }
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt ??= new \DateTimeImmutable();
        $this->updateStatutIfNeeded();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->updateStatutIfNeeded();
    }
}
