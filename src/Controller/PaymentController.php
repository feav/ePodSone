<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\UserRepository;
use App\Repository\AbonnementRepository;
use App\Repository\PanierRepository;
use App\Service\StripeService;
use App\Service\UserService;
use App\Entity\User;
use App\Entity\Abonnement;
use App\Entity\Panier;

use Stripe\Stripe;
use \Stripe\Charge;

use Dompdf\Options;
use Dompdf\Dompdf;

class PaymentController extends AbstractController
{   
    private $params_dir;
    private $stripe_s;
    private $user_s;
    private $userRepository;
    private $panierRepository;
    private $entityManager;
    private $abonnementRepository;

    public function __construct(ParameterBagInterface $params_dir, UserRepository $userRepository, UserService $user_s, StripeService $stripe_s, AbonnementRepository $abonnementRepository, PanierRepository $panierRepository){
        $this->params_dir = $params_dir;
        $this->stripe_s = $stripe_s;
        $this->user_s = $user_s;
        $this->userRepository = $userRepository;
        $this->panierRepository = $panierRepository;
        $this->abonnementRepository = $abonnementRepository;
    }
    /**
     * @Route("/checkout", name="checkout_product")
     */
    public function checkout(Request $request, \Swift_Mailer $mailer)
    {   
        $this->entityManager = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $message = $result = "";

        $panier = $this->panierRepository->findOneBy(['user'=>$user->getId(), 'status'=>0]);
        if(!is_null($panier)){
            $amount = $panier->getTotalPrice();
            if(!$amount)
                return new Response("le montant de votre commande est null", 500);
        }
        else
            return new Response("Vous n'avez aucun panier en attente de paiement", 500);
        
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
            $flashBag = $this->get('session')->getFlashBag()->clear();
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
            $panier->setStatus(1);
            $panier->setPaiementDate(new \Datetime());
            $this->entityManager->flush();

            $assetFile = $this->params_dir->get('file_upload_dir');
            if (!file_exists($request->server->get('DOCUMENT_ROOT') .'/'. $assetFile)) {
                mkdir($request->server->get('DOCUMENT_ROOT') .'/'. $assetFile, 0705);
            } 
            $ouput_name = 'facture_'.$panier->getId().'.pdf';
            $save_path = $assetFile.$ouput_name;
            $params = [
                'format'=>['value'=>'A4', 'affichage'=>'portrait'],
                'is_download'=>['value'=>true, 'save_path'=>$save_path]
            ];
            $dompdf = $this->generatePdf('emails/facture.html.twig', $panier , $params);

            $this->sendMail($mailer, $user, $panier, $save_path);
            
            return new Response("Paiement Effectué avec Succèss. ".$message, 200);
        }
        else
            return new Response('Erreur : ' . $errorMessage , 500);
        return new Response(json_encode(['ok'=>true]));
    }

    public function sendMail($mailer, $user, $panier, $commande_pdf){

        if(count($panier->getCommandes())){
            $content = "<p>Bonjour ".$user->getName().", <br> Vous avez acheté d'ePodsOne pour ".$panier->getTotalPrice()."€<br>
            Livraison Gratuite (Essai de 3 jours) pour un de nos abonnements</p>";
            $url = $this->generateUrl('home');
            try {
                $mail = (new \Swift_Message('Confirmation commande'))
                    ->setFrom(array('alexngoumo.an@gmail.com' => 'EpodsOne'))
                    ->setTo([$user->getEmail()=>$user->getName()])
                    ->setCc("alexngoumo.an@gmail.com")
                    ->attach(\Swift_Attachment::fromPath($commande_pdf))
                    ->setBody(
                        $this->renderView(
                            'emails/mail_template.html.twig',['content'=>$content, 'url'=>$url]
                        ),
                        'text/html'
                    );
                $mailer->send($mail);
            } catch (Exception $e) {
                print_r($e->getMessage());
            }            
        }
        if(count($panier->getAbonnements())){
            $abonnement = $panier->getAbonnements()[0];
            $mois_annee = ($abonnement->getFormule()->getMonth() == 12) ? "ans" : $abonnement->getFormule()->getMonth()."mois";

            $content = "<p>Bien joué ".$user->getName()."! Confirmation de votre essai de ".$abonnement->getFormule()->getTryDays()." jours à notre abonnement de Livraison Gratuite en  illimité pour ".$abonnement->getFormule()->getPrice()."€/".$mois_annee.". <br><br>Il vous reste ".$abonnement->getFormule()->getTryDays()." jours d’essai pour commander et obtenir la Livraison Gratuite en  illimité sur notre boutique au lieu de 10€.<br><br>Vous serez débité de ".$abonnement->getFormule()->getPrice()."€/".$mois_annee." à partir du ". $this->getFullDate($abonnement->getEnd())." au moment de la  fin de votre essai.<br><br> Si vous souhaitez résilier veuillez vous connecter sur notre boutique et faire votre  demande de résiliation de manière automatique.<br><br>Vos informations de connexion vous ont été envoyés à cette email (<small>".$user->getEmail()."</small>) lors de votre tout premier achat";
            $url = $this->generateUrl('home');
            try {
                $mail = (new \Swift_Message('Abonnement réussit'))
                    ->setFrom(array('alexngoumo.an@gmail.com' => 'EpodsOne'))
                    ->setTo([$user->getEmail()=>$user->getName()])
                    ->setCc("alexngoumo.an@gmail.com")
                    //->attach(\Swift_Attachment::fromPath($commande_pdf))
                    ->setBody(
                        $this->renderView(
                            'emails/mail_template.html.twig',['content'=>$content, 'url'=>$url]
                        ),
                        'text/html'
                    );
                $mailer->send($mail);
            } catch (Exception $e) {
                print_r($e->getMessage());
            }            
        }     
        return 1; 
    }

    public function getFullDate($date){
        $day = array("Dimanche","Lundi","Mardi","Mercredi","Jeudi","Vendredi","Samedi"); 
        $month = array("01"=>"janvier", "02"=>"février", "03"=>"mars", "04"=>"avril", "05"=>"mai", "06"=>"juin", "07"=>"juillet", "08"=>"août", "09"=>"septembre", "10"=>"octobre", "11"=>"novembre", "12"=>"décembre"); 
        $fullDate = "";
        $fullDate .= $date->format('d')." ".$month[(string)$date->format('m')]." ".$date->format('Y'). " à ".$date->format('H:i');

        return $fullDate;
    }
    /**
     * @Route("/checkout/direct-paid", name="direct_paid")
     */
    public function directPaid(Request $request, \Swift_Mailer $mailer){
        
        $result = "";
        $user = $this->getUser();
        if(is_null($user))
            $response = new Response(json_encode("Vous devez etre connecté pour un payement en un click"), 500);

        $amount = 0;
        $panier = $this->panierRepository->findOneBy(['user'=>$user->getId(), 'status'=>0]);
        if(!is_null($panier)){
            $amount = $panier->getTotalPrice();
            if(!$amount)
                return new Response("le montant de votre commande est null", 500);
        }
        else
            return new Response("Vous n'avez aucun panier en attente de paiement", 500);
        
        $result = $this->stripe_s->proceedPayment($user, $amount);
        if($result == ""){
            $panier->setStatus(1);
            $panier->setPaiementDate(new \Datetime());
            $this->entityManager->flush();
            
            $assetFile = $this->params_dir->get('file_upload_dir');
            if (!file_exists($request->server->get('DOCUMENT_ROOT') .'/'. $assetFile)) {
                mkdir($request->server->get('DOCUMENT_ROOT') .'/'. $assetFile, 0705);
            } 
            $ouput_name = 'facture_'.$panier->getId().'.pdf';
            $save_path = $assetFile.$ouput_name;
            $params = [
                'format'=>['value'=>'A4', 'affichage'=>'portrait'],
                'is_download'=>['value'=>true, 'save_path'=>$save_path]
            ];
            $dompdf = $this->generatePdf('emails/facture.html.twig', $panier , $params);
            $this->sendMail($mailer, $user, $panier, $save_path);
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

    public function generatePdf($template, $data, $params){
        $options = new Options();
        $dompdf = new Dompdf($options);
        $dompdf -> setPaper ($params['format']['value'], $params['format']['affichage']);
        $html = $this->renderView($template, ['data' => $data]);
        $dompdf->loadHtml($html);
        $dompdf->render();
        if($params['is_download']['value']){
            $output = $dompdf->output();
            file_put_contents($params['is_download']['save_path'], $output);
        }
        return $dompdf;
    }
}
