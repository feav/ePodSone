<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Form\PanierType;
use App\Service\ProductService;
use App\Repository\PanierRepository;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Service\StripeService;

use Stripe\Stripe;
use \Stripe\Charge;

/**
 * @Route("/dashboard/panier")
 */
class PanierController extends AbstractController
{   
    private $stripe_s;
    private $panierRepository;

    public function __construct(StripeService $stripe_s, PanierRepository $panierRepository){
        $this->stripe_s = $stripe_s;
        $this->panierRepository = $panierRepository;
    }

    /**
     * @Route("/", name="panier_index", methods={"GET"})
     */
    public function index(PanierRepository $panierRepository): Response
    {
        return $this->render('panier/index.html.twig', [
            'paniers' => $panierRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="panier_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $panier = new Panier();
        $form = $this->createForm(PanierType::class, $panier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($panier);
            $entityManager->flush();

            return $this->redirectToRoute('panier_index');
        }

        return $this->render('panier/new.html.twig', [
            'panier' => $panier,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="panier_show", methods={"GET"})
     */
    public function show(Panier $panier): Response
    {
        return $this->render('panier/show.html.twig', [
            'panier' => $panier,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="panier_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Panier $panier): Response
    {
        $form = $this->createForm(PanierType::class, $panier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('panier_index');
        }

        return $this->render('panier/edit.html.twig', [
            'panier' => $panier,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="panier_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Panier $panier): Response
    {
        if ($this->isCsrfTokenValid('delete'.$panier->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($panier);
            $entityManager->flush();
        }

        return $this->redirectToRoute('panier_index');
    }

        /**
     * @Route("/confirm-remboursement/{id}", name="confirm_remboursement", methods={"GET"})
     */
    public function remboursement(Request $request, $id, \Swift_Mailer $mailer){
        $entityManager = $this->getDoctrine()->getManager();
        $panier = $this->panierRepository->find($id);
        $result = $this->stripe_s->refund($panier->getStripeChargeId());
        $panier->setRemboursement(2);
        $entityManager->flush();

        $url = $this->generateUrl('home', [], UrlGenerator::ABSOLUTE_URL);
        $content = "<p>Bonjour ".$panier->getUser()->getName().",<br> nous vous confirmons que votre demande de remboursement vient d'etre traitée</p>";
        try {
            $mail = (new \Swift_Message("Confirmation remboursement"))
                ->setFrom(["bahuguillaume@gmail.com" => 'EpodsOne'])
                ->setTo([$panier->getUser()->getEmail()=>$panier->getUser()->getEmail()])
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

        $this->addFlash('success', "Remboursement confirmé");
        return $this->redirectToRoute('panier_index');                
    }
}
