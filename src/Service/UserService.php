<?php
namespace App\Service;


use Symfony\Component\Config\Definition\Exception\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use App\Repository\UserRepository;
use App\Entity\User;

class UserService{
    private $passwordEncoder;
    private $userRepository;
    
    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder, UserRepository $userRepository){
    	$this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
        $this->em = $em;
    }
    public function filterUser($key){
        $users = $this->userRepository->filterUser($key);
        return $users;
    }

    public function generateUsername($user){
        do{

            $randUsername ="".$user->getPrenom()[0].$user->getNom()[0];

            $nb_a_tirer = 4;

            $val_min = 0;

            $val_max = 9;

            $tab_result = array();
            while($nb_a_tirer != 0 ){

                $nombre = mt_rand($val_min, $val_max);

                $tab_result[] = $nombre;

                $nb_a_tirer--;

                $randUsername = $randUsername.$nombre;

            }
            $randUsername = $randUsername."-howard";
            $existUsername = $this->userRepository->findOneByUsername($randUsername);

        }while(!is_null($existUsername));

        return strtolower ($randUsername);
    }

    public function generatePassword(){
        $nb_a_tirer = 4;

        $val_min = 0;

        $val_max = 9;

        $tab_rang = "";
        while($nb_a_tirer != 0 ){
            $tab_rang .= mt_rand($val_min, $val_max);
            $nb_a_tirer--;
        }
        $alphabet="abcdefghijklmnopqrstuvwxyz";
        for ($i=0; $i <4 ; $i++) { 
          $lettre_aleatoire=$alphabet[rand(0,25)];
          $tab_rang .=$lettre_aleatoire;
        }
        
        $tab_rang = "@".$tab_rang."_";
        
        return strtolower (str_shuffle($tab_rang));
    }
    public function register(\Swift_Mailer $mailer, $email, $name){
        $user = new User();
        $fullPassword = $this->generatePassword();
        $user->setName($name);
        $user->setEmail($email);
        $user->setPassword($this->passwordEncoder->encodePassword($user, $fullPassword));
        $user->setRoles(['ROLE_USER']);
        try {
            $mail = (new \Swift_Message('Vos informations de connexion'))
                ->setFrom(array('alexngoumo.an@gmail.com' => 'EpodsOne'))
                ->setTo([$user->getEmail()=>$user->getName()])
                ->setBody("Bonjour ".$user->getName()."<br>Un compte vous a été automatiquement crée. vous trouverez ci-dessus vos acces pour vous connecter à la plateforme<br> <b>Email: </b>".$user->getEmail()."<br><b>Mot de passe: </b> ".$fullPassword ,
                    'text/html'
                );
           $mailer->send($mail);
        } catch (Exception $e) {
            print_r($e->getMessage());
        }

        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }
}
