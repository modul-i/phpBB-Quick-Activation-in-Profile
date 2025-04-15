<?php

namespace moemorox\qaip\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use const PROFILE_FIELDS_DATA_TABLE;


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
	) {
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
		$this->add_activate_url($event);
		$this->inject_email($event);
		$this->inject_website($event);
		$this->inject_ip($event);
	}

	public function add_activate_url($event)
	{
		// Admin check
		if (!$this->user->data['is_registered'] || !($this->auth->acl_get('a_') || $this->user->data['user_type'] == USER_FOUNDER)) {
			return;
		}

		$user_id = (int) $this->request->variable('u', 0);
		if (empty($user_id)) {
			return;
		}

		// Get user_actkey from DB
		$sql = 'SELECT user_actkey FROM ' . USERS_TABLE . ' WHERE user_id = ' . $user_id . ' AND user_type = 1;';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!empty($row['user_actkey'])) {
			$activate_link = generate_board_url() . "/ucp.php?mode=activate&u={$user_id}&k=" . $row['user_actkey'];

			$this->template->assign_vars([
				'U_ACTIVATE' => $activate_link,
				'L_USER_ACTIVATE' => $this->language->lang('USER_ACTIVATE')
			]);
		}
	}

	public function inject_email($event)
	{
		if (
			!$this->user->data['is_registered']
			|| !($this->auth->acl_get('a_') || $this->user->data['user_type'] == USER_FOUNDER)
		) {
			return;
		}

		$user_id = (int) $this->request->variable('u', 0);
		if (empty($user_id)) {
			return;
		}

		$sql = 'SELECT user_email FROM ' . USERS_TABLE . ' WHERE user_id = ' . $user_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!empty($row['user_email'])) {
			$this->template->assign_vars([
				'USER_REAL_EMAIL' => $row['user_email']
			]);
		}
	}

	public function inject_website($event)
	{
		if (
			!$this->user->data['is_registered']
			|| !($this->auth->acl_get('a_') || $this->user->data['user_type'] == USER_FOUNDER)
		) {
			return;
		}

		if (!$this->db_tools->sql_column_exists(PROFILE_FIELDS_DATA_TABLE, 'pf_phpbb_website')) {
			return;
		}

		$user_id = (int) $this->request->variable('u', 0);
		if (empty($user_id)) {
			return;
		}

		$sql = 'SELECT pf_phpbb_website FROM ' . PROFILE_FIELDS_DATA_TABLE . ' WHERE user_id = ' . $user_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!empty($row['pf_phpbb_website'])) {
			$this->template->assign_vars([
				'USER_WEBSITE_RAW' => $row['pf_phpbb_website'],
			]);
		}
	}

	public function inject_ip($event)
	{
		if (
			!$this->user->data['is_registered']
			|| !($this->auth->acl_get('a_') || $this->user->data['user_type'] == USER_FOUNDER)
		) {
			return;
		}

		$user_id = (int) $this->request->variable('u', 0);
		if (empty($user_id)) {
			return;
		}

		$sql = 'SELECT user_ip FROM ' . USERS_TABLE . ' WHERE user_id = ' . $user_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!empty($row['user_ip'])) {
			$this->template->assign_vars([
				'USER_IP' => $row['user_ip'],
				'USER_IP_URL' => 'https://ipinfo.io/' . $row['user_ip'],
			]);
		}
	}
}
