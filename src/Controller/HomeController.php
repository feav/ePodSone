<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ProductService;
use App\Repository\FormuleRepository;
use App\Repository\TemoignageRepository;
use App\Service\UserService;

class HomeController extends AbstractController
{   
    private $prodService;
    private $user_s;

    public function __construct(ProductService $prodService,UserService $user_s){
        $this->prodService = $prodService;
        $this->user_s = $user_s;
    }
    /**
     * @Route("/", name="home")
     */
    public function index(FormuleRepository $formuleRepository,TemoignageRepository $temoignageRepository)
    {

        $products = $this->prodService->findAll();
        $formule = $formuleRepository->findAll();
        $temoignage = $temoignageRepository->findAll();
        return $this->render('home/index.html.twig', [
            'controller_name' => 'ePodSone',
            'products' => $products,
            'formules' => $formule,
            'temoignages' => $temoignage
        ]);
    }
    /**
     * @Route("/account", name="account")
     */
    public function account(FormuleRepository $formuleRepository,TemoignageRepository $temoignageRepository)
    {
        $user = $this->getUser();
        if($user){
            if(isset($_POST['user'])){
                $user_data = $_POST['user'];
                $this->user_s->updateUser($user_data,$user);
            }
        }
        return $this->render('home/account.html.twig', [
            'controller_name' => 'ePodSone'
        ]);
    }
    /**
     * @Route("/contact", name="contact")
     */
    public function contact(FormuleRepository $formuleRepository,TemoignageRepository $temoignageRepository)
    {

        $products = $this->prodService->findAll();
        $formule = $formuleRepository->findAll();
        $temoignage = $temoignageRepository->findAll();
        return $this->render('home/contact.html.twig', [
            'controller_name' => 'ePodSone',
            'products' => $products,
            'formules' => $formule,
            'temoignages' => $temoignage
        ]);
    }
}
