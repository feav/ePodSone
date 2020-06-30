<?php

namespace App\Controller;

use App\Entity\Formule;
use App\Form\FormuleType;
use App\Repository\FormuleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\StripeService;

/**
 * @Route("/dashboard/formule")
 */
class FormuleController extends AbstractController
{
    private $stripe_s;

    public function __construct(StripeService $stripe_s){
        $this->stripe_s = $stripe_s;
    }

    /**
     * @Route("/", name="formule_index", methods={"GET"})
     */
    public function index(FormuleRepository $formuleRepository): Response
    {
        return $this->render('formule/index.html.twig', [
            'formules' => $formuleRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="formule_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $formule = new Formule();
        $stripeProducts =  $this->stripe_s->getAllProduct();
        if(!count($stripeProducts)){
            $stripeProducts = [$this->stripe_s->createProduct()];
        }

        $form = $this->createForm(FormuleType::class, $formule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formule->setStripeProductId($request->request->get('stripe_product_id'));
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($formule);
            $entityManager->flush();

            return $this->redirectToRoute('formule_index');
        }

        return $this->render('formule/new.html.twig', [
            'formule' => $formule,
            'form' => $form->createView(),
            'stripeProducts'=>$stripeProducts
        ]);
    }

    /**
     * @Route("/{id}", name="formule_show", methods={"GET"})
     */
    public function show(Formule $formule): Response
    {
        return $this->render('formule/show.html.twig', [
            'formule' => $formule,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="formule_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Formule $formule): Response
    {
        $stripeProducts =  $this->stripe_s->getAllProduct();
        if(!count($stripeProducts)){
            $stripeProducts = [$this->stripe_s->createProduct()];
        }

        $form = $this->createForm(FormuleType::class, $formule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formule->setStripeProductId($request->request->get('stripe_product_id'));
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('formule_index');
        }

        return $this->render('formule/edit.html.twig', [
            'formule' => $formule,
            'form' => $form->createView(),
            'stripeProducts'=>$stripeProducts
        ]);
    }

    /**
     * @Route("/{id}", name="formule_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Formule $formule): Response
    {
        if ($this->isCsrfTokenValid('delete'.$formule->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($formule);
            $entityManager->flush();
        }

        return $this->redirectToRoute('formule_index');
    }
}
