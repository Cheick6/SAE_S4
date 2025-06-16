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

    socket.send(JSON.stringify(messageData));
    afficherMessage(messageData, true);

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
        
        if (data.error) {
            alert('Erreur: ' + data.error);
            return;
        }

        if (data.type === 'received') {
            afficherMessage(data, false);
            annotationEnAttente = true;
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
    if (messageContainer.classList.contains('annotated')) {
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
        messageContainer.classList.add('annotated');
        messageContainer.removeChild(finAnnotationButton);
        messageContainer.removeChild(emojiSelect);
        messageContainer.removeChild(validerButton);
        const btn = messageContainer.querySelector('.annoter-button');
        if (btn) messageContainer.removeChild(btn);
        
        annotationEnAttente = false;
        
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
        const annotationContainer = document.createElement('div');
        annotationContainer.classList.add('emoji-annotation');

        const emojiSpan = document.createElement('span');
        emojiSpan.textContent = selectedEmoji;
        annotationContainer.appendChild(emojiSpan);

        const messageElement = messageContainer.querySelector('.message');
        messageContainer.insertBefore(annotationContainer, messageElement.nextSibling);

        // Envoyer l'annotation au serveur avec les informations complètes
        const annotationData = {
            type: 'annotation',
            messageId: messageContainer.id,
            annotations: selectedEmoji,
            user_id: userId,
            timestamp: new Date().toISOString()
        };
        
        if (socket.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify(annotationData));
        }
    }
}

// Auto-scroll au chargement
window.addEventListener('load', scrollToBottom);