<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Commande|null find($id, $lockMode = null, $lockVersion = null)
 * @method Commande|null findOneBy(array $criteria, array $orderBy = null)
 * @method Commande[]    findAll()
 * @method Commande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
        $this->em = $this->getEntityManager()->getConnection();
    }

    // /**
    //  * @return Commande[] Returns an array of Commande objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Commande
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getPanierByDate($dateDebut, $dateFin){
        $sql = "SELECT cmd.id FROM commande as cmd inner join panier  WHERE cmd.panier_id = panier.id AND  panier.emmission >= :dateDebut AND panier.emmission <= :dateFin ";

        $posts = $this->em->prepare($sql);
        $posts->execute(['dateDebut' => $dateDebut, 'dateFin' => $dateFin]);
        $posts = $posts->fetchAll();

        $postsArray = [];
        foreach ($posts as $key => $value) {
            $qb = $this->createQueryBuilder('commande')
                ->Where('commande.id = :id')
                ->setParameter('id', $value['id']);
            $postsArray[] = $qb->getQuery()->getOneOrNullResult();
        }
        return $postsArray;
    }
    

    public function countCommandePaye($dateDebut, $dateFin){
        $sql = "SELECT COUNT(DISTINCT panier_id) as count FROM commande as cmd inner join panier as pan WHERE cmd.panier_id = pan.id AND pan.status = :status AND pan.remboursement IS NULL AND pan.paiement_date >= :dateDebut AND pan.paiement_date <= :dateFin ";

        $val = $this->em->prepare($sql);
        $val->execute(['status' => 1, 'dateDebut'=>$dateDebut->format('Y-m-d H:i:s'), 'dateFin'=>$dateFin->format('Y-m-d H:i:s')]);
        return $val->fetch();
    }
    public function sumCommandePaye($dateDebut, $dateFin){
        $sql = "SELECT SUM(cmd.total_price) as price FROM commande as cmd inner join panier as pan WHERE cmd.panier_id = pan.id AND pan.status = :status AND pan.remboursement IS NULL AND pan.paiement_date >= :dateDebut AND pan.paiement_date <= :dateFin";

        $val = $this->em->prepare($sql);
        $val->execute(['status' => 1, 'dateDebut'=>$dateDebut->format('Y-m-d H:i:s'), 'dateFin'=>$dateFin->format('Y-m-d H:i:s')]);
        return $val->fetch();
    }

    public function getInfosVente($dateDebut, $dateFin){

        $commandePaye = $this->countCommandePaye($dateDebut, $dateFin);
        //dd($commandePaye);
        $vente = $this->sumCommandePaye($dateDebut, $dateFin);

        return ['nbr_commande'=>$commandePaye['count'], 'vente'=>$vente['price']];
    }
}
