<?php 

$titre = "Messagerie" ;

$rel1 = "stylesheet";
$href1= "../css/messagerie.css";

$rel2 = "";
$href2 = "";

require 'view_debut.php';

?>
    <div class="messagerie">
        <div class="barre">
            <div class="nom-groupe">
                <span class="sujet">Sujet : Apprendre à se connaître</span>
            </div>
        </div>

        <div class="boite">
            <div class="ctrle-message">
                <div id="messageDisplayAreaUser" class="message-display">
                    <!-- Les messages du user s'affichent ici -->
                </div>
            </div>

        </div>

        <div class="Ecriture">
            <input class="barre-message" type="text" id="messageInput" placeholder="Écrivez votre message" />
            <div id="emojiList">
                </div>

            <select id="emojiSelect">
                <option value=""></option>
                <option value="😊">😊​</option>
                <option value="😡​">😡​</option>
                <option value="😞">😞</option>
                <option value="😖">😖</option>
                <option value="😵‍💫">😵‍💫</option>
                <option value="😰">😰</option>
            </select>

            <button class="envoie" onclick="envoyerMessage()">Envoyer</button>
        </div>
    </div>
<?php 
$src1 = "../js/messagerie.js";
$src2 = "";
require 'view_fin.php';
?>