# 📬 PingMe - Application de Messagerie avec Annotations par Émoji

PingMe est une application de messagerie en temps réel qui permet aux utilisateurs de communiquer et d'annoter les messages reçus avec des émojis représentant leurs émotions. L'application utilise **WebSockets** pour une communication en temps réel et une **base de données MySQL** pour stocker les messages et les annotations.

## 🚀 Fonctionnalités

- **Messagerie en temps réel** : Communication instantanée via WebSockets (Ratchet PHP)
- **Système d'annotation émotionnelle** : Annotation obligatoire des messages reçus avec 6 émotions (joie, colère, tristesse, dégoût, surprise, peur)
- **Gestion complète des utilisateurs** : Inscription, connexion, gestion de profil
- **Interface moderne** : Design responsive avec CSS moderne et animations
- **Sécurité** : Hachage des mots de passe, protection contre les injections SQL
- **Stockage persistant** : Historique des messages et annotations en base de données

## 📦 Architecture du Projet

```
SAE_S4/
├── web/
│   ├── Controllers/          # Contrôleurs MVC
│   ├── Models/              # Modèles de données
│   ├── Views/               # Vues PHP
│   ├── css/                 # Fichiers CSS
│   ├── js/                  # Fichiers JavaScript
│   ├── img/                 # Images et ressources
│   └── Utils/               # Utilitaires (paramètres DB)
├── vendor/                  # Dépendances Composer
├── composer.json           # Configuration Composer
├── serveur-websocket-ratchet.php  # Serveur WebSocket
└── README.md
```

## 📋 Pré-requis

- **PHP** (v7.4 ou plus récent, recommandé v8.0+)
- **Composer** (gestionnaire de dépendances PHP)
- **MySQL** (v5.7 ou plus récent)
- **Serveur web** : 
  - **XAMPP** (Windows/Linux/macOS)
  - **WAMP** (Windows)
  - **MAMP** (macOS/Windows)
- **Extensions PHP requises** :
  - `pdo`
  - `pdo_mysql`
  - `sockets`
  - `mbstring`

## 🔧 Installation

### 1. Cloner le projet

```bash
git clone https://github.com/votre-username/SAE_S4.git
cd SAE_S4
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configuration de la base de données

#### Paramètres selon votre environnement

Le fichier `web/Utils/parametre.php` contient les paramètres de connexion à la base de données. **Vous devez les modifier selon votre environnement** :

**Configuration par défaut (MAMP) :**
```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '8889');        // Port MAMP par défaut
define('DB_NAME', 'appli');
define('DB_USER', 'root');
define('DB_PASSWORD', 'root');    // Mot de passe MAMP par défaut
```

**Pour XAMPP :**
```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');        // Port XAMPP par défaut
define('DB_NAME', 'appli');
define('DB_USER', 'root');
define('DB_PASSWORD', '');        // Souvent vide par défaut sur XAMPP
```

**Pour WAMP :**
```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');        // Port WAMP par défaut
define('DB_NAME', 'appli');
define('DB_USER', 'root');
define('DB_PASSWORD', '');        // Souvent vide par défaut sur WAMP
```

#### Création de la base de données

1. **Démarrez votre serveur local** (XAMPP/WAMP/MAMP)
2. **Accédez à phpMyAdmin** :
   - XAMPP/WAMP : `http://localhost/phpmyadmin`
   - MAMP : `http://localhost:8888/phpMyAdmin` (ou selon votre configuration)
3. **Créez une nouvelle base de données** nommée `appli`
4. **Importez le schéma** en exécutant le fichier `creation_BD.sql` :
   - Cliquez sur la base `appli`
   - Onglet "SQL"
   - Copiez/collez le contenu de `creation_BD.sql`
   - Exécutez

### 4. Configuration du serveur web

#### Pour XAMPP/WAMP
Placez le projet dans :
- **XAMPP** : `C:\xampp\htdocs\SAE_S4\`
- **WAMP** : `C:\wamp64\www\SAE_S4\`

#### Pour MAMP
Placez le projet dans : `/Applications/MAMP/htdocs/SAE_S4/`

### 5. Accès à l'application

- **URL** : `http://localhost/SAE_S4/web/` (ou `http://localhost:8888/SAE_S4/web/` pour MAMP)
- **Page d'accueil** : Page de connexion

## 🌐 Démarrage du serveur WebSocket

Pour activer la messagerie en temps réel, démarrez le serveur WebSocket :

```bash
# Dans le répertoire racine du projet
php serveur-websocket-ratchet.php
```

Le serveur démarrera sur le port **8081**. Vous devriez voir :
```
ChatServer initialisé avec connexion base de données
Serveur WebSocket avec base de données démarré sur 0.0.0.0:8081
Accessible via votre IP locale sur le port 8081
```

**Important** : Le serveur WebSocket doit rester actif pendant l'utilisation de la messagerie.

## 📱 Utilisation

### 1. Inscription/Connexion
- Créez un compte ou connectez-vous
- Remplissez tous les champs obligatoires
- Acceptez la politique de confidentialité

### 2. Navigation
- **Accueil** : Liste des utilisateurs pour démarrer une conversation
- **Messagerie** : Interface de chat en temps réel
- **Paramètres** : Gestion du profil et préférences

### 3. Messagerie
- **Envoi de message** : Tapez votre message + sélectionnez un émoji obligatoire
- **Réception** : Les messages apparaissent en temps réel
- **Annotation** : Cliquez sur "Réagir avec un émoji" pour annoter les messages reçus
- **Émotions disponibles** : Joie 😊, Colère 😡, Tristesse 😞, Dégoût 😖, Surprise 😵‍💫, Peur 😰

## 🗄️ Structure de la base de données

```sql
User (utilisateurs)
├── user_id (PK)
├── username
├── email
├── password_hash
├── genre
├── is_online
└── created_at

Conversation (conversations)
├── conversation_id (PK)
├── user_1_id (FK)
├── user_2_id (FK)
└── created_at

Message (messages)
├── message_id (PK)
├── conversation_id (FK)
├── sender_id (FK)
├── receiver_id (FK)
├── content
└── created_at

Annotation (annotations émotionnelles)
├── annotation_id (PK)
├── message_id (FK)
├── annotator_id (FK)
├── emotion (ENUM)
└── created_at
```

## 🛠️ Dépannage

### Problèmes courants

**1. Erreur de connexion à la base de données**
- Vérifiez que MySQL est démarré
- Contrôlez les paramètres dans `web/Utils/parametre.php`
- Vérifiez que la base `appli` existe

**2. WebSocket ne fonctionne pas**
- Vérifiez que le port 8081 n'est pas bloqué
- Redémarrez le serveur WebSocket : `php serveur-websocket-ratchet.php`
- Vérifiez les logs du serveur WebSocket

**3. Erreur 404 sur les pages**
- Vérifiez l'URL de base (doit inclure `/web/`)
- Contrôlez la configuration Apache/Nginx
- Assurez-vous que mod_rewrite est activé

**4. Erreurs PHP**
- Vérifiez que toutes les extensions PHP sont installées
- Contrôlez les logs d'erreur PHP
- Vérifiez les permissions des fichiers

### Configuration des ports

**Ports utilisés :**
- **Application web** : 80 (HTTP) ou 443 (HTTPS)
- **MySQL** : 3306 (XAMPP/WAMP) ou 8889 (MAMP)
- **WebSocket** : 8081
- **phpMyAdmin** : Selon votre configuration locale

---

**Note importante** : N'oubliez pas de modifier le fichier `web/Utils/parametre.php` selon votre environnement de développement (XAMPP/WAMP/MAMP) avant de lancer l'application !
