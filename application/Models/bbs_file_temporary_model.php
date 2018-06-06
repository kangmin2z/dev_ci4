<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Bbs_file_temporary_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // --------------------------------------------------------------------

    /**
     * insert
     *
     * @author KangMin
     * @since 2014.06.29
     *
     * @return bool
     */
    public function insert($user_idx, $conversion_filename)
    {
        $query = '
						INSERT INTO
							tb_bbs_file_temporary
							(
							user_idx
							, conversion_filename
							, timestamp
							)
						VALUES
							(
							?
							, ?
							, UNIX_TIMESTAMP(NOW())
							)
					';

        $query = $this->db->query($query, array(
                $user_idx
                , $conversion_filename
            )
        );

        return $query;
    }

    // --------------------------------------------------------------------

    /**
     * 삭제할 첨부파일 리스트
     *
     * @author  KangMin
     * @since 2014.06.29
     *
     * @param int arrangefiles_last_idx (쿼리에서 한방에 할 수도 있지만, 시점 차이로 문제가 발생할 수 있어서 받아서 처리한다.)
     *
     * @return array
     */
    public function get_arrangefiles($arrangefiles_last_idx)
    {
        $query = '
                SELECT
                    BBS_FILE_TEMPORARY.idx
                    , BBS_FILE_TEMPORARY.conversion_filename
                    , BBS_FILE.idx AS bbs_file_idx
                FROM
                    tb_bbs_file_temporary AS BBS_FILE_TEMPORARY
                LEFT JOIN
                    tb_bbs_file AS BBS_FILE
                ON
                    BBS_FILE_TEMPORARY.conversion_filename = BBS_FILE.conversion_filename
                WHERE
                    BBS_FILE_TEMPORARY.timestamp < ' . strtotime('-1 day') . '
                    AND BBS_FILE.idx IS NULL 
					AND BBS_FILE_TEMPORARY.idx > ?
                ORDER BY
                    BBS_FILE_TEMPORARY.idx DESC
                ';

        $query = $this->db->query($query, array($arrangefiles_last_idx));
        $rows = $query->result();

        return $rows;
    }
}

//EOF