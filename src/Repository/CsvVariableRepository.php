<?php

namespace App\Repository;

use App\Entity\CsvVariable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CsvVariableRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CsvVariable::class);
    }

    public function findByDatasetAndType($dataset, string $type): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.dataset = :dataset')
            ->andWhere('v.type = :type')
            ->setParameter('dataset', $dataset)
            ->setParameter('type', $type)
            ->orderBy('v.columnIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
