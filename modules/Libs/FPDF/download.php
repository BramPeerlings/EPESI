<?php
/**
 * Download file
 * 
 * @author Paul Bukowski <pbukowski@telaxus.com>
 * @copyright Copyright &copy; 2006, Telaxus LLC
 * @version 1.0
 * @license SPL
 * @package epesi-libs
 * @subpackage fpdf
 */
if(!isset($_REQUEST['id']) || !isset($_REQUEST['pdf']) || !isset($_REQUEST['filename'])) die('Invalid usage');
$id = $_REQUEST['id'];
$pdf_id = $_REQUEST['pdf'];
$filename = $_REQUEST['filename'];

define('CID', $id);
require_once('../../../include.php');

$buffer = Module::static_get_module_variable($pdf_id,'pdf',null);
session_commit();

if(headers_sent())
    die('Some data has already been output to browser, can\'t send PDF file');
header('Content-Type: application/pdf');
header('Content-Length: '.strlen($buffer));
header('Content-disposition: inline; filename="'.$filename.'"');
echo $buffer;
?>
