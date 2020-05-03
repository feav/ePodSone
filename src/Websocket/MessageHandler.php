<?php
namespace App\Websocket;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use App\Entity\Discussion;
use App\Entity\Message;
use App\Entity\User;
use SplObjectStorage;

class MessageHandler implements MessageComponentInterface
{

    protected $connections;
    private $users = [];
    private $defaultChannel = 'general';
    private $em;

    public function __construct($em)
    {
        $this->em = $em;
        $this->connections = new SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->connections->attach($conn);
        $this->users[$conn->resourceId] = [
            'connection' => $conn,
            'user' => '',
            'channels' => []
        ];
        echo sprintf('New connection: Hello #%d', $conn->resourceId);
    }

    public function onMessage(ConnectionInterface $from, $message)
    {   
        $messageData = json_decode($message);
        if ($messageData === null) {
            return false;
        }
        $user = $this->em->getRepository(User::class)->find($messageData->userId);
        $action = $messageData->action ?? 'unknown';
        $channel = $messageData->channel ?? $this->defaultChannel;
        $message = $messageData->message ?? '';
        $date = new \DateTime();

        switch ($action) {
            case 'subscribe':
                $this->subscribeToChannel($from, $channel, $user->getUsername());
                return $this->sendMessageToChannel($from, $channel, $user->getId(), $message, $date, $action);
            case 'unsubscribe':
                $this->unsubscribeFromChannel($from, $channel, $user->getUsername());
                return true;
            case 'message':
                $discussion = $this->em->getRepository(Discussion::class)->find($channel);
                $msg = New Message();
                $msg->setContenu($message);
                $msg->setDiscussion($discussion);
                $msg->setDateCreate($date);
                $msg->setDestinateur($user);
                $msg->setIsRead(false);
                
                $this->em->persist($msg);
                $this->em->flush();
                
                return $this->sendMessageToChannel($from, $channel, $user->getId(), $message, $date, $action);
            default:
                echo sprintf('L\'action "%s" n\'est pas supportée!', $action);
                break;
        }
        return false;
        //$this->broadCast($from, $message);
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->connections->detach($conn);
        // Suppression de la connexion des utilisateurs
        unset($this->users[$closedConnection->resourceId]);
        echo sprintf('Connection #%d has disconnected\n', $closedConnection->resourceId);
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        //$this->connections->detach($conn);
        $conn->send('An error has occurred: '.$e->getMessage());
        $conn->close();
    }


    private function subscribeToChannel(ConnectionInterface $conn, $channel, $user)
    {
        $this->users[$conn->resourceId]['channels'][$channel] = $channel;
        echo sprintf($user.' a rejoint la discussion qui a pour sujet : #'.$channel);
    }

    private function unsubscribeFromChannel(ConnectionInterface $conn, $channel, $user)
    {
        if (array_key_exists($channel, $this->users[$conn->resourceId]['channels'])) {
            unset($this->users[$conn->resourceId]['channels']);
        }
        echo sprintf($user.' a quitté la discussion qui a pour sujet : #'.$channel);
    }

    private function broadCast(ConnectionInterface $from, $msg){
        foreach($this->connections as $connection){
            if($connection === $from){
                continue;
            }
            $connection->send($msg);
        }
    }

    private function sendMessageToChannel(ConnectionInterface $from, $channel, $userId, $message, $date, $action)
    {
        /* si je ne suis pas dans ma propre discussion */
        if (!isset($this->users[$from->resourceId]['channels'][$channel])) {
            return false;
        }
        foreach ($this->users as $connectionId => $userConnection) {
            if (array_key_exists($channel, $userConnection['channels'])) {
                $userConnection['connection']->send(json_encode([
                    'action' => $action,
                    'channel' => $channel,
                    'userId' => $userId,
                    'message' => $message,
                    'date' => $date->format('Y-m-d H:i') ?? (new \DateTime())->format('Y-m-d H:i'),
                ]));
            }
        }
        return true;
    }
}
