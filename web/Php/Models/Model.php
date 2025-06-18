<?php

class Model {
    /**
     * Attribut contenant l'instance PDO
     */
    private $bd;

    /**
     * Attribut statique qui contiendra l'unique instance de Model
     */
    private static $instance = null;

    /**
     * Constructeur : effectue la connexion à la base de données.
     */
    public function __construct()
    {
        try{
            // Configuration pour MAMP
            $host = '127.0.0.1';
            $dbname = 'appli';
            $username = 'root';
            $password = 'root';   // Mot de passe par défaut MAMP
            $port = 8889;         // Port MySQL MAMP
            
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
            
            $this->bd = new PDO($dsn, $username, $password);
            $this->bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->bd->query("SET NAMES 'utf8'");
            
        } catch(PDOException $e) {
            die("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }

    /**
     * NOUVEAU: Méthode pour accéder à la connexion PDO
     */
    public function getBd()
    {
        return $this->bd;
    }

    /**
     * Méthode permettant de récupérer un modèle car le constructeur est privé (Implémentation du Design Pattern Singleton)
     */
    public static function getModel()
    {
        if (self::$instance === null) {
            self::$instance = new Model();
        }
        return self::$instance;
    }

    /**
     * Vérifie si l'utilisateur existe et renvoie ses informations
     * @param string $email Email de l'utilisateur
     * @return array|false Données utilisateur ou false si inexistant
     */
    public function userExists($email)
    {
        try {
            // Utiliser le bon nom de table : User
            $query = $this->bd->prepare('SELECT user_id, username, password_hash FROM `User` WHERE email = :mail');
            $query->execute([':mail' => $email]);
            
            return $query->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur userExists: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ajoute un utilisateur passé en paramètre dans la base de données.
     * @param array $infos Informations de l'utilisateur
     * @return bool retourne true si la personne a été ajoutée dans la base de données, et false sinon
     */
    public function addUser($infos)
    {
        try {
            // DEBUG: Afficher les données reçues
            error_log("addUser - Données reçues: " . print_r($infos, true));
            
            // Utiliser le bon nom de table : User
            $requete = $this->bd->prepare('
            INSERT INTO `User` (username, genre, email, password_hash, is_online, created_at)
            VALUES (:pseudo, :genre, :mail, :pswd, FALSE, NOW())');

            $params = [
                ':pseudo' => $infos['pseudo'],
                ':genre' => $infos['genre'],
                ':mail' => $infos['email'],
                ':pswd' => $infos['password_hash'],
            ];
            
            error_log("addUser - Paramètres SQL: " . print_r($params, true));

            $success = $requete->execute($params);

            if ($success) {
                $newUserId = $this->bd->lastInsertId();
                error_log("addUser - Utilisateur ajouté avec succès, ID: $newUserId");
                return true;
            } else {
                error_log("addUser - Échec de l'exécution de la requête");
                return false;
            }
            
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout de l'utilisateur : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie les identifiants de connexion d'un utilisateur
     * @param string $email Email de l'utilisateur
     * @param string $password Mot de passe en clair
     * @return array|false Données utilisateur si connexion réussie, false sinon
     */
    public function checkUser($email, $password)
    {
        try {
            $query = $this->bd->prepare('SELECT * FROM `User` WHERE email = :mail');
            $query->execute([':mail' => $email]);

            $user = $query->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                return $user;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            error_log("Erreur checkUser: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Change le pseudo d'un utilisateur connecté
     * @param string $pseudo Nouveau pseudo
     * @return bool|string True si succès, message d'erreur sinon
     */
    public function changePseudo($pseudo)
    {
        try {
            if (isset($_COOKIE['user_id'])) {
                $id = $_COOKIE['user_id'];
                $query = $this->bd->prepare('UPDATE `User` SET username = :pseudo WHERE user_id = :id');
                $query->execute([
                    ':id' => $id,
                    ':pseudo' => $pseudo,
                ]);
                return $query->rowCount() > 0;
            }
            return 'utilisateur introuvable reconnectez vous';
        } catch (PDOException $e) {
            error_log("Erreur changePseudo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si un mot de passe correspond à celui en base
     * @param string $email Email de l'utilisateur
     * @param string $mdp Mot de passe à vérifier
     * @return bool True si le mot de passe est correct, false sinon
     */
    public function checkMdp($email, $mdp)
    {
        try {
            $query = $this->bd->prepare('SELECT password_hash FROM `User` WHERE email = :email');
            $query->execute([':email' => $email]);

            $user = $query->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if (password_verify($mdp, $user['password_hash'])) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (PDOException $e) {
            error_log("Erreur checkMdp: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Change le mot de passe d'un utilisateur
     * @param string $actuel Mot de passe actuel (haché)
     * @param string $newmdp Nouveau mot de passe (à hacher)
     * @return bool True si le changement a réussi, false sinon
     */
    public function changeMdp($actuel, $newmdp)
    {
        try {
            if (isset($_COOKIE['user_id'])) {
                $userId = $_COOKIE['user_id'];
                
                $newPasswordHash = password_hash($newmdp, PASSWORD_DEFAULT);
                
                $query = $this->bd->prepare('UPDATE `User` SET password_hash = :newmdp WHERE user_id = :user_id');
                $query->execute([
                    ':newmdp' => $newPasswordHash,
                    ':user_id' => $userId,
                ]);
                return $query->rowCount() > 0;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur changeMdp: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enregistre l'heure de déconnexion d'un utilisateur
     * @return bool|string True si succès, message d'erreur sinon
     */
    public function deconnexion()
    {
        try {
            if (isset($_COOKIE['user_id'])) {
                $userId = $_COOKIE['user_id'];
                $stmt = $this->bd->prepare("UPDATE `User` SET is_online = FALSE WHERE user_id = :user_id");
                $stmt->execute(['user_id' => $userId]);
                return $stmt->rowCount() > 0;
            } else {
                return 'Utilisateur non connecté.';
            }
        } catch (PDOException $e) {
            error_log("Erreur deconnexion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ajoute un message dans la base de données
     * @param array $messageData Données du message
     * @return int|false ID du message inséré ou false en cas d'erreur
     */
    public function addMessage($messageData)
    {
        try {
            $conversationId = $this->getOrCreateConversation($messageData['sender_id'], $messageData['receiver_id']);
            
            $requete = $this->bd->prepare('
            INSERT INTO Message (conversation_id, sender_id, receiver_id, content, created_at)
            VALUES (:conversation_id, :sender_id, :receiver_id, :content, NOW())');

            $success = $requete->execute([
                ':conversation_id' => $conversationId,
                ':sender_id' => $messageData['sender_id'],
                ':receiver_id' => $messageData['receiver_id'],
                ':content' => $messageData['message']
            ]);
            
            if ($success) {
                $messageId = $this->bd->lastInsertId();
                error_log("Message ajouté avec ID: " . $messageId);
                return $messageId; // CORRECTION: Retourner l'ID au lieu de true
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout du message : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère ou crée une conversation entre deux utilisateurs
     * @param int $user1Id ID du premier utilisateur
     * @param int $user2Id ID du second utilisateur
     * @return int ID de la conversation
     */
    private function getOrCreateConversation($user1Id, $user2Id)
    {
        try {
            $query = $this->bd->prepare('
                SELECT conversation_id FROM Conversation 
                WHERE (user_1_id = :user1 AND user_2_id = :user2) 
                   OR (user_1_id = :user2 AND user_2_id = :user1)
            ');
            $query->execute([
                ':user1' => $user1Id,
                ':user2' => $user2Id
            ]);
            
            $conversation = $query->fetch(PDO::FETCH_ASSOC);
            
            if ($conversation) {
                return $conversation['conversation_id'];
            }
            
            $insertQuery = $this->bd->prepare('
                INSERT INTO Conversation (user_1_id, user_2_id, created_at)
                VALUES (:user1, :user2, NOW())
            ');
            $insertQuery->execute([
                ':user1' => $user1Id,
                ':user2' => $user2Id
            ]);
            
            return $this->bd->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Erreur getOrCreateConversation: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Ajoute une annotation dans la base de données
     * @param array $annotationData Données de l'annotation
     * @return bool True si succès, false sinon
     */
    public function addAnnotation($annotationData)
    {
        try {
            // CORRECTION: Ajouter du logging pour debug
            error_log("Tentative d'ajout annotation: " . print_r($annotationData, true));
            
            $requete = $this->bd->prepare('
            INSERT INTO Annotation (message_id, annotator_id, emotion, created_at)
            VALUES (:message_id, :annotator_id, :emotion, NOW())');

            $success = $requete->execute([
                ':message_id' => $annotationData['message_id'],
                ':annotator_id' => $annotationData['annotator_id'],
                ':emotion' => $annotationData['emotion']
            ]);
            
            if ($success) {
                error_log("Annotation ajoutée avec succès");
            } else {
                error_log("Échec de l'ajout de l'annotation");
                error_log("Erreur PDO: " . print_r($requete->errorInfo(), true));
            }
            
            return $success;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout de l'annotation : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si un utilisateur a déjà annoté un message
     * @param int $messageId ID du message
     * @param int $annotatorId ID de l'utilisateur
     * @return bool True si l'utilisateur a déjà annoté, false sinon
     */
    public function hasUserAnnotatedMessage($messageId, $annotatorId)
    {
        try {
            $query = $this->bd->prepare('
                SELECT COUNT(*) as count FROM Annotation 
                WHERE message_id = :message_id AND annotator_id = :annotator_id
            ');
            $query->execute([
                ':message_id' => $messageId,
                ':annotator_id' => $annotatorId
            ]);
            
            $result = $query->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Erreur hasUserAnnotatedMessage: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour le statut en ligne d'un utilisateur
     * @param int $userId ID de l'utilisateur
     * @param bool $isOnline Statut en ligne
     * @return bool True si succès, false sinon
     */
    public function updateOnlineStatus($userId, $isOnline)
    {
        try {
            $stmt = $this->bd->prepare("UPDATE `User` SET is_online = :is_online WHERE user_id = :user_id");
            $stmt->execute([
                'is_online' => $isOnline ? 1 : 0,
                'user_id' => $userId
            ]);
            return $stmt->rowCount() >= 0;
        } catch (PDOException $e) {
            error_log("Erreur updateOnlineStatus: " . $e->getMessage());
            return false;
        }
    }
}