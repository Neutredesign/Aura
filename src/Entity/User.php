<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    /** @var string The hashed password */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private bool $isVerified = false;

    // --------- NOUVEAUX CHAMPS ---------
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarUrl = null;
    // -----------------------------------

    /** @var Collection<int, Outfit> */
    #[ORM\OneToMany(targetEntity: Outfit::class, mappedBy: 'user')]
    private Collection $outfits;

    /** @var Collection<int, Garment> */
    #[ORM\OneToMany(targetEntity: Garment::class, mappedBy: 'user')]
    private Collection $garments;

    public function __construct()
    {
        $this->outfits  = new ArrayCollection();
        $this->garments = new ArrayCollection();
    }

    // --------- IDENTITÉ / AUTH ---------
    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getUserIdentifier(): string { return (string) $this->email; }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', (string) $this->password);
        return $data;
    }

    #[\Deprecated] public function eraseCredentials(): void {}

    public function isVerified(): bool { return $this->isVerified; }
    public function setIsVerified(bool $isVerified): static { $this->isVerified = $isVerified; return $this; }

    // --------- CHAMPS PROFIL ---------
    public function getUsername(): ?string { return $this->username; }
    public function setUsername(?string $username): static
    {
        $this->username = $username ? mb_substr($username, 0, 50) : null;
        return $this;
    }

    public function getAvatarUrl(): ?string { return $this->avatarUrl; }
    public function setAvatarUrl(?string $avatarUrl): static { $this->avatarUrl = $avatarUrl; return $this; }

    public function getDisplayName(): string { return $this->username ?: ($this->email ?? 'Moi'); }

    // --------- RELATION OUTfits ---------
    /** @return Collection<int, Outfit> */
    public function getOutfits(): Collection { return $this->outfits; }

    public function addOutfit(Outfit $outfit): static
    {
        if (!$this->outfits->contains($outfit)) {
            $this->outfits->add($outfit);
            $outfit->setUser($this);
        }
        return $this;
    }

    public function removeOutfit(Outfit $outfit): static
    {
        if ($this->outfits->removeElement($outfit)) {
            if ($outfit->getUser() === $this) {
                $outfit->setUser(null);
            }
        }
        return $this;
    }

    // --------- RELATION GARMENTS ---------
    /** @return Collection<int, Garment> */
    public function getGarments(): Collection { return $this->garments; }

    public function addGarment(Garment $garment): static
    {
        if (!$this->garments->contains($garment)) {
            $this->garments->add($garment);
            $garment->setUser($this);
        }
        return $this;
    }

    public function removeGarment(Garment $garment): static
    {
        if ($this->garments->removeElement($garment)) {
            if ($garment->getUser() === $this) {
                $garment->setUser(null);
            }
        }
        return $this;
    }
}
