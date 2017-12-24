<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Block extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
	}

	// --------------------------------------------------------------------

	/**
	 * 비정상적인 접근
	 */
	public function index()
	{
		show_error(lang('unusual_approach'));
	}

	// --------------------------------------------------------------------

	/**
	 * 사이트 차단
	 */
	public function site()
	{
		show_error(SETTING_site_block_contents);
	}

	// --------------------------------------------------------------------

	/**
	 * ip 차단
	 */
	public function ip()
	{
		show_error(SETTING_block_client_ip_contents);
	}
}

//EOF