<?php

namespace App\Entity;

use App\Repository\ContactRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
class Contact
{
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
    private ?string $status = null; // 'pending', 'appointment_scheduled', 'confirmed', 'rejected'

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $responseDate = null;

    // Nouveaux champs
    #[ORM\Column(nullable: true)]
    private ?\DateTimeInterface $appointmentDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $customMessage = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = 'pending';
    }

    // Getters et setters existants...
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

    // Nouveaux getters/setters
    public function getAppointmentDate(): ?\DateTimeInterface
    {
        return $this->appointmentDate;
    }

    public function setAppointmentDate(?\DateTimeInterface $appointmentDate): static
    {
        $this->appointmentDate = $appointmentDate;
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

    public function getStatusText(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'appointment_scheduled' => 'RDV programmé',
            'confirmed' => 'Confirmé',
            'rejected' => 'Refusé',
            default => 'Inconnu'
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'appointment_scheduled' => 'info',
            'confirmed' => 'success',
            'rejected' => 'danger',
            default => 'secondary'
        };
    }
}
