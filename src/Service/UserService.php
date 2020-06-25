<?php
namespace App\Service;


use Symfony\Component\Config\Definition\Exception\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

use App\Repository\UserRepository;
use App\Entity\User;

class UserService{
    private $passwordEncoder;
    private $userRepository;
    private $encoderFactory;
    
    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder, UserRepository $userRepository, EncoderFactoryInterface $encoderFactory){
    	$this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
        $this->encoderFactory = $encoderFactory;
        $this->em = $em;
    }
    public function filterUser($key){
        $users = $this->userRepository->filterUser($key);
        return $users;
    }
    /**
    ** - town : Ville
    ** - country : Pays
    ** - street : Rue
    ** - zip_code : Code postal
    ** - name : Nom 
    ** - surname : Prenom
    ** - email : Email
    ** - phone : Telephone
    ** - 
    **/
    public function updateUser($lists, $user){
        if($lists && $user){
            foreach ($lists as $key => $value) {
                switch ($key) {
                    case 'town':
                        $user->setTown($value);
                        break;
                    
                    case 'country':
                        $user->setCountry($value);
                        break;
                    
                    case 'street':
                        $user->setStreet($value);
                        break;
                    
                    case 'zip_code':
                        $user->setZipCode($value);
                        break;
                    
                    case 'name':
                        $user->setName($value);
                        break;
                    
                    case 'surname':
                        $user->setSurName($value);
                        break;
                    
                    case 'phone':
                        $user->setPhone($value);
                        break;
                
                }
            }

            $this->em->persist($user);
            $this->em->flush();
        }
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

    public function send_mail(\Swift_Mailer $mailer, $email, $phone, $name,$surname, $message){
        try {
            $mail = (new \Swift_Message('Message - Contact '.$name))
                ->setFrom(array('feavfeav@gmail.com' => 'VinsPro'))
                ->setTo('feavfeav@gmail.com')
                ->setCc('feavfeav@gmail.com')
                ->setBody(" je suis ".$name." ".$surname." repondant au numero : ".$phone.". ".$message,
                    'text/html'
                );
           $mailer->send($mail);
           return true;
        } catch (Exception $e) {
            print_r($e->getMessage());
            return false;
        }
    }
    public function register(\Swift_Mailer $mailer, $email, $name){
        $user = new User();
        $fullPassword = $this->generatePassword();
        $user->setName($name);
        $user->setEmail($email);
        $user->setPassword($this->passwordEncoder->encodePassword($user, $fullPassword));
        $user->setRoles(['ROLE_USER']);

        $this->em->persist($user);
        $this->em->flush();

        $content = "<p> Bienvenue ".$user->getName().",<br>Un compte vous a été automatiquement crée. Voici vos identifiants: <br> Email: ".$user->getEmail()." / Mot de passe: ".$fullPassword."</p>";
        try {
        $mail = (new \Swift_Message('Création crée'))
            ->setFrom(array('alexngoumo.an@gmail.com' => 'VinsPro'))
            ->setTo([$user->getEmail()=>$user->getName()])
            ->setCc("alexngoumo.an@gmail.com")
            /*->setBody(
                $this->templating->render(
                    'emails/confirm_paiement.twig',['content'=>$content, 'url'=>$url]
                ),
                'text/html'
            );*/
            ->setBody($content,
                'text/html'
            );
        $mailer->send($mail);
        } catch (Exception $e) {
            print_r($e->getMessage());
        } 

        return $user;
    }

}
