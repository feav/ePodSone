<?php
namespace App\Service;


use Symfony\Component\Config\Definition\Exception\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use App\Repository\UserRepository;
use App\Entity\User;

use Stripe\Stripe;
use \Stripe\Charge;
\Stripe\Stripe::setApiKey('sk_test_QM1PN2GsJWClfDtLTfPSbiZn00IwVC4sK5');

class StripeService{
    
    private $stripeApiKey = 'sk_test_QM1PN2GsJWClfDtLTfPSbiZn00IwVC4sK5';
    private $stripeCurrency = "eur";
    private $userRepository;

    public function __construct(EntityManagerInterface $em, UserRepository $userRepository){
        $this->userRepository = $userRepository;
        $this->em = $em;
    }

    public function createStripeCustom($source, $metadata){
        \Stripe\Stripe::setApiKey('sk_test_QM1PN2GsJWClfDtLTfPSbiZn00IwVC4sK5');
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

        return 1;
    }

    public function proceedPayment($user, $amount){

        $result = "";
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

}
