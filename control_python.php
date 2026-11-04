<?php
session_start();

// Vérification de la session
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { 
    header("Location: login.php?error=session_expiree");
    exit; 
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$script_name = "simulateurPresse.py";
$script_path = __DIR__ . DIRECTORY_SEPARATOR . $script_name;

// Détection automatique de l'exécutable Python selon l'OS
$is_windows = (strncasecmp(PHP_OS, 'WIN', 3) == 0);
$python_exe = $is_windows ? "python" : "python3"; 

if ($action === 'stop') {
    if ($is_windows) {
        // Commande Windows
        exec("taskkill /F /IM python.exe /T");
    } else {
        // Commande Linux (arrête proprement le script python ciblé)
        exec("pkill -f " . escapeshellarg($script_name));
    }
    echo "Superviseur arrêté avec succès.";
} 
elseif ($action === 'start') {
    if (file_exists($script_path)) {
        if ($is_windows) {
            // Lancement en arrière-plan sous Windows
            $cmd = 'start "" /B ' . $python_exe . ' ' . escapeshellarg($script_path);
            $handle = popen($cmd, "r");
            $success = ($handle !== false);
            if ($handle) pclose($handle);
        } else {
            // Lancement en arrière-plan sous Linux (avec nohup)
            $cmd = 'nohup ' . $python_exe . ' ' . escapeshellarg($script_path) . ' > /dev/null 2>&1 &';
            exec($cmd, $output, $result_code);
            $success = ($result_code === 0);
        }

        if ($success) {
            echo "Superviseur démarré (Fichier: " . htmlspecialchars($script_name) . ")";
        } else {
            echo "Erreur lors du lancement du processus.";
        }
    } else {
        echo "Erreur : Script introuvable à l'adresse " . htmlspecialchars($script_path);
    }
} else {
    echo "Action non reconnue.";
}
?>