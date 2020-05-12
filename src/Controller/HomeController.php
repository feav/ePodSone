<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ProductService;
use App\Repository\FormuleRepository;
use App\Repository\TemoignageRepository;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;

use App\Repository\PanierRepository;
use App\Repository\CommandeRepository;
use App\Repository\CouponRepository;
use App\Repository\AbonnementRepository;
use App\Repository\UserRepository;

use Dompdf\Options;
use Dompdf\Dompdf;

class HomeController extends AbstractController
{   
    private $prodService;
    private $user_s;
    private $productService;
    private $panierRepository;
    private $commandeRepository;
    private $couponRepository;
    private $userRepository;
    private $entityManager;
    private $money_unit;
    private $params_dir;

    public function __construct(ParameterBagInterface $params_dir, UserRepository $userRepository,AbonnementRepository $abonnementRepository,FormuleRepository $formuleRepository,PanierRepository $panierRepository,CouponRepository $couponRepository,CommandeRepository $commandeRepository, ProductService $productService,ProductService $prodService,UserService $user_s){

        $this->prodService = $prodService;
        $this->user_s = $user_s;

        $this->money_unit = "$";
        $this->productService = $productService;
        $this->panierRepository = $panierRepository;
        $this->commandeRepository = $commandeRepository;
        $this->abonnementRepository = $abonnementRepository;
        $this->couponRepository = $couponRepository;
        $this->formuleRepository = $formuleRepository;
        $this->UserRepository = $userRepository;
        $this->params_dir = $params_dir;
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
     * @Route("/account/facture/{id}", name="billing")
     */
    public function getCurrentCard(Request $request, $id)
    {
        $this->entityManager = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        if($user){

            $panier = $this->panierRepository->findOneById($id);
            /**
            ** if commande do not exist create one
            **/
            if($panier){
                //return $this->render('home/facture.html.twig', array('card' => $panier ));
                
                $assetFile = $this->params_dir->get('file_upload_dir');
                if (!file_exists($request->server->get('DOCUMENT_ROOT') .'/'. $assetFile."facture_".$panier->getId().".pdf")) {
                    return $this->render('home/facture.html.twig', array('card' => $panier ));
                }
                $url = $this->generateUrl('home', [], UrlGenerator::ABSOLUTE_URL);
                return $this->redirect($url."documents/facture_".$panier->getId().".pdf");
            }
        }

        return $this->render('home/account.html.twig');
    }
    /**
     * @Route("/contact", name="contact")
     */
    public function contact(FormuleRepository $formuleRepository,TemoignageRepository $temoignageRepository,\Swift_Mailer $mailer)
    {
        if(isset($_POST['name'])){
            $name = $_POST['name'];
            $surname = $_POST['surname'];
            $email = $_POST['email'];
            $message = $_POST['message'];
            $phone = $_POST['phone'];

            $response = new Response(json_encode( array('status'=>'success','message' =>  "Merci, un membre de l'equipe reviendra vers vous dans de brefs delais." )));
            $response->headers->set('Content-Type', 'application/json');
            if($this->user_s->send_mail($mailer, $email, $phone, $name,$surname, $message)){

            }else{
                $response = new Response(json_encode( array('status'=>'failled','message' =>  "Un probleme pendant l'envoie de votre message" )));
            }

            return $response;
        }
        return $this->render('home/contact.html.twig');
    }

    /**
     * @Route("/resiliation-abonnement/{id}", name="abonnement_resilie", methods={"GET"})
     */
    public function resile(Request $request, $id, \Swift_Mailer $mailer)
    {   
        $user = $this->getUser();
        $entityManager = $this->getDoctrine()->getManager();
        $abonnement = $this->abonnementRepository->find($id);
        if($abonnement->getStart() >= new \DateTime()){
            $flashBag = $this->get('session')->getFlashBag()->clear();
            $this->addFlash('warning', "La periode d'essaie de cet abonnement est passée, vous ne pouvez plus le resilier");
            return $this->redirectToRoute('account');
        }
        if(!$abonnement->getActive()){
            $flashBag = $this->get('session')->getFlashBag()->clear();
            $this->addFlash('warning', "cet abonnement n'est pas actif");
            return $this->redirectToRoute('account');
        }
        $abonnement->setResilie(1);
        $abonnement->setActive(0);
        $entityManager->flush();

        $content = "<p>Votre abonnement a bien été resilié</p>";
        $url = $this->generateUrl('home', [], UrlGenerator::ABSOLUTE_URL);
        try {
            $mail = (new \Swift_Message("Résiliation d'abonement"))
                ->setFrom(array('alexngoumo.an@gmail.com' => 'EpodsOne'))
                ->setTo([$user->getEmail()=>$user->getName()])
                ->setCc("alexngoumo.an@gmail.com")
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
        $flashBag = $this->get('session')->getFlashBag()->clear();
        $this->addFlash('success', "Abonnement resilié");
        return $this->redirectToRoute('account');
    }

    public function generatePdf($template, $data, $params){
        $options = new Options();
        $dompdf = new Dompdf($options);
        $dompdf -> setPaper ($params['format']['value'], $params['format']['affichage']);
        $html = $this->renderView($template, ['data' => $data]);
        $dompdf->loadHtml($html);
        $dompdf->render();
        if($params['is_download']['value']){
            $output = $dompdf->output();
            file_put_contents($params['is_download']['save_path'], $output);
        }
        return $dompdf;
    }
}
