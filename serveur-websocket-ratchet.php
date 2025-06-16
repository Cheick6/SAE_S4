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
    protected $model;
    protected $userConnections; // Pour associer les connexions aux utilisateurs

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->model = Model::getModel();
        $this->userConnections = [];
        echo "ChatServer initialisé avec connexion base de données\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        
        // Extraire l'ID utilisateur depuis les paramètres de la requête
        $queryString = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryString, $params);
        
        if (isset($params['user_id'])) {
            $userId = $params['user_id'];
            $this->userConnections[$conn->resourceId] = $userId;
            
            // Mettre à jour le statut en ligne
            $this->model->updateOnlineStatus($userId, true);
            echo "Utilisateur {$userId} connecté! (Connexion: {$conn->resourceId})\n";
        } else {
            echo "Nouvelle connexion anonyme! ({$conn->resourceId})\n";
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connexion %d envoie le message "%s" à %d autre(s) connexion(s)' . "\n",
            $from->resourceId, $msg, $numRecv);
        
        $messageData = json_decode($msg, true);
        
        if (!$messageData) {
            echo "Erreur: Message JSON invalide\n";
            return;
        }

        // Vérifier si l'utilisateur est connecté
        if (!isset($this->userConnections[$from->resourceId])) {
            echo "Erreur: Utilisateur non authentifié\n";
            $from->send(json_encode(['error' => 'Non authentifié']));
            return;
        }

        $senderId = $this->userConnections[$from->resourceId];

        // Traitement selon le type de message
        switch ($messageData['type']) {
            case 'sent':
                $this->handleSentMessage($from, $messageData, $senderId);
                break;
            case 'annotation':
                $this->handleAnnotation($from, $messageData, $senderId);
                break;
            case 'annotation-complete':
                // Juste relayer l'information de fin d'annotation
                $this->broadcastToOthers($from, $msg);
                break;
            default:
                $this->broadcastToOthers($from, $msg);
        }
    }

    private function handleSentMessage($from, $messageData, $senderId) {
        // Pour cette démo, on utilise une conversation par défaut (ID=1)
        // En production, vous devriez gérer les conversations appropriées
        $conversationId = 1;
        $receiverId = $this->getOtherUserInConversation($senderId, $conversationId);

        // Sauvegarder le message en base de données
        $messageToSave = [
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message' => $messageData['message']
        ];

        $saved = $this->model->addMessage($messageToSave);
        
        if ($saved) {
            echo "Message sauvegardé en base de données\n";
            
            // Préparer le message pour diffusion
            $messageData['type'] = 'received';
            $messageData['sender_id'] = $senderId;
            $messageData['message_id'] = $this->getLastInsertedMessageId();
            
            // Diffuser aux autres clients
            $this->broadcastToOthers($from, json_encode($messageData));
        } else {
            echo "Erreur lors de la sauvegarde du message\n";
            $from->send(json_encode(['error' => 'Erreur de sauvegarde']));
        }
    }

    private function handleAnnotation($from, $messageData, $annotatorId) {
        if (!isset($messageData['messageId']) || !isset($messageData['annotations'])) {
            echo "Données d'annotation manquantes\n";
            return;
        }

        // Convertir l'emoji en type d'émotion
        $emotion = $this->convertEmojiToEmotion($messageData['annotations']);
        
        if (!$emotion) {
            echo "Emoji non reconnu: " . $messageData['annotations'] . "\n";
            return;
        }

        // Extraire l'ID du message depuis l'ID du conteneur
        $messageId = $this->extractMessageIdFromContainer($messageData['messageId']);
        
        // Sauvegarder l'annotation
        $annotationToSave = [
            'message_id' => $messageId,
            'annotator_id' => $annotatorId,
            'emotion' => $emotion
        ];

        $saved = $this->model->addAnnotation($annotationToSave);
        
        if ($saved) {
            echo "Annotation sauvegardée: {$emotion} pour le message {$messageId}\n";
            
            // Diffuser l'annotation aux autres clients
            $this->broadcastToOthers($from, json_encode($messageData));
        } else {
            echo "Erreur lors de la sauvegarde de l'annotation\n";
        }
    }

    private function convertEmojiToEmotion($emoji) {
        $emojiMap = [
            '😊' => 'joie',
            '😡' => 'colere',
            '😞' => 'tristesse',
            '😖' => 'degout',
            '😵‍💫' => 'surprise',
            '😰' => 'peur'
        ];
        
        return isset($emojiMap[$emoji]) ? $emojiMap[$emoji] : null;
    }

    private function extractMessageIdFromContainer($containerId) {
        // Le conteneur a un ID comme "message-1672589123456"
        // On extrait le timestamp et on utilise ça comme ID temporaire
        // En production, vous devriez utiliser un vrai ID de message de la BD
        if (preg_match('/message-(\d+)/', $containerId, $matches)) {
            return $matches[1];
        }
        return 1; // Valeur par défaut
    }

    private function getLastInsertedMessageId() {
        // Cette méthode devrait retourner l'ID du dernier message inséré
        // Pour l'instant, on retourne 1 comme valeur par défaut
        // Vous devriez modifier Model.php pour retourner l'ID inséré
        return 1;
    }

    private function getOtherUserInConversation($senderId, $conversationId) {
        // Pour cette démo, on retourne un ID fixe
        // En production, vous devriez récupérer l'autre utilisateur de la conversation
        return $senderId == 1 ? 2 : 1;
    }

    private function broadcastToOthers($from, $message) {
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($message);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // Mettre à jour le statut hors ligne si l'utilisateur était connecté
        if (isset($this->userConnections[$conn->resourceId])) {
            $userId = $this->userConnections[$conn->resourceId];
            $this->model->updateOnlineStatus($userId, false);
            unset($this->userConnections[$conn->resourceId]);
            echo "Utilisateur {$userId} déconnecté (Connexion: {$conn->resourceId})\n";
        }
        
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

echo "Serveur WebSocket avec base de données démarré sur 0.0.0.0:8081\n";
echo "Accessible via votre IP locale sur le port 8081\n";
$server->run();
?>