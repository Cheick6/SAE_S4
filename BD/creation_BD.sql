-- Database: sae1

CREATE TABLE User (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    genre CHAR(1),
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    is_online BOOLEAN,  /* Statut en ligne des utilisateurs */
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  /* Date de création du compte */
    CHECK (genre IN ('F', 'M'))  /* Contraint le genre à 'F' ou 'M' */
);

-- Table des conversations
CREATE TABLE Conversation (
    conversation_id INT AUTO_INCREMENT PRIMARY KEY,
    user_1_id INT NOT NULL,
    user_2_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_1_id) REFERENCES `User`(user_id),
    FOREIGN KEY (user_2_id) REFERENCES `User`(user_id)
);

-- Table des messages
CREATE TABLE Message (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT,
    sender_id INT,
    receiver_id INT,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES Conversation(conversation_id),
    FOREIGN KEY (sender_id) REFERENCES `User`(user_id),
    FOREIGN KEY (receiver_id) REFERENCES `User`(user_id)
);

-- Table des annotations émotionnelles
CREATE TABLE Annotation (
    annotation_id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT,
    annotator_id INT,
    emotion ENUM('joie', 'colere', 'tristesse', 'surprise', 'degout', 'peur') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES Message(message_id),
    FOREIGN KEY (annotator_id) REFERENCES `User`(user_id)
);

