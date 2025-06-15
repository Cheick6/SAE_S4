<?php
// Masquer les avertissements de dépréciation pour un affichage plus propre
error_reporting(E_ALL & ~E_DEPRECATED);

// D'abord charger l'autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Puis charger Model.php  
require_once __DIR__ . '/web/Php/Models/Model.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;

class ChatServer implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        echo "ChatServer initialisé\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Nouvelle connexion! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connexion %d envoie le message "%s" à %d autre(s) connexion(s)' . "\n",
            $from->resourceId, $msg, $numRecv);
        
        $messageData = json_decode($msg, true);
        
        if ($messageData && isset($messageData['type']) && $messageData['type'] === 'sent') {
            $messageData['type'] = 'received';
            
            // Diffuser aux autres clients
            foreach ($this->clients as $client) {
                if ($from !== $client) {
                    $client->send(json_encode($messageData));
                }
            }
        } else {
            // Diffuser le message tel quel
            foreach ($this->clients as $client) {
                if ($from !== $client) {
                    $client->send($msg);
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connexion {$conn->resourceId} fermée\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Erreur: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    8081,
    '0.0.0.0'  // Accepte les connexions de toutes les adresses
);

echo "Serveur WebSocket Ratchet démarré sur 0.0.0.0:8081\n";
echo "Accessible via votre IP locale sur le port 8081\n";
$server->run();
?>