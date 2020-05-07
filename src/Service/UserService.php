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
                ->setCc("alexngoumo.an@gmail.com")
                ->setBody("“Bien joué ".$user->getName()."! C’est partie” <br> Confirmation de votre essai de 3 jours à notre abonnement de Livraison Gratuite en  illimité pour 59€/mois. <br><br> Il vous reste 3 jours d’essai pour commander et obtenir la Livraison Gratuite en  illimité sur notre boutique au lieu de 10€. <br><br> Vous serez débité de 59€/mois à partir du 11 mars 2020 à 11:21 au moment de la  fin de votre essai. <br><br> Si vous souhaitez résilier veuillez vous connecter sur notre boutique et faire votre  demande de résiliation de manière automatique. <br>   Voici vos identifiants :  Email : ​".$user->getEmail()." / Mot de passe : ".$fullPassword." ",
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

    public function login(Request $request)
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');

        $user = $this->userRepository->findOneBy(['email'=>$email]);
        if(!$user){
            return ['message'=>'identifiants incorrect', 'status'=>500];
        }

        $encoder = $this->encoderFactory->getEncoder($user);
        $salt = $user->getSalt();
        if(!$encoder->isPasswordValid($user->getPassword(), $password, $salt)) {
            return ['message'=>'identifiants incorrect', 'status'=>500];
        } 

        /*if (!$user->isEnabled()) {
            return ['message'=>"Votre compte n'est pas activé", 'status'=>500];
        }*/
        
        return $this->authentification($request, $user);
    }

    public function authentification(Request $request, $user){
        // The third parameter "main" can change according to the name of your firewall in security.yml
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->get('security.token_storage')->setToken($token);

        // If the firewall name is not main, then the set value would be instead:
        // $this->get('session')->set('_security_XXXFIREWALLNAMEXXX', serialize($token));
        $this->get('session')->set('_security_main', serialize($token));
        
        // Fire the login event manually
        $event = new InteractiveLoginEvent($request, $token);
        $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
        
        $request->getSession()->get('_security.main.target_path');
        return ['message'=>"Vous etes connectés maintenant", 'status'=>200];
    }

}
