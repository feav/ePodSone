<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

use App\Entity\Visiteur;
use App\Repository\VisiteurRepository;

class VisiteurController extends AbstractController
{
	private $visiteurRepository;

    public function __construct(VisiteurRepository $visiteurRepository){
        $this->visiteurRepository = $visiteurRepository; 
    }

    /**
     * @Route("/create-visiteur", name="visiteur_create")
     */
    public function create(Request $request)
    {
    	$ip = $this->get_user_ip_address();
    	if(!empty($ip)){
    		$entityManager = $this->getDoctrine()->getManager();
    		$visiteurExist = $this->visiteurRepository->findOneBy(['ip'=>$ip]);
    		if(!is_null($visiteurExist)){
    			$visiteurExist->setLastDataVisite(new \DateTime());
    		}
    		else{
	    		$visiteur = new Visiteur();
	    		$visiteur->setIp($ip);
	            $entityManager->persist($visiteur);
	        }
	        $entityManager->flush();
    	}
    	
        return new Response('visiteur mis à jour', 200);
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