<?php
session_start();

// Détection automatique de l'OS
$is_windows = (strncasecmp(PHP_OS, 'WIN', 3) == 0);

if ($is_windows) {
    // Commande Windows (avec masquage des erreurs si aucun processus ne tourne)
    shell_exec("taskkill /F /IM python.exe /T 2>NUL");
} else {
    // Commande Linux / Unix (arrête proprement le script python associé)
    shell_exec("pkill -f simulateurPresse.py > /dev/null 2>&1");
}

session_destroy();
header("Location: login.php");
exit;
?>