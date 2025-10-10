<?php

namespace App\Repository;

use App\Entity\CsvDataset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CsvDatasetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CsvDataset::class);
    }

    public function findConfigured(): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.isConfigured = :configured')
            ->setParameter('configured', true)
            ->orderBy('d.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUser($user): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.uploadedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('d.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
