<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
	
class Layout
{	    
	private $obj;
	private $layout;
	
	public function __construct($layout='layout_view')
	{
		$this->obj =& get_instance();
		$this->layout = $layout;
		$this->layout_only_contents = 'layout_only_contents_view'; //popup, alert
		$this->layout_admin = 'admin/layout_admin_view';
		$this->layout_admin_only_contents = 'admin/layout_admin_only_contents_view'; //popup, alert
	}

	// --------------------------------------------------------------------
	   
	//front 기본 view
	public function view($view, $data=NULL, $return=FALSE)
	{
		return $this->set_view($this->layout, $view, $data, $return);
	}

	// --------------------------------------------------------------------

	//front top/bottom 없는 view (alert 용)
	public function view_only_contents($view, $data=NULL, $return=FALSE)
	{
		return $this->set_view($this->layout_only_contents, $view, $data, $return);
	}

	// --------------------------------------------------------------------

	//admin 기본 view
	public function view_admin($view, $data=NULL, $return=FALSE)
	{
		return $this->set_view($this->layout_admin, 'admin/'.$view, $data, $return);		
	}

	// --------------------------------------------------------------------

	//admin top/bottom 없는 view (alert 용)
	public function view_admin_only_contents($view, $data=NULL, $return=FALSE)
	{
		return $this->set_view($this->layout_admin_only_contents, 'admin/'.$view, $data, $return);
	}

	// --------------------------------------------------------------------

	/**
	 * layout view 공용
	 *
	 * @param string
	 * @param mix
	 * @param bool
	 *
	 * @return ?
	 */
	private function set_view($layout, $view, $data, $return)
	{
		$loaded_data = array();
		$loaded_data['contents'] = $this->obj->load->view($view, $data, TRUE);
		
		if($return)
		{
			$output = $this->obj->load->view($layout, $loaded_data, TRUE);
			return $output;
		}
		else
		{
			$this->obj->load->view($layout, $loaded_data, FALSE);
		}
	}
}

//EOF