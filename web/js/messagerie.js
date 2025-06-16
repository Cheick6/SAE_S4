// Établir la connexion WebSocket avec le serveur
const socket = new WebSocket('ws://172.20.10.2:8080');

// Ajout du code de statut de la connexion
const statusMessage = document.getElementById('status-message');

socket.onopen = function(event) {
 console.log("Connexion WebSocket établie avec le serveur !");
  if (statusMessage){
      statusMessage.textContent = 'Connecté';
      statusMessage.style.color = 'green';
  }
};

socket.onerror = function (error) {
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

let annotationEnAttente = false;

function scrollToBottom() {
    var messageDisplayAreaUser = document.getElementById('messageDisplayAreaUser');
    messageDisplayAreaUser.scrollTop = messageDisplayAreaUser.scrollHeight;
}

function ajouterEmoji(emoji) {
    var emojiList = document.getElementById('emojiList');
    var emojiSpan = document.createElement('span');
    emojiSpan.textContent = emoji;
    emojiSpan.style.cursor = 'pointer';
    emojiSpan.onclick = function () {
        emojiList.removeChild(emojiSpan);
    };
    emojiList.appendChild(emojiSpan);
}

function envoyerMessage() {
    var messageInput = document.getElementById('messageInput');
    var emojiList = document.getElementById('emojiList');
    var messageText = messageInput.value.trim();
    var emojis = '';

    var emojiSpans = emojiList.getElementsByTagName('span');
    for (var i = 0; i < emojiSpans.length; i++) {
        emojis += emojiSpans[i].textContent;
    }

    if (annotationEnAttente) {
        return;
    }

    if (emojis === "") {
        alert("Veuillez sélectionner un emoji avant d'envoyer votre message.");
        return;
    }
    if (emojis !== "" && messageText !== "") {
        var messageData = {
            message: messageText,
            annotations: emojis,
            type: 'sent'
        };

        socket.send(JSON.stringify(messageData));

        afficherMessage(messageData, true);

        messageInput.value = '';
        emojiList.innerHTML = '';
    }
}

document.getElementById('emojiSelect').addEventListener('change', function (event) {
    var selectedEmoji = this.value;
    var emojiList = document.getElementById('emojiList');
    emojiList.innerHTML = '';
    if (selectedEmoji) {
        var emojiSpan = document.createElement('span');
        emojiSpan.textContent = selectedEmoji;
        emojiList.appendChild(emojiSpan);
    }
});

document.getElementById('messageInput').addEventListener('keypress', function (event) {
    if (event.key === 'Enter') {
        envoyerMessage();
    }
});

socket.onmessage = function (event) {
    var messageData = JSON.parse(event.data);

    if (messageData.type === 'sent') {
        messageData.type = 'received';
    }

    afficherMessage(messageData, false);
    annotationEnAttente = true;
};

function afficherMessage(messageData, showAnnotations) {
    var messageContainer = document.createElement('div');
    messageContainer.classList.add('message-container', messageData.type);

    var messageElement = document.createElement('div');
    messageElement.classList.add('message', messageData.type);
    messageElement.textContent = messageData.message;
    messageContainer.appendChild(messageElement);

    if (showAnnotations && messageData.annotations) {
        var annotationContainer = document.createElement('div');
        annotationContainer.classList.add('emoji-annotation');
        for (var i = 0; i < messageData.annotations.length; i++) {
            var emojiSpan = document.createElement('span');
            emojiSpan.textContent = messageData.annotations[i];
            annotationContainer.appendChild(emojiSpan);
        }
        messageContainer.appendChild(annotationContainer);
    }

    // Afficher le bouton 'Annoter' uniquement pour les messages reçus
    if (messageData.type === 'received' && !messageContainer.classList.contains('annotated')) {
        var annoterButton = document.createElement('button');
        annoterButton.classList.add('annoter-button');
        annoterButton.textContent = 'Annoter';
        annoterButton.onclick = function () {
            annoterMessageRecu(messageContainer);
        };
        messageContainer.appendChild(annoterButton);
    }

    var messageDisplayAreaUser = document.getElementById('messageDisplayAreaUser');
    messageDisplayAreaUser.appendChild(messageContainer);
    scrollToBottom();
}

function annoterMessageRecu(messageContainer) {
    if (messageContainer.classList.contains('annotated')) {
        return;
    }

    var emojiSelect = document.createElement('select');
    emojiSelect.id = 'emojiSelectAnnotation';
    var emojis = ['😊','😡​', '😞', '😖', '😵‍⃫', '😰'];
    emojis.forEach(function (emoji) {
        var option = document.createElement('option');
        option.value = emoji;
        option.text = emoji;
        emojiSelect.appendChild(option);
    });

    var validerButton = document.createElement('button');
    validerButton.textContent = 'Valider';

    var finAnnotationButton = document.createElement('button');
    finAnnotationButton.textContent = 'Fin annotation';
    finAnnotationButton.disabled = true;

    finAnnotationButton.onclick = function () {
        messageContainer.classList.add('annotated');
        messageContainer.removeChild(finAnnotationButton);
        messageContainer.removeChild(emojiSelect);
        messageContainer.removeChild(validerButton);
        var btn = messageContainer.querySelector('.annoter-button');
        if (btn) messageContainer.removeChild(btn);
        annotationEnAttente = false;
        socket.send(JSON.stringify({ type: 'annotation-complete' }));
    };

    validerButton.onclick = function () {
        ajouterAnnotations(messageContainer, emojiSelect);
        finAnnotationButton.disabled = false;
    };

    messageContainer.appendChild(emojiSelect);
    messageContainer.appendChild(validerButton);
    messageContainer.appendChild(finAnnotationButton);
}

function ajouterAnnotations(messageContainer, emojiSelect) {
    var selectedEmojis = Array.from(emojiSelect.selectedOptions)
        .map(option => option.value)
        .join('');

    var annotationContainer = document.createElement('div');
    annotationContainer.classList.add('emoji-annotation');

    for (var i = 0; i < selectedEmojis.length; i++) {
        var emojiSpan = document.createElement('span');
        emojiSpan.textContent = selectedEmojis[i];
        annotationContainer.appendChild(emojiSpan);
    }

    var messageElement = messageContainer.querySelector('.message');
    messageContainer.insertBefore(annotationContainer, messageElement.nextSibling);

    var annotationData = {
        messageId: messageContainer.id,
        annotations: selectedEmojis,
        type: 'annotation'
    };
    socket.send(JSON.stringify(annotationData));
}

window.addEventListener('load', scrollToBottom);
