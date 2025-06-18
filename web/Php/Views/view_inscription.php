<?php 

$titre = "Inscription" ;

$rel1 = "stylesheet";
$href1= "../css/CSS_inscription.css";

$rel2 = "stylesheet";
$href2 = "../css/general.css";

require 'view_debut.php';

?>

 <h1>Ping Me !</h1>
 <h2>Vous n'êtes toujours pas inscrit ?</h2>
 <h3>Inscriver vous pour commencer !</h3>

    <div id="formulaire">

        <form method="POST" action="index.php?controller=inscription&action=inscription">

        <!-- Pseudo -->
        <div>
            <label for="pseudo"> <img src="../img/utilisateur.png" /> Pseudo: </label>
            <input type="text" id="pseudo" name="pseudo" class="champs" placeholder="Entrez votre pseudo " required>
            <small class="error-message">Veuillez entrer un pseudo valide (au moins 3 caractères).</small>
        </div>

        <!-- Genre -->
        <div>
            <label for="genre"> <img src="../img/les-genres.png" />Genre:</label>
            <select id="genre" name="genre" required>
                <option value="M">Homme</option>
                <option value="F">Femme</option>
            </select>
            <small class="error-message">Veuillez sélectionner un genre.</small>
        </div>

        <!-- Email -->
        <div>
            <label for="email"><img src="../img/enveloppe.png" /> Adresse mail:</label>                                   
            <input type="email" id="email" name="email" class="champs" placeholder="Entrez votre adresse email" pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}" required>
            <small class="error-message">Veuillez entrer une adresse e-mail valide.</small>
        </div>

        <!-- Mot de passe -->
        <div>
            <label for="password"> <img src="../img/cadenas.png" />Mot de passe:</label>
            <input type="password" id="password" name="password" class="champs" placeholder="Entrez votre mot de passe" required>
            <small class="error-message">Le mot de passe doit contenir au moins 6 caractères.</small>
        </div>

        <div>
            <label for="rappele">Se rappeler de moi</label>
            <input type="checkbox" id="rappele"  name="rappele">
        </div>
        
    
        <!-- Politique -->
        <div>
            <label for="politique">J'accepte la <a href="index.php?controller=politique">politique de confidentialité</a></label>
            <input type="checkbox" id="politique" name="politique" value="inscription">
        </div>
    
        <!-- Bouton Valider -->
        <div>
            <button type="submit" id="submit" name="action" value="inscription">Valider</button>
        </div>
    </form>

     <?php if (isset($message) && !empty($message)): ?>
        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif ?>
    </div>
 
     <p>Déja inscrit ? <a href="index.php?controller=connexion">Connectez vous</a> ! </p> 
 
<?php 
$src1 = "../js/submit.js";
$src2 = "";
require 'view_fin.php';

?>