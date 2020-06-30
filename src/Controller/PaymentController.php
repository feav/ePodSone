<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\UserRepository;
use App\Repository\AbonnementRepository;
use App\Repository\CommandeRepository;
use App\Repository\PanierRepository;
use App\Repository\FormuleRepository;
use App\Repository\ConfigRepository;
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
    private $formuleRepository;
    private $entityManager;
    private $abonnementRepository;
    private $global_s;
    private $configRepository;

    public function __construct(ParameterBagInterface $params_dir, UserRepository $userRepository, UserService $user_s, StripeService $stripe_s, AbonnementRepository $abonnementRepository, PanierRepository $panierRepository, CommandeRepository $commandeRepository, GlobalService $global_s, FormuleRepository $formuleRepository, ConfigRepository $configRepository){
        $this->params_dir = $params_dir;
        $this->stripe_s = $stripe_s;
        $this->user_s = $user_s;
        $this->global_s = $global_s;
        $this->userRepository = $userRepository;
        $this->panierRepository = $panierRepository;
        $this->commandeRepository = $commandeRepository;
        $this->formuleRepository = $formuleRepository;
        $this->configRepository = $configRepository;
        $this->abonnementRepository = $abonnementRepository;
        $this->stripeApiKey = !is_null($this->configRepository->findOneBy(['mkey'=>'STRIPE_PRIVATE_KEY'])) ? $this->configRepository->findOneBy(['mkey'=>'STRIPE_PRIVATE_KEY'])->getValue() : "";
    }

    /**
     * @Route("/paiement-cart", name="paiement_cart", methods={"GET"})
     */
    public function paiement(): Response
    {
        return $this->render('home/paiement.html.twig', []);
    }

    /**
      * @Route("/success-payment", name="success_payment", methods={"GET"})
     */
    public function payementSuccess(){
        return $this->render('home/success_payment.html.twig', []);
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

            if(!count($panier->getAbonnements())){
                if(!$this->global_s->isAbonnementValide($user->getId()))
                    $this->createNewAbonnement();
            }

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
            //$dompdf = $this->generatePdf('emails/facture.html.twig', $panier , $params);

            $this->sendMail($mailer, $user, $panier, $save_path, $amount);
            
            return new Response($message, 200);
        }
        else
            return new Response('Erreur : ' . $errorMessage , 500);
        return new Response(json_encode(['ok'=>true]));
    }

    public function sendMail($mailer, $user, $panier, $commande_pdf, $amount){

        if(count($panier->getCommandes())){
            $content = "<p>Bonjour ".$user->getName().", <br> Vous avez fait des achat pour ".$panier->getTotalPrice()."€<br>
            Livraison Gratuite (Essai de 3 jours) pour un de nos abonnements</p>";
            $url = $this->generateUrl('home', [], UrlGenerator::ABSOLUTE_URL);
            try {
                $mail = (new \Swift_Message('Confirmation commande'))
                    ->setFrom(array('alexngoumo.an@gmail.com' => 'VinsPro'))
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
        if(count($panier->getAbonnements())){
            $abonnement = $panier->getAbonnements()[0];
            $mois_annee = ($abonnement->getFormule()->getMonth() == 12) ? "ans" : $abonnement->getFormule()->getMonth()."mois";

            $content = "<p>Bien joué ".$user->getName()."! Confirmation de votre essai de ".$abonnement->getFormule()->getTryDays()." jours à notre abonnement de Livraison Gratuite en  illimité pour ".$abonnement->getFormule()->getPrice()."€/".$mois_annee.". <br><br>Il vous reste ".$abonnement->getFormule()->getTryDays()." jours d’essai pour commander et obtenir la Livraison Gratuite en  illimité sur notre boutique au lieu de 10€.<br><br>Vous serez débité de ".$abonnement->getFormule()->getPrice()."€/".$mois_annee." à partir du ". $this->getFullDate($abonnement->getEnd())." au moment de la  fin de votre essai.<br><br> Si vous souhaitez résilier veuillez vous connecter sur notre boutique et faire votre  demande de résiliation de manière automatique.<br><br>Vos informations de connexion vous ont été envoyés à cette email (<small>".$user->getEmail()."</small>) lors de votre tout premier achat";
            $url = $this->generateUrl('home');
            try {
                $mail = (new \Swift_Message('Abonnement réussit'))
                    ->setFrom(array('alexngoumo.an@gmail.com' => 'VinsPro'))
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
            $response = $this->stripe_s->proceedPayment($user, $amount);
            $this->stripe_s->saveChargeToRefund($panier, $response['charge']);
            $result = $response['message'];
        }

        if($result == ""){
            $panier->setStatus(1);
            $panier->setPaiementDate(new \Datetime());
            $this->entityManager->flush();
            if(!count($panier->getAbonnements())){
                if(!$this->global_s->isAbonnementValide($user->getId()))
                    $this->createNewAbonnement();
            }

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
            //$dompdf = $this->generatePdf('emails/facture.html.twig', $panier , $params);
            $this->sendMail($mailer, $user, $panier, $save_path, $amount);
            $response = new Response(json_encode($message), 200);
        }
        else
            $response = new Response(json_encode('Erreur : ' . $errorMessage), 500);

        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function createNewAbonnement(){
        $this->entityManager = $this->getDoctrine()->getManager();
        $formule = $this->formuleRepository->findOneBy([], ['id'=>'DESC'], 1);
        $date = new \DateTime();
        $date_start = new \DateTime();
        $month = $formule->getMonth();
        $trialDay = $formule->getTryDays();
        $date->add(new \DateInterval('P0Y'.$month.'M'.$trialDay.'DT0H0M0S'));

        $user = $this->getUser();
        $entity = new Abonnement();
        $entity->setFormule($formule);
        $entity->setStart($date_start);
        $entity->setEnd($date);
        $entity->setUser($user);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        $this->stripe_s->subscription($user, $entity);
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

    public function totalAmount($panier){
        $amount = $panier->getTotalPrice();
        if($panier->getTotalReduction() > 0)
            $amount -= $panier->getTotalReduction();
        return $amount;
    }
    public function preparePaid($panier, $mailer){
        $amount = $panier->getTotalPrice();
        if($panier->getTotalReduction() > 0)
            $amount -= $panier->getTotalReduction();
            
        $this->addFlash('success', "Paiement Effectué avec Succèss");
        $response = ['paid'=>true, 'message'=>"Paiement Effectué avec Succèss", 'amount'=>$amount];
        
        return $response;
    }

    /**
     * @Route("/webhook-subscription-vinspro", name="webhook_subscription_vinspro")
     */
    public function subscriptionWebhook(Request $request, \Swift_Mailer $mailer){

        \Stripe\Stripe::setApiKey($this->stripeApiKey);

        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            throw new \Exception('Bad JSON body from Stripe!');
        }
        $event = \Stripe\Event::retrieve($data['id']);

        $message ="";
        // Handle the event
        switch ($event->type) {
            case 'customer.subscription.updated':
                $subscription = $event->data->object; 
                $message = "subscription.updated";
                $this->updateSubscription('updated', $subscription, $mailer);
                break;
            case 'customer.subscription.created':
                $subscription = $event->data->object;
                $message = "subscription.created";
                $this->updateSubscription('created', $subscription, $mailer);
                break;
            case 'customer.subscription.pending_update_expired':
                $subscription = $event->data->object;
                $message = "subscription.expired";
                $this->updateSubscription('expired', $subscription, $mailer);
                break;
            case 'invoice.payment_succeeded':
                $paymentMethod = $event->data->object; 
                $subscription_id = $paymentMethod->lines->data[0]->subscription;
                $abonnementId = $paymentMethod->lines->data[0]->metadata->abonnement_id;

                if(!is_null($paymentMethod->billing_reason) && ($paymentMethod->billing_reason == "subscription_create" || $paymentMethod->billing_reason == "subscription_cycle" ) ){
                    $customer_email = $paymentMethod->customer_email;
                    $invoice_pdf = $paymentMethod->invoice_pdf;
                    $message = '<p>Bonjour, cliquer sur le lien ci-dessous pour telecharger votre facture <br>'.$invoice_pdf.'</p>';
                    if($paymentMethod->amount_paid > 0)
                        $this->factureMail('Facture abonnement', $message, $customer_email, $mailer);
                }
                break;
            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object; 
                if( !is_null($paymentIntent->charges->data->description) && ($paymentIntent->charges->data->description == "Subscription creation" || $paymentIntent->charges->data->description == "Subscription update" ) ){
                    $source_id = $paymentIntent->charges->data->source->id;
                    //
                }
                break;
            default:
                return new Response('Evenement inconnu',400);
                /*http_response_code(400);
                exit();*/
        }
        //http_response_code(200);
        return new Response('Evenement terminé avec success',200);
    }

    public function factureMail($objet, $message, $clientEmail, $mailer){
        $url = $this->generateUrl('home', [], UrlGenerator::ABSOLUTE_URL);
        try {
                $mail = (new \Swift_Message($objet))
                ->setFrom(array("alexngoumo.an@gmail.com" => 'VinsPro'))
                ->setTo([$clientEmail=>$clientEmail])
                ->setCc("alexngoumo.an@gmail.com")
                ->setBody(
                    $this->renderView(
                        'emails/mail_template.html.twig',['content'=>$message, 'url'=>$url]
                    ),
                    'text/html'
                );
                $mailer->send($mail);
            } catch (Exception $e) {
                print_r($e->getMessage());
        }
        return 1;
    }

    public function updateSubscription($status, $subscription, $mailer){

        $this->entityManager = $this->getDoctrine()->getManager();
        $abonnement = $this->abonnementRepository->findOneBy(['subscription'=>$subscription->id]);

        if(!is_null($abonnement)){
            $user = $abonnement->getUser();
            $message = "";
            if( ($status == "created" || $status == "updated") && ($subscription->status == "active" || $subscription->status == "trialing" )){
                $abonnement->setActive(1);
                $abonnement->setStart(new \DateTime(date('Y-m-d H:i:s', $subscription->current_period_start)));
                $abonnement->setEnd(new \DateTime(date('Y-m-d H:i:s', $subscription->current_period_end)));
                
                $mois_annee = ($abonnement->getFormule()->getMonth() == 12) ? "ans" : $abonnement->getFormule()->getMonth()."mois";

                if($status == "created"){
                    $message = "<p>Bien joué ".$user->getName()."! Confirmation de votre essai de ".$abonnement->getFormule()->getTryDays()." jours à notre abonnement de Livraison Gratuite en  illimité pour ".$abonnement->getFormule()->getPrice()."€/".$mois_annee.". <br><br>Il vous reste ".$abonnement->getFormule()->getTryDays()." jours d’essais pour commander et obtenir la Livraison Gratuite en  illimité sur notre boutique au lieu de 10€.<br><br>Vous serez débité dans un delais de ".$abonnement->getFormule()->getTryDays()." à  partir de fin de votre essai.<br><br> Si vous souhaitez résilier veuillez vous connecter sur notre boutique et faire votre  demande de résiliation de manière automatique.<br><br>Vos informations de connexion vous ont été envoyés à cette email (<small>".$user->getEmail()."</small>) lors de votre tout premier achat";
                }
                else{
                    $message = "<p> Bonjour, <br> nous vous confirmons que votre abonnement a été renouvellé avec succèss. </p>";
                }
            }
            if($status == "expired"){
                $abonnement->setActive(0);
                $message="<p> Bonjour, <br> nous n'avons pas pu renouveller votre abonnement, il sera donc suspendu</p>";
            }
            $this->entityManager->flush();
            $url = $this->generateUrl('home', [], UrlGenerator::ABSOLUTE_URL);
            try {
                $mail = (new \Swift_Message("Status abonnement"))
                ->setFrom(array($user->getEmail() => 'VinsPro'))
                ->setCc("alexngoumo.an@gmail.com")
                ->setTo([$user->getEmail()=> $user->getEmail()])
                ->setBody(
                    $this->renderView(
                        'emails/mail_template.html.twig',['content'=>$message, 'url'=>$url]
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

}
