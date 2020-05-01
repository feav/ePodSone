<?php
namespace App\Service;

use App\Entity\Product;
use Symfony\Component\Config\Definition\Exception\Exception;
use Doctrine\ORM\EntityManagerInterface;

class ProductService{
    private $doctrine;
    
    public function __construct(EntityManagerInterface $doctrine){
        $this->doctrine = $doctrine;
    }
    public function findAll(){
        $products = $this->doctrine->getRepository(Product::class)->findAll();
        return $products;
    }
}
