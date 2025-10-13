<?php

namespace App\Entity;

use App\Repository\CsvDatasetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['csv_dataset:read']]
        ),
        new Get(
            normalizationContext: ['groups' => ['csv_dataset:read']]
        )
    ]
)]
#[ORM\Entity(repositoryClass: CsvDatasetRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CsvDataset
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['csv_dataset:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['csv_dataset:read'])]
    private ?string $filename = null;

    #[ORM\Column(length: 255)]
    #[Groups(['csv_dataset:read'])]
    private ?string $originalName = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['csv_dataset:read'])]
    private ?User $uploadedBy = null;

    #[ORM\Column]
    #[Groups(['csv_dataset:read'])]
    private ?\DateTimeImmutable $uploadedAt = null;

    #[ORM\Column]
    #[Groups(['csv_dataset:read'])]
    private ?int $fileSize = null;

    #[ORM\Column]
    #[Groups(['csv_dataset:read'])]
    private ?int $rowCount = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['csv_dataset:read'])]
    private bool $isConfigured = false;

    #[ORM\OneToMany(targetEntity: CsvVariable::class, mappedBy: 'dataset', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['csv_dataset:read'])]
    private Collection $variables;

    public function __construct()
    {
        $this->variables = new ArrayCollection();
        $this->uploadedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;
        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): static
    {
        $this->originalName = $originalName;
        return $this;
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): static
    {
        $this->uploadedBy = $uploadedBy;
        return $this;
    }

    public function getUploadedAt(): ?\DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(\DateTimeImmutable $uploadedAt): static
    {
        $this->uploadedAt = $uploadedAt;
        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): static
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getRowCount(): ?int
    {
        return $this->rowCount;
    }

    public function setRowCount(int $rowCount): static
    {
        $this->rowCount = $rowCount;
        return $this;
    }

    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    public function setIsConfigured(bool $isConfigured): static
    {
        $this->isConfigured = $isConfigured;
        return $this;
    }

    public function getVariables(): Collection
    {
        return $this->variables;
    }

    public function addVariable(CsvVariable $variable): static
    {
        if (!$this->variables->contains($variable)) {
            $this->variables->add($variable);
            $variable->setDataset($this);
        }
        return $this;
    }

    public function removeVariable(CsvVariable $variable): static
    {
        if ($this->variables->removeElement($variable)) {
            if ($variable->getDataset() === $this) {
                $variable->setDataset(null);
            }
        }
        return $this;
    }

    public function getPublicUrl(): string
    {
        return '/media/csv/' . $this->filename;
    }

    #[Groups(['csv_dataset:read'])]
    public function getPublicUrlForApi(): string
    {
        return $this->getPublicUrl();
    }
}
