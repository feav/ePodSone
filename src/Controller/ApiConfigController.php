<?php

namespace App\Controller;

use App\Entity\Config;
use App\Repository\ConfigRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiConfigController extends AbstractController
{
    private $configRepository;
    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }
    public function getScripts():Response
    {
        $entityManager = $this->getDoctrine()->getManager();

         $configs = (new Config())->getKeyName();
        $list_key_js = array();

        foreach ($configs as $key => $value) {
            if(   strpos($value['key'], "_JS_")){
                $list_key_js[] = $value['key'];
            }
        }
        $retour = "";
        foreach ($list_key_js as $key => $value) {
            $config = $this->configRepository->findOneByMkey($value);
            if($config){
                $retour .= "<script>".$config->getValue()."</script>";
            }
        }
        return new Response($retour);
    }

}
