<?php

namespace App\Controller;

use App\Entity\AbonnementSubscription;
use App\Form\AbonnementSubscriptionType;
use App\Repository\AbonnementSubscriptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/dashboard/abonnement-subscription")
 */
class AbonnementSubscriptionController extends AbstractController
{
    /**
     * @Route("/", name="abonnement_subscription_index", methods={"GET"})
     */
    public function index(AbonnementSubscriptionRepository $abonnementSubscriptionRepository): Response
    {
        return $this->render('abonnement_subscription/index.html.twig', [
            'abonnement_subscriptions' => $abonnementSubscriptionRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="abonnement_subscription_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $abonnementSubscription = new AbonnementSubscription();
        $form = $this->createForm(AbonnementSubscriptionType::class, $abonnementSubscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($abonnementSubscription);
            $entityManager->flush();

            return $this->redirectToRoute('abonnement_subscription_index');
        }

        return $this->render('abonnement_subscription/new.html.twig', [
            'abonnement_subscription' => $abonnementSubscription,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="abonnement_subscription_show", methods={"GET"})
     */
    public function show(AbonnementSubscription $abonnementSubscription): Response
    {
        return $this->render('abonnement_subscription/show.html.twig', [
            'abonnement_subscription' => $abonnementSubscription,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="abonnement_subscription_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, AbonnementSubscription $abonnementSubscription): Response
    {
        $form = $this->createForm(AbonnementSubscriptionType::class, $abonnementSubscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('abonnement_subscription_index');
        }

        return $this->render('abonnement_subscription/edit.html.twig', [
            'abonnement_subscription' => $abonnementSubscription,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="abonnement_subscription_delete", methods={"DELETE"})
     */
    public function delete(Request $request, AbonnementSubscription $abonnementSubscription): Response
    {
        if ($this->isCsrfTokenValid('delete'.$abonnementSubscription->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($abonnementSubscription);
            $entityManager->flush();
        }

        return $this->redirectToRoute('abonnement_subscription_index');
    }
}
