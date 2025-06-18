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
    protected $messageIds; // Pour suivre les IDs des messages

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->model = Model::getModel();
        $this->userConnections = [];
        $this->messageIds = []; // Nouveau: pour tracker les IDs de messages
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
            $from->resourceId, substr($msg, 0, 50), $numRecv);
        
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
        $conversationId = 1;
        $receiverId = $this->getOtherUserInConversation($senderId, $conversationId);

        // Sauvegarder le message en base de données
        $messageToSave = [
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message' => $messageData['message']
        ];

        $messageId = $this->model->addMessage($messageToSave);
        
        if ($messageId) {
            echo "Message sauvegardé en base de données avec ID: {$messageId}\n";
            
            // CORRECTION: Stocker l'association entre l'ID client et l'ID BDD
            $clientMessageId = $messageData['message_id'];
            $this->messageIds[$clientMessageId] = $messageId;
            
            // NOUVEAU: Sauvegarder l'annotation de l'expéditeur
            if (isset($messageData['annotations']) && !empty($messageData['annotations'])) {
                $emotion = $this->convertEmojiToEmotion($messageData['annotations']);
                if ($emotion) {
                    $senderAnnotation = [
                        'message_id' => $messageId,
                        'annotator_id' => $senderId,
                        'emotion' => $emotion
                    ];
                    
                    $annotationSaved = $this->model->addAnnotation($senderAnnotation);
                    if ($annotationSaved) {
                        echo "✅ Annotation de l'expéditeur sauvegardée: {$emotion} pour le message {$messageId}\n";
                    } else {
                        echo "❌ Erreur lors de la sauvegarde de l'annotation de l'expéditeur\n";
                    }
                }
            }
            
            // Préparer le message pour diffusion avec le vrai ID de la BDD
            $messageForBroadcast = [
                'type' => 'received',
                'message' => $messageData['message'],
                'annotations' => $messageData['annotations'],
                'sender_id' => $senderId,
                'message_id' => $clientMessageId, // Garder l'ID client pour le frontend
                'db_message_id' => $messageId, // Ajouter l'ID de la BDD
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            // Diffuser aux autres clients
            $this->broadcastToOthers($from, json_encode($messageForBroadcast));
        } else {
            echo "Erreur lors de la sauvegarde du message\n";
            $from->send(json_encode(['error' => 'Erreur de sauvegarde']));
        }
    }

    private function handleAnnotation($from, $messageData, $annotatorId) {
        echo "Traitement annotation reçue: " . json_encode($messageData) . "\n";
        
        if (!isset($messageData['messageId']) || !isset($messageData['annotations'])) {
            echo "Données d'annotation manquantes\n";
            echo "messageId présent: " . (isset($messageData['messageId']) ? 'oui' : 'non') . "\n";
            echo "annotations présent: " . (isset($messageData['annotations']) ? 'oui' : 'non') . "\n";
            return;
        }

        // CORRECTION: Récupérer l'ID réel de la BDD
        $clientMessageId = $messageData['messageId'];
        $realMessageId = null;
        
        // Chercher l'ID réel dans notre mapping
        if (isset($this->messageIds[$clientMessageId])) {
            $realMessageId = $this->messageIds[$clientMessageId];
        } else {
            // Fallback: extraire le timestamp et essayer de trouver le message
            if (preg_match('/message-(\d+)/', $clientMessageId, $matches)) {
                $timestamp = $matches[1];
                // Ici vous pourriez chercher le message par timestamp approximatif
                // Pour l'instant, on utilise le dernier message ID comme fallback
                $realMessageId = $this->getLatestMessageId();
            }
        }
        
        if (!$realMessageId) {
            echo "Impossible de trouver l'ID réel du message pour: {$clientMessageId}\n";
            return;
        }

        // NOUVEAU: Vérifier si l'utilisateur a déjà annoté ce message
        if ($this->model->hasUserAnnotatedMessage($realMessageId, $annotatorId)) {
            echo "⚠️ L'utilisateur {$annotatorId} a déjà annoté le message {$realMessageId}\n";
            $from->send(json_encode([
                'error' => 'Vous avez déjà annoté ce message',
                'type' => 'annotation_error',
                'messageId' => $clientMessageId
            ]));
            return;
        }

        // Convertir l'emoji en type d'émotion
        $emotion = $this->convertEmojiToEmotion($messageData['annotations']);
        
        if (!$emotion) {
            echo "Emoji non reconnu: " . $messageData['annotations'] . "\n";
            return;
        }

        echo "Tentative d'enregistrement annotation: message_id={$realMessageId}, annotator_id={$annotatorId}, emotion={$emotion}\n";
        
        // Sauvegarder l'annotation
        $annotationToSave = [
            'message_id' => $realMessageId,
            'annotator_id' => $annotatorId,
            'emotion' => $emotion
        ];

        $saved = $this->model->addAnnotation($annotationToSave);
        
        if ($saved) {
            echo "✅ Annotation sauvegardée avec succès: {$emotion} pour le message {$realMessageId}\n";
            
            // Diffuser l'annotation aux autres clients
            $this->broadcastToOthers($from, json_encode($messageData));
        } else {
            echo "❌ Erreur lors de la sauvegarde de l'annotation\n";
            $from->send(json_encode([
                'error' => 'Erreur lors de la sauvegarde de l\'annotation',
                'type' => 'annotation_error',
                'messageId' => $clientMessageId
            ]));
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
        
        echo "Conversion emoji '{$emoji}' -> " . (isset($emojiMap[$emoji]) ? $emojiMap[$emoji] : 'non trouvé') . "\n";
        return isset($emojiMap[$emoji]) ? $emojiMap[$emoji] : null;
    }

    private function getLatestMessageId() {
        // NOUVEAU: Méthode pour récupérer le dernier ID de message
        // Vous devriez ajouter cette méthode dans Model.php
        try {
            // Requête directe pour récupérer le dernier message_id
            $pdo = $this->model->getBd(); // Vous devez ajouter cette méthode dans Model.php
            $stmt = $pdo->query('SELECT MAX(message_id) as max_id FROM Message');
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['max_id'] ?? 1;
        } catch (Exception $e) {
            echo "Erreur lors de la récupération du dernier message ID: " . $e->getMessage() . "\n";
            return 1;
        }
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