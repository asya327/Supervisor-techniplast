<?php
$bd = new SQLite3('techniplast_data.db');

$conditions = [];
if (!empty($_GET['pressenum'])) {
    $conditions[] = "presse = " . (int)$_GET['pressenum'];
}
if (!empty($_GET['type'])) {
    $conditions[] = "type_arret = '" . SQLite3::escapeString($_GET['type']) . "'";
}
if (!empty($_GET['duree_min'])) {
    $conditions[] = "duree >= " . (int)$_GET['duree_min'];
}

$where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

$sql = "SELECT presse, type_arret, date_debut, date_fin, duree FROM historique_arrets $where ORDER BY date_debut DESC";
$result = $bd->query($sql);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=historique_techniplast.csv');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
fputcsv($output, array('Presse', 'Etat', 'Date Debut', 'Date Fin', 'Duree (Sec)', 'Duree (Format)'), ';');
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $s = $row['duree'];
    $h = floor($s / 3600);
    $m = floor(($s % 3600) / 60);
    $sec = $s % 60;
    $formated = sprintf('%02d:%02d:%02d', $h, $m, $sec);
    fputcsv($output, array(
        $row['presse'],
        $row['type_arret'],
        $row['date_debut'],
        $row['date_fin'],
        $row['duree'],
        $formated
    ), ';');
}
fclose($output);
exit;
?>