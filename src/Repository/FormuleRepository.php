<?php

namespace App\Repository;

use App\Entity\Formule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Formule|null find($id, $lockMode = null, $lockVersion = null)
 * @method Formule|null findOneBy(array $criteria, array $orderBy = null)
 * @method Formule[]    findAll()
 * @method Formule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formule::class);
    }

    // /**
    //  * @return Formule[] Returns an array of Formule objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Formule
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
