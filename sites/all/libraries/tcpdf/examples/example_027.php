<?php
//============================================================+
// File name   : example_027.php
// Begin       : 2008-03-04
// Last Update : 2009-09-30
// 
// Description : Example 027 for TCPDF class
//               1D Barcodes
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
 * @abstract TCPDF - Example: 1D Barcodes.
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
$pdf->SetTitle('TCPDF Example 027');
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

// set a barcode on the page footer
$pdf->setBarcode(date('Y-m-d H:i:s'));

// set font
$pdf->SetFont('helvetica', '', 10);

// add a page
$pdf->AddPage();

$style = array(
	'position' => 'S',
	'border' => true,
	'padding' => 4,
	'fgcolor' => array(0,0,0),
	'bgcolor' => false, //array(255,255,255),
	'text' => true,
	'font' => 'helvetica',
	'fontsize' => 8,
	'stretchtext' => 4
);

// PRINT VARIOUS 1D BARCODES

// CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9.
$pdf->Cell(0, 0, 'CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9', 0, 1);
$pdf->write1DBarcode('CODE 39', 'C39', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// CODE 39 + CHECKSUM
$pdf->Cell(0, 0, 'CODE 39 + CHECKSUM', 0, 1);
$pdf->write1DBarcode('CODE 39 +', 'C39+', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// CODE 39 EXTENDED
$pdf->Cell(0, 0, 'CODE 39 EXTENDED', 0, 1);
$pdf->write1DBarcode('CODE 39 E', 'C39E', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// CODE 39 EXTENDED + CHECKSUM
$pdf->Cell(0, 0, 'CODE 39 EXTENDED + CHECKSUM', 0, 1);
$pdf->write1DBarcode('CODE 39 E+', 'C39E+', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// CODE 93 - USS-93
$pdf->Cell(0, 0, 'CODE 93 - USS-93', 0, 1);
$pdf->write1DBarcode('TEST93', 'C93', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// Standard 2 of 5
$pdf->Cell(0, 0, 'Standard 2 of 5', 0, 1);
$pdf->write1DBarcode('1234567', 'S25', '', '', 80, 30, 0.4, $style, 'N');

// add a page ----------
$pdf->AddPage();

// Standard 2 of 5 + CHECKSUM
$pdf->Cell(0, 0, 'Standard 2 of 5 + CHECKSUM', 0, 1);
$pdf->write1DBarcode('1234567', 'S25+', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// Interleaved 2 of 5
$pdf->Cell(0, 0, 'Interleaved 2 of 5', 0, 1);
$pdf->write1DBarcode('1234567', 'I25', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// Interleaved 2 of 5 + CHECKSUM
$pdf->Cell(0, 0, 'Interleaved 2 of 5 + CHECKSUM', 0, 1);
$pdf->write1DBarcode('1234567', 'I25+', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// CODE 128 A
$pdf->Cell(0, 0, 'CODE 128 A', 0, 1);
$pdf->write1DBarcode('CODE 128 A', 'C128A', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// CODE 128 B
$pdf->Cell(0, 0, 'CODE 128 B', 0, 1);
$pdf->write1DBarcode('CODE 128 B', 'C128B', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// CODE 128 C
$pdf->Cell(0, 0, 'CODE 128 C', 0, 1);
$pdf->write1DBarcode('0123456789', 'C128C', '', '', 80, 30, 0.4, $style, 'N');

// add a page ----------
$pdf->AddPage();

// EAN 8
$pdf->Cell(0, 0, 'EAN 8', 0, 1);
$pdf->write1DBarcode('1234567', 'EAN8', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// EAN 13
$pdf->Cell(0, 0, 'EAN 13', 0, 1);
$pdf->write1DBarcode('1234567890128', 'EAN13', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// 2-Digits UPC-Based Extention
$pdf->Cell(0, 0, '2-Digits UPC-Based Extention', 0, 1);
$pdf->write1DBarcode('34', 'EAN2', '', '', 20, 30, 0.4, $style, 'N');

$pdf->Ln();

// 5-Digits UPC-Based Extention
$pdf->Cell(0, 0, '5-Digits UPC-Based Extention', 0, 1);
$pdf->write1DBarcode('51234', 'EAN5', '', '', 40, 30, 0.4, $style, 'N');

$pdf->Ln();

// UPC-A
$pdf->Cell(0, 0, 'UPC-A', 0, 1);
$pdf->write1DBarcode('12345678901', 'UPCA', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// UPC-E
$pdf->Cell(0, 0, 'UPC-E', 0, 1);
$pdf->write1DBarcode('04210000526', 'UPCE', '', '', 80, 30, 0.4, $style, 'N');

// add a page ----------
$pdf->AddPage();

// MSI
$pdf->Cell(0, 0, 'MSI', 0, 1);
$pdf->write1DBarcode('80523', 'MSI', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// MSI + CHECKSUM (module 11)
$pdf->Cell(0, 0, 'MSI + CHECKSUM (module 11)', 0, 1);
$pdf->write1DBarcode('80523', 'MSI+', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// IMB - Intelligent Mail Barcode - Onecode - USPS-B-3200
$pdf->Cell(0, 0, 'IMB - Intelligent Mail Barcode - Onecode - USPS-B-3200', 0, 1);
$pdf->write1DBarcode('01234567094987654321-01234567891', 'IMB', '', '', 130, 20, 0.4, $style, 'N');

$pdf->Ln();

// POSTNET
$pdf->Cell(0, 0, 'POSTNET', 0, 1);
$pdf->write1DBarcode('98000', 'POSTNET', '', '', 80, 20, 0.4, $style, 'N');

$pdf->Ln();

// PLANET
$pdf->Cell(0, 0, 'PLANET', 0, 1);
$pdf->write1DBarcode('98000', 'PLANET', '', '', 80, 20, 0.4, $style, 'N');

$pdf->Ln();

// RMS4CC (Royal Mail 4-state Customer Code) - CBC (Customer Bar Code)
$pdf->Cell(0, 0, 'RMS4CC (Royal Mail 4-state Customer Code) - CBC (Customer Bar Code)', 0, 1);
$pdf->write1DBarcode('SN34RD1A', 'RMS4CC', '', '', 80, 20, 0.4, $style, 'N');

$pdf->Ln();

// KIX (Klant index - Customer index)
$pdf->Cell(0, 0, 'KIX (Klant index - Customer index)', 0, 1);
$pdf->write1DBarcode('SN34RDX1A', 'KIX', '', '', 80, 20, 0.4, $style, 'N');

// add a page ----------
$pdf->AddPage();

// CODABAR
$pdf->Cell(0, 0, 'CODABAR', 0, 1);
$pdf->write1DBarcode('123456789', 'CODABAR', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// CODE 11
$pdf->Cell(0, 0, 'CODE 11', 0, 1);
$pdf->write1DBarcode('123-456-789', 'CODE11', '', '', 80, 30, 0.4, $style, 'N');

$pdf->Ln();

// PHARMACODE
$pdf->Cell(0, 0, 'PHARMACODE', 0, 1);
$pdf->write1DBarcode('789', 'PHARMA', '', '', 30, 30, 0.4, $style, 'N');

$pdf->Ln();

// PHARMACODE TWO-TRACKS
$pdf->Cell(0, 0, 'PHARMACODE TWO-TRACKS', 0, 1);
$pdf->write1DBarcode('105', 'PHARMA2T', '', '', 20, 30, 0.4, $style, 'N');

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// TEST BARCDE ALIGNMENTS

// add a page
$pdf->AddPage();

// Left alignment
$style['position'] = 'L';
$pdf->write1DBarcode('LEFT', 'C128A', '', '', 180, 30, 0.4, $style, 'N');

$pdf->Ln();

// Center alignment
$style['position'] = 'C';
$pdf->write1DBarcode('CENTER', 'C128A', '', '', 180, 30, 0.4, $style, 'N');

$pdf->Ln();

// Right alignment
$style['position'] = 'R';
$pdf->write1DBarcode('RIGHT', 'C128A', '', '', 180, 30, 0.4, $style, 'N');

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_027.pdf', 'I');

//============================================================+
// END OF FILE                                                 
//============================================================+
?>
