<?php
/**
 * @author Florian DANIEL aka Facyla janvier 2022
 * License AGPLv3 https://www.gnu.org/licenses/agpl-3.0.fr.html#license-text
 */
?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<title>Bulletins  d'estimation du risque d'avalanche</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="description" content="">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"><meta name="mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-capable" content="yes">
<link rel="icon" href="bra_favicon.png">
</head>
<body>
<h1>Bulletin d'estimation des risques d'avalanche</h1>

<?php
$now = time();
echo '<h2>Liste des Bulletins d\'estimation du risque d\'avalanche disponibles</h2>';

echo "<h3>Estimation pour demain</h3>";
echo bra_list_bulletins($now);

echo '<hr />';
echo "<h3>Estimations d'hier pour aujourd'hui</h3>";
echo bra_list_bulletins($now - 3600*24);



/**
 * Génère une liste des bulletins disponibles du jour
 */
function bra_list_bulletins($ts = false) {
	// Some base data, vars, text strings
	$base_data_url = "https://donneespubliques.meteofrance.fr/donnees_libres/Pdf/BRA/";

	if (!$ts) { $ts = time(); }
	$date = date("Ymd", $ts);
	// Liste des données dispo / massif => indique pour chaque massif l'heure de relevé de ce jour
	$data_url = $base_data_url . "bra.$date.json";
	$content = '';

	// Données disponibles
	$donnees = file_get_contents($data_url);
	if ($donnees) {
		//echo '<pre>' . $donnees . '</pre>';
		$donnees_json = json_decode($donnees);
		//echo '<pre>' . print_r($donnees_json, true) . '</pre><hr />';
		if ($donnees_json) {
			foreach($donnees_json as $donnees_massif) {
				$horaires = '';
				$donnees_massifs["{$donnees_massif->massif}"] = $donnees_massif->heures;
			}
			if ($donnees_massifs) {
				echo "<h3>Bulletins du " . date("d/m/Y", $ts) . "</h3>";
				$content .= '<ul class="">';
				foreach($donnees_massifs as $massif => $dates) {
					$content .= "<li>{$massif} : ";
						if (!empty($dates)) {
							foreach($dates as $date) {
								$heure = substr($date, 6, 2) . "/" . substr($date, 4, 2) . " " . substr($date, 0, 4) . " à " . substr($date, 8, 2) . "h" . substr($date, 10, 2);
								$dl_url = $base_data_url . "BRA.$massif.$date.pdf";
								$content .= " <a href=\"$dl_url\" target=\"\blank\">$heure</a> ";
							}
						} else {
							$content .= "Aucun bulletin disponible";
						}
					$content .= '</li>';
				}
				$content .= '</ul>';
			}
		} else {
			$content .= "Aucun bulletin publié.";
		}
	} else {
		$content .= "La liste des bulletins n'est pas disponible.";
	}
	return $content;
}


