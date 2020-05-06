<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/dashboard/product")
 */
class ProductController extends AbstractController
{
    /**
     * @Route("/", name="product_index", methods={"GET"})
     */
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }
    private function saveImage($uploadedFile,$dir){
        $destination = $this->getParameter('kernel.project_dir').$dir;
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $name_file = $uploadedFile->getClientOriginalName();
        $sanitize_file = preg_replace('|[^-\\\\a-z0-9~+_.?\[\]^#=!&;,/:%@$\|*`\'<>"()\x80-\xff{}]|i', '', $name_file);
        $newFilename = uniqid().'_'.$sanitize_file;
        $uploadedFile->move(
            $destination,
            $destination.$newFilename
        );
        return $newFilename;
    }
    /**
     * @Route("/new", name="product_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $uploadedFile = $form['image']->getData();
            $newFilename = "avatar.png";
            if($uploadedFile)
                $newFilename = $this->saveImage($uploadedFile,'/public/assets/img/products/');

            $product->setImage($newFilename);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($product);
            $entityManager->flush();
            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="product_show", methods={"GET"})
     */
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="product_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Product $product): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $old = $product->getImage();
        // $new = $form['image']->getData();
        $image_send = false;
        if(isset($_FILES["product"]) && $_FILES["product"]["size"]["image"]){
            $image_send = true;
        }

        if (isset($_POST['product'])) {

            $entityManager = $this->getDoctrine()->getManager();

            if($image_send){
                $form->handleRequest($request);
                $uploadedFile = $form['image']->getData();
                $newFilename = $this->saveImage($uploadedFile,'/public/assets/img/products/');
                $product->setImage($newFilename);
            }
            $product->setPrice(floatval($_POST['product']['price']));
            $product->setName($_POST['product']['name']);
            $product->setDescription($_POST['product']['description']);
            $product->setType($_POST['product']['type']);
            $product->setQuantity($_POST['product']['quantity']);
            $product->setOldPrice(floatval($_POST['product']['old_price']));

            $entityManager->persist($product);
            $entityManager->flush();
            return $this->redirectToRoute('product_index');
        }
        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="product_delete", methods={"DELETE","GET"})
     */
    public function delete(Request $request, Product $product): Response
    {
        // if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($product);
            $entityManager->flush();
        // }

        return $this->redirectToRoute('product_index');
    }
}
