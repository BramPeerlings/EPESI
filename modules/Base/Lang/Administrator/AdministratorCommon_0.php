<?php
/**
 * Lang_Administrator class.
 * 
 * @author Paul Bukowski <pbukowski@telaxus.com>
 * @copyright Copyright &copy; 2008, Telaxus LLC
 * @license MIT
 * @version 1.0
 * @package epesi-base
 * @subpackage lang-administrator
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Base_Lang_AdministratorCommon extends Base_AdminModuleCommon {
	public static function admin_caption() {
		return 'Language & Translations';
	}

	public static function user_settings(){
		if(!Variable::get('allow_lang_change')) return null;
		if(DEMO_MODE && Base_UserCommon::get_my_user_login()=='admin') {
			$langs = array('en'=>'en');
		} else {
			$langs = Base_LangCommon::get_installed_langs();
		}
		return array('Regional settings'=>array(
			array('type'=>'header','label'=>'Language','name'=>null),
			array('name'=>'language','label'=>'Language you want to use','type'=>'select','values'=>$langs,'translate'=>false,'default'=>Variable::get('default_lang'))
			));
	}
	
}

?>