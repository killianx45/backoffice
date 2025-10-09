<?php

namespace App\Entity;

use App\Repository\ArticleChartRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ArticleChartRepository::class)]
class ArticleChart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'charts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Article $article = null;

    #[ORM\Column(length: 255)]
    private ?string $csvFile = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['bar', 'line'])]
    private ?string $chartType = 'bar';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column]
    private ?int $displayOrder = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $xAxisLabel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $yAxisLabel = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCsvFile(): ?string
    {
        return $this->csvFile;
    }

    public function setCsvFile(string $csvFile): static
    {
        $this->csvFile = $csvFile;

        return $this;
    }

    public function getChartType(): ?string
    {
        return $this->chartType;
    }

    public function setChartType(string $chartType): static
    {
        $this->chartType = $chartType;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDisplayOrder(): ?int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    public function getXAxisLabel(): ?string
    {
        return $this->xAxisLabel;
    }

    public function setXAxisLabel(?string $xAxisLabel): static
    {
        $this->xAxisLabel = $xAxisLabel;

        return $this;
    }

    public function getYAxisLabel(): ?string
    {
        return $this->yAxisLabel;
    }

    public function setYAxisLabel(?string $yAxisLabel): static
    {
        $this->yAxisLabel = $yAxisLabel;

        return $this;
    }
}
