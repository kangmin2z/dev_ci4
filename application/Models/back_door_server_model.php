<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Back_door_server_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // --------------------------------------------------------------------

    /**
     * 프로젝트 유무 검사
     *
     * @param string $project_code
     *
     * @return bool
     */
    public function check_project_code($project_code)
    {
        $query = '
                    SELECT
                        COUNT(idx) AS cnt
                    FROM
                        bd_master
                    WHERE
                        project_code = ?
                ';

        $query = $this->db->query($query, array($project_code));

        $row = $query->row();

        if(isset($row->cnt) == TRUE && (int)$row->cnt > 0)
        {
            return TRUE;
        }

        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * 로그 인서트
     *
     * @param array $values
     *
     * @return mixed
     */
    public function insert_install_log($values)
    {
        $query = '
                    INSERT INTO
                        bd_install_log
                    (
                        project_idx
                        , type
                        , server_info
                        , client_ip
                        , flag
                    )
                    VALUES
                    (
                        (SELECT idx FROM bd_master WHERE project_code = ?)
                        , ?
                        , ?
                        , ?
                        , 1
                    )
                ';

        $query = $this->db->query($query, array(
                                               $values['project_code']
                                               , $values['type']
                                               , $values['server_info']
                                               , $values['client_ip']
                                          )
        );

        return $query;
    }
}

//EOF