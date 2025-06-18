// Établir la connexion WebSocket avec le serveur
const socket = new WebSocket('ws://172.20.10.2:8080');

// Variables d'état
let isSending = false;
let annotationEnAttente = false;
let annotatedMessages = new Set(); // Pour garder trace des messages déjà annotés

console.log("Annotation en attente");


// Gestion de la connexion WebSocket
const statusMessage = document.getElementById('status-message');

socket.onopen = function(event) {
    console.log("Connexion WebSocket établie avec le serveur !");
    if (statusMessage){
        statusMessage.textContent = 'Connecté';
        statusMessage.style.color = 'green';
    }
};

socket.onerror = function(error) {
    console.error("Erreur WebSocket :", error);
    if (statusMessage){
        statusMessage.textContent = 'Erreur de connexion';
        statusMessage.style.color = 'red';
    }
};

socket.onclose = function(event) {
    console.log('Connexion WebSocket fermée');
    if (statusMessage){
        statusMessage.textContent = 'Déconnecté';
        statusMessage.style.color = 'orange';
    }
};

// Fonctions utilitaires
function scrollToBottom() {
    const messageDisplayAreaUser = document.getElementById('messageDisplayAreaUser');
    messageDisplayAreaUser.scrollTop = messageDisplayAreaUser.scrollHeight;
}

// Envoi de message
function envoyerMessage() {
    if (isSending) return;
    
    const messageInput = document.getElementById('messageInput');
    const emojiList = document.getElementById('emojiList');
    const messageText = messageInput.value.trim();
    let emojis = '';

    const emojiSpans = emojiList.getElementsByTagName('span');
    for (let i = 0; i < emojiSpans.length; i++) {
        emojis += emojiSpans[i].textContent;
    }

    if (annotationEnAttente) {
        alert("Veuillez terminer l'annotation en cours avant d'envoyer un nouveau message.");
        return;
    }

    if (emojis === "") {
        alert("Veuillez sélectionner un emoji avant d'envoyer votre message.");
        return;
    }

    if (emojis !== "" && messageText !== "") {
        isSending = true;
        const sendButton = document.getElementById('sendButton');
        if (sendButton) sendButton.disabled = true;
        
        const messageData = {
            message: messageText,
            annotations: emojis,
            type: 'sent'
        };

        socket.send(JSON.stringify(messageData));
        afficherMessage(messageData, true);

        messageInput.value = '';
        emojiList.innerHTML = '';
        // NE PAS réactiver ici, attendre la réception d'un message
    }
}

// Gestion des emojis
document.getElementById('emojiSelect').addEventListener('change', function(event) {
    const selectedEmoji = this.value;
    const emojiList = document.getElementById('emojiList');
    emojiList.innerHTML = '';
    if (selectedEmoji) {
        const emojiSpan = document.createElement('span');
        emojiSpan.textContent = selectedEmoji;
        emojiList.appendChild(emojiSpan);
    }
});

// Envoi avec la touche Entrée
document.getElementById('messageInput').addEventListener('keypress', function(event) {
    if (event.key === 'Enter' && !isSending) {
        envoyerMessage();
    }
});

// Réception des messages
socket.onmessage = function(event) {
    const messageData = JSON.parse(event.data);

    if (messageData.type === 'sent') {
        messageData.type = 'received';
    }

    afficherMessage(messageData, false);
    annotationEnAttente = true;

    // Débloquer l'envoi si on reçoit un message de l'autre utilisateur
    if (messageData.type === 'received') {
        isSending = false;
        const sendButton = document.getElementById('sendButton');
        if (sendButton) sendButton.disabled = false;
    }
};

// Affichage des messages
function afficherMessage(messageData, showAnnotations) {
    const messageContainer = document.createElement('div');
    messageContainer.classList.add('message-container', messageData.type);
    
    // Ajout d'un ID unique pour le message
    const messageId = 'msg-' + Date.now();
    messageContainer.id = messageId;

    const messageElement = document.createElement('div');
    messageElement.classList.add('message', messageData.type);
    messageElement.textContent = messageData.message;
    messageContainer.appendChild(messageElement);

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

    // Afficher le bouton 'Annoter' uniquement pour les messages reçus non annotés
    if (messageData.type === 'received' && !annotatedMessages.has(messageId)) {
        const annoterButton = document.createElement('button');
        annoterButton.classList.add('annoter-button');
        annoterButton.textContent = 'Annoter';
        annoterButton.onclick = function() {
            if (!annotatedMessages.has(messageId)) {
                annotatedMessages.add(messageId);
                annoterButton.disabled = true;
                annoterButton.style.opacity = '0.6';
                annoterButton.style.cursor = 'not-allowed';
                annoterMessageRecu(messageContainer);
            }
        };
        messageContainer.appendChild(annoterButton);
    }

    const messageDisplayAreaUser = document.getElementById('messageDisplayAreaUser');
    messageDisplayAreaUser.appendChild(messageContainer);
    scrollToBottom();
}

// Annotation des messages reçus
function annoterMessageRecu(messageContainer) {
    const emojiSelect = document.createElement('select');
    emojiSelect.id = 'emojiSelectAnnotation';
    const emojis = ['😊','😡', '😞', '😖', '😵‍💫', '😰'];
    
    emojis.forEach(function(emoji) {
        const option = document.createElement('option');
        option.value = emoji;
        option.text = emoji;
        emojiSelect.appendChild(option);
    });

    const validerButton = document.createElement('button');
    validerButton.textContent = 'Valider';
    validerButton.classList.add('valider-button');

    validerButton.onclick = function() {
        ajouterAnnotations(messageContainer, emojiSelect);
        // Supprimer l'interface d'annotation après validation
        messageContainer.removeChild(emojiSelect);
        messageContainer.removeChild(validerButton);
        annotationEnAttente = false;
    };

    messageContainer.appendChild(emojiSelect);
    messageContainer.appendChild(validerButton);
}


// Ajout des annotations
function ajouterAnnotations(messageContainer, emojiSelect) {
    const selectedEmojis = Array.from(emojiSelect.selectedOptions)
        .map(option => option.value)
        .join('');

    const annotationContainer = document.createElement('div');
    annotationContainer.classList.add('emoji-annotation');

    for (let i = 0; i < selectedEmojis.length; i++) {
        const emojiSpan = document.createElement('span');
        emojiSpan.textContent = selectedEmojis[i];
        annotationContainer.appendChild(emojiSpan);
    }

    const messageElement = messageContainer.querySelector('.message');
    messageContainer.insertBefore(annotationContainer, messageElement.nextSibling);

    const annotationData = {
        messageId: messageContainer.id,
        annotations: selectedEmojis,
        type: 'annotation'
    };
    socket.send(JSON.stringify(annotationData));
}

window.addEventListener('load', scrollToBottom);