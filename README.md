# 📬 Application de Messagerie avec Annotations par Émoji

Ce projet est une application de messagerie en temps réel qui permet aux utilisateurs d'envoyer et de recevoir des messages après avoir sélectionné une émoji en tant qu'annotation. L'application utilise **WebSockets** pour une communication en temps réel et une **base de données PostgreSQL** pour stocker les messages et les annotations.

## 🚀 Fonctionnalités

- **Messagerie en temps réel** : Les messages sont envoyés et reçus instantanément grâce aux WebSockets implémentés en **PHP**.
- **Annotations par émoji** : Avant chaque envoi de message, l'utilisateur doit sélectionner une émoji pour annoter son message.
- **Gestion des utilisateurs** : Les utilisateurs peuvent s'identifier pour commencer une session de chat.
- **Historique des messages** : Les messages et leurs annotations sont stockés dans une base de données pour permettre un historique de conversation.
- **Interface conviviale** : Une interface utilisateur simple et intuitive, optimisée pour les interactions rapides.

## 📦 Architecture du Projet

1. **Back-end** : Développé en **PHP** avec une bibliothèque WebSocket (comme **Ratchet** ou **Swoole**) pour gérer la communication en temps réel.
2. **Front-end** : Interface utilisateur en **Php, CSS et JavaScript** pour envoyer, recevoir, et annoter les messages.
3. **Base de données** : Utilisation de **PostgreSQL, mysql** (administrée via **pgAdmin, mysql**) pour stocker les utilisateurs, messages et annotations.

## 📋 Pré-requis

- **PHP** (v7.4 ou plus récent, recommandé v8.0+) avec les extensions `sockets`, `pdo`, et `pdo_pgsql` activées.
- **Composer** (pour la gestion des dépendances PHP).
- **PostgreSQL** (v10 ou plus récent).
- **pgAdmin** (pour l'administration de la base de données - optionnel mais recommandé).
- Un serveur web compatible PHP (comme **Apache** ou **Nginx**).

## 🔧 Installation

1. **Cloner le dépôt** :
   ```bash
   git clone https://github.com/Cheick6/SAE_S1.git
   cd SAE_S1

azerty