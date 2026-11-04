<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}
include("auth.php"); 

$message = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST['old_pass'];
    $new = $_POST['new_pass'];
    $conf = $_POST['conf_pass'];

    // Vérifie l'ancien mot de passe
    if (!password_verify(trim($old), trim($password_admin))) {
        $message = "L'ancien mot de passe est incorrect.";
    } 
    // Vérifie correspondance nouveau / confirmation
    elseif ($new !== $conf) {
        $message = "Les nouveaux mots de passe ne correspondent pas.";
    } 
    else {
        // Génère un hash bcrypt
        $nouveau_hache = password_hash(trim($new), PASSWORD_DEFAULT);

        // Prépare le hash pour preg_replace (échapper $)
        $nouveau_hache_esc = str_replace('$', '\\$', $nouveau_hache);

        $config_file = "auth.php";
        $content = file_get_contents($config_file);

        // Remplace le mot de passe existant dans config.php
        $content = preg_replace(
            "/\\\$password_admin\s*=\s*'[^']*';/",
            "\$password_admin = '$nouveau_hache_esc';",
            $content
        );

        if (file_put_contents($config_file, $content)) {
            $message = "Mot de passe crypté et sauvegardé ! Veuillez vous reconnecter.";
            $success = true;
            session_destroy();
        } else {
            $message = "Erreur d'écriture sur le serveur (vérifiez les droits de config.php).";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paramètres - Techniplast</title>
    <link rel="stylesheet" href="css/settings.css?v=1">
    <?php if ($success): ?>
        <meta http-equiv="refresh" content="2;url=login.php">
        <script>
            setTimeout(function() {
                window.top.location.href = "login.php";
            }, 2000);
        </script>
    <?php endif; ?>
</head>
<body>
    <div class="box-settings">
        <h2>🔒 Sécurité du compte</h2>
        <p>Pour plus de sécurité, le mot de passe sera crypté</p>
        <hr>

        <?php if ($message !== ""): ?>
            <p class="<?php echo $success ? 'success' : 'message'; ?>">
                <?php echo $message; ?>
            </p>
        <?php endif; ?>

        <?php if (!$success): ?>
            <form method="POST">
                <label>Ancien mot de passe</label>
                <input type="password" name="old_pass" placeholder="Tapez l'actuel" required>

                <label>Nouveau mot de passe</label>
                <input type="password" name="new_pass" placeholder="Minimum 5 caractères" min=5 required>

                <label>Confirmer le nouveau</label>
                <input type="password" name="conf_pass" placeholder="Répétez le nouveau" required>

                <button type="submit">METTRE À JOUR</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
