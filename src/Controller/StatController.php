<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use App\Repository\PanierRepository;
use App\Repository\AbonnementRepository;
use App\Repository\CommandeRepository;

class StatController extends AbstractController
{
	private $panierRepository;
	private $abonnementRepository;
	private $commandeRepository;

    public function __construct(PanierRepository $panierRepository, CommandeRepository $commandeRepository, AbonnementRepository $abonnementRepository){
        $this->panierRepository = $panierRepository; 
        $this->abonnementRepository = $abonnementRepository; 
        $this->commandeRepository = $commandeRepository; 
    }

    /**
     * @Route("/dashboard", name="statistique")
     */
    public function index(Request $request)
    {	
    	$commandes = $this->panierRepository->countCommande();
    	$remboursement = $this->panierRepository->countRemboursement();
    	$commandePaye = $this->panierRepository->countCommandePaye();

    	$abonnements = $this->abonnementRepository->countAbonnement();
    	$abonnementResilie = $this->abonnementRepository->countAbonnementResilie();
    	$abonnementPaye = $this->abonnementRepository->countAbonnementPaye();

    	$rapportSemaineCmd = $this->getInfosCommandeByEcheance('semaine', 'commande');
    	$rapportMoisCmd = $this->getInfosCommandeByEcheance('mois', 'commande');
    	$rapportAnneeCmd = $this->getInfosCommandeByEcheance('annee', 'commande');

    	$rapportSemaineAbon = $this->getInfosCommandeByEcheance('semaine', 'abonnement');
    	$rapportMoisAbon = $this->getInfosCommandeByEcheance('mois', 'abonnement');
    	$rapportAnneeAbon = $this->getInfosCommandeByEcheance('annee', 'abonnement');


    	$rapportSemaineCmd = $this->buildDataCmd($rapportSemaineCmd, 'semaine');
    	$rapportMoisCmd = $this->buildDataCmd($rapportMoisCmd, 'mois');
    	$rapportAnneeCmd = $this->buildDataCmd($rapportAnneeCmd, 'annee');

    	$rapportSemaineAbon = $this->buildDataAbonnement($rapportSemaineAbon, 'semaine');
    	$rapportMoisAbon = $this->buildDataAbonnement($rapportMoisAbon, 'mois');
    	$rapportAnneeAbon = $this->buildDataAbonnement($rapportAnneeAbon, 'annee');

    	//dd($rapportSemaineCmd);
        return $this->render('admin/statistique.html.twig', [
            'commandes' => $commandes['count'],
	    	'remboursement' => $remboursement['count'],
	    	'commandePaye' => $commandePaye['count'],
	    	'abonnements' => $abonnements['count'],
	    	'abonnementResilie' => $abonnementResilie['count'],
	    	'abonnementPaye' => $abonnementPaye['count'],
	    	'rapportSemaineCmd'=> $rapportSemaineCmd,
	    	'rapportMoisCmd'=> $rapportMoisCmd,
	    	'rapportAnneeCmd'=> $rapportAnneeCmd,
	    	'rapportSemaineAbon'=> $rapportSemaineAbon,
	    	'rapportMoisAbon'=> $rapportMoisAbon,
	    	'rapportAnneeAbon'=> $rapportAnneeAbon,
        ]);
       
    }

    public function getInfosCommandeByEcheance($echeance, $produit)
    {

        $dateNow = new \Datetime('2020-05-22');
        $finalActivity = [];
        if($echeance == "semaine"){
            $day = $dateNow->format('D');
            $tabWeek = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            $dayRang = array_search($day, $tabWeek);
            array_splice($tabWeek, ($dayRang+1));
            $dateStart = date('Y-m-d',strtotime($dateNow->format('Y-m-d') . "-".$dayRang." days"));
            $finalActivity = $this->buildInfosVente($dateStart, $tabWeek, $produit);
        }
        elseif($echeance == "mois"){
            $day = $dateNow->format('d');
            $tabMois = [];
            for($i = 1; $i <= 31; $i++)
                $tabMois[] = $i;

            $dayRang = array_search($day, $tabMois);
            array_splice($tabMois, ($dayRang+1));
            $dateStart = date('Y-m-d',strtotime($dateNow->format('Y-m-d') . "-".$dayRang." days"));
            $finalActivity = $this->buildInfosVente($dateStart, $tabMois, $produit);
        }
        elseif($echeance == "annee"){
            $month = $dateNow->format('M');
            $tabAnnee = [ '01'=>'Jan', '02'=>'Feb', '03'=>'Mar', '04'=>'Apr', '05'=>'May', '06'=>'Jun', '07'=>'Jul', '08'=>'Aug', '09'=>'Sep', '10'=>'Oct', '11'=>'Nov', '12'=>'Dec'];

            $monthRang = array_search($month, $tabAnnee);
            array_splice($tabAnnee, ($monthRang));     
            if($produit == "commande"){
	            foreach ($tabAnnee as $key => $value) {
	                $finalActivity[] = $this->commandeRepository->getInfosVente(
	                    new \Datetime($dateNow->format('Y')."-".$key."-"."01"." 00:00:00"), 
	                    new \Datetime($dateNow->format('Y')."-".$key."-".
	                        cal_days_in_month(CAL_GREGORIAN, $key, $dateNow->format('Y'))." 23:59:59") );
	            }
	        }
	        elseif($produit == "abonnement"){
	        	foreach ($tabAnnee as $key => $value) {
	                $finalActivity[] = $this->abonnementRepository->getInfosAbonnement(
	                    new \Datetime($dateNow->format('Y')."-".$key."-"."01"." 00:00:00"), 
	                    new \Datetime($dateNow->format('Y')."-".$key."-".
	                        cal_days_in_month(CAL_GREGORIAN, $key, $dateNow->format('Y'))." 23:59:59") );
	            }
	        }
        }

        return $finalActivity;
    }

    public function buildInfosVente($dateStart, $tabEchantillons, $produit){

        $finalActivity = [];
        foreach ($tabEchantillons as $value) {
        	if($produit == "commande"){
	            $finalActivity[] = $this->commandeRepository->getInfosVente(new \Datetime($dateStart." 00:00:00"), 
	            new \Datetime($dateStart." 23:59:59"));
	            $dateStart = date('Y-m-d',strtotime($dateStart . "+1 days"));
	        }
	        elseif($produit == "abonnement"){
	            $finalActivity[] = $this->abonnementRepository->getInfosAbonnement(new \Datetime($dateStart." 00:00:00"), 
	            new \Datetime($dateStart." 23:59:59"));
	            $dateStart = date('Y-m-d',strtotime($dateStart . "+1 days"));
	        }
        }
        return $finalActivity;
    }

    public function buildDataCmd($data, $echeance){
    	$DATA_VENTE = [];
    	$DATA_NBR = [];
    	foreach ($data as $key => $value) {
    		$DATA_VENTE[] = round($value['vente'], 2);
    		$DATA_NBR[] = $value['nbr_commande'];
    	}
    	$AXIS_ANNEE = $this->buildXAxis($echeance, count($DATA_VENTE) );
    	$data = ['vente'=>$DATA_VENTE, 'nbr_commande'=>$DATA_NBR, 'x_axis'=>$AXIS_ANNEE];

    	return $data;
    }
    public function buildDataAbonnement($data, $echeance){
    	$DATA_VENTE = [];
    	$DATA_NBR = [];
    	foreach ($data as $key => $value) {
    		$DATA_VENTE[] = round($value['vente'], 2);
    		$DATA_NBR[] = $value['nbr_commande'];
    	}
    	//$AXIS_ANNEE = $this->buildXAxis($echeance, count($DATA_VENTE) );
    	$data = ['vente'=>$DATA_VENTE, 'nbr_commande'=>$DATA_NBR];

    	return $data;
    }

    public function buildXAxis($echeance, $nbr){
    	if($echeance == 'annee'){
    		$x_axis_full = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep','Oct','Nov','Dec'];
    		$x_axis = [];
    		for ($i=0; $i < $nbr ; $i++) { 
    			$x_axis[] = $x_axis_full[$i];
    		}
    	}
    	elseif($echeance == 'mois'){
    		$x_axis = [];
    		for ($i=1; $i <= $nbr ; $i++) { 
    			$x_axis[] = $i;
    		}
    	}
    	elseif($echeance == 'semaine'){
    		$x_axis_full = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    		$x_axis = [];
    		for ($i=0; $i < $nbr ; $i++) { 
    			$x_axis[] = $x_axis_full[$i];
    		}
    	}

    	return $x_axis;
    }
}