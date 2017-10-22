<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Back_door_client
{
    /**
     * 인스톨 로그 적재
     *
     * @param string $project_code (e.g. tapbbs)
     * @param int $type (0:실패, 1:성공)
     */
    public function install_log($project_code, $type=0)
    {
        $CI =& get_instance();

        $result = array();
        $result['project_code'] = $project_code;
        $result['type'] = $type;
        $result['server_info'] = $this->get_phpinfo();
        $result['client_ip'] = $CI->input->ip_address();

        $CI->curl->simple_post('http://www.tapbbs.com/back_door_server/install_log', $result);
    }

    // --------------------------------------------------------------------

    /**
     * phpinfo
     */
    private function get_phpinfo()
    {
        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents();
        ob_end_clean();

        return $phpinfo;
    }
}

//EOF