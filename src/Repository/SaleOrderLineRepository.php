<?php

namespace App\Repository;

use App\Entity\SaleOrderLine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SaleOrderLine>
 *
 * @method SaleOrderLine|null find($id, $lockMode = null, $lockVersion = null)
 * @method SaleOrderLine|null findOneBy(array $criteria, array $orderBy = null)
 * @method SaleOrderLine[]    findAll()
 * @method SaleOrderLine[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SaleOrderLineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SaleOrderLine::class);
    }

//    /**
//     * @return SaleOrderLine[] Returns an array of SaleOrderLine objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?SaleOrderLine
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
