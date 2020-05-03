<?php
// ./src/Controller/ListController


namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;
use App\Entity\User;
use App\Service\UserService;
class UserController extends AbstractController
{
    private $user_s;
    private $userRepository;
    
    public function __construct(UserRepository $userRepository, UserService $user_s){
        $this->userRepository = $userRepository;
        $this->user_s = $user_s;
    }

    public function filter(Request $request)
    {   
        if($request->query->get('key') == "")
            return new Response(json_encode([]),200);
        $users = $this->user_s->filterUser($request->query->get('key'));
        return new Response(json_encode($users), 200);
    }
}