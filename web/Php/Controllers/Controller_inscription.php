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

        // Vérification si le formulaire a été soumis
        if (isset($_POST['action'])&& $_POST['action'] === 'inscription') {
        // Récupération des champs avec protection contre les injections
            $pseudo = htmlspecialchars($_POST['pseudo']);
            $genre = htmlspecialchars($_POST['genre']);
            $email = htmlspecialchars($_POST['email']);
            $password = htmlspecialchars($_POST['password']);
            $password_hash = password_hash($password, PASSWORD_DEFAULT); // Hachage du mot de passe

            // Vérification si la case "Politique de confidentialité" a été cochée
            if (!isset($_POST['politique']) || $_POST['politique'] !== 'inscription') {
                $message = "Vous devez accepter la politique de confidentialité.";
                $this->render('inscription', ['message' => $message]);
                return;
            }

            // Vérification des champs
            if (empty($pseudo) || empty($genre) || empty($email) || empty($password)) {
                $message = "Tous les champs doivent être remplis.";
                $this->render('inscription', ['message' => $message]);
                return;
            }

            // Vérification si l'email est valide
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = "L'adresse email n'est pas valide.";
                $this->render('inscription', ['message' => $message]);
                return;
            }

            // Vérification si l'email existe déjà dans la base de données
            if ($this->model->userExists($email)) {
                $message = "Un compte existe déjà avec cet email.";
                $this->render('inscription', ['message' => $message]);
                return;
            }

            // Insertion de l'utilisateur dans la base de données
            $userData = [
                'pseudo' => $pseudo,
                'genre' => $genre,
                'email' => $email,
                'password_hash' => $password_hash
            ];

            // Ajout de l'utilisateur dans la base de données
            $result = $this->model->addUser($userData);

            if ($result) {
                // Redirige vers la page de connexion après l'inscription
                header("Location: index.php?controller=connexion");
                exit;
            } else {
                $message = "Une erreur est survenue, veuillez réessayer.";
                $this->render('inscription', ['message' => $message]);
                return;
            }
        }
        // Si le formulaire n'est pas soumis, afficher la page d'inscription
        $message="Le formulaire n'a pas été soumis. Veuillez réessayer";
        $this->render('inscription', ['message' => $message]);
    }
}