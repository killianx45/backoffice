<?php

namespace App\Entity;

use App\Repository\CsvVariableRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['csv_variable:read']]
        ),
        new Get(
            normalizationContext: ['groups' => ['csv_variable:read']]
        )
    ]
)]
#[ORM\Entity(repositoryClass: CsvVariableRepository::class)]
class CsvVariable
{
    public const TYPE_CATEGORICAL = 'categorical';
    public const TYPE_NUMERIC = 'numeric';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['csv_variable:read', 'csv_dataset:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CsvDataset::class, inversedBy: 'variables')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['csv_variable:read'])]
    private ?CsvDataset $dataset = null;

    #[ORM\Column(length: 255)]
    #[Groups(['csv_variable:read', 'csv_dataset:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 20)]
    #[Groups(['csv_variable:read', 'csv_dataset:read'])]
    private ?string $type = null;

    #[ORM\Column]
    #[Groups(['csv_variable:read', 'csv_dataset:read'])]
    private ?int $columnIndex = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['csv_variable:read', 'csv_dataset:read'])]
    private ?array $sampleValues = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDataset(): ?CsvDataset
    {
        return $this->dataset;
    }

    public function setDataset(?CsvDataset $dataset): static
    {
        $this->dataset = $dataset;
        return $this;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        if (!in_array($type, [self::TYPE_CATEGORICAL, self::TYPE_NUMERIC])) {
            throw new \InvalidArgumentException('Invalid variable type');
        }
        $this->type = $type;
        return $this;
    }

    public function getColumnIndex(): ?int
    {
        return $this->columnIndex;
    }

    public function setColumnIndex(int $columnIndex): static
    {
        $this->columnIndex = $columnIndex;
        return $this;
    }

    public function getSampleValues(): ?array
    {
        return $this->sampleValues;
    }

    public function setSampleValues(?array $sampleValues): static
    {
        $this->sampleValues = $sampleValues;
        return $this;
    }

    public function isCategorical(): bool
    {
        return $this->type === self::TYPE_CATEGORICAL;
    }

    public function isNumeric(): bool
    {
        return $this->type === self::TYPE_NUMERIC;
    }
}
