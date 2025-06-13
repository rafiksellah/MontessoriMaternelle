<?php

namespace App\Entity;

use App\Repository\ContactRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContactRepository::class)]

class Contact
{
    // Constantes pour les statuts
    public const STATUS_PENDING = '0';
    public const STATUS_APPOINTMENT_SCHEDULED = '1';
    public const STATUS_CONFIRMED = '2';
    public const STATUS_AFTER_VISIT = '3';
    public const STATUS_INSCRIPTION_ACCEPTED = '4';
    public const STATUS_PROCESSED = '6';
    public const STATUS_REJECTED = '5';

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

    #[ORM\Column(length: 180)]
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

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = self::STATUS_PENDING;
    }


    // Méthode pour vérifier si un statut est valide
    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::getAllStatuses());
    }

    // Getters et setters
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
        if (!self::isValidStatus($status)) {
            throw new \InvalidArgumentException(sprintf('Status "%s" is not valid', $status));
        }
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

    // Méthodes utilitaires
    public function getChildAge(): int
    {
        if (!$this->childBirthDate) {
            return 0;
        }

        return $this->childBirthDate->diff(new \DateTime())->y;
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

    // Nouvelles méthodes de transition pour le statut PROCESSED
    public function canProcess(): bool
    {
        return $this->status === self::STATUS_INSCRIPTION_ACCEPTED;
    }

    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
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

    // Méthodes pour vérifier les statuts
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

    // Méthodes pour les transitions de statut
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
}
