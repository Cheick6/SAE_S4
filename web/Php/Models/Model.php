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
            // Préparation de la requête pour récupérer l'utilisateur et le mot de passe
            $query = $this->bd->prepare('SELECT user_id, username, password_hash FROM Usera WHERE email = :mail');
            $query->execute([':mail' => $email]);
            
            // Récupérer l'utilisateur
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
            //Préparation de la requête
            $requete = $this->bd->prepare('
            INSERT INTO Usera (username, nom, prenom, genre, email, password_hash)
            VALUES (:pseudo, :nom, :prenom, :genre, :mail, :pswd)');

            //Remplacement des marqueurs de place par les valeurs
            $success = $requete->execute([
                ':pseudo' => $infos['pseudo'],
                ':nom' => $infos['nom'],
                ':prenom' => $infos['prenom'],
                ':genre' => $infos['genre'],
                ':mail' => $infos['email'],
                ':pswd' => $infos['password_hash'],
            ]);

            //Retourne true si l'insertion a réussi, sinon false
            return $success;
        } catch (PDOException $e) {
            // Gestion des erreurs
            error_log("Erreur lors de l'ajout de l'utilisateur : " . $e->getMessage());
            return false; // Retourne false en cas d'échec
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
            $query = $this->bd->prepare('SELECT * FROM Usera WHERE email = :mail');
            $query->execute([':mail' => $email]);

            $user = $query->fetch(PDO::FETCH_ASSOC); // Retourne l'utilisateur ou false
            
            if ($user && password_verify($password, $user['password_hash'])) {
                return $user; // Si l'utilisateur existe et que le mot de passe correspond, on retourne l'utilisateur
            } else {
                return false; // Si l'utilisateur n'existe pas ou que le mot de passe ne correspond pas, on retourne false
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
                $query = $this->bd->prepare('UPDATE Usera SET username = :pseudo WHERE user_id = :id');
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
            $query = $this->bd->prepare('SELECT password_hash FROM Usera WHERE email = :email');
            $query->execute([':email' => $email]);

            //Récupérer le mot de passe haché
            $user = $query->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Comparaison du mot de passe entré avec le haché
                if (password_verify($mdp, $user['password_hash'])) {
                    // Le mot de passe est correct
                    return true;
                } else {
                    // Le mot de passe est incorrect
                    return false;
                }
            } else {
                // L'utilisateur n'a pas été trouvé (email incorrect)
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
            // Il faut d'abord récupérer l'utilisateur par son ID (cookie)
            if (isset($_COOKIE['user_id'])) {
                $userId = $_COOKIE['user_id'];
                
                // Hacher le nouveau mot de passe
                $newPasswordHash = password_hash($newmdp, PASSWORD_DEFAULT);
                
                $query = $this->bd->prepare('UPDATE Usera SET password_hash = :newmdp WHERE user_id = :user_id');
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
                $stmt = $this->bd->prepare("UPDATE Usera SET last_online_at = NOW(), is_online = FALSE WHERE user_id = :user_id");
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
     * @return bool True si succès, false sinon
     */
    public function addMessage($messageData)
    {
        try {
            //Préparation de la requête
            $requete = $this->bd->prepare('
            INSERT INTO Message (conversation_id, sender_id, receiver_id, content)
            VALUES (:conversation_id, :sender_id, :receiver_id, :content)');

            //Remplacement des marqueurs de place par les valeurs
            $success = $requete->execute([
                ':conversation_id' => $messageData['conversation_id'],
                ':sender_id' => $messageData['sender_id'],
                ':receiver_id' => $messageData['receiver_id'],
                ':content' => $messageData['message']
            ]);
            
            //Retourne true si l'insertion a réussi, sinon false
            return $success;
        } catch (PDOException $e) {
            // Gestion des erreurs
            error_log("Erreur lors de l'ajout du message : " . $e->getMessage());
            return false; // Retourne false en cas d'échec
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
            $requete = $this->bd->prepare('
            INSERT INTO Annotation (message_id, annotator_id, emotion)
            VALUES (:message_id, :annotator_id, :emotion)');

            $success = $requete->execute([
                ':message_id' => $annotationData['message_id'],
                ':annotator_id' => $annotationData['annotator_id'],
                ':emotion' => $annotationData['emotion']
            ]);
            
            return $success;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout de l'annotation : " . $e->getMessage());
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
            $stmt = $this->bd->prepare("UPDATE Usera SET is_online = :is_online WHERE user_id = :user_id");
            $stmt->execute([
                'is_online' => $isOnline ? 1 : 0,
                'user_id' => $userId
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Erreur updateOnlineStatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les messages d'une conversation
     * @param int $conversationId ID de la conversation
     * @return array|false Messages de la conversation ou false
     */
    public function getMessages($conversationId)
    {
        try {
            $query = $this->bd->prepare('
                SELECT m.*, u.username as sender_name 
                FROM Message m 
                JOIN Usera u ON m.sender_id = u.user_id 
                WHERE m.conversation_id = :conversation_id 
                ORDER BY m.created_at ASC
            ');
            $query->execute([':conversation_id' => $conversationId]);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur getMessages: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère toutes les conversations d'un utilisateur
     * @param int $userId ID de l'utilisateur
     * @return array|false Conversations ou false
     */
    public function getUserConversations($userId)
    {
        try {
            $query = $this->bd->prepare('
                SELECT c.*, 
                       u1.username as user1_name, 
                       u2.username as user2_name
                FROM Conversation c
                JOIN Usera u1 ON c.user_1_id = u1.user_id
                JOIN Usera u2 ON c.user_2_id = u2.user_id
                WHERE c.user_1_id = :user_id OR c.user_2_id = :user_id
                ORDER BY c.created_at DESC
            ');
            $query->execute([':user_id' => $userId]);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur getUserConversations: " . $e->getMessage());
            return false;
        }
    }
}