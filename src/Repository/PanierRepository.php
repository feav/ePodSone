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
    public function getPanierByDate($user_id, $dateDebut, $dateFin){
        $sql = "SELECT id FROM panier WHERE panier.user_id = :user_id AND panier.emmission >= :dateDebut AND panier.emmission <= :dateFin ";

        $posts = $this->em->prepare($sql);
        $posts->execute(['user_id' => $user_id, 'dateDebut' => $dateDebut, 'dateFin' => $dateFin]);
        $posts = $posts->fetchAll();

        $postsArray = [];
        foreach ($posts as $key => $value) {
            $qb = $this->createQueryBuilder('panier')
                ->Where('panier.id = :id')
                ->setParameter('id', $value['id']);
            $postsArray[] = $qb->getQuery()->getOneOrNullResult();
        }
        return $postsArray;
    }
}
