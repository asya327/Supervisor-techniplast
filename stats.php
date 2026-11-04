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

$db_file = 'techniplast_data.db';
if (!file_exists($db_file)) die("Erreur : La base de données est introuvable.");

$bd = new SQLite3($db_file);

function formatDuration($s) {
    if ($s <= 0 || $s === null) return "00:00:00";
    $h = floor($s / 3600);
    $m = floor(($s % 3600) / 60);
    $sec = floor($s % 60);
    return sprintf('%02d:%02d:%02d', $h, $m, $sec);
}

$filtre = isset($_POST['periode']) ? $_POST['periode'] : 'global';
$date_query = "";
if ($filtre === 'today') $date_query = "AND date_debut >= date('now')";
elseif ($filtre === 'week') $date_query = "AND date_debut >= date('now', 'weekday 1', '-7 days')";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Supervision Techniplast - Dashboard</title>
<link rel="stylesheet" href="css/stats.css?v=1.3">
<link rel="stylesheet" href="css/responsive.css?v=<?php echo time(); ?>">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
</head>
<body>

<div id="header-stats" class="no-print">
    <h1>📈 Tableau de Bord Analytique</h1>
    <button class="btn btn-print" onclick="window.print()">🖨️ Rapport PDF</button>
</div>

<div id="stats-container">

    <div class="card">
        <h3>Supervision par Machine</h3>
        <form method="post" class="controls no-print">
            <input type="number" name="pressenum" placeholder="N° Presse" required
                value="<?php echo isset($_POST['pressenum']) ? $_POST['pressenum'] : ''; ?>">
            <select name="periode">
                <option value="global" <?php if($filtre=='global') echo 'selected'; ?>>Global</option>
                <option value="today" <?php if($filtre=='today') echo 'selected'; ?>>Aujourd'hui</option>
                <option value="week" <?php if($filtre=='week') echo 'selected'; ?>>Cette Semaine</option>
            </select>
            <button type="submit" class="btn btn-analyze">Analyser</button>
        </form>

        <?php if(isset($_POST['pressenum'])):
            $p = intval($_POST['pressenum']);
            $res = $bd->query("SELECT SUM(duree) as val, LOWER(type_arret) as type FROM historique_arrets WHERE presse=$p $date_query GROUP BY type");

            $stats = ['off'=>0, 'stop'=>0, 'maintenance'=>0];
            $found = false;
            while($row = $res->fetchArray(SQLITE3_ASSOC)) {
                if(isset($stats[$row['type']])) { $stats[$row['type']] = $row['val']; $found=true; }
            }

            if($found):
                $total_arrets = array_sum($stats);
                $ref = ($filtre == 'today') ? (date('H')*3600 + date('i')*60) : 36000;
                $run = max(0, $ref - $total_arrets);
                $dispo = round(($run / $ref) * 100, 1);
        ?>
            <span class="trs-value"><?php echo $dispo; ?> %</span>
            <p style="text-align:center; color:#666;">Taux de Disponibilité (Presse <?php echo $p; ?>)</p>

            <!-- Boutons toggle (écran seulement) -->
            <div class="switch-btns no-print">
                <div id="btn1" class="btn-s active" onclick="toggleView(1)">📊 Performance</div>
                <div id="btn2" class="btn-s" onclick="toggleView(2)">⚠️ Détails Arrets</div>
            </div>

            <!-- Charts -->
            <div id="view1" class="chart-box"><canvas id="canvasTRS"></canvas></div>
            <div id="view2" class="chart-box hidden"><canvas id="canvasLoss"></canvas></div>

            <script>
                Chart.register(ChartDataLabels);

                const chartOptions = {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        datalabels: {
                            color: '#fff',
                            font: { weight: 'bold' },
                            formatter: (v, ctx) => {
                                let sum = ctx.chart.data.datasets[0].data.reduce((a,b)=>a+b,0);
                                return v>0 ? (v*100/sum).toFixed(1)+"%" : "";
                            }
                        }
                    }
                };

                new Chart(document.getElementById('canvasTRS'), {
                    type:'doughnut',
                    data:{
                        labels:['PRODUCTION (RUN)','ARRÊTS TOTAL'],
                        datasets:[{data:[<?php echo $run; ?>, <?php echo $total_arrets; ?>], backgroundColor:['#28a745','#dc3545']}]
                    },
                    options: chartOptions
                });

                new Chart(document.getElementById('canvasLoss'), {
                    type:'pie',
                    data:{
                        labels:['OFF','STOP','MAINTENANCE'],
                        datasets:[{data:[<?php echo $stats['off']; ?>, <?php echo $stats['stop']; ?>, <?php echo $stats['maintenance']; ?>],
                                   backgroundColor:['#ff4d4d','#ffa500','#f1c40f']}]
                    },
                    options: chartOptions
                });

                function toggleView(v){
                    document.getElementById('view1').classList.toggle('hidden',v!==1);
                    document.getElementById('view2').classList.toggle('hidden',v!==2);
                    document.getElementById('btn1').classList.toggle('active',v===1);
                    document.getElementById('btn2').classList.toggle('active',v===2);
                }
            </script>

        <?php else: ?>
            <p style="color:orange; text-align:center;">Aucune donnée pour cette période.</p>
        <?php endif; endif; ?>
    </div>

    <!-- Top 5 arrêts -->
    <div class="card">
        <h3>Top 5 des Arrêts (Critiques)</h3>
        <p><small>Classement par cumul du temps "STOP" (<?php echo ucfirst($filtre); ?>)</small></p>
        <table>
        <?php
        $top_query = $bd->query("SELECT presse, SUM(duree) as tot FROM historique_arrets WHERE LOWER(type_arret)='stop' $date_query GROUP BY presse ORDER BY tot DESC LIMIT 5");
        $max_val=0; $rows=[];
        while($r = $top_query->fetchArray(SQLITE3_ASSOC)){
            if($max_val==0) $max_val=$r['tot'];
            $rows[]=$r;
        }
        foreach($rows as $i=>$row):
            $pct = ($max_val>0)?($row['tot']/$max_val*100):0;
        ?>
            <tr>
                <td style="width:50px;"><span class="rank-badge"><?php echo $i+1; ?></span></td>
                <td>
                    <strong>Presse <?php echo $row['presse']; ?></strong>
                    <div class="progress-container"><div class="progress-bar" style="width:<?php echo $pct; ?>%"></div></div>
                </td>
                <td style="text-align:right;"><span class="time-val"><?php echo formatDuration($row['tot']); ?></span></td>
            </tr>
        <?php endforeach; if(empty($rows)) echo "<tr><td colspan='3'>Aucune donnée d'arrêt critique.</td></tr>"; ?>
        </table>
    </div>

</div>
</body>
</html>
