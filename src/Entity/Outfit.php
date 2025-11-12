<?php

namespace App\Entity;

use App\Repository\OutfitRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OutfitRepository::class)]
class Outfit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    // On stocke le JSON du canvas en TEXT (simple & robuste)
    #[ORM\Column(type: 'text')]
    private ?string $items = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $snapshotUrl = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'outfits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    // --- Getters / Setters ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getItems(): ?string
    {
        return $this->items;
    }

    public function setItems(array|string $items): static
    {
        // Si c’est un tableau, on le convertit automatiquement en JSON
        $this->items = is_array($items) ? json_encode($items) : $items;
        return $this;
    }

    public function getSnapshotUrl(): ?string
    {
        return $this->snapshotUrl;
    }

    public function setSnapshotUrl(?string $snapshotUrl): static
    {
        $this->snapshotUrl = $snapshotUrl;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }
}
