<?php
/**
 * @author Arkadiusz Bisaga <abisaga@telaxus.com>
 * @copyright Copyright &copy; 2008, Telaxus LLC
 * @license Commercial
 * @version 1.0
 * @package epesi-premium
 * @subpackage timesheet
 */
if(!isset($_POST['module']) || !isset($_POST['original']) || !isset($_POST['new']) || !isset($_POST['cid']))
	die('alert(\'Invalid request\')');

define('JS_OUTPUT',1);
define('CID',$_POST['cid']); 
define('READ_ONLY_SESSION',true);
require_once('../../../../include.php');
ModuleManager::load_modules();

global $translations;
Base_LangCommon::load();

$module = json_decode($_POST['module']);
$original = json_decode($_POST['original']);
$new = json_decode($_POST['new']);

$translations[$module][$original] = $new;

Base_LangCommon::save();

?>