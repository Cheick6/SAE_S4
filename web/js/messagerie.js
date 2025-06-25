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
let currentMessageIdForAnnotation = null;

function scrollToBottom() {
    const messageDisplayAreaUser = document.getElementById('messageDisplayAreaUser');
    if (messageDisplayAreaUser) {
        messageDisplayAreaUser.scrollTop = messageDisplayAreaUser.scrollHeight;
    }
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

    console.log("Envoi du message avec annotations:", messageData);
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
        console.log("Message reçu du serveur:", data);
        
        if (data.error) {
            if (data.type === 'annotation_error') {
                alert('Erreur: ' + data.error);
                const messageContainer = document.getElementById(data.messageId);
                if (messageContainer) {
                    marquerMessageCommeAnnoté(messageContainer);
                }
            } else {
                alert('Erreur: ' + data.error);
            }
            return;
        }

        if (data.type === 'received') {
            afficherMessage(data, false);
            annotationEnAttente = true;
            currentMessageIdForAnnotation = data.message_id;
        } else {
            console.log('Message reçu:', data);
        }
    } catch (error) {
        console.error("Erreur lors du parsing du message:", error);
    }
};

// Fonction pour afficher un message
function afficherMessage(messageData, showAnnotations) {
    const messageContainer = document.createElement('div');
    messageContainer.classList.add('message-container', messageData.type);
    
    const messageId = messageData.message_id || `message-${Date.now()}`;
    messageContainer.id = messageId;
    messageContainer.setAttribute('data-message-id', messageId);

    const messageElement = document.createElement('div');
    messageElement.classList.add('message', messageData.type);
    messageElement.textContent = messageData.message;
    messageContainer.appendChild(messageElement);

    // Afficher les annotations de l'expéditeur pour les messages envoyés
    if (messageData.type === 'sent' && messageData.annotations) {
        afficherAnnotationExpéditeur(messageContainer, messageData.annotations);
    }

    // Bouton d'annotation pour les messages reçus non annotés
    if (messageData.type === 'received' && !messageContainer.classList.contains('annotated')) {
        ajouterBoutonAnnotation(messageContainer);
    }

    const messageDisplayAreaUser = document.getElementById('messageDisplayAreaUser');
    messageDisplayAreaUser.appendChild(messageContainer);
    scrollToBottom();
}

// Fonction pour afficher l'annotation de l'expéditeur
function afficherAnnotationExpéditeur(messageContainer, annotations) {
    const annotationContainer = document.createElement('div');
    annotationContainer.classList.add('emoji-annotation', 'sender-annotation');
    
    const annotations_str = typeof annotations === 'string' ? 
        annotations : 
        (Array.isArray(annotations) ? annotations.join('') : '');
        
    const emojiSpan = document.createElement('span');
    emojiSpan.textContent = annotations_str;
    emojiSpan.title = "Votre émotion";
    annotationContainer.appendChild(emojiSpan);
    messageContainer.appendChild(annotationContainer);
}

// Fonction pour ajouter le bouton d'annotation
function ajouterBoutonAnnotation(messageContainer) {
    const buttonContainer = document.createElement('div');
    buttonContainer.classList.add('annotation-controls');
    
    const annoterButton = document.createElement('button');
    annoterButton.classList.add('annoter-button');
    annoterButton.textContent = 'Réagir avec un émoji';
    annoterButton.onclick = function () {
        ouvrirInterfaceAnnotation(messageContainer);
    };
    
    buttonContainer.appendChild(annoterButton);
    messageContainer.appendChild(buttonContainer);
}

// Fonction pour ouvrir l'interface d'annotation
function ouvrirInterfaceAnnotation(messageContainer) {
    // Vérifier si déjà annoté
    if (messageContainer.classList.contains('user-annotated')) {
        alert("Vous avez déjà annoté ce message.");
        return;
    }

    // Supprimer les contrôles existants
    const existingControls = messageContainer.querySelector('.annotation-controls');
    if (existingControls) {
        existingControls.remove();
    }

    // Créer l'interface d'annotation
    const annotationInterface = document.createElement('div');
    annotationInterface.classList.add('annotation-interface');
    
    // Titre
    const title = document.createElement('div');
    title.classList.add('annotation-title');
    title.textContent = 'Choisissez votre réaction :';
    annotationInterface.appendChild(title);
    
    // Container des émojis
    const emojiContainer = document.createElement('div');
    emojiContainer.classList.add('emoji-selector');
    
    const emojis = [
        { emoji: '😊', label: 'Joie' },
        { emoji: '😡', label: 'Colère' },
        { emoji: '😞', label: 'Tristesse' },
        { emoji: '😖', label: 'Dégoût' },
        { emoji: '😵‍💫', label: 'Surprise' },
        { emoji: '😰', label: 'Peur' }
    ];
    
    emojis.forEach(emojiData => {
        const emojiButton = document.createElement('button');
        emojiButton.classList.add('emoji-button');
        emojiButton.innerHTML = `
            <span class="emoji">${emojiData.emoji}</span>
            <span class="emoji-label">${emojiData.label}</span>
        `;
        emojiButton.onclick = function() {
            selectionnerEmoji(messageContainer, emojiData.emoji, annotationInterface);
        };
        emojiContainer.appendChild(emojiButton);
    });
    
    annotationInterface.appendChild(emojiContainer);
    
    // Bouton annuler
    const cancelButton = document.createElement('button');
    cancelButton.classList.add('cancel-button');
    cancelButton.textContent = 'Annuler';
    cancelButton.onclick = function() {
        fermerInterfaceAnnotation(messageContainer, annotationInterface);
    };
    
    annotationInterface.appendChild(cancelButton);
    messageContainer.appendChild(annotationInterface);
}

// Fonction pour sélectionner un emoji
function selectionnerEmoji(messageContainer, selectedEmoji, annotationInterface) {
    // Vérifier si déjà annoté
    if (messageContainer.classList.contains('user-annotated')) {
        alert("Vous avez déjà annoté ce message.");
        return;
    }

    // Créer l'annotation visuelle
    const annotationContainer = document.createElement('div');
    annotationContainer.classList.add('emoji-annotation', 'receiver-annotation');

    const emojiSpan = document.createElement('span');
    emojiSpan.textContent = selectedEmoji;
    emojiSpan.title = "Votre réaction";
    annotationContainer.appendChild(emojiSpan);

    // Insérer l'annotation après le message
    const messageElement = messageContainer.querySelector('.message');
    messageContainer.insertBefore(annotationContainer, messageElement.nextSibling);

    // Marquer comme annoté
    messageContainer.classList.add('user-annotated');

    // Envoyer au serveur
    const messageId = messageContainer.getAttribute('data-message-id') || messageContainer.id;
    const annotationData = {
        type: 'annotation',
        messageId: messageId,
        annotations: selectedEmoji,
        user_id: userId,
        timestamp: new Date().toISOString()
    };
    
    console.log("Envoi de l'annotation:", annotationData);
    
    if (socket.readyState === WebSocket.OPEN) {
        socket.send(JSON.stringify(annotationData));
    } else {
        console.error("WebSocket non connecté");
    }

    // Fermer l'interface d'annotation
    annotationInterface.remove();
    
    // Ajouter l'indicateur de fin
    ajouterIndicateurAnnotationTerminée(messageContainer);
    
    // Réinitialiser l'état global
    annotationEnAttente = false;
    currentMessageIdForAnnotation = null;
}

// Fonction pour fermer l'interface d'annotation
function fermerInterfaceAnnotation(messageContainer, annotationInterface) {
    annotationInterface.remove();
    ajouterBoutonAnnotation(messageContainer);
}

// Fonction pour ajouter l'indicateur de fin d'annotation
function ajouterIndicateurAnnotationTerminée(messageContainer) {
    const indicator = document.createElement('div');
    indicator.classList.add('annotation-completed');
    indicator.innerHTML = '<i class="check-icon">✓</i> Annotation envoyée';
    messageContainer.appendChild(indicator);
}

// Fonction pour gérer les messages annotés précédemment
function marquerMessageCommeAnnoté(messageContainer) {
    messageContainer.classList.add('annotated', 'user-annotated');
    
    // Supprimer tous les contrôles d'annotation
    const controls = messageContainer.querySelector('.annotation-controls');
    const interface_annotation = messageContainer.querySelector('.annotation-interface');
    
    if (controls) controls.remove();
    if (interface_annotation) interface_annotation.remove();
    
    // Ajouter l'indicateur si pas déjà présent
    if (!messageContainer.querySelector('.annotation-completed')) {
        ajouterIndicateurAnnotationTerminée(messageContainer);
    }
}

// Fonctions de compatibilité avec l'ancien code
function annoterMessageRecu(messageContainer) {
    ouvrirInterfaceAnnotation(messageContainer);
}

function markMessageAsAnnotated(messageContainer) {
    marquerMessageCommeAnnoté(messageContainer);
}

// Auto-scroll au chargement
window.addEventListener('load', scrollToBottom);