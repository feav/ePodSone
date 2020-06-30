<?php
namespace App\Service;


use Symfony\Component\Config\Definition\Exception\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use App\Repository\UserRepository;
use App\Repository\CommandeRepository;
use App\Repository\ConfigRepository;
use App\Repository\VisiteurRepository;

use App\Entity\User;
use App\Entity\Visiteur;
use App\Entity\Config;
use App\Entity\Commande;

use Stripe\Stripe;
use \Stripe\Charge;

class StripeService{
    
    private $stripeApiKey;
    private $stripeCurrency = "eur";
    private $userRepository;
    private $commandeRepository;
    private $configRepository;
    private $visiteurRepository;


    public function __construct(EntityManagerInterface $em, UserRepository $userRepository, ConfigRepository $configRepository, CommandeRepository $commandeRepository, VisiteurRepository $visiteurRepository){
        $this->userRepository = $userRepository;
        $this->commandeRepository = $commandeRepository;
        $this->configRepository = $configRepository;
        $this->visiteurRepository = $visiteurRepository; 
        $this->em = $em;
        $this->stripeApiKey = !is_null($this->configRepository->findOneBy(['mkey'=>'STRIPE_PRIVATE_KEY'])) ? $this->configRepository->findOneBy(['mkey'=>'STRIPE_PRIVATE_KEY'])->getValue() : "";
    }
    public function getValueByKey($key){
        $config = $this->configRepository->findOneBy(['mkey'=>$key]);
        return is_null($config) ? "" : $config->getValue();
    }

    public function createStripeCustom($source, $metadata){
        \Stripe\Stripe::setApiKey($this->stripeApiKey);
        $custom =  \Stripe\Customer::create([
            'source' => $source,
            'email' => $metadata['email'],
            'name' => $metadata['name'],
            'description' => 'Client de la boutique VinsPro',
        ]);
        $user = $this->userRepository->findOneBy(['email'=>$metadata['email']]);
        $user->setStripeCustomId($custom['id']);
        $this->em->flush();

        return $custom['id'];
    }

    public function paidByStripeCustom($stripe_custom_id, $amount){
        Stripe::setApiKey($this->stripeApiKey);
        $charge = \Stripe\Charge::create([
            'amount' => $amount*100,
            'currency' => $this->stripeCurrency,
            'customer' => $stripe_custom_id, 
        ]);

        return $charge['id'];
    }

    public function proceedPayment($user, $amount){

        $result = "";
        Stripe::setApiKey($this->stripeApiKey);
        try {
            $this->updateVisiteur($user->getId());
            $chargeId = $this->paidByStripeCustom($user->getStripeCustomId(), $amount);
            

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
        
        return ['message'=>$result, 'charge'=> $chargeId];
    }

    public function saveChargeToRefund($panier, $charge){
        if(count($panier->getCommandes())){
            $panier->setStripeChargeId($charge);
            $this->em->flush();
        }
        return $panier->getId();
    }

    public function refund($charge){
        \Stripe\Stripe::setApiKey($this->stripeApiKey);
        $charge = \Stripe\Refund::create([
          'charge' => $charge,
        ]);

        return 1;
    }

    public function updateVisiteur($user_id){
        $ip = $this->get_user_ip_address();
        if(!empty($ip)){
            $visiteur = $this->visiteurRepository->findOneBy(['ip'=>$ip]);
            if(!is_null($visiteur)){
                $visiteur->setNbAchat($visiteur->getNbAchat() +1);
                $visiteur->setUserId($user_id);
                $visiteur->setLastDataVisite(new \DateTime());
                $this->em->flush();
            }
        }
        
        return 1;
    }

    /* abonnement avec definition du prix */
    public function subscription($user, $abonnement){

        if($abonnement->getFormule()->getMonth() == 1){
            $interval = 'month';
        }
        elseif($abonnement->getFormule()->getMonth() == 12)
            $interval = 'year';

        \Stripe\Stripe::setApiKey($this->stripeApiKey);
        $subscription = \Stripe\Subscription::create([
          'customer' => $user->getStripeCustomId(),
          'trial_period_days'=>(int)$abonnement->getFormule()->getTryDays(),
          'items' => [[
            'price_data' => [
              'unit_amount' => 100*$abonnement->getFormule()->getPrice(),
              'currency' => $this->stripeCurrency,
              'product' => $abonnement->getFormule()->getStripeProductId(),
              'recurring' => [
                'interval' => $interval,
              ],
            ],
          ]],
          'metadata' => 
            [
                'abonnement_id' => $abonnement->getId()
            ]
        ]);
        $this->updateAbonnement($subscription['id'], $abonnement);
        return $subscription['id'];
    }

    public function updateAbonnement($subscription, $abonnement){
        $abonnement->setSubscription($subscription);
        $this->em->flush();
    }

    public function getAllProduct(){
        \Stripe\Stripe::setApiKey($this->stripeApiKey);
        $products = \Stripe\Product::all();
        if(!(array)$products)
            return [];
        return $products['data'];
    }

    public function createProduct(){
        \Stripe\Stripe::setApiKey($this->stripeApiKey);
        $product = \Stripe\Product::create([
          'name' => 'abonnement vitanatural',
        ]);
        return $product;
    }

    public function subscriptionCancel($subscription_id){
        \Stripe\Stripe::setApiKey($this->stripeApiKey);
        /*$subscription = \Stripe\Subscription::update(
          $subscription_id,
          [
            'cancel_at_period_end' => true,
          ]
        );*/
        
        $subscription = \Stripe\Subscription::retrieve($subscription_id);
        $subscription->cancel();//resili imediatement 
        
        return $subscription['id'];
    }

    public function get_user_ip_address($return_type=NULL){
        $ip_addresses = array();
        $ip_elements = array(
            'HTTP_X_FORWARDED_FOR', 'HTTP_FORWARDED_FOR',
            'HTTP_X_FORWARDED', 'HTTP_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_CLUSTER_CLIENT_IP',
            'HTTP_X_CLIENT_IP', 'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        );
        foreach ( $ip_elements as $element ) {
            if(isset($_SERVER[$element])) {
                if ( !is_string($_SERVER[$element]) ) {
                    // Log the value somehow, to improve the script!
                    continue;
                }
                $address_list = explode(',', $_SERVER[$element]);
                $address_list = array_map('trim', $address_list);
                // Not using array_merge in order to preserve order
                foreach ( $address_list as $x ) {
                    $ip_addresses[] = $x;
                }
            }
        }
        if ( count($ip_addresses)==0 ) {
            return FALSE;
        } elseif ( $return_type==='array' ) {
            return $ip_addresses;
        } elseif ( $return_type==='single' || $return_type===NULL ) {
            return $ip_addresses[0];
        }
    }
}
