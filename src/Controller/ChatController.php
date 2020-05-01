<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    public function home()
    {
        return $this->render('admin/chat/home.html.twig', []);
    }
}
