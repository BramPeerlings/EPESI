<?php
/**
 * ActionBar
 *
 * This class provides action bar component.
 *
 * @author Paul Bukowski <pbukowski@telaxus.com>
 * @copyright Copyright &copy; 2007, Telaxus LLC
 * @version 1.0
 * @package epesi-base-extra
 * @subpackage actionbar
 * @license SPL
 */
defined("_VALID_ACCESS") || die('Direct access forbidden');

class Base_ActionBar extends Module {

	/**
	 * Compares two action bar entries to determine order.
	 * For internal use only.
	 *
	 * @param mixed action bar entry
	 * @param mixed action bar entry
	 * @return int comparison result
	 */
	public function compare($a, $b) {
		$ret = Base_ActionBarCommon::$available_icons[$a['icon']]-Base_ActionBarCommon::$available_icons[$b['icon']];
		if($ret==0) $ret = strcmp($a['label'],$b['label']);
		return $ret;
	}

	public function compare_launcher($a, $b) {
		return strcmp($a['label'],$b['label']);
	}

	/**
	 * Displays action bar.
	 */
	public function body() {
		$icons = Base_ActionBarCommon::get();
		$l = & $this->init_module('Base/Lang');

		if(Acl::is_user())
			$display_settings = Base_User_SettingsCommon::get('Base/ActionBar','display');
		else
			$display_settings = 'both';
		$display_icon = ($display_settings == 'both' || $display_settings == 'icons only');
		$display_text = ($display_settings == 'both' || $display_settings == 'text only');

		//sort
		usort($icons, array($this,'compare'));

		//translate
		foreach($icons as &$i) {
			$i['label'] = $l->ht($i['label']);
			$i['description'] = $l->ht($i['description']);
			if($display_text)
				if($i['description'])
					$t = Utils_TooltipCommon::open_tag_attrs($i['description']);
				else
					$t = '';
			else
				$t = Utils_TooltipCommon::open_tag_attrs($i['label'].(($i['description'])?' - '.$i['description']:''),false);
			$i['open'] = '<a '.$i['action'].' '.$t.'>';
			$i['close'] = '</a>';
			$i['icon'] = Base_ThemeCommon::get_template_file('Base_ActionBar','icons/'.$i['icon'].'.png');
		}


		$launcher=array();
		if(Acl::is_user()) {
			$opts = Base_Menu_QuickAccessCommon::get_options();
			if(!empty($opts)) {
				$dash = ($mod=ModuleManager::get_instance('/Base_Box|0')) && ($main=$mod->get_main_module()) && $main->get_type()=='Base_Dashboard';
				$launchpad = array();
				foreach ($opts as $k=>$v) {
					if($dash && Base_User_SettingsCommon::get('Base_Menu_QuickAccess',$v['name'].'_d')) {
						$ii = array();
						$trimmed_label = substr(strrchr($v['label'],':'),1);
						$ii['label'] = $trimmed_label?$trimmed_label:$v['label'];
						$ii['description'] = $v['label'];
						$ii['open'] = '<a '.$this->create_href($v['link']).'>';
						$ii['close'] = '</a>';
						try {
							if(isset($v['link']['__icon__']))
								$icon = Base_ThemeCommon::get_template_file($v['module'],$v['link']['__icon__']);
							else
								$icon = Base_ThemeCommon::get_template_file($v['module'],'icon.png');
						} catch(Exception $e) {
							$icon = Base_ThemeCommon::get_template_file($this->get_type(),'default_icon.png');
						}
						$ii['icon'] = $icon;
						$launcher[] = $ii;
					}
					if (Base_User_SettingsCommon::get('Base_Menu_QuickAccess',$v['name'].'_l')) {
						$ii = array();
						$trimmed_label = substr(strrchr($v['label'],':'),1);
						$ii['label'] = $trimmed_label?$trimmed_label:$v['label'];
						$ii['description'] = $v['label'];
						$ii['open'] = '<a onClick="actionbar_launchpad_deactivate();'.$this->create_href_js($v['link']).'" href="javascript:void(0)">';
						$ii['close'] = '</a>';
						try {
							if(isset($v['link']['__icon__']))
								$icon = Base_ThemeCommon::get_template_file($v['module'],$v['link']['__icon__']);
							else
								$icon = Base_ThemeCommon::get_template_file($v['module'],'icon.png');
						} catch(Exception $e) {
							$icon = Base_ThemeCommon::get_template_file($this->get_type(),'default_icon.png');
						}
						$ii['icon'] = $icon;
						$launchpad[] = $ii;
					}
				}
				usort($launchpad,array($this,'compare_launcher'));
				if(!empty($launchpad)) {
					$icon = Base_ThemeCommon::get_template_file($this->get_type(),'launcher.png');
					$th = & $this->pack_module('Base/Theme');
					$th->assign('display_icon',$display_icon);
					$th->assign('display_text',$display_text);
					usort($launchpad,array($this,'compare_launcher'));
					$th->assign('icons',$launchpad);
					eval_js_once('actionbar_launchpad_deactivate = function(){leightbox_deactivate(\'actionbar_launchpad\');}');
					ob_start();
					$th->display('launchpad');
					$lp_out = ob_get_clean();
					Libs_LeightboxCommon::display('actionbar_launchpad',$lp_out,$l->t('Launchpad'));
					$launcher[] = array('label'=>'Launchpad','description'=>'Quick modules launcher','open'=>'<a '.Libs_LeightboxCommon::get_open_href('actionbar_launchpad').'>','close'=>'</a>','icon'=>$icon);
				}
			}
		}

		//display
		$th = & $this->pack_module('Base/Theme');
		$th->assign('display_icon',$display_icon);
		$th->assign('display_text',$display_text);
		$th->assign('icons',$icons);
		$th->assign('launcher',array_reverse($launcher));
		$th->display();
	}

}

?>
