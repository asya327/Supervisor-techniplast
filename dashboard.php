<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo "<div class='accesRef'>
            <h2>Accès refusé</h2>
            <p>Veuillez vous connecter via la page d'accueil.</p>
            <a href='login.php'>Retour au login</a>
          </div>";
    exit;
}

$db_path = __DIR__ . "/techniplast_data.db";
$etat_initial = [];
$signals_initial = [];

if (file_exists($db_path)) {
    $bd = new SQLite3($db_path, SQLITE3_OPEN_READONLY);
    
    $res = $bd->query("SELECT * FROM etat_presse ORDER BY presse");
    while($l = $res->fetchArray(SQLITE3_ASSOC)) { $etat_initial[] = $l; }
    
    $res2 = $bd->query("SELECT UPPER(type_arret) AS type, presse, date_debut FROM instant_arrets ORDER BY date_debut DESC LIMIT 15");
    while($l = $res2->fetchArray(SQLITE3_ASSOC)) { $signals_initial[] = $l; }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Techniplast</title>
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/responsive.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/nologin.css">
</head>
<body>
    <div id="dashboard" name="main">
        <div class="table-wrapper">
            <h2>Signals</h2>
            <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Etat</th>
                        <th>Presse</th>
                        <th>Date début</th>
                    </tr>
                </thead>
                <tbody id="tableSignals">
                    <?php foreach ($signals_initial as $s): 
                        $emoji = ($s['type'] == "OFF") ? "⚫" : (($s['type'] == "STOP") ? "🔴" : "🟡");
                    ?>
                        <tr>
                            <td><?php echo $emoji . " " . $s['type']; ?></td>
                            <td><?php echo $s['presse']; ?></td>
                            <td><?php echo $s['date_debut']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <div class="table-wrapper">
            <h2>Liste machines & Etat</h2>
            <table>
                <thead>
                    <tr>
                        <th>Presse</th>
                        <th>Etat maintenant</th>
                    </tr>
                </thead>
                <tbody id="tableEtat">
                    <?php foreach ($etat_initial as $e): 
                        $emo = ($e['etat'] == "run") ? "🟢" : 
       (($e['etat'] == "stop") ? "🔴" : 
       (($e['etat'] == "maintenance") ? "🟡" : "⚫"));
                    ?>
                        <tr>
                            <td>Presse <?php echo $e['presse']; ?></td>
                            <td><?php echo $emo; ?> <b><?php echo strtoupper($e['etat']); ?></b></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="js/dashboard.js"></script>
</body>
</html>