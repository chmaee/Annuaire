<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_LOGIN', fields: ['login'])]
#[UniqueEntity('login', message : "Ce login est déjà pris !")]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['adresseEmail'])]
#[UniqueEntity('adresseEmail', message : "Cette adresse mail est déjà prise !")]
#[ORM\HasLifecycleCallbacks]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\Length(min: 4, minMessage: 'Il faut au moins 4 caractères !')]
    #[Assert\Length(max: 20, maxMessage: 'Il faut au maximum 20 caractères !')]
    private ?string $login = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\Email(message : 'Veuillez entrer une adresse email valide !')]
    private ?string $adresseEmail = null;

    /**
     * @var string The hashed password
     */
    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(type: 'boolean')]
    private bool $visible = true;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateDerniereEdition = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateDerniereConnexion = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(string $login): static
    {
        $this->login = $login;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->login;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // Garantir que chaque utilisateur a au moins ROLE_USER
        if (!in_array('ROLE_USER', $roles)) {
            $roles[] = 'ROLE_USER';
        }
        return array_unique($roles);
    }


    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Si vous stockez des données temporaires et sensibles sur l'utilisateur, effacez-les ici
        // $this->plainPassword = null ;
    }

    public function getAdresseEmail(): ?string
    {
        return $this->adresseEmail;
    }

    public function setAdresseEmail(string $adresseEmail): static
    {
        $this->adresseEmail = $adresseEmail;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;
        return $this;
    }

    public function getDateDerniereEdition(): ?\DateTimeInterface
    {
        return $this->dateDerniereEdition;
    }

    public function setDateDerniereEdition(?\DateTimeInterface $dateDerniereEdition): static
    {
        $this->dateDerniereEdition = $dateDerniereEdition;
        return $this;
    }

    public function getDateDerniereConnexion(): ?\DateTimeInterface
    {
        return $this->dateDerniereConnexion;
    }

    public function setDateDerniereConnexion(?\DateTimeInterface $dateDerniereConnexion): static
    {
        $this->dateDerniereConnexion = $dateDerniereConnexion;
        return $this;
    }

    public function addRole(string $role): void
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
    }

    public function removeRole($role): void
    {
        $index = array_search($role, $this->roles);
        if ($index !== false) {
            unset($this->roles[$index]);
        }
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    #[ORM\PreUpdate]
    public function onPreUpdateChange(PreUpdateEventArgs $event): void
    {
        // On vérifie que l'entité a vraiment été modifiée
        if ($event->hasChangedField('login') || $event->hasChangedField('adresseEmail') || $event->hasChangedField('code') || $event->hasChangedField('telephone')) {
            $this->updateLastEdited();
        }
    }

    public function updateLastEdited(): static
    {
        $this->dateDerniereEdition = (new \DateTime())->modify('+2 hours');
        return $this;
    }

}
