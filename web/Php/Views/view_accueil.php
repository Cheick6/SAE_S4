<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - PingMe</title>
    <link rel="stylesheet" href="../css/CSS_accueil.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <h1 class="app-title">
                    <i class="fas fa-comment-dots"></i>
                    PingMe
                </h1>
            </div>
            <div class="header-right">
                <a href="index.php?controller=parametres" class="settings-btn" title="Paramètres">
                    <i class="fas fa-cog"></i>
                </a>
            </div>
        </header>

        <!-- Contenu principal -->
        <main class="main-content">
            <div class="welcome-section">
                <div class="welcome-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h2 class="welcome-title">Bienvenue sur PingMe !</h2>
                <p class="welcome-subtitle">
                    Commencez une nouvelle conversation et partagez vos émotions
                </p>
            </div>

            <div class="action-section">
                <a href="index.php?controller=messagerie" class="start-chat-btn">
                    <div class="btn-content">
                        <i class="fas fa-plus-circle"></i>
                        <span class="btn-text">Démarrer une conversation</span>
                    </div>
                    <div class="btn-description">
                        Cliquez ici pour commencer à chatter
                    </div>
                </a>
            </div>


        </main>

        <!-- Footer -->
        <footer class="footer">
            <p>© 2024 PingMe. Tous droits réservés.</p>
        </footer>
    </div>
</body>
</html>