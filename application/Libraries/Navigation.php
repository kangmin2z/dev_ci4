<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Navigation
{
    private $CI = null;

    public function run()
    {
        $this->CI =& get_instance();

		$home = lang('navigation_home');
		$bbs = lang('navigation_bbs');
		$joiner = lang('navigation_joiner');

		$user_name = lang('mypage');

        $result = array('<a href="'. BASE_URL . '">' . $home . '</a><span id="navigation_after_home">');

        $segments = $this->CI->uri->segment_array();
        $flag = (!empty($segments[1])) ? $segments[1] : '';

        if ($this->is_bbs($flag) === true)
        {

            $result[] = $bbs;
            $bbs_name = $this->get_bbs_name($segments[3]);
            if (!empty($bbs_name)) {
                $result[] = "<a href='" . BASE_URL . "bbs/lists/{$segments[3]}'>{$bbs_name}</a>";
            }
        }
        else
        {
            if ($this->is_plugin($flag) === true)
            {
                $plugin = $this->get_plugin_name($segments[2]);
                if (!empty($plugin)) {
                    $result[] = "<a href='" . BASE_URL . "plugin/{$segments[2]}/{$plugin['method']}'>{$plugin['name']}</a>";
                }
            }
            else if($this->is_user($flag) === true)
            {
                $result[] = "<a href='" . BASE_URL . "user/modify'>{$user_name}</a>";
            }
        }

        return (count($result) > 0) ? implode($joiner, $result).'</span>' : '';
    }

    /**
     * 게시판인지 확인
     *
     * @param string $flag
     *
     * @return bool
     */
    private function is_bbs($flag = '')
    {
        return (strtolower($flag) === 'bbs');
    }

    /**
     * 플러그인인지 확인
     *
     * @param string $flag
     *
     * @return bool
     */
    private function is_plugin($flag = '')
    {
        return (strtolower($flag) === 'plugin');
    }

    /**
     * 마이페이지 확인
     *
     * @param string $flag
     *
     * @return bool
     */
    private function is_user($flag = '')
    {
        return (strtolower($flag) === 'user');
    }

    private function get_plugin_name($plugin = '')
    {
        $return = '';

        if (!empty($plugin))
        {
            $list = array(
                'onedayonememo' => array('name' => lang('plugin_onedayonememo'), 'method' => 'lists')
            );
            $return = (!empty($list[$plugin])) ? $list[$plugin] : '';
        }

        return $return;
    }

    /**
     * 게시판명 반환
     *
     * @param $bbs_id
     *
     * @return mixed
     */
    private function get_bbs_name($bbs_id)
    {
        $this->CI->load->model('bbs_model');

        return $this->CI->bbs_model->get_bbs_name($bbs_id);
    }
}

//EOF