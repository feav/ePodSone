<?php
namespace App\Service;

use App\Entity\Product;
use Symfony\Component\Config\Definition\Exception\Exception;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;

class UserService{
    private $doctrine;
    private $userRepository;
    
    public function __construct(UserRepository $userRepository, EntityManagerInterface $doctrine){
    	$this->userRepository = $userRepository;
        $this->doctrine = $doctrine;
    }
    public function filterUser($key){
        $users = $this->userRepository->filterUser($key);
        return $users;
    }
}
