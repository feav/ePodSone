<?php

namespace App\Repository;

use App\Entity\Abonnement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Abonnement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Abonnement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Abonnement[]    findAll()
 * @method Abonnement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AbonnementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Abonnement::class);
        $this->em = $this->getEntityManager()->getConnection();
    }

    // /**
    //  * @return Abonnement[] Returns an array of Abonnement objects
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
    public function findOneBySomeField($value): ?Abonnement
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function updateAbonnement(){
        $sql = "SELECT abonnement.id, abonnement.end, abonnement.state, abonnement.is_paid, formule.price, user.stripe_custom_id  FROM abonnement inner join formule inner join user 
        WHERE abonnement.formule = formule.id AND abonnement.user = user.id AND (abonnement.end >= :today AND abonnement.state = 0) OR (abonnement.end <= :today AND abonnement.is_paid = 0)";

        $abonnement = $this->em->prepare($sql);
        $abonnement->execute(['today'=>new \Datetime()]);
        $abonnement = $abonnement->fetchAll();
        return $abonnement;
    }

        public function countAbonnement(){
        $sql = "SELECT COUNT(*) as count FROM abonnement";

        $val = $this->em->prepare($sql);
        $val->execute();
        return $val->fetch();
    }
    public function countAbonnementResilie(){
        $sql = "SELECT COUNT(*) as count FROM abonnement WHERE resilie = :resilie";

        $val = $this->em->prepare($sql);
        $val->execute(['resilie' => 1]);
        return $val->fetch();
    }
    public function countAbonnementPaye(){
        $sql = "SELECT COUNT(*) as count FROM abonnement WHERE is_paid = :is_paid";

        $val = $this->em->prepare($sql);
        $val->execute(['is_paid' => 1]);
        return $val->fetch();
    }
    public function countAbonnementNonPaye(){
        $sql = "SELECT COUNT(*) as count FROM abonnement WHERE is_paid != :is_paid";

        $val = $this->em->prepare($sql);
        $val->execute(['is_paid' => 1]);
        return $val->fetch();
    }
}
