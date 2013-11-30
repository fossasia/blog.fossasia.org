<?php
//============================================================+
// File name   : example_040.php
// Begin       : 2008-10-28
// Last Update : 2009-09-30
// 
// Description : Example 040 for TCPDF class
//               Booklet mode (double-sided pages)
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
 * @abstract TCPDF - Example: Booklet mode (double-sided pages)
 * @author Nicola Asuni
 * @copyright 2004-2009 Nicola Asuni - Tecnick.com S.r.l (www.tecnick.com) Via Della Pace, 11 - 09044 - Quartucciu (CA) - ITALY - www.tecnick.com - info@tecnick.com
 * @link http://tcpdf.org
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 * @since 2008-10-28
 */

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false); 

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 040');
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

// set booklet mode
$pdf->SetBooklet(true, 10, 30);

// set core font
$pdf->SetFont('helvetica', '', 18);

// add a page (left page)
$pdf->AddPage();

// print a line using Cell()
$pdf->Cell(0, 0, 'LEFT PAGE 1', 1, 1, 'C');

// add a page (right page)
$pdf->AddPage();

// print a line using Cell()
$pdf->Cell(0, 0, 'RIGHT PAGE 2', 1, 1, 'C');


// add a page (left page)
$pdf->AddPage();

// print a line using Cell()
$pdf->Cell(0, 0, 'LEFT PAGE 3', 1, 1, 'C');

// add a page (right page)
$pdf->AddPage();

// print a line using Cell()
$pdf->Cell(0, 0, 'RIGHT PAGE 4', 1, 1, 'C');

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_040.pdf', 'I');

//============================================================+
// END OF FILE                                                 
//============================================================+
?>
