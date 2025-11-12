<?php

namespace App\Entity;

use App\Repository\GarmentRepository;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: GarmentRepository::class)]
class Garment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['garment:list','garment:item'])]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Groups(['garment:list','garment:item'])]
    private ?string $name = null;


    #[ORM\Column(length: 255)]
    #[Groups(['garment:list','garment:item'])]
    private ?string $imageUrl = null;

    #[ORM\Column(length: 32)]
    #[Groups(['garment:list','garment:item'])]
    private ?string $category = null;

    #[ORM\Column(length: 24, nullable: true)]
    #[Groups(['garment:list','garment:item'])]
    private ?string $color = null;

    #[ORM\Column(length: 24, nullable: true)]
    #[Groups(['garment:list','garment:item'])]
    private ?string $season = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['garment:list','garment:item'])]
    private ?array $styleTags = null;

    #[ORM\Column]
    #[Groups(['garment:list','garment:item'])]
    private ?\DateTimeImmutable $createdAt = null;

    // Propriétaire
    #[ORM\ManyToOne(inversedBy: 'garments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // GETTERS / SETTERS
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }
    public function setImageUrl(string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }
    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }
    public function setColor(?string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getSeason(): ?string
    {
        return $this->season;
    }
    public function setSeason(?string $season): self
    {
        $this->season = $season;
        return $this;
    }

    public function getStyleTags(): ?array
    {
        return $this->styleTags;
    }
    public function setStyleTags(?array $styleTags): self
    {
        $this->styleTags = $styleTags;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    // HELPERS D'IMAGE
    public function getImagePath(): ?string
    {
        $v = $this->imageUrl;
        if (!$v) {
            return null;
        }

        // URL absolue ?
        if (preg_match('#^https?://#i', $v) === 1) {
            return $v;
        }

        // Normalisation d’un chemin local
        $path = str_replace('\\', '/', $v);
        // enlève "public/" au début si présent
        $path = preg_replace('#^public/#i', '', $path);
        // s’assure que ça commence par un slash
        if ($path !== '' && $path[0] !== '/') {
            $path = '/' . $path;
        }

        return $path;
    }

    public function getImageWeb(): ?string
    {
        return $this->getImagePath();
    }
}
