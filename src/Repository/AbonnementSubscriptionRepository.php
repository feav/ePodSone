<?php

namespace App\Repository;

use App\Entity\AbonnementSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AbonnementSubscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method AbonnementSubscription|null findOneBy(array $criteria, array $orderBy = null)
 * @method AbonnementSubscription[]    findAll()
 * @method AbonnementSubscription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AbonnementSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbonnementSubscription::class);
    }

    // /**
    //  * @return AbonnementSubscription[] Returns an array of AbonnementSubscription objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AbonnementSubscription
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
