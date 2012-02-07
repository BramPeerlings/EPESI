<?php
/**
 * CRM Contacts class.
 *
 * @author Arkadiusz Bisaga <abisaga@telaxus.com>
 * @copyright Copyright &copy; 2008, Telaxus LLC
 * @license MIT
 * @version 1.0
 * @package epesi-crm
 * @subpackage contacts
 */

defined("_VALID_ACCESS") || die();

class CRM_Contacts extends Module {
	private $rb = null;

	public function applet($conf, $opts) { //available applet options: toggle,href,title,go,go_function,go_arguments,go_contruct_arguments
		$opts['go'] = 1;
		$mode = 'contact';
		$rb = $this->init_module('Utils/RecordBrowser',$mode,$mode);
		$conds = array(
									array(	array('field'=>'last_name', 'width'=>10),
											array('field'=>'first_name', 'width'=>10),
											array('field'=>'company_name', 'width'=>10)
										),
									$conf['conds']=='fav'?array(':Fav'=>1):array(':Recent'=>1),
									array('last_name'=>'ASC','first_name'=>'ASC','company_name'=>'ASC'),
									array('CRM_ContactsCommon','applet_info_format'),
									15,
									$conf,
									& $opts
				);
		
		$opts['actions'][] = Utils_RecordBrowserCommon::applet_new_record_button('contact',array(	'country'=>Base_User_SettingsCommon::get('Base_RegionalSettings','default_country'),
								'zone'=>Base_User_SettingsCommon::get('Base_RegionalSettings','default_state'),
								'permission'=>'0','home_country'=>Base_User_SettingsCommon::get('Base_RegionalSettings','default_country'),
								'home_zone'=>Base_User_SettingsCommon::get('Base_RegionalSettings','default_state')));
		$this->display_module($rb, $conds, 'mini_view');
	}

	public function body() {
		if (isset($_REQUEST['mode'])) $this->set_module_variable('mode', $_REQUEST['mode']);
		$mode = $this->get_module_variable('mode','contact');
		if ($mode=='my_contact') {
			$this->rb = $this->init_module('Utils/RecordBrowser','contact','contact');
			$me = CRM_ContactsCommon::get_my_record();
			$this->display_module($this->rb, array('view', $me['id'], array(), array('back'=>false)), 'view_entry');
			return;
		}
		if ($mode=='main_company') {
			$this->rb = $this->init_module('Utils/RecordBrowser','company','company');
			$me = CRM_ContactsCommon::get_main_company();
			$this->display_module($this->rb, array('view', $me, array(), array('back'=>false)), 'view_entry');
			return;
		}
		if ($mode!='contact' && $mode!='company') trigger_error('Unknown mode.');

		$this->rb = $this->init_module('Utils/RecordBrowser',$mode,$mode);
		$this->rb->set_defaults(array(	'country'=>Base_User_SettingsCommon::get('Base_RegionalSettings','default_country'),
										'zone'=>Base_User_SettingsCommon::get('Base_RegionalSettings','default_state'),
										'permission'=>Base_User_SettingsCommon::get('CRM_Common','default_record_permission')));
		if ($mode=='contact') {
			$fcallback = array('CRM_ContactsCommon','company_format_default');
			$this->rb->set_custom_filter('company_name', array('type'=>'autoselect','label'=>$this->t('Company Name'),'args'=>array(), 'args_2'=>array(array('CRM_ContactsCommon','autoselect_company_suggestbox'), array(array(), $fcallback)), 'args_3'=>$fcallback, 'trans_callback'=>array('CRM_ContactsCommon','autoselect_company_filter_trans')));
			$this->rb->set_defaults(array(	'home_country'=>Base_User_SettingsCommon::get('Base_RegionalSettings','default_country'),
											'home_zone'=>Base_User_SettingsCommon::get('Base_RegionalSettings','default_state')));
			$this->rb->set_default_order(array('last_name'=>'ASC', 'first_name'=>'ASC'));
			$this->rb->set_additional_actions_method(array($this, 'contacts_actions'));
		} else {
			$this->rb->set_default_order(array('company_name'=>'ASC'));
			$this->rb->set_additional_actions_method(array($this, 'companies_actions'));
		}
		$this->display_module($this->rb);
	}

	public function admin(){
		if($this->is_back()) {
			if($this->parent->get_type()=='Base_Admin')
				$this->parent->reset();
			else
				location(array());
			return;
		}
		Base_ActionBarCommon::add('back', 'Back', $this->create_back_href());
		
		$filter = $this->get_module_variable_or_unique_href_variable('filter',1);
		
		if($filter) {
			$c = CRM_ContactsCommon::get_company(CRM_ContactsCommon::get_main_company());
			print('<h2>'.$this->t('"%s" contacts',array($c['company_name'])).'</h2>');
		} else
			print('<h2>'.$this->t('Epesi users').'</h2>');

		$logins = DB::GetAssoc('SELECT id,login FROM user_login');
		$ccc = CRM_ContactsCommon::get_contacts(array('login'=>array_keys($logins)));

		if($filter)
			$c = CRM_ContactsCommon::get_contacts(array('company_name'=>array(CRM_ContactsCommon::get_main_company())));
		else
			$c = & $ccc;
		$gb = $this->init_module('Utils/GenericBrowser',null,'my_contacts');
		$gb->set_table_columns(array(
			array('name'=>$this->t('Login'),'search'=>1,'order'=>'l'),
			array('name'=>$this->t('Contact'),'search'=>1,'order'=>'c')
			));
			
		foreach($c as $r) {
			if(isset($logins[$r['login']])) {
				$login = $logins[$r['login']];
			} else $login = '---';
			if($filter) 
				$contact = CRM_ContactsCommon::contact_format_no_company($r);
			else
				$contact = CRM_ContactsCommon::contact_format_default($r);
			$gb->add_row($login,$contact);
		}
		$this->display_module($gb,array(true),'automatic_display');
		
		foreach($ccc as $v) {
			unset($logins[$v['login']]);
		}
		print($this->t('Users without contact: %s.',array(implode(', ',$logins))));


		Base_ActionBarCommon::add('settings', 'Change main company', $this->create_callback_href(array($this,'admin_main_company')));
		if($filter)
			Base_ActionBarCommon::add('view', 'Show all users', $this->create_unique_href(array('filter'=>0)));
		else
			Base_ActionBarCommon::add('view', 'Show main company contacts', $this->create_unique_href(array('filter'=>1)));
	}
	
	public function admin_main_company() {
		if($this->is_back()) {
			return false;
		}
		$qf = $this->init_module('Libs/QuickForm',null,'my_company');
		$companies = CRM_ContactsCommon::get_companies(array(), array(), array('company_name'=>'ASC'), array(), true);
		$x = array();
		foreach($companies as $c)
			$x['s'.$c['id']] = $c['company_name'];//.' ('.$c['short_name'].')'
		$qf->addElement('select','company',$this->t('Choose main company'),$x);
		$qf->addElement('static',null,null,$this->t('Contacts assigned to this company are treated as employees. You should set the main company only once.'));
		try {
			$main_company = Variable::get('main_company');
			$qf->setDefaults(array('company'=>'s'.$main_company));
		} catch(NoSuchVariableException $e) {
		}

		if($qf->validate()) {
			Variable::set('main_company',trim($qf->exportValue('company'),'s'));
			return false;
		}
		$qf->display();

		Base_ActionBarCommon::add('back', 'Back', $this->create_back_href());
		Base_ActionBarCommon::add('save', 'Save', $qf->get_submit_form_href());
		return true;
	}
	
	public function company_addon($arg){
		$rb = $this->init_module('Utils/RecordBrowser','contact','contact_addon');
		$rb->set_additional_actions_method(array($this, 'contacts_actions'));
		if(Utils_RecordBrowserCommon::get_access('contact','add'))
			Base_ActionBarCommon::add('add','Add contact', $this->create_callback_href(array($this, 'company_addon_new_contact'), array($arg['id'])));
		$rb->set_button($this->create_callback_href(array($this, 'company_addon_new_contact'), array($arg['id'])));
		$this->display_module($rb, array(array('(company_name'=>$arg['id'],'|related_companies'=>array($arg['id'])), array('company_name'=>false), array('last_name'=>'ASC','first_name'=>'ASC')), 'show_data');
        $uid = Base_AclCommon::get_acl_user_id(Acl::get_user());
        if( Base_AclCommon::is_user_in_group($uid, 'Employee Manager') || Base_AclCommon::i_am_admin() ) {
            $prompt_id = "contacts_address_fix";
            $content = $this->update_contacts_address_prompt($arg, $prompt_id);
            Libs_LeightboxCommon::display($prompt_id, $content, $this->t('Update Contacts'));
            Base_ActionBarCommon::add('all', 'Update Contacts', Libs_LeightboxCommon::get_open_href($prompt_id));
        }
    }

	public function contacts_actions($r, $gb_row) {
		$is_employee = false;
		if (is_array($r['company_name']) && in_array(CRM_ContactsCommon::get_main_company(), $r['company_name'])) $is_employee = true;
		$me = CRM_ContactsCommon::get_my_record();
		$emp = array($me['id']);
		$cus = array();
		if ($is_employee) $emp[] = $r['id'];
		else $cus[] = 'P:'.$r['id'];
		if (ModuleManager::is_installed('CRM/Meeting')!==-1 && Utils_RecordBrowserCommon::get_access('crm_meeting','add')) $gb_row->add_action(Utils_RecordBrowserCommon::create_new_record_href('crm_meeting', array('employees'=>$emp,'customers'=>$cus,'status'=>0, 'priority'=>1, 'permission'=>0)), 'New Event', null, Base_ThemeCommon::get_template_file('CRM_Calendar','icon-small.png'));
		if (ModuleManager::is_installed('CRM/Tasks')!==-1 && Utils_RecordBrowserCommon::get_access('task','add')) $gb_row->add_action(Utils_RecordBrowserCommon::create_new_record_href('task', array('employees'=>$emp,'customers'=>$cus,'status'=>0, 'priority'=>1, 'permission'=>0)), 'New Task', null, Base_ThemeCommon::get_template_file('CRM_Tasks','icon-small.png'));
		if (ModuleManager::is_installed('CRM/PhoneCall')!==-1 && Utils_RecordBrowserCommon::get_access('phonecall','add')) $gb_row->add_action(Utils_RecordBrowserCommon::create_new_record_href('phonecall', array('date_and_time'=>date('Y-m-d H:i:s'),'customer'=>'P:'.$r['id'],'employees'=>$me['id'],'status'=>0, 'permission'=>0, 'priority'=>1),'none',array('date_and_time')), 'New Phonecall', null, Base_ThemeCommon::get_template_file('CRM_PhoneCall','icon-small.png'));
		$gb_row->add_action(Utils_RecordBrowser::$rb_obj->add_note_button_href('contact/'.$r['id']), 'New Note', null, Base_ThemeCommon::get_template_file('Utils_Attachment','icon_small.png'));
	}

	public function companies_actions($r, $gb_row) {
		$me = CRM_ContactsCommon::get_my_record();
		$emp = array($me['id']);
		$cus = array();
		$cus[] = 'C:'.$r['id'];
		if (ModuleManager::is_installed('CRM/Meeting')!==-1 && Utils_RecordBrowserCommon::get_access('crm_meeting','add')) $gb_row->add_action(Utils_RecordBrowserCommon::create_new_record_href('crm_meeting', array('employees'=>$emp,'customers'=>$cus,'status'=>0, 'priority'=>1, 'permission'=>0)), 'New Event', null, Base_ThemeCommon::get_template_file('CRM_Calendar','icon-small.png'));
		if (ModuleManager::is_installed('CRM/Tasks')!==-1 && Utils_RecordBrowserCommon::get_access('task','add')) $gb_row->add_action(Utils_RecordBrowserCommon::create_new_record_href('task', array('employees'=>$emp,'customers'=>$cus,'status'=>0, 'priority'=>1, 'permission'=>0)), 'New Task', null, Base_ThemeCommon::get_template_file('CRM_Tasks','icon-small.png'));
		if (ModuleManager::is_installed('CRM/PhoneCall')!==-1 && Utils_RecordBrowserCommon::get_access('phonecall','add')) $gb_row->add_action(Utils_RecordBrowserCommon::create_new_record_href('phonecall', array('date_and_time'=>date('Y-m-d H:i:s'),'customer'=>'C:'.$r['id'],'employees'=>$me['id'],'status'=>0, 'permission'=>0, 'priority'=>1),'none',array('date_and_time')), 'New Phonecall', null, Base_ThemeCommon::get_template_file('CRM_PhoneCall','icon-small.png'));
		$gb_row->add_action(Utils_RecordBrowser::$rb_obj->add_note_button_href('company/'.$r['id']), 'New Note', null, Base_ThemeCommon::get_template_file('Utils_Attachment','icon_small.png'));
	}

	public function company_addon_new_contact($id){
		$x = ModuleManager::get_instance('/Base_Box|0');
		if(!$x) trigger_error('There is no base box module instance',E_USER_ERROR);
		$x->push_main('CRM/Contacts','new_contact',$id,array());
		return false;
	}

    public function update_contacts_address_prompt($company, $lid) {
        $html = '<br/>'.$this->t('This action will update all contacts within this company with values copied from company record.<br/><br/>Please check which data would you like to copy to company contacts:');
        $form = $this->init_module('Libs/QuickForm');

        $data = array( /* Source ID, Target ID, Text, Checked state */
            array('sid'=>'address_1', 'tid'=>'address_1', 'text'=>$this->t('Address 1'), 'checked'=>true),
            array('sid'=>'address_2', 'tid'=>'address_2', 'text'=>$this->t('Address 2'), 'checked'=>true),
            array('sid'=>'city', 'tid'=>'city', 'text'=>$this->t('City'), 'checked'=>true),
            array('sid'=>'country', 'tid'=>'country', 'text'=>$this->t('Country'), 'checked'=>true),
            array('sid'=>'zone', 'tid'=>'zone', 'text'=>$this->t('Zone'), 'checked'=>true),
            array('sid'=>'postal_code', 'tid'=>'postal_code', 'text'=>$this->t('Postal Code'), 'checked'=>true),
            array('sid'=>'phone', 'tid'=>'work_phone', 'text'=>$this->t('Phone as Work Phone'), 'checked'=>false),
            array('sid'=>'fax', 'tid'=>'fax', 'text'=>$this->t('Fax'), 'checked'=>false),
        );
        foreach($data as $row) {
            $form->addElement('checkbox', $row['sid'], $row['text'], '&nbsp;&nbsp;<span style="color: gray">'.$company[$row['sid']].'</span>', $row['checked'] ? array('checked'=>'checked'): array());
        }

        $ok = $form->createElement('submit', 'submit', $this->t('Confirm'), array('onclick'=>'leightbox_deactivate("'.$lid.'")'));
        $cancel = $form->createElement('button', 'cancel', $this->t('Cancel'), array('onclick'=>'leightbox_deactivate("'.$lid.'")'));
        $form->addGroup(array($ok, $cancel));

        if($form->validate()) {
            $values = $form->exportValues();
            $fields = array();
            foreach($data as $row) {
                if(array_key_exists($row['sid'], $values)) {
                    $fields[$row['tid']] = $row['sid'];
                }
            }
            $this->update_contacts_address($company, $fields);
            location(array());
        }

        $html .= $form->toHtml();

        return $html;
    }

    public function update_contacts_address($company, $fields) {
        $recs = CRM_ContactsCommon::get_contacts(array('company_name' => $company['id']), array('id'));
        $new_data = array();
        foreach($fields as $k => $v) {
            $new_data[$k] = $company[$v];
        }
        foreach($recs as $contact) {
            Utils_RecordBrowserCommon::update_record('contact', $contact['id'], $new_data);
        }
    }

	public function new_contact($company){
		CRM_ContactsCommon::$paste_or_new = $company;
		$rb = $this->init_module('Utils/RecordBrowser','contact','contact');
		$this->rb = $rb;
		$ret = $rb->view_entry('add', null, array('company_name'=>array($company),
												'country'=>Base_User_SettingsCommon::get('Base_RegionalSettings','default_country'),
												'zone'=>Base_User_SettingsCommon::get('Base_RegionalSettings','default_state'),											
												'permission'=>'0'));
		$this->set_module_variable('view_or_add', 'add');
		if ($ret==false) {
			$x = ModuleManager::get_instance('/Base_Box|0');
			if(!$x) trigger_error('There is no base box module instance',E_USER_ERROR);
			return $x->pop_main();
		}
	}

	public function edit_user_form($user_id) {
		if (!$this->isset_module_variable('last_location')) $this->set_module_variable('last_location',isset($_REQUEST['__location'])?$_REQUEST['__location']:true);
		$m = $this->init_module('Base/User/Administrator');
		$this->display_module($m, array($user_id), 'edit_user_form');
//		if($m->is_back()) Epesi::alert('back');
		if ($m->is_back() || (isset($_REQUEST['__location']) && $_REQUEST['__location']!=$this->get_module_variable('last_location'))) {
			$x = ModuleManager::get_instance('/Base_Box|0');
			if (!$x) trigger_error('There is no base box module instance',E_USER_ERROR);
			$x->pop_main();
		}
	}

	public function caption(){
		if (isset($this->rb)) return $this->rb->caption();
	}
}
?>
