<?php
//============================================================+
// File name   : example_050.php
// Begin       : 2009-04-09
// Last Update : 2010-03-23
//
// Description : Example 050 for TCPDF class
//               2D Barcodes
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
 * @abstract TCPDF - Example: 2D barcodes.
 * @author Nicola Asuni
 * @copyright 2004-2009 Nicola Asuni - Tecnick.com S.r.l (www.tecnick.com) Via Della Pace, 11 - 09044 - Quartucciu (CA) - ITALY - www.tecnick.com - info@tecnick.com
 * @link http://tcpdf.org
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 * @since 2008-03-04
 */

require_once('../config/lang/eng.php');
require_once('../tcpdf.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 050');
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

// set font
$pdf->SetFont('helvetica', '', 10);

// add a page
$pdf->AddPage();

$style = array(
	'border' => true,
	'padding' => 4,
	'fgcolor' => array(0,0,0),
	'bgcolor' => false, //array(255,255,255)
);

// NOTE: 2D barcode algorithms must be implemented on 2dbarcode.php class file.

// write TEST 2D Barcode
$pdf->write2DBarcode('X', 'TEST', '', '', 30, 20, $style, 'N');

// ---

$pdf->Ln();
$pdf->Cell(0, 0, ' ', 0, 1);

$style = array(
	'border' => false,
	'padding' => 1,
	'fgcolor' => array(0,0,0),
	'bgcolor' => false, //array(255,255,255)
);

// QRCODE,L : QR-CODE Low error correction
$pdf->Cell(0, 0, 'QRCODE L', 0, 1);
$pdf->write2DBarcode('www.tcpdf.org', 'QRCODE,L', '', '', 30, 30, $style, 'N');

$pdf->Ln();

// QRCODE,M : QR-CODE Medium error correction
$pdf->Cell(0, 0, 'QRCODE M', 0, 1);
$pdf->write2DBarcode('www.tcpdf.org', 'QRCODE,M', '', '', 30, 30, $style, 'N');

$pdf->Ln();

// QRCODE,Q : QR-CODE Better error correction
$pdf->Cell(0, 0, 'QRCODE Q', 0, 1);
$pdf->write2DBarcode('www.tcpdf.org', 'QRCODE,Q', '', '', 30, 30, $style, 'N');

$pdf->Ln();

// QRCODE,H : QR-CODE Best error correction
$pdf->Cell(0, 0, 'QRCODE H', 0, 1);
$pdf->write2DBarcode('www.tcpdf.org', 'QRCODE,H', '', '', 30, 30, $style, 'N');

$pdf->Ln();

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_050.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
?>
