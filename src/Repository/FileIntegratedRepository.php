<?php

namespace App\Repository;

use App\Entity\FileIntegrated;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FileIntegrated>
 *
 * @method FileIntegrated|null find($id, $lockMode = null, $lockVersion = null)
 * @method FileIntegrated|null findOneBy(array $criteria, array $orderBy = null)
 * @method FileIntegrated[]    findAll()
 * @method FileIntegrated[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileIntegratedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileIntegrated::class);
    }

//    /**
//     * @return FileIntegrated[] Returns an array of FileIntegrated objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?FileIntegrated
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
