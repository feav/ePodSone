<?php
namespace App\Service;

use Symfony\Component\Config\Definition\Exception\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use App\Repository\AbonnementRepository;
use App\Entity\Abonnement;


class GlobalService{

    private $abonnementRepository;
    
    public function __construct(AbonnementRepository $abonnementRepository){
        $this->abonnementRepository = $abonnementRepository;
    }


    public function isAbonnementValide($user_id){
        $abonnement = $this->abonnementRepository->findOneBy(['user'=>$user_id], ['id'=>'DESC'], 1);
        if(is_null($abonnement) || !$abonnement->getActive() || ($abonnement->getEnd() > new \DateTime()) ){
            return false;
        }
        return true;
    }
}
