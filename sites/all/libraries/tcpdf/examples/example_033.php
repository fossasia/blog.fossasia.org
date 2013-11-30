<?php
//============================================================+
// File name   : example_033.php
// Begin       : 2008-06-24
// Last Update : 2009-09-30
// 
// Description : Example 033 for TCPDF class
//               Mixed font types
// 
// Author: Nicola Asuni
// 
// (c) Copyright:
//               Nicola Asuni
//               Tecnick.com s.r.l.
//               Via Della Pace, 11
//               09044 Quartucciu (CA)
//               ITALY
//               www.tecnick.com
//               info@tecnick.com
//============================================================+

/**
 * Creates an example PDF TEST document using TCPDF
 * @package com.tecnick.tcpdf
 * @abstract TCPDF - Example: Mixed font types
 * @author Nicola Asuni
 * @copyright 2004-2009 Nicola Asuni - Tecnick.com S.r.l (www.tecnick.com) Via Della Pace, 11 - 09044 - Quartucciu (CA) - ITALY - www.tecnick.com - info@tecnick.com
 * @link http://tcpdf.org
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 * @since 2008-06-24
 */

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false); 

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 033');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

//set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO); 

//set some language-dependent strings
$pdf->setLanguageArray($l); 

// ---------------------------------------------------------

// add a page
$pdf->AddPage();

$pdf->SetFont('dejavusans', '', 10);
$pdf->MultiCell(80, 0, "[True Type Unicode font] : Cras eros leo, porttitor porta, accumsan fermentum, ornare ac, est. Praesent dui lorem, imperdiet at, cursus sed, facilisis aliquam, nibh. Nulla accumsan nonummy diam. Donec tempus. Etiam posuere. Proin lectus. Donec purus. Duis in sem pretium urna feugiat vehicula. Ut suscipit velit eget massa. Nam nonummy, enim commodo euismod placerat, tortor elit tempus lectus, quis suscipit metus lorem blandit turpis.\n", 1, 'J', 0, 1, '', '', true, 0);

$pdf->Ln(2);

$pdf->SetFont('times', '', 10);
$pdf->MultiCell(80, 0, "[Core font] : Cras eros leo, porttitor porta, accumsan fermentum, ornare ac, est. Praesent dui lorem, imperdiet at, cursus sed, facilisis aliquam, nibh. Nulla accumsan nonummy diam. Donec tempus. Etiam posuere. Proin lectus. Donec purus. Duis in sem pretium urna feugiat vehicula. Ut suscipit velit eget massa. Nam nonummy, enim commodo euismod placerat, tortor elit tempus lectus, quis suscipit metus lorem blandit turpis.\n", 1, 'J', 0, 1, '', '', true, 0);

$pdf->Ln(2);

$pdf->SetFont('arialunicid0', '', 9);
$pdf->MultiCell(80, 0, "[CID-0 font] : Cras eros leo, porttitor porta, accumsan fermentum, ornare ac, est. Praesent dui lorem, imperdiet at, cursus sed, facilisis aliquam, nibh. Nulla accumsan nonummy diam. Donec tempus. Etiam posuere. Proin lectus. Donec purus. Duis in sem pretium urna feugiat vehicula. Ut suscipit velit eget massa. Nam nonummy, enim commodo euismod placerat, tortor elit tempus lectus, quis suscipit metus lorem blandit turpis.\n", 1, 'J', 0, 1, '', '', true, 0);


// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_033.pdf', 'I');

//============================================================+
// END OF FILE                                                 
//============================================================+
?>
