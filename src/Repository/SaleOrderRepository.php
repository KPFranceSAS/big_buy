<?php

namespace App\Repository;

use App\Entity\SaleOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SaleOrder>
 *
 * @method SaleOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method SaleOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method SaleOrder[]    findAll()
 * @method SaleOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SaleOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SaleOrder::class);
    }

//    /**
//     * @return SaleOrder[] Returns an array of SaleOrder objects
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

//    public function findOneBySomeField($value): ?SaleOrder
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
