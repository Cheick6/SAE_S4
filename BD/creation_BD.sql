-- Database: appli

-- Désactiver temporairement les contraintes de clés étrangères
SET FOREIGN_KEY_CHECKS = 0;

-- Suppression des tables existantes dans l'ordre inverse des dépendances
DROP TABLE IF EXISTS Annotation;
DROP TABLE IF EXISTS Message;
DROP TABLE IF EXISTS Conversation;
DROP TABLE IF EXISTS UserStatus;  -- Table supplémentaire qui pourrait exister
DROP TABLE IF EXISTS `User`;
DROP TABLE IF EXISTS Usera;       -- Ancienne table à supprimer si elle existe

-- Réactiver les contraintes de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;

-- Table des utilisateurs
CREATE TABLE `User` (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    genre CHAR(1) CHECK (genre IN ('F', 'M')),
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    is_online BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_online (is_online)
);

-- Table des conversations
CREATE TABLE Conversation (
    conversation_id INT AUTO_INCREMENT PRIMARY KEY,
    user_1_id INT NOT NULL,
    user_2_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_1_id) REFERENCES `User`(user_id) ON DELETE CASCADE,
    FOREIGN KEY (user_2_id) REFERENCES `User`(user_id) ON DELETE CASCADE,
    INDEX idx_user1 (user_1_id),
    INDEX idx_user2 (user_2_id),
    INDEX idx_users (user_1_id, user_2_id)
);

-- Table des messages
CREATE TABLE Message (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES Conversation(conversation_id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES `User`(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES `User`(user_id) ON DELETE CASCADE,
    INDEX idx_conversation (conversation_id),
    INDEX idx_sender (sender_id),
    INDEX idx_receiver (receiver_id),
    INDEX idx_created_at (created_at)
);

-- Table des annotations émotionnelles
CREATE TABLE Annotation (
    annotation_id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    annotator_id INT NOT NULL,
    emotion ENUM('joie', 'colere', 'tristesse', 'surprise', 'degout', 'peur') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES Message(message_id) ON DELETE CASCADE,
    FOREIGN KEY (annotator_id) REFERENCES `User`(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_annotation (message_id, annotator_id),
    INDEX idx_message (message_id),
    INDEX idx_annotator (annotator_id),
    INDEX idx_emotion (emotion)
);

-- Insertion de données de test
INSERT INTO `User` (username, genre, password_hash, email, is_online) VALUES
('Alice', 'F', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alice@example.com', TRUE),
('Bob', 'M', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'bob@example.com', FALSE),
('Charlie', 'M', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'charlie@example.com', TRUE),
('Diana', 'F', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'diana@example.com', FALSE);

-- Insertion d'une conversation de test
INSERT INTO Conversation (user_1_id, user_2_id) VALUES (1, 2);

-- Insertion de quelques messages de test
INSERT INTO Message (conversation_id, sender_id, receiver_id, content) VALUES
(1, 1, 2, 'Salut Bob ! Comment ça va ?'),
(1, 2, 1, 'Salut Alice ! Ça va bien, merci !');

-- Insertion d'annotations de test
INSERT INTO Annotation (message_id, annotator_id, emotion) VALUES
(1, 2, 'joie'),
(2, 1, 'joie');

-- Vérification des données insérées
SELECT 'Utilisateurs créés:' as Info;
SELECT user_id, username, email, is_online FROM `User`;

SELECT 'Conversations créées:' as Info;
SELECT c.conversation_id, u1.username as user1, u2.username as user2 
FROM Conversation c
JOIN `User` u1 ON c.user_1_id = u1.user_id
JOIN `User` u2 ON c.user_2_id = u2.user_id;

SELECT 'Messages créés:' as Info;
SELECT m.message_id, u.username as sender, m.content, m.created_at
FROM Message m
JOIN `User` u ON m.sender_id = u.user_id
ORDER BY m.created_at;

SELECT 'Annotations créées:' as Info;
SELECT a.annotation_id, m.content as message, u.username as annotator, a.emotion
FROM Annotation a
JOIN Message m ON a.message_id = m.message_id
JOIN `User` u ON a.annotator_id = u.user_id;