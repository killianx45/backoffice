<?php

namespace App\Entity;

use App\Repository\RatingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Get;

#[ApiResource(
    operations: [
        new Post(
            normalizationContext: ['groups' => ['rating:read']],
            denormalizationContext: ['groups' => ['rating:write']]
        ),
        new Get(
            normalizationContext: ['groups' => ['rating:read']]
        )
    ]
)]
#[ORM\Entity(repositoryClass: RatingRepository::class)]
#[ORM\Table(name: 'rating')]
#[ORM\UniqueConstraint(name: 'unique_user_article_rating', columns: ['user_id', 'article_id'])]
#[ORM\HasLifecycleCallbacks]
class Rating
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['rating:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['rating:read', 'rating:write'])]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Article::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['rating:read', 'rating:write'])]
    private ?Article $article = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\NotBlank(message: 'La note est obligatoire')]
    #[Assert\Range(
        min: 1,
        max: 5,
        notInRangeMessage: 'La note doit être comprise entre {{ min }} et {{ max }} étoiles'
    )]
    #[Groups(['rating:read', 'rating:write'])]
    private ?int $stars = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['rating:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['rating:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): static
    {
        $this->article = $article;

        return $this;
    }

    public function getStars(): ?int
    {
        return $this->stars;
    }

    public function setStars(int $stars): static
    {
        $this->stars = $stars;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}