<?php
/**
 * @author Florian DANIEL aka Facyla janvier 2022
 * License AGPLv3 https://www.gnu.org/licenses/agpl-3.0.fr.html#license-text
 */

//$today = date("Ymd", time() - 3600*24);
$today = date("Ymd");

// Liste des données dispo / massif : "/donnees_libres/Pdf/BRA/bra."+$( "#datepicker" ).val()+".json", soit par ex.  => indique pour chaque massif l'heure de relevé de ce jour
$data_url = "https://donneespubliques.meteofrance.fr/donnees_libres/Pdf/BRA/bra.$today.json";

/* CONFIGURATION */
// Sécurisation niveau 0 : avec une clef secrète quelconque - l'envoi d'email n'est déclenché que si on fournit la clef
$script_auth_key = 'ahb_une_clef_secrete_un_peu_longue_pouvant_etre passee_via_une_url_4engah7';
// Massifs sélectionnés
$mail_massifs = ["COUSERANS", "HAUTE-ARIEGE", "ANDORRE", "ORLU__ST_BARTHELEMY", "CAPCIR-PUYMORENS", "LUCHONNAIS", "VERCORS", "CHARTREUSE", "BELLEDONNE", "OISANS"];
// Destinataire et Expéditeur
$mailto = 'recipient @ domain .tld'; // email du destinataire
$mailfrom = "Script BRA <sender @ domain . tld>"; // Nom et email de l'expéditeur
$subject = "Bulletins d'estimation des risques d'avalanche du " . date("d/m/Y"); // Sujet du mail

// Ensure only an authenticated, parametered URL will send an email
$auth_key = @$_GET['auth_key'];
$auth = false;
$send_email = false;
if ($auth_key == $script_auth_key) {
	if ($_GET['send_email'] == 'yes') { $send_email = true; }
}
// OVH shared hosting cron does not support parameters: instead put the script in a restricted access directory and use not parameter
$auth = true; $send_email = true;


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
$dls = []; // URLs des fichiers à télécharger - et qui seront mis en cache pour pas abuser...

// Données disponibles
$donnees = file_get_contents($data_url);

//echo '<hr />';
echo '<h3>Liste des Bulletins d\'estimation du risque d\'avalanche disponibles</h3>';
//echo $data_url;
if ($donnees) {
	//echo '<pre>' . $donnees . '</pre>';
	$donnees_json = json_decode($donnees);
	//echo '<pre>' . print_r($donnees_json, true) . '</pre><hr />';
	foreach($donnees_json as $donnees_massif) {
		$horaires = '';
		$donnees_massifs["{$donnees_massif->massif}"] = $donnees_massif->heures;
	}
	//echo '<pre>' . print_r($donnees_massifs, true) . '</pre><hr />';

	echo '<ul class="">';
	foreach($donnees_massifs as $massif => $dates) {
		echo "<li>{$massif} : ";
			if (!empty($dates)) {
				foreach($dates as $date) {
					$heure = substr($date, 6, 2) . "/" . substr($date, 4, 2) . " " . substr($date, 0, 4) . " à " . substr($date, 8, 2) . "h" . substr($date, 10, 2);
					$dl_url = "https://donneespubliques.meteofrance.fr/donnees_libres/Pdf/BRA/BRA.$massif.$date.pdf";
					$dls[$massif] = $dl_url;
					echo " <a href=\"$dl_url\" target=\"\blank\">$heure</a> ";
				}
			} else {
				echo "Aucun bulletin disponible";
			}
		echo '</li>';
	}
	echo '</ul>';
}


// These features are restricted to known users
if ($auth) {
	// DL, cache files to bra_files/ folder, and prepare them as attachments
	echo "<h3>Récupération des prévisions pour les massifs sélectionnés</h3>";

	// Prepare email and send files as attachment
	$message = "Bulletins PDF en pièce jointe pour les massifs : " . implode(', ', $mail_massifs);

	// carriage return type (RFC)
	$eol = "\r\n";
	// a random hash will be necessary to send mixed content
	$separator = md5(time());

	// Prepare attachments
	$email_attachments = '';
	echo '<ul>';
	foreach($dls as $massif => $dl_url) {
		// DL only useful files
		if (!in_array($massif, $mail_massifs)) { continue; }
		echo '<li>';
		$data_folder = dirname(__FILE__) . '/bra_files/';
		if (!is_dir($data_folder)) {
			if (!mkdir($data_folder, 0750, true)) {
				echo "Impossible de créer le dossier de cache";
			}
		}
		if (is_dir($data_folder)) {
			$file_name = parse_url($dl_url);
			$file_name = explode('/', $file_name['path']);
			$file_name = end($file_name);
			$local_filename = $data_folder . $file_name;
			// Save a copy locally
			if (!file_exists($local_filename)) {
				$file_content = bra_get_file($dl_url);
				bra_write_file($local_filename, $file_content);
				echo "$massif : $dl_url récupéré et mis en cache <a href=\"bra_files/$file_name\" target=\"_blank\">$file_name</a>";
			} else {
				echo "$massif : fichier déjà disponible <a href=\"bra_files/$file_name\" target=\"_blank\">$file_name</a>";
			}
			// Add to email attachments
			$email_file_content = file_get_contents($local_filename);
			$email_attachment_data = chunk_split(base64_encode($email_file_content));
			$email_attachments .= "Content-Type: application/octet-stream; name=\"" . $file_name . "\"" . $eol;
			$email_attachments .= "Content-Transfer-Encoding: base64" . $eol;
			$email_attachments .= "Content-Disposition: attachment; filename=\"" . $file_name . "\"" . $eol;
			$email_attachments .= $email_attachment_data . $eol;
			$email_attachments .= "--" . $separator . $eol;
		}
		echo '</li>';
	}
	echo '</ul>';


	// main header (multipart mandatory)
	$headers = "From: $mailfrom" . $eol;
	$headers .= "MIME-Version: 1.0" . $eol;
	$headers .= "Content-Type: multipart/mixed; boundary=\"" . $separator . "\"" . $eol;
	$headers .= "Content-Transfer-Encoding: 7bit" . $eol;
	$headers .= "This is a MIME encoded message." . $eol;

	// message
	$body = "--" . $separator . $eol;
	$body .= "Content-Type: text/plain; charset=\"utf-8\"" . $eol;
	$body .= "Content-Transfer-Encoding: 8bit" . $eol;
	$body .= $message . $eol;
	$body .= "--" . $separator . $eol;

	// attachments
	$body .= "--" . $separator . $eol;
	$body .= $email_attachments;

	// SEND Mail
	if ($send_email) {
		echo "<h3>Envoi d'un email avec les prévisions des massifs sélectionnés</h3>";
		if (mail($mailto, $subject, $body, $headers)) {
			echo "Email envoyé avec les PDF en pièces jointes à $mailto";
		} else {
			echo "ERREUR lors de l'envoi : aucun email envoyé !";
			echo print_r( error_get_last(), true );
		}
	}
}




/* Gets a file from an URL */
function bra_get_file($url) {
	// File retrieval can fail on timeout or redirects, so make it more failsafe
	$context = stream_context_create(array('http' => array('max_redirects' => 5, 'timeout' => 60)));
	// using timestamp and URL hash for quick retrieval based on time and URL source unicity
	return file_get_contents($url, false, $context);
}

// Write file to disk
function bra_write_file($target_file = "", $content = '') {
	if ($fp = fopen($target_file, 'w')) {
		fwrite($fp, $content);
		fclose($fp);
		return true;
	}
	return false;
}
