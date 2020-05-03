<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Discussion;
use App\Repository\DiscussionRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;

class ChatController extends AbstractController
{
	private $discussionRepository;
    private $messageRepository;
    private $userRepository;
    
    public function __construct(DiscussionRepository $discussionRepository, MessageRepository $messageRepository, UserRepository $userRepository){
      $this->discussionRepository = $discussionRepository;
      $this->messageRepository = $messageRepository;
      $this->userRepository = $userRepository;
    }

    /**
     * @Route("/dashboard/chat/{id_discussion}", name="chat_home")
     */
    public function home($id_discussion = null)
    {
      $discussions = $this->discussionRepository->findAll();
    	if(is_null($id_discussion)){
    		return $this->render('admin/chat/home.html.twig', [
    			'user'=>$this->getUser(),
          'discussions'=>$discussions,
    		]);
    	}
      $discussion = $this->discussionRepository->find($id_discussion);        
      dd($discussions);
      return $this->render('admin/chat/home.html.twig', [
		    'ws_url' => 'localhost:3001',
      	'discussion'=>$discussion,
      	'discussions'=>$discussions,
		    'user'=>$this->getUser(),
  		]);
    }

    /**
     * @Route("/dashboard/discussion/{id_user}", name="discussion")
     */
    public function discussion($id_user)
    {   
    	$destinataire = $this->userRepository->find($id_user);
    	$discussions = $destinataire->getDiscussions();
        $entityManager = $this->getDoctrine()->getManager();
        if(count($discussions))
        	$discussionId = $discussions[0]->getId();
        else{
          $discussion = new Discussion();
          $discussion->addUser($this->getUser());
          $discussion->addUser($destinataire);
          $entityManager->persist($discussion);
          $entityManager->flush();
          $discussionId = $discussion->getId();
        } 
        return $this->redirectToRoute('chat_home',['id_discussion'=>$discussionId]);
    }
}
