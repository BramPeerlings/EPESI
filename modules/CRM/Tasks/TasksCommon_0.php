<?php
/**
 * @author Arkadiusz Bisaga <abisaga@telaxus.com>
 * @copyright Copyright &copy; 2008, Telaxus LLC
 * @license MIT
 * @version 1.0
 * @package epesi-crm
 * @subpackage tasks
 */

defined("_VALID_ACCESS") || die('Direct access forbidden');

class CRM_TasksCommon extends ModuleCommon {
	public static function applet_caption() {
		if (Utils_RecordBrowserCommon::get_access('task','browse'))
			return __('Tasks');
	}

	public static function applet_info() {
		return __('To do list');
	}

	public static function applet_info_format($r){

		// Build array representing 2-column tooltip
		// Format: array (Label,value)
		$access = Utils_CommonDataCommon::get_translated_array('CRM/Access');
		$priority = Utils_CommonDataCommon::get_translated_array('CRM/Priority');
		$status = Utils_CommonDataCommon::get_translated_array('CRM/Status');

		$contacts = array();
		$companies = array();
		$customers = '';
		foreach($r['customers'] as $arg) {
			if ($customers) $customers .='<br>';
			$customers .= CRM_ContactsCommon::autoselect_company_contact_format($arg);
		}

		$args=array(
					__('Task')=>'<b>'.$r['title'].'</b>',
					__('Description')=>$r['description'],
					__('Assigned to')=>CRM_ContactsCommon::display_contact(array('id'=>$r['employees']),true,array('id'=>'id', 'param'=>'::;CRM_ContactsCommon::contact_format_no_company')),
					__('Customers')=> $customers,
					__('Status')=>$status[$r['status']],
					__('Deadline')=>$r['deadline']!=''?Base_RegionalSettingsCommon::time2reg($r['deadline'],false):__('Not set'),
					__('Longterm')=>$r['longterm']!=0?__('Yes'):__('No'),
					__('Permission')=>$access[$r['permission']],
					__('Priority')=>$priority[$r['priority']],
					);

		$bg_color = '';
		switch ($r['priority']) {
			case 0: $bg_color = '#FFFFFF'; break; // low priority
			case 1: $bg_color = '#FFFFD5'; break; // medium
			case 2: $bg_color = '#FFD5D5'; break; // high
		}

		// Pass 2 arguments: array containing pairs: label/value
		// and the name of the group for translation
		//return	Utils_TooltipCommon::format_info_tooltip($args,'CRM_Tasks');

		$ret = array('notes'=>Utils_TooltipCommon::format_info_tooltip($args));
		if ($bg_color) $ret['row_attrs'] = 'style="background:'.$bg_color.';"';
		return $ret;
	}

	public static function menu() {
		if (Utils_RecordBrowserCommon::get_access('task','browse'))
			return array(_M('CRM')=>array('__submenu__'=>1,_M('Tasks')=>array('__icon__'=>'tasks')));
		else
			return array();
	}

	public static function task_bbcode($text, $param, $opt) {
		return Utils_RecordBrowserCommon::record_bbcode('task', array('title'), $text, $param, $opt);
	}

	public static function get_tasks($crits = array(), $cols = array(), $order = array()) {
		return Utils_RecordBrowserCommon::get_records('task', $crits, $cols, $order);
	}

	public static function get_task($id) {
		return Utils_RecordBrowserCommon::get_record('task', $id);
	}

	public static function applet_settings() {
        $settings = array(
			array('label' => __('Additional title'),
				  'name' => 'subtitle',
				  'type' => 'text',
				  'default' => ''),
            array('label'   => __('Display tasks marked as'), 'name' => 'term',
                  'type'    => 'select',
                  'values'  => array('s' => __('Short-term'),
                                     'l' => __('Long-term'), 'b' => __('Both')),
                  'default' => 's',
                  'rule'    => array(array('message' => __('Field required'),
                                           'type'    => 'required')))
        );
        $statuses = Utils_CommonDataCommon::get_translated_array('CRM/Status');
        ksort($statuses);
        foreach ($statuses as $status_id => $status_label) {
            $name = "status_$status_id";
            $label = __('Display with status: %s', array($status_label));
            $default = ($status_id < 3) ? true : false; // Open, In progress, On hold
            $settings[] = array('label' => $label, 'name' => $name,
                                'type'  => 'checkbox', 'default' => $default);
        }
        $settings[] = array('label'   => __('You are in field'),
                            'name'    => 'related',
                            'type'    => 'select',
                            'values'  => array(0 => __('Employee'),
                                               1 => __('Customer'),
                                               2 => __('Employee or Customer'),
                                               'none' => __('---')),
                            'default' => '0');
		$settings[] = array('label'   => __('Advanced Filter'),
                            'name'    => 'crits',
                            'type'    => 'crits',
							'param'   => 'task',
							'default' => array()
		);

        return Utils_RecordBrowserCommon::applet_settings($settings);
	}

	public static function employees_crits(){
		return array('(company_name'=>CRM_ContactsCommon::get_main_company(),'|related_companies'=>array(CRM_ContactsCommon::get_main_company()));
	}
	public static function customers_crits($arg){
		if (!$arg) return array('(:Fav'=>true, '|:Recent'=>true);
		else return array();
	}
	public static function display_employees($record, $nolink, $desc) {
		return CRM_ContactsCommon::display_contacts_with_notification('task', $record, $nolink, $desc);
	}

	public static function display_deadline($record, $nolink, $desc)
	{
		if (!$record['deadline']) {
			return '';
		}
		$deadline = strtotime($record['deadline']);
		$timeless = isset($record['timeless']) ? $record['timeless'] : false;
		$show_time = !$timeless;
		$time2reg = $show_time;
		$ret = Base_RegionalSettingsCommon::time2reg($deadline, $show_time, true, $time2reg);
		$past_deadline = $timeless
				? (Base_RegionalSettingsCommon::time2reg(null, false, true, true, false) > date('Y-m-d', $deadline))
				: (time() > $deadline);
		if ($past_deadline) {
			$ret = '<span style="color:red;font-weight:bold;">' . $ret . '</span>';
		}
		return $ret;
	}
    public static function display_title($record, $nolink) {
		$ret = Utils_RecordBrowserCommon::create_linked_label_r('task', 'Title', $record, $nolink);
		if (isset($record['description']) && $record['description']!='') $ret = '<span '.Utils_TooltipCommon::open_tag_attrs(Utils_RecordBrowserCommon::format_long_text($record['description']), false).'>'.$ret.'</span>';
		return $ret;
	}
    public static function display_title_with_mark($record) {
		return self::display_title($record, false);
	}
	public static function display_status($record, $nolink, $desc) {
		$prefix = 'crm_tasks_leightbox';
		$v = $record[$desc['id']];
		if (!$v) $v = 0;
		$status = Utils_CommonDataCommon::get_translated_array('CRM/Status');
		if ($v>=3 || $nolink) return $status[$v];
		CRM_FollowupCommon::drawLeightbox($prefix);
		if (!Utils_RecordBrowserCommon::get_access('task', 'edit', $record) && !Base_AclCommon::i_am_admin()) return $status[$v];
		if (isset($_REQUEST['form_name']) && $_REQUEST['form_name']==$prefix.'_follow_up_form' && $_REQUEST['id']==$record['id']) {
			unset($_REQUEST['form_name']);
			$v = $_REQUEST['closecancel'];
			$action  = $_REQUEST['action'];

			$note = $_REQUEST['note'];
			if ($note) {
				if (get_magic_quotes_gpc())
					$note = stripslashes($note);
				$note = str_replace("\n",'<br />',$note);
				Utils_AttachmentCommon::add('task/'.$record['id'],0,Acl::get_user(),$note);
			}

			if ($action == 'set_in_progress') $v = 1;
			Utils_RecordBrowserCommon::update_record('task', $record['id'], array('status'=>$v));
			if ($action == 'set_in_progress') location(array());

			$values = $record;
			$values['date_and_time'] = date('Y-m-d H:i:s');
			$values['title'] = __('Follow-up').': '.$values['title'];
			$values['status'] = 0;

			if ($action != 'none') {		
				$values['follow_up'] = array('task',$record['id'],$record['title']);
				if ($action == 'new_task') Base_BoxCommon::push_module(Utils_RecordBrowser::module_name(),'view_entry',array('add', null, $values), array('task'));
				if ($action == 'new_meeting') Base_BoxCommon::push_module(Utils_RecordBrowser::module_name(),'view_entry',array('add', null, array('title'=>$values['title'],'permission'=>$values['permission'],'priority'=>$values['priority'],'description'=>$values['description'],'date'=>date('Y-m-d'),'time'=>date('H:i:s'),'duration'=>3600,'status'=>0,'employees'=>$values['employees'], 'customers'=>$values['customers'],'follow_up'=>$values['follow_up'])), array('crm_meeting'));
				if ($action == 'new_phonecall') Base_BoxCommon::push_module(Utils_RecordBrowser::module_name(),'view_entry',array('add', null, array('subject'=>$values['title'],'permission'=>$values['permission'],'priority'=>$values['priority'],'description'=>$values['description'],'date_and_time'=>date('Y-m-d H:i:s'),'employees'=>$values['employees'],'status'=>0, 'customer'=>!empty($values['customers'])?array_pop($values['customers']):'','follow_up'=>$values['follow_up'])), array('phonecall'));
				return false;
			}

			location(array());
		}
		if ($v==0) {
			return '<a href="javascript:void(0)" onclick="'.$prefix.'_set_action(\'set_in_progress\');'.$prefix.'_set_id(\''.$record['id'].'\');'.$prefix.'_submit_form();">'.$status[$v].'</a>';
		}
		return '<a href="javascript:void(0)" class="lbOn" rel="'.$prefix.'_followups_leightbox" onMouseDown="'.$prefix.'_set_id('.$record['id'].');">'.$status[$v].'</a>';
	}
	public static function subscribed_employees($v) {
		if (!is_array($v)) return;
		foreach ($v['employees'] as $k) {
			$user = Utils_RecordBrowserCommon::get_value('contact',$k,'Login');
			if ($user!==false && $user!==null) Utils_WatchdogCommon::user_subscribe($user, 'task', $v['id']);
		}
	}

	public static function submit_task($values, $mode) {
		$me = CRM_ContactsCommon::get_my_record();
		switch ($mode) {
		case 'display':
			$values['title'] = __('Follow-up').': '.$values['title'];
			$values['status'] = 0;
			$values['deadline'] = date('Y-m-d', strtotime('+1 day'));
			$ret = array();
			$cus = reset($values['customers']);
			if (CRM_MeetingInstall::is_installed()) $ret['new']['event'] = '<a '.Utils_TooltipCommon::open_tag_attrs(__('New Meeting')).' '.Utils_RecordBrowserCommon::create_new_record_href('crm_meeting', array('title'=>$values['title'],'permission'=>$values['permission'],'priority'=>$values['priority'],'description'=>$values['description'],'date'=>date('Y-m-d'),'time'=>date('H:i:s'),'duration'=>3600,'employees'=>$values['employees'], 'customers'=>$values['customers'],'status'=>0), 'none', false).'><img border="0" src="'.Base_ThemeCommon::get_template_file('CRM_Calendar','icon-small.png').'" /></a>';
			$ret['new']['task'] = '<a '.Utils_TooltipCommon::open_tag_attrs(__('New Task')).' '.Utils_RecordBrowserCommon::create_new_record_href('task', $values).'><img border="0" src="'.Base_ThemeCommon::get_template_file('CRM_Tasks','icon-small.png').'" /></a>';
			if (CRM_PhoneCallInstall::is_installed()) $ret['new']['phonecall'] = '<a '.Utils_TooltipCommon::open_tag_attrs(__('New Phonecall')).' '.Utils_RecordBrowserCommon::create_new_record_href('phonecall', array('subject'=>$values['title'],'permission'=>$values['permission'],'priority'=>$values['priority'],'description'=>$values['description'],'date_and_time'=>date('Y-m-d H:i:s'),'employees'=>$values['employees'], 'customer'=>$cus,'status'=>0), 'none', false).'><img border="0" src="'.Base_ThemeCommon::get_template_file('CRM_PhoneCall','icon-small.png').'" /></a>';
			$ret['new']['note'] = Utils_RecordBrowser::$rb_obj->add_note_button('task/'.$values['id']);
			return $ret;
		case 'adding':
			if (!isset($values['deadline'])) {
				$values['deadline'] = strtotime(date('Y-m-d').' 23:59:59');
			}
			$values['permission'] = Base_User_SettingsCommon::get('CRM_Common','default_record_permission');
			break;
		case 'editing':
			if (isset($values['timeless']) && $values['timeless']) {
				// if it is timeless event then adjust time to show it properly
				// in GUI - timestamp field translates time.
				$values['deadline'] = Base_RegionalSettingsCommon::reg2time($values['deadline']);
			}
			break;
		case 'add':
		case 'edit':
			if (isset($values['timeless']) && $values['timeless'] && $values['deadline']) {
				// if we set timeless event then, set certain time to database
				$values['deadline'] = Base_RegionalSettingsCommon::time2reg($values['deadline'], false, true, true, false) . ' 12:00:00';
			}
			break;
		case 'edited':
			$old_values = Utils_RecordBrowserCommon::get_record('task',$values['id']);
			$old_related = array_merge($old_values['employees'],$old_values['customers']);
		case 'added':
			if (isset($values['follow_up']))
				CRM_FollowupCommon::add_tracing_notes($values['follow_up'][0], $values['follow_up'][1], $values['follow_up'][2], 'task', $values['id'], $values['title']);
			self::subscribed_employees($values);
			$related = array_merge($values['employees'],$values['customers']);
			foreach ($related as $v) {
				if ($mode==='edit' && in_array($v, $old_related)) continue;
				if (!is_numeric($v)) {
					list($t, $id) = explode('/', $v);
				} else {
					$t = 'contact';
					$id = $v;
				}
				$subs = Utils_WatchdogCommon::get_subscribers($t,$id);
				foreach($subs as $s)
					Utils_WatchdogCommon::user_subscribe($s, 'task',$values['id']);
			}
			break;
		}
		return $values;
	}
	public static function watchdog_label($rid = null, $events = array(), $details = true) {
		return Utils_RecordBrowserCommon::watchdog_label(
				'task',
				__('Tasks'),
				$rid,
				$events,
				'title',
				$details
			);
	}

	public static function search_format($id) {
		if(!Utils_RecordBrowserCommon::get_access('task','browse')) return false;
		$row = self::get_tasks(array('id'=>$id));
		if(!$row) return false;
		$row = array_pop($row);
		return Utils_RecordBrowserCommon::record_link_open_tag('task', $row['id']).__( 'Task (attachment) #%d, %s', array($row['id'], $row['title'])).Utils_RecordBrowserCommon::record_link_close_tag();
	}

	public static function get_alarm($id) {
		$a = Utils_RecordBrowserCommon::get_record('task',$id);

		if (!$a) return __('Private record');

		if($a['deadline'])
			$date = __('Task Deadline: %s',array(Base_RegionalSettingsCommon::time2reg($a['deadline'],true,false)));
		else
			$date = __('Task without deadline');

		return $date."\n".__('Title: %s',array($a['title']));
	}

	public static function crm_calendar_handler($action) {
		$args = func_get_args();
		array_shift($args);
		$ret = null;
		switch ($action) {
			case 'get_all': $ret = call_user_func_array(array('CRM_TasksCommon','crm_event_get_all'), $args);
							break;
			case 'update': $ret = call_user_func_array(array('CRM_TasksCommon','crm_event_update'), $args);
							break;
			case 'get': $ret = call_user_func_array(array('CRM_TasksCommon','crm_event_get'), $args);
							break;
			case 'delete': $ret = call_user_func_array(array('CRM_TasksCommon','crm_event_delete'), $args);
							break;
			case 'new_event_types': $ret = array(array('label'=>__('Task'),'icon'=>Base_ThemeCommon::get_template_file('CRM_Tasks','icon.png')));
							break;
			case 'new_event': $ret = call_user_func_array(array('CRM_TasksCommon','crm_new_event'), $args);
							break;
			case 'view_event': $ret = call_user_func_array(array('CRM_TasksCommon','crm_view_event'), $args);
							break;
			case 'edit_event': $ret = call_user_func_array(array('CRM_TasksCommon','crm_edit_event'), $args);
							break;
			case 'recordset': $ret = 'task';
		}
		return $ret;
	}

	public static function crm_view_event($id, $cal_obj) {
		$rb = $cal_obj->init_module('Utils_RecordBrowser', 'task');
		$rb->view_entry('view', $id);
		return true;
	}
	public static function crm_edit_event($id, $cal_obj) {
		$rb = $cal_obj->init_module('Utils_RecordBrowser', 'task');
		$rb->view_entry('edit', $id);
		return true;
	}
	public static function crm_new_event($timestamp, $timeless, $id, $object, $cal_obj) {
		$me = CRM_ContactsCommon::get_my_record();
		$defaults = array('employees'=>$me['id'], 'priority'=>CRM_CommonCommon::get_default_priority(), 'permission'=>0, 'status'=>0);
		$defaults['timeless'] = ($timeless != false);
		$defaults['deadline'] = $timestamp;
		if($object) $defaults['employees'] = $object;
		Base_BoxCommon::push_module(Utils_RecordBrowser::module_name(),'view_entry',array('add', null, $defaults), 'task');
	}

	public static function crm_event_delete($id) {
		if (!Utils_RecordBrowserCommon::get_access('task','delete', self::get_task($id))) return false;
		Utils_RecordBrowserCommon::delete_record('task',$id);
		return true;
	}
	public static function crm_event_update($id, $start, $duration, $timeless) {
		if (!Utils_RecordBrowserCommon::get_access('task','edit', self::get_task($id))) return false;
		$deadline = $timeless ? date('Y-m-d 12:00:00', $start) : date('Y-m-d H:i:s', $start);
		$values = array('deadline' => $deadline, 'timeless'=> ($timeless == true));
		Utils_RecordBrowserCommon::update_record('task', $id, $values);
		return true;
	}
	public static function crm_event_get_all($start, $end, $filter=null, $customers=null) {
		$start = date('Y-m-d',Base_RegionalSettingsCommon::reg2time($start));
		$crits = array();
		if ($filter===null) $filter = CRM_FiltersCommon::get();
		$f_array = explode(',',trim($filter,'()'));
		if($filter!='()' && $filter)
			$crits['('.'employees'] = $f_array;
		if ($customers && !empty($customers))
			$crits['|customers'] = $customers;
		elseif($filter!='()' && $filter) {
			$crits['|customers'] = $f_array;
			foreach ($crits['|customers'] as $k=>$v)
				$crits['|customers'][$k] = 'contact/'.$v;
		}
		$crits['<=deadline'] = $end;
		$crits['>=deadline'] = $start;

		$ret = Utils_RecordBrowserCommon::get_records('task', $crits, array(), array(), CRM_CalendarCommon::$events_limit);

		$result = array();
		foreach ($ret as $r)
			$result[] = self::crm_event_get($r);

		return $result;
	}

	public static function crm_event_get($id) {
		if (!is_array($id)) {
			$r = Utils_RecordBrowserCommon::get_record('task', $id);
		} else {
			$r = $id;
			$id = $r['id'];
		}
        $r = Utils_RecordBrowserCommon::filter_record_by_access('task', $r);
        if (!$r) {
            return null;
        }

		$next = array('type'=>__('Task'));

		$deadline = $r['deadline'];
		$iday = strtotime($deadline);
		$next['id'] = $r['id'];

		$next['start'] = $iday;
		$next['timeless'] = $r['timeless'] ? date('Y-m-d', $iday) : null;

		$next['duration'] = -1;
		$next['title'] = (string)$r['title'];
		$next['description'] = (string)$r['description'];
		$next['color'] = 'gray';
		if ($r['status']==0 || $r['status']==1)
			switch ($r['priority']) {
				case 0: $next['color'] = 'green'; break;
				case 1: $next['color'] = 'yellow'; break;
				case 2: $next['color'] = 'red'; break;
			}
		if ($r['status']==2)
			$next['color'] = 'blue';
		if ($r['status']==3)
			$next['color'] = 'gray';

		$next['view_action'] = Utils_RecordBrowserCommon::create_record_href('task', $r['id'], 'view');
		if (Utils_RecordBrowserCommon::get_access('task','edit', $r)!==false)
			$next['edit_action'] = Utils_RecordBrowserCommon::create_record_href('task', $r['id'], 'edit');
		else {
			$next['edit_action'] = false;
			$next['move_action'] = false;
		}
		if (Utils_RecordBrowserCommon::get_access('task','delete', $r)==false)
			$next['delete_action'] = false;

/*		$r_new = $r;
		if ($r['status']==0) $r_new['status'] = 1;
		if ($r['status']<=1) $next['actions'] = array(
			array('icon'=>Base_ThemeCommon::get_template_file('CRM/Meeting', 'close_event.png'), 'href'=>self::get_status_change_leightbox_href($r_new, false, array('id'=>'status')))
		);*/

        $start_time = Base_RegionalSettingsCommon::time2reg($next['start'],2,false,false);
        $event_date = Base_RegionalSettingsCommon::time2reg($next['start'],false,3,false);

        $inf2 = array(
            __('Date')=>'<b>'.$event_date.'</b>');

		$emps = array();
		foreach ($r['employees'] as $e) {
			$e = CRM_ContactsCommon::contact_format_no_company($e, true);
			$e = str_replace('&nbsp;',' ',$e);
			if (mb_strlen($e,'UTF-8')>33) $e = mb_substr($e , 0, 30, 'UTF-8').'...';
			$emps[] = $e;
		}
		$next['busy_label'] = $r['employees'];

		$cuss = array();
		foreach ($r['customers'] as $c) {
			$c = CRM_ContactsCommon::display_company_contact(array('customers'=>$c), true, array('id'=>'customers'));
            $cuss[] = str_replace('&nbsp;',' ',$c);
		}

		$inf2 += array(	__('Task')=> '<b>'.$next['title'].'</b>',
						__('Description')=> $next['description'],
						__('Assigned to')=> implode('<br>',$emps),
						__('Contacts')=> implode('<br>',$cuss),
						__('Status')=> Utils_CommonDataCommon::get_value('CRM/Status/'.$r['status'],true),
						__('Access')=> Utils_CommonDataCommon::get_value('CRM/Access/'.$r['permission'],true),
						__('Priority')=> Utils_CommonDataCommon::get_value('CRM/Priority/'.$r['priority'],true),
						__('Notes')=> Utils_AttachmentCommon::count('task/'.$r['id'])
					);

		$next['employees'] = $r['employees'];
		$next['customers'] = $r['customers'];
		$next['status'] = $r['status']<=2?'active':'closed';
		$next['custom_tooltip'] =
									'<center><b>'.
										__('Task').
									'</b></center><br>'.
									Utils_TooltipCommon::format_info_tooltip($inf2).'<hr>'.
									CRM_ContactsCommon::get_html_record_info($r['created_by'],$r['created_on'],null,null);
		return $next;
	}

    public static function QFfield_timeless(&$form, $field, $label, $mode, $default, $desc, $rb_obj) {
		Utils_RecordBrowserCommon::QFfield_checkbox($form, $field, $label, $mode, $default, $desc, $rb_obj);
		$js = "jq('#$field').unbind('change').change(function() {
		 var visible = !jq(this).is(':checked');
		 jq('[name=\"deadline\\[__date\\]\\[i\\]\"]').parent().toggle(visible);
		});";
		eval_js($js);
		eval_js('jq(\'[name="deadline\[__date\]\[i\]"]\').parent().toggle('.($default ? 'false' : 'true') .');');
	}

    public static function QFfield_recordset(&$form, $field, $label, $mode, $default) {
        if ($mode == 'add' || $mode == 'edit') {
            $rss = DB::GetCol('SELECT f_recordset FROM task_related_data_1 WHERE active=1');
            // remove currently selected value
            $key = array_search($default, $rss);
            if ($key !== false)
                unset($rss[$key]);
            $tabs = DB::GetAssoc('SELECT tab, caption FROM recordbrowser_table_properties WHERE tab not in (\'' . implode('\',\'', $rss) . '\') AND tab not like %s', array('%_related'));
            foreach ($tabs as $k => $v) {
                $tabs[$k] = _V($v) . " ($k)";
            }
            $form->addElement('select', $field, $label, $tabs, array('id' => $field));
            $form->addRule($field, 'Field required', 'required');
            if ($mode == 'edit')
                $form->setDefaults(array($field => $default));
        } else {
            $form->addElement('static', $field, $label);
            $form->setDefaults(array($field => $default));
        }
    }

    public static function display_recordset($r, $nolink = false) {
        $caption = Utils_RecordBrowserCommon::get_caption($r['recordset']);
        return $caption . ' (' . $r['recordset'] . ')';
    }

    public static function QFfield_related(&$form, $field, $label, $mode, $default, $desc, $rb_obj) {
        if(DB::GetOne('SELECT 1 FROM task_related_data_1 WHERE active=1'))
            Utils_RecordBrowserCommon::QFfield_select($form, $field, $label, $mode, $default, $desc, $rb_obj);
    }

    public static function related_crits() {
        $recordsets = DB::GetCol('SELECT f_recordset FROM task_related_data_1 WHERE active=1');
        $crits = array(
            '' => array(),
        );
        foreach ($recordsets as $rec)
            $crits[$rec] = array();
        return $crits;
    }

    public static function processing_related($values, $mode) {
        switch ($mode) {
            case 'edit':
            $rec = Utils_RecordBrowserCommon::get_record('task_related', $values['id']);
            $rs = $rec['recordset'];
            self::delete_addon($rs);
            case 'add':
            $rs = $values['recordset'];
            self::new_addon($rs);
            break;

            case 'delete':
            $rs = $values['recordset'];
            self::delete_addon($rs);
            break;
        }
        return $values;
    }

    public static function new_addon($table) {
        Utils_RecordBrowserCommon::new_addon($table, CRM_Tasks::module_name(), 'addon', 'Tasks');
    }

    public static function delete_addon($table) {
        Utils_RecordBrowserCommon::delete_addon($table, CRM_Tasks::module_name(), 'addon');
    }

    public static function admin_caption() {
        return array('label' => __('Tasks'), 'section' => __('Features Configuration'));
    }

}

?>
