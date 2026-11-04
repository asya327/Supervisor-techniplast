<?php
session_start();


if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo "<div style='text-align:center; padding:50px;'><h2>Accès refusé</h2><a href='login.php'>Connexion</a></div>";
    exit;
}

$bd = new SQLite3('techniplast_data.db');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conditions = [];
    if (!empty($_POST['pressenum'])) $conditions[] = "presse = " . (int)$_POST['pressenum'];
    if (!empty($_POST['type'])) $conditions[] = "type_arret = '".$bd->escapeString($_POST['type'])."'";
    if (!empty($_POST['duree_min'])) $conditions[] = "duree >= " . (int)$_POST['duree_min'];

    $where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    $limit = 10;
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $offset = ($page - 1) * $limit;

    $totalRows = $bd->querySingle("SELECT COUNT(*) FROM historique_arrets $where");
    $totalPages = ceil($totalRows / $limit);

    $sql = "SELECT presse, type_arret AS etat, date_debut, date_fin, duree 
            FROM historique_arrets $where ORDER BY date_debut DESC LIMIT $limit OFFSET $offset";
    $result = $bd->query($sql);

    $tbody = "";
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $s = $row['duree'];
        $duree_f = sprintf('%02d:%02d:%02d', floor($s/3600), floor(($s%3600)/60), $s%60);
        $etat_css = strtolower($row['etat']);
        $tbody .= "<tr>
                    <td>{$row['presse']}</td>
                    <td><span class='badge badge-$etat_css'>".strtoupper($row['etat'])."</span></td>
                    <td>{$row['date_debut']}</td>
                    <td>{$row['date_fin']}</td>
                    <td>$duree_f</td> 
                  </tr>";
    }

    $labels = []; 
    $counts = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('d/m', strtotime($d));
        $counts[] = $bd->querySingle("SELECT COUNT(*) FROM historique_arrets WHERE date(date_debut) = '$d'");
    }

    header('Content-Type: application/json');
    echo json_encode([
        'tbody' => $tbody,
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'trendLabels' => $labels,
        'trendData' => $counts
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique - Techniplast</title>
    
    <link rel="stylesheet" href="css/historique.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/responsive.css?v=<?php echo time(); ?>">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div id="historique">
    
    <div class="top-row">
        <div class="panel chart-mini">
            <h4>📈 Fréquence globale (7j)</h4>
            <div class="chart-container" style="position: relative; height:180px; width:100%">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <div class="panel filter-side">
            <h4>🔍 Filtrage</h4>
            <form id="formhist">
                <input type="number" name="pressenum" placeholder="N° presse">
                <select name="type">
                    <option value="">-- Tous les états --</option>
                    <option value="off">OFF</option>
                    <option value="stop">STOP</option>
                    <option value="maintenance">MAINTENANCE</option>
                </select>
                <input type="number" name="duree_min" placeholder="Durée min (s)">
                
                <div class="btn-group">
                    <button type="submit" class="btn-ok">Filtrer</button>
                    <button type="button" id="clearFilter" class="btn-reset">Reset</button>
                </div>
                <button type="button" id="exportCSV" class="btn-export">Exporter CSV</button>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="table-wrapper">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Presse</th>
                        <th>Etat</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Durée</th>
                    </tr>
                </thead>
                <tbody id="tableHistorique">
                    </tbody>
            </table>
        </div> 
        
        <div class="pagination-arrows">
            <button id="prevPage" class="btn-arrow" onclick="changePage(-1)">←</button>
            <span id="page-info">...</span>
            <button id="nextPage" class="btn-arrow" onclick="changePage(1)">→</button>
        </div>
    </div>
</div>
<script src='js/historique.js'></script>
</body>
</html>