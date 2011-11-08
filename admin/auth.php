<?php
require_once('include.php');

if (DEMO_MODE) die('Feature unavailable');

$lpa = ModuleManager::get_load_priority_array();
$load = array('Base_User'=>1,'Base_User_Login'=>1);

ModuleManager::$not_loaded_modules = $lpa;
ModuleManager::$loaded_modules = array();
foreach($lpa as $row) {
	$module = $row['name'];
	$version = $row['version'];
	ModuleManager :: include_common($module, $version);
	ModuleManager :: register($module, $version, ModuleManager::$modules);
	if(isset($load[$module])) {
		unset($load[$module]);
		if(empty($load)) break;
	}
}


try {
$anonymous = Variable::get('anonymous_setup');
} catch(NoSuchVariableException $e) {
$anonymous = true;
}

if((!Acl::is_user() || !Acl::check('Administration','Main','Users',Acl::get_user())) && !$anonymous) {
	$form = new HTML_QuickForm('loginform','post',$_SERVER['PHP_SELF'].'?'.http_build_query($_GET));
	$form->addElement('html', '<CENTER>Admin Tools<HR>');
	$form->addElement('text','user','Login');
	$form->addRule('user','Field required','required');
	$form->addElement('password','pass','Password');
	$form->addRule('pass','Field required','required');
	$form->registerRule('check_login', 'callback', 'submit_login');
	$form->addRule('user', 'Login or password incorrect', 'check_login', $form);
	$form->addElement('submit',null,'Ok');
	$form->addElement('html', '</CENTER>');
	if($form->validate()) {
		$user = $form->exportValue('user');
		Acl::set_user(Base_UserCommon::get_user_id($user));
	} else {
		$form->display();
		closetable();
		pagefooter();
		exit();
	}
}



function submit_login($username,$form) {
	return ModuleManager::is_installed('Base_User_Login')>=0 && Base_User_LoginCommon::check_login($username, $form->exportValue('pass')) && Acl::check('Administration','Main','Users',Base_UserCommon::get_user_id($username));
}
?>
