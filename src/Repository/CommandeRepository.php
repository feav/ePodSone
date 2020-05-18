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
}
