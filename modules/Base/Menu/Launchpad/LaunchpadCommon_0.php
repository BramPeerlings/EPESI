<?php
/**
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Base_Menu_LaunchpadCommon extends ModuleCommon {
	private static $options = null;

	public static function user_settings() {
		$ret_opts = array();
		foreach(self::get_options() as $opt) {
			unset($opt['link']);
			$name = $opt['name'];
			unset($opt['name']);
			$opt = array_merge($opt,array(
						'type'=>'bool',
						'reload'=>true,
						'default'=>0
						)
			);

			$elem = array_merge($opt, array(
					'values'=>__('Launchpad'),
					'name'=>$name
				)
			);

			$ret_opts[] = array(
				'type'=>'group',
				'label'=>$opt['label'],
				'elems'=> array($elem)
			);
		}
		//trigger_error(print_r($ret_opts,true));
		if (Acl::is_user()) return array(__('Launchpad')=>$ret_opts);
		return array();
	}

	public static function get_options() {
		static $user;
		if (isset(self::$options) && $user==Acl::get_user()) return self::$options;
		$user = Acl::get_user();
		self::$options = array();
		$modules_menu = array();

		$menus = Base_MenuCommon::get_menus();
		ksort($menus);
		foreach($menus as $name=>$ret) {
			if ($name=='Base_Admin') continue;
			if ($name=='Base_Menu_Launchpad') continue;
			Base_MenuCommon::add_default_menu($ret, $name);
			$modules_menu = array_merge($modules_menu,self::check_for_links('',$ret,$name));
		}
		self::$options = & $modules_menu;
		return self::$options;
	}

	private static function check_for_links($prefix,$array,$mod,$prefixt=''){
		$result = array();
		foreach($array as $k=>$v){
			if (substr($k,0,2)=='__') continue;
			$c_pre = $prefixt._V($k); // ****** Menu options label
			if (is_array($v) && array_key_exists('__submenu__',$v)) $result = array_merge($result,self::check_for_links($prefix.$k.': ',$v,$mod,$c_pre.': '));
			elseif(is_array($v)) {
				$result[] = array('name'=>md5($prefix.$k)
							,'link'=>$v
							,'label'=>$c_pre
							,'module'=>$mod);
			}
		}
		return $result;
	}
}

?>
