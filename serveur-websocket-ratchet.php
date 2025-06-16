<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\ConnectionInterface;
require_once '/opt/lampp/htdocs/SAE_S1-main/web/Php/Models/Model.php';

require 'vendor/autoload.php';

class ChatServer implements Ratchet\MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
         $this->clients->attach($conn);
         echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;
         echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');
        $messageData = json_decode($msg, true);
         var_dump($messageData);
        $model = Model::getModel();
        if ($messageData && isset($messageData['type']) && $messageData['type'] === 'sent') {
           $messageData['type'] = 'received';
           $model->addMessage($messageData);
            foreach ($this->clients as $client) {
                if ($from !== $client) {
                    $client->send(json_encode($messageData));
                }
            }
        }
         else if ($messageData && isset($messageData['type']) && $messageData['type'] === 'connect'){
            // Traitement de la connexion: on associe un utilisateur à une connexion
             $this->clients->attach($from, $messageData['userId']);
              echo sprintf('Connection %d associated with User "%s"' . "\n"
               , $from->resourceId,  $messageData['userId'] );
           }
         else if ($messageData && isset($messageData['type']) && $messageData['type'] === 'annotation') {
            // Traitement des annotations
              $model->addAnnotation($messageData);
           foreach ($this->clients as $client) {
               if ($from !== $client) {
                     $client->send($msg);
                }
             }
          }
       else {
           foreach ($this->clients as $client) {
                if ($from !== $client) {
                   $client->send($msg);
                }
          }
      }
    }

    public function onClose(ConnectionInterface $conn) {
        $userId = $this->clients->offsetGet($conn);
        $this->clients->detach($conn);
        $remoteAddress = $conn->remoteAddress;
        echo "Connection {$remoteAddress} of user {$userId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
         echo "An error has occurred: {$e->getMessage()}\n";
         $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
   8080
);

$server->run();
?>