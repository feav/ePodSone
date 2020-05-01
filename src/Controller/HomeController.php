<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ProductService;

class HomeController extends AbstractController
{   
    private $prodService;
    public function __construct(ProductService $prodService){
        $this->prodService = $prodService;
    }
    /**
     * @Route("/", name="home")
     */
    public function index()
    {

        $products = $this->prodService->findAll();
       
        return $this->render('home/index.html.twig', [
            'controller_name' => 'ePodSone',
            'products' => $products
        ]);
    }
}
