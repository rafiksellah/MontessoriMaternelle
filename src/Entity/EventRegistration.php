<?php

namespace App\Entity;

use App\Repository\EventRegistrationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRegistrationRepository::class)]
class EventRegistration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Veuillez fournir un Nom")]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Veuillez fournir un Prenom")]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Veuillez fournir un email valide")]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas un email valide.")]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: "Veuillez fournir un numéro de téléphone")]
    #[Assert\Regex(pattern: "/^[0-9+\s]+$/", message: "Le numéro de téléphone doit contenir uniquement des chiffres")]
    private ?string $phone = null;
    
    #[ORM\Column]
    private ?\DateTimeImmutable $registeredAt = null;
    
    #[ORM\OneToMany(mappedBy: 'eventRegistration', targetEntity: Guest::class, cascade: ['persist'], orphanRemoval: true)]
    #[Assert\Valid]
    #[Assert\Count(
        max: 6,
        maxMessage: "Vous ne pouvez pas ajouter plus de {{ limit }} participants"
    )]
    private Collection $guests;

    #[ORM\OneToOne(mappedBy: 'eventRegistration', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    
    #[ORM\Column(options: ["default" => false])]
    private bool $reminderSent = false;
    
    public function __construct()
    {
        $this->guests = new ArrayCollection();
        $this->registeredAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

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

    /**
     * @return Collection<int, Guest>
     */
    public function getGuests(): Collection
    {
        return $this->guests;
    }

    public function addGuest(Guest $guest): static
    {
        if (!$this->guests->contains($guest)) {
            $this->guests->add($guest);
            $guest->setEventRegistration($this);
        }

        return $this;
    }

    public function removeGuest(Guest $guest): static
    {
        if ($this->guests->removeElement($guest)) {
            // set the owning side to null (unless already changed)
            if ($guest->getEventRegistration() === $this) {
                $guest->setEventRegistration(null);
            }
        }

        return $this;
    }

    /**
     * Get the value of registeredAt
     */ 
    public function getRegisteredAt()
    {
        return $this->registeredAt;
    }

    /**
     * Set the value of registeredAt
     *
     * @return  self
     */ 
    public function setRegisteredAt($registeredAt)
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        // unset the owning side of the relation if necessary
        if ($user === null && $this->user !== null) {
            $this->user->setEventRegistration(null);
        }

        // set the owning side of the relation if necessary
        if ($user !== null && $user->getEventRegistration() !== $this) {
            $user->setEventRegistration($this);
        }

        $this->user = $user;

        return $this;
    }

    public function isReminderSent(): bool
    {
        return $this->reminderSent;
    }

    public function setReminderSent(bool $reminderSent): self
    {
        $this->reminderSent = $reminderSent;
        return $this;
    }
}