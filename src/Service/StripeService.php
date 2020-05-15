<?php
namespace App\Service;


use Symfony\Component\Config\Definition\Exception\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use App\Repository\UserRepository;
use App\Repository\CommandeRepository;
use App\Repository\ConfigRepository;
use App\Entity\User;
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

    public function __construct(EntityManagerInterface $em, UserRepository $userRepository, ConfigRepository $configRepository, CommandeRepository $commandeRepository){
        $this->userRepository = $userRepository;
        $this->commandeRepository = $commandeRepository;
        $this->configRepository = $configRepository;
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
            'description' => 'Client de la boutique EpodsOne',
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
}
