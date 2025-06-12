<?php
require_once 'Utils/parametre.php';// contient les données de connexion à la base de données 
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
            $dsn = SQL_app.':host='. DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
            $this->bd = new PDO($dsn, DB_USER, DB_PASSWORD);
            $this->bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->bd->query("SET NAMES 'utf8'");
        }catch(PDOException $e){
            die("Erreur de connexion à la base de données: ". $e->getMessage());
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

    //verifie si l'utilisateur existe renvoie faux si 
   public function userExists($email)
    {
        // Préparation de la requête pour récupérer l'utilisateur et le mot de passe
        $query = $this->bd->prepare('SELECT user_id,username, password_hash FROM User WHERE email = :mail');
        $query->execute([
            ':mail' => $email // On peut passer directement $email sans htmlspecialchars car déjà verifié dans la méthode execute
        ]);
        // Récupérer l'utilisateur
        return $user= $query->fetch();
    }
    /**
     * Ajoute un utilsateur passé en paramètre dans la base de données.
     * @return [boolean] retourne true si la personne a été ajoutée dans la base de données, et false sinon
     */
    public function addUser($infos)
    {
        try{
        //Préparation de la requête
        $requete = $this->bd->prepare('
        INSERT INTO User (username, genre, email, password_hash)
        VALUES (:pseudo, :genre, :mail, :pswd)');

        //Remplacement des marqueurs de place par les valeurs
        $success = $requete->execute([
                    ':pseudo'=>$infos['pseudo'],
                    ':genre'=>$infos['genre'],
                    ':mail'=>$infos['email'],
                    ':pswd'=> $infos['password_hash'],
                ]);

        //Retourne true si l'insertion a réussi, sinon false
        return $success;
            }catch (PDOException $e) {
                // Gestion des erreurs
                error_log("Erreur lors de l'ajout de l'utilisateur : " . $e->getMessage());;
                return false; // Retourne false en cas d'échec
            }
    }

    public function checkUser($email, $password){
         $query = $this->bd->prepare('SELECT * FROM User WHERE email = :mail');
         $query->execute([':mail'=>$email]);

        $user = $query->fetch(PDO::FETCH_ASSOC); // Retourne l'utilisateur ou false
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user; // Si l'utilisateur existe et que le mot de passe correspond, on retourne l'utilisateur
       } else {
            return false; // Si l'utilisateur n'existe pas ou que le mot de passe ne correspond pas, on retourne false
       }
    }
    public function changePseudo($pseudo){
        if (isset($_COOKIE['user_id'])){
            $id = $_COOKIE['user_id'];
            $query = $this->bd->prepare('UPDATE User SET username = :pseudo WHERE user_id = :id');
            $query->execute([
                ':id' =>$id,
                ':pseudo' =>$pseudo,
            ]);
            return $query->rowCount() > 0;
        }
    return 'utilisateur introuvable reconnectez vous';
    }

    public function checkMdp($email,$mdp){
        $query = $this->bd->prepare('SELECT password_hash FROM User WHERE email= :email');
        $query->execute([
            ':email' =>$email
        ]);

        //Récupérer le mot de passe haché
        $user=$query->fetch(PDO::FETCH_ASSOC);

        if($user){
            // Comparaison du mot de passe entrée avec le haché
            if (password_verify($mdp,$user['password_hash'])){
                // Le mot de passe est correct
                return true;
            }else{
                // Le mot de passe est incorrect
                return false;
            }
        }else {
                // L'utilisateur n'a pas été trouvé (email incorrect)
                return false;
        }
    }
        public function changeMdp($actuel, $newmdp){
        $query = $this->bd->prepare('UPDATE User SET password_hash = :newmdp WHERE password_hash = :pswd');
        $query->execute([
            ':newmdp' =>$newmdp,
            ':pswd' =>$actuel,
        ]);
        return $query->rowCount() > 0;
    }
     public function deconnexion(){
        if (isset($_COOKIE['user_id'])){
            $userId = $_COOKIE['user_id'];
            $stmt = $this->bd->prepare("UPDATE User SET last_online_at = NOW() WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
            return $stmt->rowCount() > 0;
        }
        else{
            return 'Utilisateur non connecté.';
        }


    }
        public function addMessage($messageData)
        {
            try{
            //Préparation de la requête
             $requete = $this->bd->prepare('
            INSERT INTO Message (conversation_id, sender_id, receiver_id, content)
            VALUES (:conversation_id, :sender_id, :receiver_id, :content)');


            //Remplacement des marqueurs de place par les valeurs
             $success = $requete->execute([
                        ':conversation_id'=>$messageData['conversation_id'],
                        ':sender_id'=>$messageData['sender_id'],
                        ':receiver_id'=>$messageData['receiver_id'],
                        ':content'=> $messageData['message']
                ]);
            //Retourne true si l'insertion a réussi, sinon false
             return $success;
           }catch (PDOException $e) {
                // Gestion des erreurs
                 error_log("Erreur lors de l'ajout du message : " . $e->getMessage());
               return false; // Retourne false en cas d'échec
           }
         }
}
