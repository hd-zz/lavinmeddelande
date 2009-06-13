<?php


	require("../_siteconfig/config.lavin.php");
	header("Content-Type: text/html; charset=utf-8");




	// Filen där lavinmeddelandet temporärt sparas
	// Katalogen måste vara skrivbar av användaren PHP exekverar som
	$local_filename = "export/lavin.html";



	$output = '';
	$ssh_errors = array();
	if(isset($_POST["post"]) || isset($_POST["remove"])) {

		if(isset($_POST["post"])) {
			$output .= '<div class="alert info-lavin">';
			$output .= '<h2></h2><p>';
			$output .= htmlspecialchars($_POST["meddelande"]);
			if(isset($_POST["lank"]))
				$output .= ' <a href="'. htmlspecialchars($_POST["lank"]) .'" class="morelnk">Läs mer</a>';
			$output .= '</p></div>';
		}


		$fd = fopen($local_filename, "w");
		fwrite($fd, $output);
		fclose($fd);



		foreach($ssh_hosts as $ssh_host) {
 	      		if(($ssh = @ssh2_connect($ssh_host["host"])) === FALSE) {
				$e =  "FEL: Lavinmeddelande INTE publicerat.<br />Orsak: Failed to connect to ". $ssh_host["host"];
				$ssh_errors[] = $e;
				continue;
			}
	
			if((@ssh2_auth_pubkey_file($ssh, $ssh_host["user"], $ssh_host["keyp"], $ssh_host["key"], "")) === FALSE) {
				$e =  "FEL: Lavinmeddelande INTE publicerat.<br />Orsak: Failed to authenticate as ". $ssh_host["user"] ." to ". $ssh_host["host"] ." (local file: $local_filename)";
				$ssh_errors[] = $e;
				continue;
			}
	
			$ssh_filename = $ssh_host["path"];
			if(@ssh2_scp_send($ssh, $local_filename, $ssh_filename) === FALSE) {
				$e = "FEL: Lavinmeddelande INTE publicerat.<br />Orsak: Failed to scp $local_filename to ". $ssh_host["user"] ."@". $ssh_host["host"] .":". $ssh_filename;
				$ssh_errors[] = $e;
       				continue;
			}
		}


		// Pga en bugg i libssh så misslyckas ssh2_auth_pubkey_file() ibland
		// Om vi fått fel försöker vi igen, då brukar det gå.
		foreach($ssh_hosts as $ssh_host) {

			// Gör inte kopieringen igen om föregående block lyckades kopiera filen
			if(count($ssh_errors) == 0)
				continue;


 	      		if(($ssh = @ssh2_connect($ssh_host["host"])) === FALSE) {
				$e =  "FEL: Lavinmeddelande INTE publicerat.<br />Orsak: Failed to connect to ". $ssh_host["host"];
				continue;
			}
	
			if((@ssh2_auth_pubkey_file($ssh, $ssh_host["user"], $ssh_host["keyp"], $ssh_host["key"], "")) === FALSE) {
				$e =  "FEL: Lavinmeddelande INTE publicerat.<br />Orsak: Failed to authenticate as ". $ssh_host["user"] ." to ". $ssh_host["host"] ." (local file: $local_filename)";
				continue;
			}
	
			$ssh_filename = $ssh_host["path"];
			if(@ssh2_scp_send($ssh, $local_filename, $ssh_filename) === FALSE) {
				$e = "FEL: Lavinmeddelande INTE publicerat.<br />Orsak: Failed to scp $local_filename to ". $ssh_host["user"] ."@". $ssh_host["host"] .":". $ssh_filename;
       				continue;
			}
		}


	} // if publicera || ta bort



	// Används för formuläret och för att visa aktuellt meddelande
	$meddelande = "";
	$lank = "";

	$html = file_get_contents($local_filename);
	if(preg_match('@<p>([^<]*)@', $html, $m))
		$meddelande = trim($m[1]);
	if(preg_match('@href="([^"]*)"@', $html, $m))
		$lank = $m[1];



?>

<html>
<head>
<title>Lägg upp lavinmeddelande på hd.se</title>
<script type="text/javascript">
function copyLabel(e) {

	// Container-elementet
	var td = e.parentElement;

	// Vi kommer få en textnod eftersom det är whitespace mellan td-elementen
	var textNode = td.nextSibling;

	// Efter textnoden med whitespacet hittar vi nästa td
	var nextCell = textNode.nextSibling;

	// Label-elementet är första childen
	var label = nextCell.firstChild;

	// Noden under label-elementet är en textnod med texten vi är ute efter
	textNode = label.firstChild;
	var msg = textNode.nodeValue;

	// Sätt innehållet i textarean till label's värde
	document.getElementById('meddelande').innerHTML = msg;

	return true;
}
</script>

<style type="text/css">
body, html {
	font-size: 1.1em;
	font-family: arial;
}

#wrapper {
	width: 800px;
	margin: 10 auto;
}
.note {
	color: green;
	font-weight: bold;
	border: 1px dashed gray;
	padding: 5px;
}
</style>

</head>


<body>
<div id="wrapper">
<?php
	if(count($ssh_errors)) {
?>
	<h1 style="color:red">Ett eller flera fel uppstod vid publiceringen av lavinmeddelandet</h1>
	<?php
		foreach($ssh_errors as $e) {
?>
	<p><?= $e ?></p>
<?php
		} // foreach

	} // if errors > 0
?>
	<h1>Lavinmeddelande</h1>
	<h3>Lavinmeddelade läggs upp av kundcenters personal (vid tryck/distributionsproblem), ansvarig nyhetschef, eller webbredaktionen</h3>


<?php
	if(isset($_POST["post"]) && count($ssh_errors) == 0) {
?>
	<p class="note">Lavinmeddelandet publicerat på <a href="http://hd.se/" target="_blank">hd.se</a></p>
<?php
	}
	else if(isset($_POST["remove"]) && count($ssh_errors) == 0) {
?>
	<p class="note">Lavinmeddelandet borttaget från <a href="http://hd.se/" target="_blank">hd.se</a>.<br />
	Det kan dröja någon minut innan ändringen syns på sajten.</p>
<?php
	}
?>

	<p>Klicka på lämplig knapp här nedan eller skriv in ett eget meddelande i rutan.<br />
	Du kan också komplettera med en länk om det finns mer info på någon annan webbadress.</p>
	<p>Tänk på att det du skriver går ut på HELA hd.se.<br />
	Tänk också på att du som lägger upp lavinmeddelandet ansvarar för att ta bort det så snart det inte är aktuellt längre.</p>

	<h3>Standardfraser</h3>
	<table cellspacing="10">
		<tr>
			<td><input type="radio" name="dummy" id="std-1"  onchange="return copyLabel(this)" /></td>
			<td><label for="std-1">På grund av problem med tryckningen i natt är distributionen av morgontidningarna försenad. Utdelningen pågår fortfarande.</label></td>
		</tr>
		<tr>
			<td><input type="radio" name="dummy" id="std-2" onchange="return copyLabel(this)" /></td>
			<td><label for="std-2">På grund av problem med tryckningen i natt är distributionen av morgontidningarna försenad. Utdelningen pågår fram till klockan nio.</label></td>
		</tr>
		<tr>
			<td><input type="radio" name="dummy" id="std-3" onchange="return copyLabel(this)" /></td>
			<td><label for="std-3">Utdelningen av morgontidningarna är försenad idag och pågår fortfarande.</label></td>
		</tr>
		<tr>
			<td><input type="radio" name="dummy" id="std-4" onchange="return copyLabel(this)" /></td>
			<td><label for="std-4">På grund av problem med rådande väderförhållanden är distributionen av morgontidningarna försenad. Utdelningen pågår fortfarande.</label></td>
		</tr>
		<tr>
			<td><input type="radio" name="dummy" id="std-5" onchange="return copyLabel(this)" /></td>
			<td><label for="std-5">Dagens tidning på hd.se är tyvärr försenad. Vi jobbar på problemet och hoppas snart ha löst det.</label></td>
		</tr>
	</table>


	<form action="/lavin/" method="post">

	<p><strong>Färdigt meddelande med plats för komplettering</strong> (tex angivelse av viss stad) eller fritext</p>
	<textarea name="meddelande" id="meddelande" cols="60" rows="4"><?= htmlspecialchars($meddelande) ?></textarea>

	<p><strong>Eventuell länk för mer info</strong> (inled med http://)<br />
	<input type="text" name="lank" value="<?= htmlspecialchars($lank) ?>" style="width: 450px" />
	</p>

	<input type="submit" name="post" value="Lägg ut lavinmeddlande" />
	<input type="submit" name="remove" value="Ta bort lavinmeddelande" />

	</form>

	<hr />
	<h3>Aktuellt meddelande</h3>
	<?php
		if(!empty($meddelande)) {
			echo '<p class="note">'. htmlspecialchars($meddelande);
			
			if(!empty($lank))
				echo '<br /><a href="'. htmlspecialchars($lank) .'">Läs mer</a>';

			echo '</p>';
		}
		else
			echo '<p>Det finns inget lavinmeddelandet publicerat just nu</p>';
	?>


	<hr />
	<h3>Övrigt</h3>
	<p>För ändringar av standardfraser eller övriga frågor, kontakta<br />
	Noah Williamsson &lt;noah@hd.se&gt;</p>
</div>
</body>

</html>
