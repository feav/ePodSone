<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\UserRepository;
use App\Repository\AbonnementRepository;
use App\Service\StripeService;
use App\Service\UserService;
use App\Entity\User;
use App\Entity\Abonnement;

use Stripe\Stripe;
use \Stripe\Charge;

class PaymentController extends AbstractController
{   
    private $params_dir;
    private $stripe_s;
    private $user_s;
    private $userRepository;
    private $abonnementRepository;

    public function __construct(ParameterBagInterface $params_dir, UserRepository $userRepository, UserService $user_s, StripeService $stripe_s, AbonnementRepository $abonnementRepository){
        $this->params_dir = $params_dir;
        $this->stripe_s = $stripe_s;
        $this->user_s = $user_s;
        $this->userRepository = $userRepository;
        $this->abonnementRepository = $abonnementRepository;
    }
    /**
     * @Route("/checkout", name="checkout_product")
     */
    public function checkout(Request $request, \Swift_Mailer $mailer)
    {   
        $user = $this->getUser();
        $message = $result = "";
        $amount = 50;
        if(is_null($user)){
            $email = $request->request->get('email');
            $emailExist = $this->userRepository->findOneBy(['email'=>$email]);
            if(!is_null($emailExist)){
                return new Response("Un utilisateur existe déjà avec l'email ".$email.". s'il s'agit de vous, veuillez vous connecter avant d'effectuer le paiement. <a href='javascript:void()' class='open-sign-in-modal'>Connectez-vous</a>", 500);
            }
            $user = $this->user_s->register($mailer, $email, $request->request->get('name'));
            $message = "Un compte vous a été crée, des informations de connexion vous ont été envoyées à l'adresse ".$user->getEmail();
            $metadata = ['name'=>$user->getName(), 'email'=>$user->getEmail()];
            
            if($request->request->get('stripeSource') !== null && $amount !== null) {
                $this->stripe_s->createStripeCustom($request->request->get('stripeSource'), $metadata);
                $result = $this->stripe_s->proceedPayment($user, $amount);
            }
            $this->addFlash('success', 'Paiement effectué avec success');
        }
        else{
            $metadata = ['name'=>$user->getName(), 'email'=>$user->getEmail()];
            
            if($request->request->get('stripeSource') !== null && $amount !== null) {
                $this->stripe_s->createStripeCustom($request->request->get('stripeSource'), $metadata);
                $result = $this->stripe_s->proceedPayment($user, $amount);
            }
            elseif($user->getStripeCustomId() !=""){
                $result = $this->stripe_s->proceedPayment($user, $amount);
            }
            else
                return new Response("Vous n'avez entré aucune carte", 500);
        }

        if($result == ""){
            //$factureInfos = $this->createFacture($request, $postMeta_s, $post_s, $global_s, $user, $pack);
            $assetFile = $this->params_dir->get('file_upload_dir');
            $ouput_name = 'facture.pdf';
            $commande_pdf = $assetFile.$ouput_name;
            $this->sendMail($mailer, $user, $commande_pdf);
            
            return new Response("Paiement Effectué avec Succèss. ".$message, 200);
        }
        else
            return new Response('Erreur : ' . $errorMessage , 500);
        //return new Response(json_encode(['ok'=>true]));
    }

    public function sendMail($mailer, $user, $commande_pdf){

        try {
            $mail = (new \Swift_Message('Confirmation commande'))
                ->setFrom(array('alexngoumo.an@gmail.com' => 'EpodsOne'))
                ->setTo([$user->getEmail()=>$user->getName()])
                ->setCc("alexngoumo.an@gmail.com")
                //->attach(\Swift_Attachment::fromPath($commande_pdf))
                ->setBody("Bonjour ".$user->getName()."<br>ePodsOne - 4,99€ <br>  Livraison Gratuite (Essai de 3 jours",
                    'text/html'
                );
            $mailer->send($mail);
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }

    /**
     * @Route("/checkout/direct-paid", name="direct_paid")
     */
    public function directPaid(Request $request, \Swift_Mailer $mailer){
        
        $result = "";
        $user = $this->getUser();
        if(is_null($user))
            $response = new Response(json_encode("Vous devez etre connecté pour un payement en un click"), 500);

        $amount = 60;
        $result = $this->stripe_s->proceedPayment($user, $amount);
        if($result == ""){
            //$factureInfos = $this->createFacture($request, $postMeta_s, $post_s, $global_s, $user, $pack);
            $assetFile = $this->params_dir->get('file_upload_dir');
            $ouput_name = 'facture.pdf';
            $commande_pdf = $assetFile.$ouput_name;
            $this->sendMail($mailer, $user, $commande_pdf);
            
            $response = new Response(json_encode("Paiement Effectué avec Succèss"), 200);
        }
        else
            $response = new Response(json_encode('Erreur : ' . $errorMessage), 200);

        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/abonnement/update-abonnement", name="update_abonnement")
     */
    public function updateAbonnements(Request $request){
        $abonnements = $this->abonnementRepository->updateAbonnement();
        foreach ($abonnements as $key => $value) {
            $result = $this->stripe_s->proceedPayment($value['stripe_custom_id'], $value['price']);
            if($result == ""){
                if(!$value['is_paid'])
                    $value->setIsPaid(1);
                else{
                    $value->setState(1);
                    $this->createNewAbonnement();    
                }
            }
        }
    }
    public function createNewAbonnement(){
        return 1;
    }

}
