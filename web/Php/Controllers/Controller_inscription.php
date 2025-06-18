<?php
require_once "Models/Model.php"; // Inclusion du modèle
require_once "Controller.php";

class Controller_inscription extends Controller
{
    private $model;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->model = Model::getModel(); // Instancie le modèle
        parent::__construct();
    }

    /**
     * Action par défaut du contrôleur
     */
    public function action_default()
    {
        $this->render('inscription');
    }

    /**
     * Action pour l'inscription
     */
    public function action_inscription()
    {
        $message = "";

        // DEBUG: Afficher les données POST reçues
        error_log("Données POST reçues: " . print_r($_POST, true));

        // Vérification si le formulaire a été soumis
        if (isset($_POST['action']) && $_POST['action'] === 'inscription') {
            
            // Récupération des champs avec protection contre les injections
            $pseudo = isset($_POST['pseudo']) ? htmlspecialchars(trim($_POST['pseudo'])) : '';
            $genre = isset($_POST['genre']) ? htmlspecialchars($_POST['genre']) : '';
            $email = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email'])) : '';
            $password = isset($_POST['password']) ? htmlspecialchars($_POST['password']) : '';

            // DEBUG: Afficher les valeurs récupérées
            error_log("Valeurs récupérées - Pseudo: $pseudo, Genre: $genre, Email: $email");

            // Vérification si la case "Politique de confidentialité" a été cochée
            if (!isset($_POST['politique']) || $_POST['politique'] !== 'inscription') {
                $message = "Vous devez accepter la politique de confidentialité.";
                error_log("Erreur: Politique non acceptée");
                $this->render('inscription', ['message' => $message]);
                return;
            }

            // Vérification des champs
            if (empty($pseudo) || empty($genre) || empty($email) || empty($password)) {
                $message = "Tous les champs doivent être remplis.";
                error_log("Erreur: Champs manquants");
                $this->render('inscription', ['message' => $message]);
                return;
            }

            // Vérification si l'email est valide
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = "L'adresse email n'est pas valide.";
                error_log("Erreur: Email invalide - $email");
                $this->render('inscription', ['message' => $message]);
                return;
            }

            // Vérification si l'email existe déjà dans la base de données
            $existingUser = $this->model->userExists($email);
            if ($existingUser) {
                $message = "Un compte existe déjà avec cet email.";
                error_log("Erreur: Email déjà existant - $email");
                $this->render('inscription', ['message' => $message]);
                return;
            }

            // Hachage du mot de passe
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insertion de l'utilisateur dans la base de données
            $userData = [
                'pseudo' => $pseudo,
                'genre' => $genre,
                'email' => $email,
                'password_hash' => $password_hash
            ];

            // DEBUG: Afficher les données avant insertion
            error_log("Données à insérer: " . print_r($userData, true));

            // Ajout de l'utilisateur dans la base de données
            $result = $this->model->addUser($userData);

            if ($result) {
                error_log("Inscription réussie pour: $email");
                // Redirige vers la page de connexion après l'inscription
                $message = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                header("Location: index.php?controller=connexion&message=" . urlencode($message));
                exit;
            } else {
                $message = "Une erreur est survenue lors de l'inscription, veuillez réessayer.";
                error_log("Erreur: Échec de l'insertion en base de données");
                $this->render('inscription', ['message' => $message]);
                return;
            }
        } else {
            // Si le formulaire n'est pas soumis correctement
            error_log("Erreur: Formulaire non soumis correctement");
            $message = "Erreur de soumission du formulaire.";
        }
        
        // Afficher la page d'inscription avec le message
        $this->render('inscription', ['message' => $message]);
    }
}