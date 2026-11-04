<?php
session_start();
include("config.php");

// Chemin universel pour la base de données peu importe l'OS (Windows/Linux)
$db_file = __DIR__ . "/techniplast_data.db";
if (!file_exists($db_file)) {
    die("Erreur : La base de données est introuvable.");
}

$bd = new SQLite3($db_file);
$ip = $_SERVER['REMOTE_ADDR'];
$max_attempts = 3;
$lock_time = 30; 
$temps_restant = 0;
$current_attempts = 0;

$stmt = $bd->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip = :ip");
$stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
$res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if ($res) {
    $current_attempts = $res['attempts'];
    $last_time = $res['last_attempt'];
    $ecoule = time() - $last_time;

    if ($ecoule < $lock_time && $current_attempts >= $max_attempts) {
        $temps_restant = $lock_time - $ecoule;
    } elseif ($ecoule >= $lock_time) {
        $current_attempts = 0;
        $bd->exec("DELETE FROM login_attempts WHERE ip = '$ip'");
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['password'])) {
    if ($current_attempts >= $max_attempts && $temps_restant > 0) {
        $error = "Accès bloqué. Veuillez patienter.";
    } 
    else if (password_verify(trim($_POST['password']), trim($password_admin))) {
        $_SESSION['logged_in'] = true;
        $bd->exec("DELETE FROM login_attempts WHERE ip = '$ip'");
        header("Location: main.php");
        exit;
    } 
    else {
        $current_attempts++;
        $now = time();
        $stmt_insert = $bd->prepare("INSERT OR REPLACE INTO login_attempts (ip, attempts, last_attempt) VALUES (:ip, :attempts, :last_attempt)");
        $stmt_insert->bindValue(':ip', $ip, SQLITE3_TEXT);
        $stmt_insert->bindValue(':attempts', $current_attempts, SQLITE3_INTEGER);
        $stmt_insert->bindValue(':last_attempt', $now, SQLITE3_INTEGER);
        $stmt_insert->execute();
        
        if ($current_attempts >= $max_attempts) {
            $temps_restant = $lock_time;
            $error = "Trop d'échecs. Sécurité activée.";
        } else {
            $error = "Mot de passe incorrect ($current_attempts/$max_attempts)";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Techniplast</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-box">
        <img src="images/plast.png" alt="Logo">
        
        <?php if ($current_attempts >= $max_attempts && $temps_restant > 0): ?>
            <div class="lock-container">
                <h3>🔒 Accès Temporairement Bloqué</h3>
                <p>Trop de tentatives infructueuses.</p>
                <div class="timer-circle" id="countdown"><?php echo $temps_restant; ?></div>
                <p>secondes avant de réessayer...</p>
            </div>

            <script>
                let timeLeft = <?php echo $temps_restant; ?>;
                const timerElement = document.getElementById('countdown');

                const countdown = setInterval(() => {
                    timeLeft--;
                    if (timeLeft <= 0) {
                        clearInterval(countdown);
                        window.location.href = "login.php";
                    } else {
                        timerElement.textContent = timeLeft;
                    }
                }, 1000);
            </script>

        <?php else: ?>
            <form method="POST">
                <h2>Supervision Industrielle</h2>
                <input type="password" name="password" placeholder="Code d'accès" required>
                <button type="submit">SE CONNECTER</button>
            </form>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <p id='erreur'><?php echo $error; ?></p>
        <?php endif; ?>
    </div>
</body>
</html>