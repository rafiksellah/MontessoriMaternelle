<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity]
#[ORM\Table(name: 'job_applications')]
#[Vich\Uploadable]
class JobApplication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire')]
    private ?string $prenom = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: 'La date de naissance est obligatoire')]
    private ?\DateTime $dateNaissance = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le lieu de résidence est obligatoire')]
    private ?string $lieuResidence = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'La nationalité est obligatoire')]
    private ?string $nationalite = null;

    #[ORM\Column(type: 'boolean')]
    private bool $permisTravail = false;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank(message: 'Le téléphone est obligatoire')]
    #[Assert\Regex(pattern: '/^[0-9+\-\s()]+$/', message: 'Format de téléphone invalide')]
    private ?string $telephone = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'L\'email n\'est pas valide')]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le poste souhaité est obligatoire')]
    private ?string $posteSouhaite = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Veuillez expliquer pourquoi ce poste vous attire')]
    #[Assert\Length(min: 10, max: 500, minMessage: 'Réponse trop courte', maxMessage: 'Réponse trop longue')]
    private ?string $raisonInteretPoste = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $cvFilename = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $motivationFilename = null;

    #[ORM\Column(type: 'json')]
    private array $langues = [];

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Veuillez expliquer votre motivation')]
    #[Assert\Length(min: 10, max: 1000, minMessage: 'Réponse trop courte', maxMessage: 'Réponse trop longue')]
    private ?string $motivationMMA = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Veuillez expliquer votre contribution')]
    #[Assert\Length(min: 10, max: 1000, minMessage: 'Réponse trop courte', maxMessage: 'Réponse trop longue')]
    private ?string $contributionMMA = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'La disponibilité est obligatoire')]
    private ?string $disponibilite = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'L\'engagement souhaité est obligatoire')]
    private ?string $engagement = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getDateNaissance(): ?\DateTime
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTime $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    public function getLieuResidence(): ?string
    {
        return $this->lieuResidence;
    }

    public function setLieuResidence(string $lieuResidence): self
    {
        $this->lieuResidence = $lieuResidence;
        return $this;
    }

    public function getNationalite(): ?string
    {
        return $this->nationalite;
    }

    public function setNationalite(string $nationalite): self
    {
        $this->nationalite = $nationalite;
        return $this;
    }

    public function isPermisTravail(): bool
    {
        return $this->permisTravail;
    }

    public function setPermisTravail(bool $permisTravail): self
    {
        $this->permisTravail = $permisTravail;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPosteSouhaite(): ?string
    {
        return $this->posteSouhaite;
    }

    public function setPosteSouhaite(string $posteSouhaite): self
    {
        $this->posteSouhaite = $posteSouhaite;
        return $this;
    }

    public function getRaisonInteretPoste(): ?string
    {
        return $this->raisonInteretPoste;
    }

    public function setRaisonInteretPoste(string $raisonInteretPoste): self
    {
        $this->raisonInteretPoste = $raisonInteretPoste;
        return $this;
    }



    public function getLangues(): array
    {
        return $this->langues;
    }

    public function setLangues(array $langues): self
    {
        $this->langues = $langues;
        return $this;
    }

    public function getMotivationMMA(): ?string
    {
        return $this->motivationMMA;
    }

    public function setMotivationMMA(string $motivationMMA): self
    {
        $this->motivationMMA = $motivationMMA;
        return $this;
    }

    public function getContributionMMA(): ?string
    {
        return $this->contributionMMA;
    }

    public function setContributionMMA(string $contributionMMA): self
    {
        $this->contributionMMA = $contributionMMA;
        return $this;
    }

    public function getDisponibilite(): ?string
    {
        return $this->disponibilite;
    }

    public function setDisponibilite(string $disponibilite): self
    {
        $this->disponibilite = $disponibilite;
        return $this;
    }

    public function getEngagement(): ?string
    {
        return $this->engagement;
    }

    public function setEngagement(string $engagement): self
    {
        $this->engagement = $engagement;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get the value of cvFilename
     */
    public function getCvFilename()
    {
        return $this->cvFilename;
    }

    /**
     * Set the value of cvFilename
     *
     * @return  self
     */
    public function setCvFilename($cvFilename)
    {
        $this->cvFilename = $cvFilename;

        return $this;
    }

    /**
     * Get the value of motivationFilename
     */
    public function getMotivationFilename()
    {
        return $this->motivationFilename;
    }

    /**
     * Set the value of motivationFilename
     *
     * @return  self
     */
    public function setMotivationFilename($motivationFilename)
    {
        $this->motivationFilename = $motivationFilename;

        return $this;
    }
}
