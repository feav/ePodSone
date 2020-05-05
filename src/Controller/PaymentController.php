<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\UserRepository;
use App\Service\UserService;
use App\Entity\User;
use App\Entity\Abonnement;
use App\Entity\AbonnementSubscription;

use Stripe\Stripe;
use \Stripe\Charge;

class PaymentController extends AbstractController
{   
    private $params_dir;
    private $user_s;
    private $stripeApiKey = 'sk_test_zJN82UbRA4k1a6Mvna4rV3qn';
    private $stripeCurrency = "eur";
    private $userRepository;

    public function __construct(ParameterBagInterface $params_dir, UserRepository $userRepository, UserService $user_s){
        $this->params_dir = $params_dir;
        $this->user_s = $user_s;
        $this->userRepository = $userRepository;
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
                return new Response("Un utilisateur existe déjà avec l'email ".$email.". s'il s'agit de vous, veuillez vous connecter", 500);
            }
            $user = $this->user_s->register($mailer, $email, $request->request->get('name'));
            $message = "Un compte vous a été crée, des informations de connexion vous ont été envoyées par mail";
            $metadata = ['name'=>$user->getName(), 'email'=>$user->getEmail()];
            
            if($request->request->get('stripeSource') !== null && $amount !== null) {
                $this->createStripeCustom($request->request->get('stripeSource'), $metadata);
                $result = $this->proceedPayment($request->request->get('stripeSource'), $amount, $metadata);
            }
        }
        else{
            $metadata = ['name'=>$user->getName(), 'email'=>$user->getEmail()];
            
            if($request->request->get('stripeSource') !== null && $amount !== null) {
                $this->createStripeCustom($request->request->get('stripeSource'), $metadata);
                $result = $this->proceedPayment($request->request->get('stripeSource'), $amount, $metadata);
            }
            elseif($user->getStripeCustomId() !=""){
                $result = $this->proceedPayment($request->request->get('stripeSource'), $amount, $metadata);
            }
            else
                return new Response("Vous n'avez entrez aucune carte", 500);
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

    public function createStripeCustom($source, $metadata){
        $em = $this->getDoctrine()->getManager();
        \Stripe\Stripe::setApiKey('sk_test_zJN82UbRA4k1a6Mvna4rV3qn');
        $custom =  \Stripe\Customer::create([
            'source' => $source,
            'email' => $metadata['email'],
            'name' => $metadata['name'],
            'description' => 'Client de la boutique EpodsOne',
        ]);
        $user = $this->userRepository->findOneBy(['email'=>$metadata['email']]);
        $user->setStripeCustomId($custom['id']);
        $em->flush();

        return $custom['id'];
    }

    public function paidByStripeCustom($stripe_custom_id, $amount){
        Stripe::setApiKey($this->stripeApiKey);
        $charge = \Stripe\Charge::create([
            'amount' => $amount,
            'currency' => $this->stripeCurrency,
            'customer' => $stripe_custom_id, 
        ]);

        return 1;
    }

    public function proceedPayment($source, $amount, $metadata){

        $result = "";
        $user = $this->userRepository->findOneBy(['email'=>$metadata['email']]);

        Stripe::setApiKey($this->stripeApiKey);
        try {
            $this->paidByStripeCustom($user->getStripeCustomId(), $amount);
        }catch(\Stripe\Error\Card $e) {
            // Since it's a decline, \Stripe\Error\Card will be caught
            $body = $e->getJsonBody();
            $err  = $body['error'];
            $result =  $err['message'];
        } catch (\Stripe\Error\RateLimit $e) {
            $result = "Trop de requêtes adressées à l'API trop rapidement";
        } catch (\Stripe\Error\InvalidRequest $e) {
            $result = "Des paramètres non valides ont été fournis à l'API de Stripe";
        } catch (\Stripe\Error\Authentication $e) {
            $result = "L'authentification avec l'API de Stripe a échoué. Peut-être avez-vous changé de clés API récemment";
        } catch (\Stripe\Error\ApiConnection $e) {
            $result = "La communication réseau avec Stripe a échoué";
        } catch (\Stripe\Error\Base $e) {
            $result = "Une erreur s'est produite suite à votre paiement";
        } catch (Exception $e) {
            $result = "Une erreur s'est produite.";
        }
        
        return $result;
    }

    public function sendMail($mailer, $user, $commande_pdf){

        try {
            $mail = (new \Swift_Message('Confirmation commande'))
                ->setFrom(array('alexngoumo.an@gmail.com' => 'EpodsOne'))
                ->setTo([$user->getEmail()=>$user->getName()])
                ->setCc("alexngoumo.an@gmail.com")
                ->attach(\Swift_Attachment::fromPath($commande_pdf))
                ->setBody("Bonjour ".$user->getName()."<br>Votre commande a été effectuée avec success, vous trouverez en piece jointe votre facture",
                    'text/html'
                );
            $mailer->send($mail);
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }
}
