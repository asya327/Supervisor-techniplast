<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['ajax'])) {
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json');
    
    try {
        $db_path = __DIR__ . "/techniplast_data.db";
        $bd = new SQLite3($db_path, SQLITE3_OPEN_READONLY);
        $bd->busyTimeout(5000);

        $etatT = $bd->query("SELECT * FROM etat_presse ORDER BY presse");
        $etat = [];
        while ($l = $etatT->fetchArray(SQLITE3_ASSOC)) { 
            $etat[] = $l; 
        }

        $sigT = $bd->query("SELECT UPPER(type_arret) AS type, presse, date_debut FROM instant_arrets ORDER BY date_debut DESC LIMIT 15");
        $signals = [];
        while ($l = $sigT->fetchArray(SQLITE3_ASSOC)) { 
            $signals[] = $l; 
        }

        echo json_encode(["etat" => $etat, "signals" => $signals]);
        exit; 
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervision Presses - Techniplast</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/responsive.css?v=<?php echo time(); ?>">
</head>
<body>
    <header>
        <div>
            <a href="dashboard.php" target="if1" title="Retour Dashboard">
                <img src="images/dashboard.png" alt="" id='dashpng'>
            </a>
            <img src="images/plast.png" alt="Logo Techniplast" id="logo">
        </div>
        
        <h1 id="titre">Système de supervision des presses</h1>
        
        <div id="last">
            <div id="currentdate"></div>
            <div id="indicateur">
                Indicateur global : 🟢 Run | 🟡 Maintenance | 🔴 Stop | ⚫ Off
            </div>
        </div>

        <nav id="nav">
            <div class="nav-links">
                <a href="historique.php" target="if1">Historique</a>
                <a href="stats.php" target="if1">Statistiques</a>
            </div>
        </nav>
    </header>
            <div class="nav-bottom">
                <a href="settings.php" target="if1" id="set">
                    <img src="images/settings.png" alt="Settings">
                </a>
                <a href="logout.php" id="logout-btn">Déconnexion</a>
            </div>
            <div class="simulator-box">
    <button type="button" onclick="managePython('start')" class="btn-start">
        ▶️ Démarrer Simulator
    </button>

    <button type="button" onclick="managePython('stop')" class="btn-stop">
        🛑 Arrêter Simulator
    </button>

    <p id="debug-msg">
        Lancer le simulateur.
    </p>
</div>

    <iframe src="dashboard.php" frameborder="0" name="if1" id="main-iframe"></iframe>
    <script src="js/main.js"></script>
</body>
</html>