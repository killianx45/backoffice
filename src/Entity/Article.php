<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $featuredImage = null;

    #[Vich\UploadableField(mapping: 'article_featured_images', fileNameProperty: 'featuredImage')]
    private ?File $featuredImageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $csvFile = null;

    #[Vich\UploadableField(mapping: 'article_csv_files', fileNameProperty: 'csvFile')]
    private ?File $csvFileFile = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Choice(
        choices: ['bar', 'line', 'pie', 'area', 'scatter', 'doughnut', 'radar'],
        message: 'Type de graphique invalide'
    )]
    private ?string $chartType = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $author = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(targetEntity: ArticleChart::class, mappedBy: 'article', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['displayOrder' => 'ASC'])]
    private Collection $charts;

    public function __construct()
    {
        $this->charts = new ArrayCollection();
    }

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

    public function setFeaturedImageFile(?File $featuredImageFile = null): void
    {
        $this->featuredImageFile = $featuredImageFile;

        if (null !== $featuredImageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getFeaturedImageFile(): ?File
    {
        return $this->featuredImageFile;
    }

    public function setCsvFileFile(?File $csvFileFile = null): void
    {
        $this->csvFileFile = $csvFileFile;

        if (null !== $csvFileFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getCsvFileFile(): ?File
    {
        return $this->csvFileFile;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getFeaturedImage(): ?string
    {
        return $this->featuredImage;
    }

    public function setFeaturedImage(?string $featuredImage): static
    {
        $this->featuredImage = $featuredImage;

        return $this;
    }

    public function getCsvFile(): ?string
    {
        return $this->csvFile;
    }

    public function setCsvFile(?string $csvFile): static
    {
        $this->csvFile = $csvFile;

        return $this;
    }

    public function getChartType(): ?string
    {
        return $this->chartType;
    }

    public function setChartType(?string $chartType): static
    {
        $this->chartType = $chartType;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

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

    /**
     * @return Collection<int, ArticleChart>
     */
    public function getCharts(): Collection
    {
        return $this->charts;
    }

    public function addChart(ArticleChart $chart): static
    {
        if (!$this->charts->contains($chart)) {
            $this->charts->add($chart);
            $chart->setArticle($this);
        }

        return $this;
    }

    public function removeChart(ArticleChart $chart): static
    {
        if ($this->charts->removeElement($chart)) {
            if ($chart->getArticle() === $this) {
                $chart->setArticle(null);
            }
        }

        return $this;
    }
}
