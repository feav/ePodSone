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
        $abonnement = $this->abonnementRepository->findBy(['user'=>$user_id, 'active'=>1]);
        if(count($abonnement)){
            return true;
        }
        return false;
    }
}
