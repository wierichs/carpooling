<?php
// Functions...
function Translate($Text, $Language)
{
    $file = "{$Language}.lng";
    if(file_exists($file))
    {
        $translation = file($file);
        foreach($translation as $line_num => $line)
        {
            $text = explode("|", trim($line));
            if($text[0] == trim($Text))
                return $text[1];
        }
    }
    return $Text;
}
function GetRole($rights)
{
    if(is_nan($rights))
        $rights = strtolower($rights);
    switch($rights)
    {
        case 1:
        case "use":
            return "User";
            break;
        case 2:
        case "administration":
            return "Administrator";
            break;
        case 3:
        case "develop":
            return "Developer";
            break;
        default:
            return "Guest";
            break;
    }
    return "Guest";
}
function GetRight($role)
{
    if(is_nan($role))
        $role = strtolower($role);
    switch($rights)
    {
        case 1:
        case "user":
            return "Use";
            break;
        case 2:
        case "administrator":
            return "Administration";
            break;
        case 3:
        case "Developer":
            return "Develop";
            break;
        default:
            return "View";
            break;
    }
    return "View";
}
function createTable($table_data, $strecken, $format = "txt", $style = "")
{
	$output = "";
	$language = $_SESSION["lang"];
	switch($format)
	{
		case "txt":
			$output .= "\"".Translate("Date", $language)."\"\t\"".Translate("Occupants", $language)."\"\t\"".Translate("Distance", $language)."\"\t\"".Translate("Consumption (per 100 km)", $language)."\"\t\"".Translate("Price/liter", $language)."\"\t\"".Translate("Price/distance", $language)."\"\t\"".Translate("Price/occupant", $language)."\"\t\"".Translate("Summary", $language)."/".Translate("Person", $language)."\"\t\"".Translate("Pay to", $language)."\"\t\"".Translate("payed", $language)."\"\r\n";
			foreach ($table_data as $row)
			{
				$date = new DateTime($row['Datum']);
				$day = date_format($date, 'l');
				$date_string = "{$row['Datum']} (".Translate($day, $language).")";
				$output .= "\"".$date_string."\"\t";
				$output .= "\"".$row['Insassen']."\"\t";
				
				$strecken_namen = array_keys($strecken);
				for($i=0; $i<count($strecken_namen); ++$i)
				{
					$value = $strecken["$strecken_namen[$i]"];
					if($strecken["$strecken_namen[$i]"] == $row['Strecke'])
					{
						$strecke = Translate($strecken_namen[$i], $language)." ({$value} km)";
						break;
					}
				}
				
				$output .= "\"".$strecke."\"\t";
				$output .= "\"".$row['Verbrauch']."\"\t";
				$output .= "\"".$row['Literpreis']."\"\t";
				
				$streckenpreis = $row['Verbrauch'] * $row['Literpreis'] * $row['Strecke'] /100;
				$output .= "\"".$streckenpreis."\"\t";
				
				$preis = $streckenpreis / $row['Insassen'];
				$output .= "\"".$preis."\"\t";
				
				$bezahlt = explode(";", $row['Bezahlt']);
				
				$personen_offen = $row['Insassen'];
				if(is_array($bezahlt))
				$personen_offen = intval($row['Insassen']) - count($bezahlt);
				else
				$personen_offen = intval($row['Insassen']) - $bezahlt;
				
				if($personen_offen == 0)
				{
					$output .= "\"€ 0,-\"\t";
				}
				else
				{
					$summe += ($preis * $personen_offen);
					$output .= "\"€ ".number_format($summe, 2, ',', '.')." (€ ".$summe.")\"\t";
				}
				$bezahlt = count(explode(";", $row['Bezahlt']));
				$insassen = $row['Insassen'];
				if($bezahlt < $insassen)
				$output .= "\"".$row['Fahrer']."\"\t";
				else
				$output .= "\"\"\t";
				
				$output .= "\"".$bezahlt." von ".$insassen."\"\r\n";
			}
			break;
		case "html":
			$output = "";
			$output .= "<HTML><head><style>{$style}</style><BODY><TABLE>\r\n";
			$output .= "<TR VALIGN=TOP><TH>".Translate("Date", $language)."</TH><TH>".Translate("Occupants", $language)."</TH><TH>".Translate("Distance", $language)."</TH><TH>".Translate("Consumption (per 100 km)", $language)."</TH><TH>".Translate("Price/liter", $language)."</TH><TH>".Translate("Price/distance", $language)."</TH><TH>".Translate("Price/occupant", $language)."</TH><TH>".Translate("Summary", $language)."/".Translate("Person", $language)."</TH><TH>".Translate("Pay to", $language)."</TH><TH>".Translate("payed", $language)."</TH></TR>\r\n";
			foreach ($table_data as $row)
			{
				
				$output .= "<TR VALIGN=TOP>";
				$date = new DateTime($row['Datum']);
				$day = date_format($date, 'l');
				$date_string = "{$row['Datum']} (".Translate($day, $language).")";
				$output .= "<TD><nobr>{$date_string}</nobr></TD>";
				$output .= "<TD>".$row['Insassen']."</TD>";
				
				$strecken_namen = array_keys($strecken);
				for($i=0; $i<count($strecken_namen); ++$i)
				{
					$value = $strecken["$strecken_namen[$i]"];
					if($strecken["$strecken_namen[$i]"] == $row['Strecke'])
					{
						$strecke = Translate($strecken_namen[$i], $language)." ({$value} km)";
						break;
					}
				}
				
				$output .= "<TD><nobr>".$strecke."</nobr></TD>";
				$output .= "<TD>".$row['Verbrauch']."</TD>";
				$output .= "<TD>".$row['Literpreis']."</TD>";
				
				$streckenpreis = $row['Verbrauch'] * $row['Literpreis'] * $row['Strecke'] /100;
				$output .= "<TD>".$streckenpreis."</TD>";
				
				$preis = $streckenpreis / $row['Insassen'];
				$output .= "<TD>".$preis."</TD>";
				
				$bezahlt = explode(";", $row['Bezahlt']);
				
				$personen_offen = $row['Insassen'];
				if(is_array($bezahlt))
					$personen_offen = intval($row['Insassen']) - count($bezahlt);
				else
					$personen_offen = intval($row['Insassen']) - $bezahlt;
				
				if($personen_offen == 0)
				{
					$output .= "<TD>€ 0,-</TD>";
				}
				else
				{
					$summe += ($preis * $personen_offen);
					$output .= "<TD>€ ".number_format($summe, 2, ',', '.')." <nobr>(€ ".$summe.")</nobr></TD>";
				}
				$bezahlt = count(explode(";", $row['Bezahlt']));
				$insassen = $row['Insassen'];
				if($bezahlt < $insassen)
					$output .= "<TD><nobr>{$row['Fahrer']}</nobr></TD>";
				else
					$output .= "<TD></TD>";
				
				$output .= "<TD>{$bezahlt} von {$insassen}</TD></TR>\r\n";
			}
			$output .= "</TABLE>\r\n</BODY>\r\n</HTML>";
			break;
	}
	return $output;
}

function printTable($dbh, $sql_query, $output = "txt", $style = "th { border: 1px solid black; border-colapse: colapse; }td { border: 1px solid black; border-colapse: colapse; }", $paper = "a4", $orientation = "landscape")
{
	global $db_tables;
	global $routes_table;
	global $attachment;

	$sql = "SELECT name FROM sqlite_master WHERE type = 'table';";
	foreach ($dbh->query($sql) as $row)
		array_push($db_tables, $row["name"]);
		
	$keys = array();
	$values = array();
	$sql = "SELECT * FROM {$routes_table};";
	if(in_array($routes_table, $db_tables))
	{
		foreach ($dbh->query($sql) as $row)
		{
			array_push($keys, $row["Streckenname"]);
			array_push($values, intval($row["Distanz"]));
		}
	}
	else
	{
		header("Location: {$_SERVER["PHP_SELF"]}");
		exit("<HTML><head><meta http-equiv=\"refresh\" content=\"0; URL={$_SERVER['PHP_SELF']}\" /></head><BODY><p>No data found!</p><A HREF=\"javascript.history.back();\">zurück</A></BODY></HTML>");
	}
	$strecken = array();
	if(count($keys) > 0 && count($values) > 0)
		$strecken = array_combine($keys, $values);
	else
		exit("<HTML><BODY><p>No data found! Are cookies allowed in your browser?</p><A HREF=\"javascript.history.back();\">zurück</A></BODY></HTML>");

	$filename = trim($_SESSION["db"], " \t\r\n\/\\\.")."_".date("Y-m-d H:i");
	switch($output)
	{
		case "pdf":
			require_once($_SERVER["DOCUMENT_ROOT"]."/dompdf/dompdf_config.inc.php");
			$html = createTable($dbh->query($sql_query), $strecken, $format = "html", $style);
			$dompdf = new DOMPDF();
			$dompdf->load_html($html);
			$dompdf->set_paper($paper, $orientation);
			$dompdf->render();
			$dompdf->stream($filename.".pdf", array("Attachment" => $attachment));
			break;
		case "csv":
			$html = createTable($dbh->query($sql_query), $strecken, $format = "txt");
			$search = array("\"\t\"");
			$replace = array("\"\,\"");
			$txt = trim(str_replace($search, $replace, $html));
//exit($txt);
			header('Content-type: text/plain');
			header('Content-Disposition: attachment; filename="'.$filename.'.csv"');
			exit($txt);
			break;
		case "ssv":
			$html = createTable($dbh->query($sql_query), $strecken, $format = "txt");
			$search = array("\"\t\"");
			$replace = array("\"\;\"");
			$txt = trim(str_replace($search, $replace, $html));
			//exit($txt);
			header('Content-type: text/plain');
			header('Content-Disposition: attachment; filename="'.$filename.'.ssv"');
			exit($txt);
			break;
		case "txt":
			$txt = createTable($dbh->query($sql_query), $strecken, $format = "txt");
			//exit($txt);
			header('Content-type: text/plain');
			header('Content-Disposition: attachment; filename="'.$filename.'.txt"');
			exit($txt);
			break;
		default:
			$html = createTable($dbh->query($sql_query), $strecken, $format = "html", $style);
			exit($html);
	}
	//unlink($tmp_file);
}
?>