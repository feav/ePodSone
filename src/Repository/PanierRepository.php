<?php

namespace App\Repository;

use App\Entity\Panier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Panier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Panier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Panier[]    findAll()
 * @method Panier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PanierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Panier::class);
        $this->em = $this->getEntityManager()->getConnection();
    }

    // /**
    //  * @return Panier[] Returns an array of Panier objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Panier
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function countCommande(){
        $sql = "SELECT COUNT(DISTINCT panier_id) as count FROM commande";
        $val = $this->em->prepare($sql);
        $val->execute();
        return $val->fetch();
    }

    public function countRemboursement(){
        $sql = "SELECT COUNT(*) as count FROM panier WHERE remboursement = :rembours";

        $val = $this->em->prepare($sql);
        $val->execute(['rembours' => 1]);
        return $val->fetch();
    }
    public function countCommandePaye(){
        $sql = "SELECT COUNT(DISTINCT panier_id) as count FROM commande as cmd inner join panier as pan WHERE cmd.panier_id = pan.id AND pan.status = :status";

        $val = $this->em->prepare($sql);
        $val->execute(['status' => 1]);
        return $val->fetch();
    }
    public function countCommandeNonPaye(){
        $sql = "SELECT COUNT(*) as count FROM commande as cmd inner join panier as pan WHERE cmd.panier_id = pan.id AND pan.status != :status";

        $val = $this->em->prepare($sql);
        $val->execute(['status' => 1]);
        return $val->fetch();
    }

}
