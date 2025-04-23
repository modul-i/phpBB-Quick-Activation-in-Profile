<?php

namespace moemorox\qaip\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	protected $db;
	protected $language;
	protected $template;
	protected $user;
	protected $auth;
	protected $db_tools;
	protected $request;

	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\language\language $language,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		\phpbb\db\tools\tools $db_tools,
		\phpbb\request\request $request
	)
	{
		$this->db = $db;
		$this->language = $language;
		$this->user = $user;
		$this->auth = $auth;
		$this->template = $template;
		$this->db_tools = $db_tools;
		$this->request = $request;

		$this->language->add_lang('common', 'moemorox/qaip');
	}

	public static function getSubscribedEvents()
	{
		return [
			'core.memberlist_modify_view_profile_template_vars' => 'memberlist_modify_view_profile_template_vars',
		];
	}

	public function memberlist_modify_view_profile_template_vars($event)
	{
		// Admin check
		if (!$this->admin_check())
		{
			return;
		}

		$user_id = $event['user_id'] ?? $this->request->variable('u', 0);
		if (empty($user_id))
		{
			return;
		}

		$user_data = $this->get_user_data($user_id);

		$this->add_activate_url($user_id, $user_data);
		$this->inject_email($user_data);
		$this->inject_ip($user_data);
		$this->inject_website($user_id);
	}

	private function admin_check()
	{
		if (!$this->user->data['is_registered'] || !($this->auth->acl_get('a_') || $this->user->data['user_type'] == USER_FOUNDER))
		{
			return false;
		}

		return true;
	}

	private function get_user_data($user_id)
	{
		$sql = 'SELECT user_actkey, user_type, user_email, user_ip FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row;
	}

	private function add_activate_url($user_id, $user_data)
	{
		// Check if user is already activated
		if (empty($user_data['user_type']) || (int) $user_data['user_type'] !== 1)
		{
			return;
		}

		if (!empty($user_data['user_actkey']))
		{
			$activate_link = generate_board_url() . "/ucp.php?mode=activate&u={$user_id}&k=" . $user_data['user_actkey'];

			$this->template->assign_vars([
				'U_ACTIVATE' => $activate_link,
			]);
		}
	}

	private function inject_email($user_data)
	{
		if (!empty($user_data['user_email']))
		{
			$this->template->assign_vars([
				'USER_REAL_EMAIL' => $user_data['user_email'],
			]);
		}
	}

	private function inject_ip($user_data)
	{
		if (!empty($user_data['user_ip']))
		{
			$this->template->assign_vars([
				'USER_IP' => $user_data['user_ip'],
				'USER_IP_URL' => 'https://ipinfo.io/' . $user_data['user_ip'],
			]);
		}
	}

	private function inject_website($user_id)
	{
		if (!$this->db_tools->sql_column_exists(PROFILE_FIELDS_DATA_TABLE, 'pf_phpbb_website'))
		{
			return;
		}

		$sql = 'SELECT pf_phpbb_website FROM ' . PROFILE_FIELDS_DATA_TABLE . ' WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!empty($row['pf_phpbb_website']))
		{
			$this->template->assign_vars([
				'USER_WEBSITE_RAW' => $row['pf_phpbb_website'],
			]);
		}
	}
}
