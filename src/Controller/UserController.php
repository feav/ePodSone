<?php
// ./src/Controller/ListController


namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/* Login ajax */
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
/* end Login ajax */


use App\Repository\UserRepository;
use App\Entity\User;
use App\Service\UserService;
class UserController extends AbstractController
{
    private $user_s;
    private $userRepository;
    private $encoderFactory;
    
    public function __construct(UserRepository $userRepository, UserService $user_s, EncoderFactoryInterface $encoderFactory){
        $this->userRepository = $userRepository;
        $this->encoderFactory = $encoderFactory;
        $this->user_s = $user_s;
    }

    public function filter(Request $request)
    {   
        if($request->query->get('key') == "")
            return new Response(json_encode([]),200);
        $users = $this->user_s->filterUser($request->query->get('key'));
        return new Response(json_encode($users), 200);
    }

     /**
     * @Route("/profile/update", name="update_profile")
     */
    public function profileUpdate(Request $request)
    {   
        $response = new Response(json_encode( array('status'=>300,'message' =>  "aucune donnees fournie", 'format'=> '[town : Ville , country : Pays , street : Rue, zip_code : Code postal, name : Nom, surname : Prenom, phone : Telephone]' )));
        $user = $this->getUser();
        if($user){
            if(isset($_POST['user'])){
                $user_data = $_POST['user'];
                $this->user_s->updateUser($user_data,$user);
            
                $response = new Response(json_encode( array('status'=>200,'message' =>  "Les informations ont ete mises a jour" )));
            }
        }else{
         $response = new Response(json_encode( array('status'=>300,'message' =>  "Utilisateur non connecte" )));
        }

        
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/login-ajax", name="login_ajax")
     */
    public function loginAjax(Request $request)
    {   
        $result = $this->customCheckLoginAjax($request);
        $response = new Response(json_encode($result['message']), $result['status']);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function customCheckLoginAjax(Request $request)
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
        /*$event = new InteractiveLoginEvent($request, $token);
        $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);*/
        
        $request->getSession()->get('_security.main.target_path');
        return ['message'=>"Vous etes connectés maintenant", 'status'=>200];
    }

    /**
     * @Route("/user/check-isconnected", name="api_use_connected_or_exist")
     */
    public function checkUserIsconnected(Request $request, \Swift_Mailer $mailer){
        $this->entityManager = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $email = $request->query->get('email');
        $existUser = $this->userRepository->findOneBy(['email'=>$email]);
        if($user){
            return new Response( json_encode(array('status' => 200, 'message' => "Utilisateur  connecte" )) );
        }
        elseif(is_null($existUser))
            return $this->registerAjax($request, $mailer, $email, $request->query->get('name'));
        else
            return new Response(json_encode(array('status' => 500, 'message' => "Un utilisateur existe déjà avec l'email ".$email.". s'il s'agit de vous, veuillez vous connecter avant d'effectuer le paiement. <a href='javascript:void()' class='open-sign-in-modal'>Connectez-vous</a>" )));
    }

    /**
     * @Route("/update-user-xhr", name="update_user_xhr")
     */
    public function updateUserXhr(Request $request)
    {
        $user = $this->getUser();
        if($user){
            if(isset($_POST['user'])){
                $user_data = $_POST['user'];
                $this->user_s->updateUser($user_data,$user);
            
                $response = new Response("Les informations ont ete mises a jour" , 200);
            }
        }else{
         $response = new Response("Utilisateur non connecte" , 500);
        }

        
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    public function registerAjax($request, $mailer, $email, $name){
        $user = $this->user_s->register($mailer, $email, $name);
        if($user){
            $result = $this->authentification($request, $user);
            if($result['status'] == 200)
                return new Response(json_encode(array('status' => 200, 'message' => "Compte crée avec success" )));
        }
        else
            return new Response(json_encode(array('status' => 500, 'message' => "Echec Création de compte" )));
    }


}