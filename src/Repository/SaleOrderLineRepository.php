<?php

namespace App\Repository;

use App\Entity\SaleOrderLine;
use DateTime;
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


    public function findAllSaleLinesBetween(DateTime $begin, DateTime $end): array
      {
          return $this->createQueryBuilder('s')
              ->andWhere('s.createdAt BETWEEN :dateBegin and :dateEnd')
                ->setParameter('dateBegin', $begin->format('Y-m-d').' 00:00:00')
                ->setParameter('dateEnd',  $end->format('Y-m-d').' 23:59:59')
              ->getQuery()
              ->getResult()
          ;
      }
}
