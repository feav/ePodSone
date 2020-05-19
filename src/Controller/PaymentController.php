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
use App\Repository\CommandeRepository;
use App\Repository\PanierRepository;
use App\Service\StripeService;
use App\Service\UserService;
use App\Service\GlobalService;
use App\Entity\User;
use App\Entity\Abonnement;
use App\Entity\Panier;
use App\Entity\Commande;

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
    private $commandeRepository;
    private $entityManager;
    private $abonnementRepository;
    private $global_s;

    public function __construct(ParameterBagInterface $params_dir, UserRepository $userRepository, UserService $user_s, StripeService $stripe_s, AbonnementRepository $abonnementRepository, PanierRepository $panierRepository, CommandeRepository $commandeRepository, GlobalService $global_s){
        $this->params_dir = $params_dir;
        $this->stripe_s = $stripe_s;
        $this->user_s = $user_s;
        $this->global_s = $global_s;
        $this->userRepository = $userRepository;
        $this->panierRepository = $panierRepository;
        $this->commandeRepository = $commandeRepository;
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
                $preparePaid = $this->preparePaid($panier, $mailer);
                $message = $preparePaid['message'];
                if($preparePaid['paid']){
                    $amount = $preparePaid['amount'];
                    if($this->global_s->isAbonnementValide($user->getId()))
                        $amount = $preparePaid['amount'] + (int)$this->stripe_s->getValueByKey('LIVRAISON_AMOUNT');
                    $response = $this->stripe_s->proceedPayment($user, $amount);
                    $this->stripe_s->saveChargeToRefund($panier, $response['charge']);
                    $result = $response['message'];
                }
            }
            $flashBag = $this->get('session')->getFlashBag()->clear();
            $this->addFlash('success', 'Paiement effectué avec success');
        }
        else{
            $metadata = ['name'=>$user->getName(), 'email'=>$user->getEmail()];
            
            if($request->request->get('stripeSource') !== null && $amount !== null) {
                $this->stripe_s->createStripeCustom($request->request->get('stripeSource'), $metadata);
                $preparePaid = $this->preparePaid($panier, $mailer);
                $message = $preparePaid['message'];
                if($preparePaid['paid']){
                    $amount = $preparePaid['amount'];
                    if($this->global_s->isAbonnementValide($user->getId()))
                        $amount = $preparePaid['amount'] + (int)$this->stripe_s->getValueByKey('LIVRAISON_AMOUNT');
                    $response = $this->stripe_s->proceedPayment($user, $amount);
                    $result = $response['message'];
                    $this->stripe_s->saveChargeToRefund($panier, $response['charge']);
                }
            }
            elseif($user->getStripeCustomId() !=""){
                $preparePaid = $this->preparePaid($panier, $mailer);
                $message = $preparePaid['message'];
                if($preparePaid['paid']){
                    $amount = $preparePaid['amount'];
                    if($this->global_s->isAbonnementValide($user->getId()))
                        $amount = $preparePaid['amount'] + (int)$this->stripe_s->getValueByKey('LIVRAISON_AMOUNT');
                    $response = $this->stripe_s->proceedPayment($user, $amount);
                    $this->stripe_s->saveChargeToRefund($panier, $response['charge']);
                    $result = $response['message'];
                    
                }
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
                'is_download'=>['value'=>true, 'save_path'=>$save_path],
                'total_price'=>$amount
            ];
            $dompdf = $this->generatePdf('emails/facture.html.twig', $panier , $params);

            $this->sendMail($mailer, $user, $panier, $save_path, $amount);
            
            return new Response($message, 200);
        }
        else
            return new Response('Erreur : ' . $errorMessage , 500);
        return new Response(json_encode(['ok'=>true]));
    }

    public function sendMail($mailer, $user, $panier, $commande_pdf, $amount){

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
        
        $this->entityManager = $this->getDoctrine()->getManager();
        $result = "";
        $user = $this->getUser();
        if(is_null($user))
            $response = new Response(json_encode("Vous devez etre connecté pour un paiement en un click"), 500);

        $amount = 0;
        $panier = $this->panierRepository->findOneBy(['user'=>$user->getId(), 'status'=>0]);
        if(!is_null($panier)){
            $amount = $panier->getTotalPrice();
            if(!$amount)
                return new Response("le montant de votre commande est null", 500);
        }
        else
            return new Response("Vous n'avez aucun panier en attente de paiement", 500);
        
        $preparePaid = $this->preparePaid($panier, $mailer);
        $message = $preparePaid['message'];
        if($preparePaid['paid']){
            $amount = $preparePaid['amount'];
            if($this->global_s->isAbonnementValide($user->getId()))
                $amount = $preparePaid['amount'] + (int)$this->stripe_s->getValueByKey('LIVRAISON_AMOUNT');
            $response = $this->stripe_s->proceedPayment($user, $amount);
            $this->stripe_s->saveChargeToRefund($panier, $response['charge']);
            $result = $response['message'];
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
                'is_download'=>['value'=>true, 'save_path'=>$save_path],
                'total_price'=>$amount
            ];
            $dompdf = $this->generatePdf('emails/facture.html.twig', $panier , $params);
            $this->sendMail($mailer, $user, $panier, $save_path, $amount);
            $response = new Response(json_encode($message), 200);
        }
        else
            $response = new Response(json_encode('Erreur : ' . $errorMessage), 500);

        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @Route("/abonnement/update-abonnement", name="update_abonnement")
     */
    public function updateAbonnements(Request $request, \Swift_Mailer $mailer){
        $this->entityManager = $this->getDoctrine()->getManager();
        $abonnements = $this->abonnementRepository->findBy(['active'=>1]);
        foreach ($abonnements as $key => $value) {
            $result = "";
            $user = $value->getUser();
            if($value->getActive()){
                $date = $value->getStart();
                $date->add(new \DateInterval('P'.$value->getFormule()->getTryDays().'D'));
                
                if(!$value->getState() && (new \Datetime() >= $date )){
                    $result = $this->stripe_s->proceedPayment($user, $value->getFormule()->getPrice());
                    if($result == ""){
                        $value->setState(1);
                        $content = "<p>Vous etes arrivé à la fin de votre periode d'essaie pour l'abonnement ".$value->getFormule()->getMonth()." mois. vous avez été débité de ".$value->getFormule()->getPrice()."€ sur votre carte</p>";
                        $url = $this->generateUrl('home');
                        try {
                            $mail = (new \Swift_Message('Abonnement Payé'))
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
                }
                if( (new \Datetime() >= $value->getEnd()) && !$value->getIsPaid() ){
                    $result = $this->stripe_s->proceedPayment($user, $value->getFormule()->getPrice());
                    if($result == ""){
                        $value->setIsPaid(1);
                        $value->setActive(0);
                        $this->createNewAbonnement($value);

                        $content = "<p>Votre abonnement ".$value->getFormule()->getMonth()." Mois a été renouvellé et vous a couté ".$value->getFormule()->getPrice()."€</p>";
                        $url = $this->generateUrl('home');
                        try {
                            $mail = (new \Swift_Message('Abonnement renouvellé'))
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
                } 
                $this->entityManager->flush();
            }
        }

        return new Response("renouvellé");
    }
    public function createNewAbonnement($abonnement){
        $this->entityManager = $this->getDoctrine()->getManager();
        $entity = new Abonnement();
        $month = $abonnement->getFormule()->getMonth();
        //$trialDay = $abonnement->getFormule()->getTryDays();

        $curDate = new \Datetime();
        $entity->setStart(new \Datetime());
        $curDate->add(new \DateInterval('P0Y'.$month.'M0DT0H0M0S'));
        $entity->setEnd($curDate);
        $entity->setState(1);
        $entity->setFormule($abonnement->getFormule());
        $entity->setPanier($abonnement->getPanier());
        $entity->setUser($abonnement->getUser());

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        return 1;
    }

    public function generatePdf($template, $data, $params, $type_produit = "product"){
        $options = new Options();
        $dompdf = new Dompdf($options);
        $dompdf -> setPaper ($params['format']['value'], $params['format']['affichage']);
        $html = $this->renderView($template, ['data' => $data, 'total_price'=>$params['total_price'] , 'type_produit'=>$type_produit]);
        $dompdf->loadHtml($html);
        $dompdf->render();
        if($params['is_download']['value']){
            $output = $dompdf->output();
            file_put_contents($params['is_download']['save_path'], $output);
        }
        return $dompdf;
    }

    public function preparePaid($panier, $mailer){
        $user = $this->getUser();
        $amount = $panier->getTotalPrice() - $panier->getTotalReduction();
        $amount = ( $amount <0 ) ? 0 : $amount;
        $message = "Paiement Effectué avec Succèss";
        if(count($panier->getAbonnements())){
            $message = "Votre abonnement sera facturé apres la periode d'essaie";   
        }
        if(!count($panier->getCommandes())){
            $this->entityManager = $this->getDoctrine()->getManager();
            $panier->setPaiementDate(new \Datetime());
            $this->entityManager->flush();
            
            $this->addFlash('success', "Votre abonnement sera facturé apres la periode d'essaie");
            return ['paid'=>false, 'message'=>"Votre abonnement sera facturé apres la periode d'essaie", 'amount'=>0];
        }
        elseif(count($panier->getAbonnements())){
            $message = "Paiement Effectué avec Succèss. Votre abonnement sera facturé apres la periode d'essaie";
            $abonnement = $panier->getAbonnements()[0];
            $abonnementAmount = $abonnement->getFormule()->getPrice();
            $amount -= $abonnementAmount;
        }
        return ['paid'=>true, 'message'=>$message, 'amount'=>$amount];
    }

}
