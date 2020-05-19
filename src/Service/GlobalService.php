<?php
namespace App\Service;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Templating\EngineInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Repository\AbonnementRepository;
use App\Entity\Abonnement;

use Dompdf\Options;
use Dompdf\Dompdf;

class GlobalService{

    private $requestStack;
    private $public_path;
    private $templating;
    private $abonnementRepository;
    
    public function __construct(EntityManagerInterface $em, RequestStack $requestStack, EngineInterface $templating, AbonnementRepository $abonnementRepository){
        $this->em = $em;
        $this->request = $requestStack->getCurrentRequest();
        $this->public_path = $this->request->server->get('DOCUMENT_ROOT');
        $this->templating = $templating;
        $this->abonnementRepository = $abonnementRepository;
    }

    public function buildFiles($files, $tabExtension, $maxSize, $directorySave, $save_originalName){
        $filesArray = array();
        foreach ($files as $key => $value) {
            if( ($value instanceof UploadedFile) && ($value->getError()=="0")){
                if($value->getSize() < $maxSize){
                    $originalName=$value->getClientOriginalName();
                    $name_array = explode('.',$originalName);
                    $file_type=$name_array[sizeof($name_array)-1];
                    $nameWithoutExt = str_replace(".".$file_type, "", $originalName);
                    $valid_filetypes=  $tabExtension;
                    
                    if(in_array(strtolower($file_type),$valid_filetypes)){
                        if($save_originalName)
                            $name = $originalName;
                        else
                            $name=$nameWithoutExt.'-'.Date("Yds").'.'.$file_type;
                        $value->move($directorySave, $name);
                        $filesArray[] = $name;
                    }else{
                        print_r("Entrez votre image avec une extension valide");
                    }
                }else{
                    print_r("Fichier trop lourd".$value->getSize());
                }
            }else{
                print_r("Erreur de chargement du fichier");
            }            
        }
        return $filesArray;
    }

    public function generatePdf($template, $data, $params){

        $options = new Options();
        $dompdf = new Dompdf($options);
        $dompdf -> setPaper ($params['format']['value'], $params['format']['affichage']);
        $html = $this->templating->render($template, ['data' => $data]);
        $dompdf->loadHtml($html);
        $dompdf->render();
        if($params['is_download']['value']){
            $output = $dompdf->output();
            file_put_contents($params['is_download']['save_path'], $output);
        }
        
        return $dompdf;
    }

    public function isAbonnementValide($user_id){
        $abonnement = $this->abonnementRepository->findOneBy(['user_id'=>$user_id], ['id'=>'DESC'], 1);
        if(is_null($abonnement) || !$abonnement->getActive() || ($abonnement->getEnd() > new \DateTime()) ){
            return false;
        }
        return true;
    }
}
