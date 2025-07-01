<?php

namespace App\Entity;

use App\Repository\ContactRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_CONTACT_EMAIL', columns: ['email'])]
#[UniqueEntity('email', message: 'Cet email a déjà été utilisé.')]
class Contact
{
    // Constantes pour les statuts - gardons les valeurs internes pour la compatibilité
    public const STATUS_PENDING = '0';                    // En attente
    public const STATUS_APPOINTMENT_SCHEDULED = '1';     // En cours (étape 1)
    public const STATUS_CONFIRMED = '2';                 // En cours (étape 2)
    public const STATUS_AFTER_VISIT = '3';              // En cours (étape 3)
    public const STATUS_INSCRIPTION_ACCEPTED = '4';      // Inscrit
    public const STATUS_PROCESSED = '6';                 // Traité
    public const STATUS_REJECTED = '5';                  // Refusé

    // Mapping des statuts simplifiés
    public const SIMPLE_STATUS_PENDING = 'pending';      // En attente
    public const SIMPLE_STATUS_IN_PROGRESS = 'in_progress'; // En cours
    public const SIMPLE_STATUS_REJECTED = 'rejected';    // Refusé
    public const SIMPLE_STATUS_ENROLLED = 'enrolled';    // Inscrit
    public const SIMPLE_STATUS_PROCESSED = 'processed';  // Traité

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $parentName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $childName = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTimeInterface $childBirthDate = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 20)]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private ?string $objective = null;

    #[ORM\Column(length: 255)]
    private ?string $heardAboutUs = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $expectations = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $responseDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $customMessage = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $appointmentDate = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = self::STATUS_PENDING;
    }

    // Méthode pour obtenir le statut simplifié
    public function getSimpleStatus(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => self::SIMPLE_STATUS_PENDING,
            self::STATUS_APPOINTMENT_SCHEDULED,
            self::STATUS_CONFIRMED,
            self::STATUS_AFTER_VISIT => self::SIMPLE_STATUS_IN_PROGRESS,
            self::STATUS_INSCRIPTION_ACCEPTED => self::SIMPLE_STATUS_ENROLLED,
            self::STATUS_PROCESSED => self::SIMPLE_STATUS_PROCESSED,
            self::STATUS_REJECTED => self::SIMPLE_STATUS_REJECTED,
            default => self::SIMPLE_STATUS_PENDING
        };
    }

    // Méthode pour obtenir le texte du statut simplifié
    public function getSimpleStatusText(): string
    {
        return match ($this->getSimpleStatus()) {
            self::SIMPLE_STATUS_PENDING => 'En attente',
            self::SIMPLE_STATUS_IN_PROGRESS => 'En cours',
            self::SIMPLE_STATUS_REJECTED => 'Refusé',
            self::SIMPLE_STATUS_ENROLLED => 'Inscrit',
            self::SIMPLE_STATUS_PROCESSED => 'Traité',
            default => 'Inconnu'
        };
    }

    // Méthode pour obtenir la couleur du statut simplifié
    public function getSimpleStatusColor(): string
    {
        return match ($this->getSimpleStatus()) {
            self::SIMPLE_STATUS_PENDING => 'warning',
            self::SIMPLE_STATUS_IN_PROGRESS => 'info',
            self::SIMPLE_STATUS_REJECTED => 'danger',
            self::SIMPLE_STATUS_ENROLLED => 'success',
            self::SIMPLE_STATUS_PROCESSED => 'secondary',
            default => 'light'
        };
    }

    // Méthode pour obtenir le détail de l'étape en cours
    public function getProgressDetail(): ?string
    {
        if ($this->getSimpleStatus() !== self::SIMPLE_STATUS_IN_PROGRESS) {
            return null;
        }

        return match ($this->status) {
            self::STATUS_APPOINTMENT_SCHEDULED => 'RDV programmé',
            self::STATUS_CONFIRMED => 'RDV confirmé',
            self::STATUS_AFTER_VISIT => 'Après visite',
            default => null
        };
    }

    // Méthode pour vérifier si un statut est valide
    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::getAllStatuses());
    }

    public static function getAllStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPOINTMENT_SCHEDULED,
            self::STATUS_CONFIRMED,
            self::STATUS_AFTER_VISIT,
            self::STATUS_INSCRIPTION_ACCEPTED,
            self::STATUS_PROCESSED,
            self::STATUS_REJECTED,
        ];
    }

    // Gardons les méthodes existantes pour la compatibilité avec les emails
    public function getStatusText(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_APPOINTMENT_SCHEDULED => 'RDV programmé',
            self::STATUS_CONFIRMED => 'Confirmé',
            self::STATUS_INSCRIPTION_ACCEPTED => 'Inscription acceptée',
            self::STATUS_PROCESSED => 'Traité',
            self::STATUS_AFTER_VISIT => 'Suivi après visite',
            self::STATUS_REJECTED => 'Refusé',
            default => 'Inconnu'
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPOINTMENT_SCHEDULED => 'info',
            self::STATUS_CONFIRMED => 'success',
            self::STATUS_INSCRIPTION_ACCEPTED => 'primary',
            self::STATUS_PROCESSED => 'success',
            self::STATUS_AFTER_VISIT => 'dark',
            self::STATUS_REJECTED => 'danger',
            default => 'secondary'
        };
    }

    // Méthodes de transition - gardées identiques pour les emails
    public function canProcess(): bool
    {
        return $this->status === self::STATUS_INSCRIPTION_ACCEPTED;
    }

    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function hasAppointmentScheduled(): bool
    {
        return $this->status === self::STATUS_APPOINTMENT_SCHEDULED;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isAfterVisit(): bool
    {
        return $this->status === self::STATUS_AFTER_VISIT;
    }

    public function isInscriptionAccepted(): bool
    {
        return $this->status === self::STATUS_INSCRIPTION_ACCEPTED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    // Méthodes pour les transitions de statut - gardées identiques
    public function canScheduleAppointment(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canConfirm(): bool
    {
        return $this->status === self::STATUS_APPOINTMENT_SCHEDULED;
    }

    public function canSendAfterVisit(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function canAcceptInscription(): bool
    {
        return $this->status === self::STATUS_AFTER_VISIT;
    }

    public function canReject(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_APPOINTMENT_SCHEDULED,
            self::STATUS_CONFIRMED,
            self::STATUS_AFTER_VISIT
        ]);
    }

    // Getters et Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParentName(): ?string
    {
        return $this->parentName;
    }

    public function setParentName(string $parentName): static
    {
        $this->parentName = $parentName;
        return $this;
    }

    public function getChildName(): ?string
    {
        return $this->childName;
    }

    public function setChildName(string $childName): static
    {
        $this->childName = $childName;
        return $this;
    }

    public function getChildBirthDate(): ?\DateTimeInterface
    {
        return $this->childBirthDate;
    }

    public function setChildBirthDate(\DateTimeInterface $childBirthDate): static
    {
        $this->childBirthDate = $childBirthDate;
        return $this;
    }

    public function getChildAge(): int
    {
        if (!$this->childBirthDate) {
            return 0;
        }
        return (new \DateTime())->diff($this->childBirthDate)->y;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getObjective(): ?string
    {
        return $this->objective;
    }

    public function setObjective(string $objective): static
    {
        $this->objective = $objective;
        return $this;
    }

    public function getHeardAboutUs(): ?string
    {
        return $this->heardAboutUs;
    }

    public function setHeardAboutUs(?string $heardAboutUs): static
    {
        $this->heardAboutUs = $heardAboutUs;
        return $this;
    }

    public function getExpectations(): ?string
    {
        return $this->expectations;
    }

    public function setExpectations(?string $expectations): static
    {
        $this->expectations = $expectations;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getResponseDate(): ?\DateTimeImmutable
    {
        return $this->responseDate;
    }

    public function setResponseDate(?\DateTimeImmutable $responseDate): static
    {
        $this->responseDate = $responseDate;
        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): static
    {
        $this->rejectionReason = $rejectionReason;
        return $this;
    }

    public function getCustomMessage(): ?string
    {
        return $this->customMessage;
    }

    public function setCustomMessage(?string $customMessage): static
    {
        $this->customMessage = $customMessage;
        return $this;
    }

    public function getAppointmentDate(): ?\DateTimeImmutable
    {
        return $this->appointmentDate;
    }

    public function setAppointmentDate(?\DateTimeImmutable $appointmentDate): static
    {
        $this->appointmentDate = $appointmentDate;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }
}
