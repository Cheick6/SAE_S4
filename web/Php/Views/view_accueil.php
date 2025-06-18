<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil</title>
    <link rel="stylesheet" href="../css/CSS_accueil.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="container">
        <p>Bienvenue sur Ping Me, <?php echo $_SESSION['username']; ?>!</p>
        <header class="header">
            <div class="menu-toggle">
                <a href="index.php?controller=parametres"> <i class="fas fa-cog"></i> </a>
            </div>
     </header>
        <main class="user-list">
            <div class="user-list">
                <?php foreach ($utilisateurs as $user): ?>
                    <a href="index.php?controller=messagerie&id=<?= $user['user_id'] ?>" class="user-item-link">
                        <div class="user-item">
                            <i class="fas fa-user-circle"></i>
                            <span class="user-name"><?= htmlspecialchars($user['username']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>  
        </main>
    </div>
</body>
</html>
<?php 
$src1 = "../js/messagerie.js";
$src2 = "";
require 'view_fin.php';
?>