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
use App\Repository\ProductRepository;
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
     * @Route("/temoignage", name="temoignage")
     */
    public function temoignage(TemoignageRepository $temoignageRepository)
    {
        $temoignages = $temoignageRepository->findAll();
        
        return $this->render('home/temoignage.html.twig', [
            'temoignages' => $temoignages
        ]);
    }

    /**
     * @Route("/marque", name="marque")
     */
    public function marque(ProductRepository $productRepository)
    {
        $produits = $productRepository->findAll();
        
        return $this->render('home/marque.html.twig', [
            'marques' => $produits
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

    public function generatePdf($template, $data, $params){
        $options = new Options();
        $dompdf = new Dompdf($options);
        $dompdf -> setPaper ($params['format']['value'], $params['format']['affichage']);
        $html = $this->renderView($template, ['data' => $data, 'date_debut'=>$params['date_debut'], 'date_fin'=>$params['date_fin'] ]);
        $dompdf->loadHtml($html);
        $dompdf->render();
        if($params['is_download']['value']){
            $output = $dompdf->output();
            file_put_contents($params['is_download']['save_path'], $output);
        }
        return $dompdf;
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


    /**
     * @Route("/demande-remboursement/{id}", name="demande_remboursement", methods={"GET"})
     */
    public function remboursement(Request $request, $id, \Swift_Mailer $mailer){
        $entityManager = $this->getDoctrine()->getManager();
        $panier = $this->panierRepository->find($id);
        $panier->setRemboursement(1);
        $entityManager->flush();

        $urlPanier = $this->generateUrl('panier_index', [], UrlGenerator::ABSOLUTE_URL);
        $url = $this->generateUrl('home', [], UrlGenerator::ABSOLUTE_URL);
        $content = "<p>l'utilisateur <b>".$this->getUser()->getEmail()."</b> vient de faire une demande de remboursement.<br> connectez-vous à la plateforme afin de valider cette demande.<br><a href='".$urlPanier."'>".$urlPanier."</a></p>";
        try {
            $mail = (new \Swift_Message("Demande de remboursement"))
                ->setFrom([$this->getUser()->getEmail()=>$this->getUser()->getName()])
                ->setTo("bahuguillaume@gmail.com")
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
        $this->addFlash('success', "Votre demande de remboursement a été envoyé");
        return $this->redirectToRoute('account');       
    }

    /**
     * @Route("/export-facture", name="export_facture")
     */
    public function exportFacture(Request $request){
        $entityManager = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $dateDebut = $request->request->get('date_debut');
        $dateFin = $request->request->get('date_fin');
        $paniers = $this->panierRepository->getPanierByDate($user->getId(), $dateDebut, $dateFin);
        
        $ouput_name = 'facture_du_'.$dateDebut.'_au_'.$dateFin.'.pdf';
        $params = [
            'format'=>['value'=>'A4', 'affichage'=>'portrait'],
            'is_download'=>['value'=>false, 'save_path'=>""],
            'date_debut'=>$dateDebut,
            'date_fin'=> $dateFin 
        ];
        $dompdf = $this->generatePdf('emails/commande_facture.html.twig', $paniers , $params);  
        return new Response ($dompdf->stream($ouput_name, array("Attachment" => false)));
    }
}
