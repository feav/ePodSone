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

        return $this->render('admin/statistique.html.twig', [
            'commandes' => $commandes['count'],
	    	'remboursement' => $remboursement['count'],
	    	'commandePaye' => $commandePaye['count'],
	    	'abonnements' => $abonnements['count'],
	    	'abonnementResilie' => $abonnementResilie['count'],
	    	'abonnementPaye' => $abonnementPaye['count'],
        	]);
       
    }
}