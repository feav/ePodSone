<?php

namespace App\Repository;

use App\Entity\Visiteur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Visiteur|null find($id, $lockMode = null, $lockVersion = null)
 * @method Visiteur|null findOneBy(array $criteria, array $orderBy = null)
 * @method Visiteur[]    findAll()
 * @method Visiteur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VisiteurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Visiteur::class);
        $this->em = $this->getEntityManager()->getConnection();
    }

    // /**
    //  * @return Visiteur[] Returns an array of Visiteur objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('v.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Visiteur
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function countVisiteur(){
        $sql = "SELECT COUNT(*) as count FROM visiteur";

        $val = $this->em->prepare($sql);
        $val->execute();
        return $val->fetch();
    }
    public function countVisiteurPaid(){
        $sql = "SELECT COUNT(*) as count FROM visiteur WHERE nb_achat != :nb_achat";

        $val = $this->em->prepare($sql);
        $val->execute(['nb_achat' => 0]);
        return $val->fetch();
    }
    public function countRecurrentPaid(){
        $sql = "SELECT COUNT(*) as count FROM visiteur WHERE nb_achat >= :nb_achat";

        $val = $this->em->prepare($sql);
        $val->execute(['nb_achat' => 2]);
        return $val->fetch();
    }
    
}
