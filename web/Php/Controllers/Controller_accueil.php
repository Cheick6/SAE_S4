<?php
require_once "Models/Model.php"; // Inclusion du modèle
require_once "Controller.php";

class Controller_accueil extends Controller
{

    private $model ;

    /**
    * Constructeur
    */
    public function __construct()
    {
        $this->model =Model::getModel();  // Instancie le modèle
        parent::__construct();
        
    }

    /**
     * Action par défaut du contrôleur
     */
    public function action_default(){
        $this->accueil();
    }

    // Exemple dans AccueilController.php
    public function accueil() {
        $utilisateurs = $this->model->getAllUsers();
        $this->render('accueil', ['utilisateurs' => $utilisateurs]);
    }
    

}
