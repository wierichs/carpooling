<?php
/* HTML to PDF converter (basics) */
require_once($_SERVER["DOCUMENT_ROOT"]."/dompdf/dompdf_config.inc.php");

//print_r($_SERVER);
//exit();

$html = $_REQUEST["src"];

if(!$paper = $_REQUEST["paper"]) $paper = "a4";
if(!$orientation = $_REQUEST["orientation"]) $orientation = "portrait";
//$style = $_REQUEST["style"];

$dompdf = new DOMPDF();
$dompdf->load_html($html);
$dompdf->set_paper($paper, $orientation);
$dompdf->render();

$dompdf->stream("dompdf_out.pdf", array("Attachment" => false));
//exit(0);
	
?>