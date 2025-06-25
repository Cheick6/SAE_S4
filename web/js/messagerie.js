// Récupérer l'ID utilisateur depuis les cookies
function getUserIdFromCookie() {
    const name = "user_id=";
    const decodedCookie = decodeURIComponent(document.cookie);
    const ca = decodedCookie.split(';');
    for(let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return null;
}

// Obtenir l'ID utilisateur
const userId = getUserIdFromCookie();
if (!userId) {
    alert("Vous devez être connecté pour utiliser la messagerie.");
    window.location.href = "index.php?controller=connexion";
}

// Détecter automatiquement l'adresse du serveur
const wsHost = window.location.hostname;
const socket = new WebSocket(`ws://${wsHost}:8081?user_id=${userId}`);

// Variable pour gérer le droit d'envoyer un message
let canSendMessage = true; // Initialisé à true pour permettre l'envoi du premier message
// Ajout du code de statut de la connexion
const statusMessage = document.getElementById('status-message');

socket.onopen = function(event) {
    console.log("Connexion WebSocket établie avec le serveur !");
    if (statusMessage) {
        statusMessage.textContent = 'Connecté';
        statusMessage.style.color = 'green';
    }
    
    // Envoyer une notification de connexion au serveur
    socket.send(JSON.stringify({
        type: 'user_connected',
        user_id: userId
    }));
};

socket.onerror = function (error) {
    console.error("Erreur WebSocket :", error);
    if (statusMessage) {
        statusMessage.textContent = 'Erreur de connexion';
        statusMessage.style.color = 'red';
    }
};

socket.onclose = function(event) {
    console.log('Connexion WebSocket fermée');
    if (statusMessage) {
        statusMessage.textContent = 'Déconnecté';
        statusMessage.style.color = 'orange';
    }
};

let annotationEnAttente = false;
let messageCounter = 0;
let currentMessageIdForAnnotation = null; // NOUVEAU: pour tracker le message à annoter

function scrollToBottom() {
    const messageDisplayAreaUser = document.getElementById('messageDisplayAreaUser');
    if (messageDisplayAreaUser) {
        messageDisplayAreaUser.scrollTop = messageDisplayAreaUser.scrollHeight;
    }
}

function ajouterEmoji(emoji) {
    const emojiList = document.getElementById('emojiList');
    const emojiSpan = document.createElement('span');
    emojiSpan.textContent = emoji;
    emojiSpan.style.cursor = 'pointer';
    emojiSpan.onclick = function () {
        emojiList.removeChild(emojiSpan);
    };
    emojiList.appendChild(emojiSpan);
}

function envoyerMessage() {

    if (!canSendMessage) {
        alert("Veuillez attendre la réponse de l'autre personne avant d'envoyer un nouveau message.");
        return;
    }

    const messageInput = document.getElementById('messageInput');
    const emojiList = document.getElementById('emojiList');
    const messageText = messageInput.value.trim();
    
    // Récupérer les émojis sélectionnés
    let emojis = '';
    const emojiSpans = emojiList.getElementsByTagName('span');
    for (let i = 0; i < emojiSpans.length; i++) {
        emojis += emojiSpans[i].textContent;
    }

    // Vérifications
    if (annotationEnAttente) {
        alert("Veuillez annoter le message reçu avant d'envoyer un nouveau message.");
        return;
    }

    if (messageText === "") {
        alert("Veuillez écrire un message.");
        return;
    }

    if (emojis === "") {
        alert("Veuillez sélectionner un emoji avant d'envoyer votre message.");
        return;
    }

    // Vérifier que la connexion WebSocket est ouverte
    if (socket.readyState !== WebSocket.OPEN) {
        alert("Connexion WebSocket fermée. Veuillez recharger la page.");
        return;
    }

    // Créer un ID unique pour le message
    messageCounter++;
    const messageId = `message-${Date.now()}-${messageCounter}`;

    // Envoyer le message avec toutes les informations nécessaires
    const messageData = {
        type: 'sent',
        message: messageText,
        annotations: emojis,
        user_id: userId,
        message_id: messageId,
        timestamp: new Date().toISOString()
    };

    console.log("Envoi du message:", messageData);
    socket.send(JSON.stringify(messageData));
    afficherMessage(messageData, true);

    canSendMessage = false; // L'utilisateur a envoyé un message, il doit attendre une réponse 

    // Nettoyer les champs
    messageInput.value = '';
    emojiList.innerHTML = '';
}

// Gestionnaire pour la sélection d'emoji
document.getElementById('emojiSelect').addEventListener('change', function (event) {
    const selectedEmoji = this.value;
    const emojiList = document.getElementById('emojiList');
    
    if (selectedEmoji) {
        // Remplacer l'emoji actuel au lieu d'ajouter
        emojiList.innerHTML = '';
        const emojiSpan = document.createElement('span');
        emojiSpan.textContent = selectedEmoji;
        emojiSpan.style.cursor = 'pointer';
        emojiSpan.onclick = function () {
            emojiList.removeChild(emojiSpan);
        };
        emojiList.appendChild(emojiSpan);
        this.value = ''; // Reset du select
    }
});

// Gestionnaire pour la touche Entrée
document.getElementById('messageInput').addEventListener('keypress', function (event) {
    if (event.key === 'Enter') {
        envoyerMessage();
    }
});

// Gestion des messages reçus
socket.onmessage = function (event) {
    try {
        const data = JSON.parse(event.data);
        console.log("Message reçu du serveur:", data);
        
        if (data.error) {
            // NOUVEAU: Gestion des erreurs d'annotation
            if (data.type === 'annotation_error') {
                alert('Erreur: ' + data.error);
                // Marquer le message comme déjà annoté pour éviter les tentatives répétées
                const messageContainer = document.getElementById(data.messageId);
                if (messageContainer) {
                    markMessageAsAnnotated(messageContainer);
                }
            } else {
                alert('Erreur: ' + data.error);
            }
            return;
        }

        if (data.type === 'received') {
            afficherMessage(data, false);
            annotationEnAttente = true;
            currentMessageIdForAnnotation = data.message_id; // CORRECTION: stocker l'ID du message
            canSendMessage = true; // L'utilisateur a reçu une réponse, il peut envoyer un nouveau message
        } else {
            console.log('Message reçu:', data);
        }
    } catch (error) {
        console.error("Erreur lors du parsing du message:", error);
    }
};

function afficherMessage(messageData, showAnnotations) {
    const messageContainer = document.createElement('div');
    messageContainer.classList.add('message-container', messageData.type);
    
    // Utiliser l'ID du message ou créer un ID unique
    const messageId = messageData.message_id || `message-${Date.now()}`;
    messageContainer.id = messageId;
    
    // CORRECTION: Stocker l'ID du message dans un attribut data
    messageContainer.setAttribute('data-message-id', messageId);

    const messageElement = document.createElement('div');
    messageElement.classList.add('message', messageData.type);
    messageElement.textContent = messageData.message;
    messageContainer.appendChild(messageElement);

    // Afficher les annotations de l'expéditeur
    if (showAnnotations && messageData.annotations) {
        const annotationContainer = document.createElement('div');
        annotationContainer.classList.add('emoji-annotation');
        for (let i = 0; i < messageData.annotations.length; i++) {
            const emojiSpan = document.createElement('span');
            emojiSpan.textContent = messageData.annotations[i];
            annotationContainer.appendChild(emojiSpan);
        }
        messageContainer.appendChild(annotationContainer);
    }

    // Afficher le bouton 'Annoter' uniquement pour les messages reçus
    if (messageData.type === 'received' && !messageContainer.classList.contains('annotated')) {
        const annoterButton = document.createElement('button');
        annoterButton.classList.add('annoter-button');
        annoterButton.textContent = 'Annoter';
        annoterButton.onclick = function () {
            annoterMessageRecu(messageContainer);
        };
        messageContainer.appendChild(annoterButton);
    }

    const messageDisplayAreaUser = document.getElementById('messageDisplayAreaUser');
    messageDisplayAreaUser.appendChild(messageContainer);
    scrollToBottom();
}

function annoterMessageRecu(messageContainer) {
    // NOUVEAU: Vérifier si le message a déjà été annoté
    if (messageContainer.classList.contains('annotated') || messageContainer.classList.contains('user-annotated')) {
        alert("Ce message a déjà été annoté.");
        return;
    }

    const emojiSelect = document.createElement('select');
    emojiSelect.id = 'emojiSelectAnnotation';
    
    // Émojis d'annotation (nettoyés)
    const emojis = ['😊', '😡', '😞', '😖', '😵‍💫', '😰'];
    
    // Option vide par défaut
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.text = 'Choisir une émotion...';
    emojiSelect.appendChild(defaultOption);
    
    emojis.forEach(function (emoji) {
        const option = document.createElement('option');
        option.value = emoji;
        option.text = emoji;
        emojiSelect.appendChild(option);
    });

    const validerButton = document.createElement('button');
    validerButton.textContent = 'Valider';

    const finAnnotationButton = document.createElement('button');
    finAnnotationButton.textContent = 'Fin annotation';
    finAnnotationButton.disabled = true;

    finAnnotationButton.onclick = function () {
        markMessageAsAnnotated(messageContainer);
        
        // Notifier le serveur que l'annotation est terminée
        if (socket.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify({ 
                type: 'annotation-complete',
                user_id: userId
            }));
        }
    };

    validerButton.onclick = function () {
        if (emojiSelect.value !== '') {
            ajouterAnnotations(messageContainer, emojiSelect);
            finAnnotationButton.disabled = false;
        } else {
            alert("Veuillez sélectionner une émotion.");
        }
    };

    messageContainer.appendChild(emojiSelect);
    messageContainer.appendChild(validerButton);
    messageContainer.appendChild(finAnnotationButton);
}

function ajouterAnnotations(messageContainer, emojiSelect) {
    const selectedEmoji = emojiSelect.value;

    if (selectedEmoji !== '') {
        // NOUVEAU: Vérifier si le message a déjà été annoté par cet utilisateur
        if (messageContainer.classList.contains('user-annotated')) {
            alert("Vous avez déjà annoté ce message.");
            return;
        }

        const annotationContainer = document.createElement('div');
        annotationContainer.classList.add('emoji-annotation');

        const emojiSpan = document.createElement('span');
        emojiSpan.textContent = selectedEmoji;
        annotationContainer.appendChild(emojiSpan);

        const messageElement = messageContainer.querySelector('.message');
        messageContainer.insertBefore(annotationContainer, messageElement.nextSibling);

        // CORRECTION: Utiliser l'ID correct du message
        const messageId = messageContainer.getAttribute('data-message-id') || messageContainer.id;
        
        // Envoyer l'annotation au serveur avec les informations complètes
        const annotationData = {
            type: 'annotation',
            messageId: messageId, // CORRECTION: utiliser messageId avec 'I' majuscule
            annotations: selectedEmoji,
            user_id: userId,
            timestamp: new Date().toISOString()
        };
        
        console.log("Envoi de l'annotation:", annotationData);
        
        if (socket.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify(annotationData));
            // NOUVEAU: Marquer immédiatement le message comme annoté côté client
            messageContainer.classList.add('user-annotated');
        } else {
            console.error("WebSocket non connecté, impossible d'envoyer l'annotation");
        }
    }
}

// NOUVEAU: Fonction pour marquer un message comme déjà annoté
function markMessageAsAnnotated(messageContainer) {
    messageContainer.classList.add('annotated', 'user-annotated');
    
    // Supprimer les boutons d'annotation s'ils existent encore
    const annoterButton = messageContainer.querySelector('.annoter-button');
    const emojiSelect = messageContainer.querySelector('#emojiSelectAnnotation');
    const validerButton = messageContainer.querySelector('button');
    const finAnnotationButton = messageContainer.querySelector('button:last-child');
    
    if (annoterButton) messageContainer.removeChild(annoterButton);
    if (emojiSelect) messageContainer.removeChild(emojiSelect);
    if (validerButton && validerButton.textContent === 'Valider') messageContainer.removeChild(validerButton);
    if (finAnnotationButton && finAnnotationButton.textContent === 'Fin annotation') messageContainer.removeChild(finAnnotationButton);
    
    // Ajouter un indicateur visuel
    const annotatedIndicator = document.createElement('div');
    annotatedIndicator.className = 'annotated-indicator';
    annotatedIndicator.textContent = '✓ Déjà annoté';
    annotatedIndicator.style.fontSize = '12px';
    annotatedIndicator.style.color = '#666';
    annotatedIndicator.style.fontStyle = 'italic';
    messageContainer.appendChild(annotatedIndicator);
    
    // Réinitialiser les variables d'état si nécessaire
    annotationEnAttente = false;
    currentMessageIdForAnnotation = null;
}

// Auto-scroll au chargement
window.addEventListener('load', scrollToBottom);