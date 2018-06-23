<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Bbs_setting_revision_model extends CI_Model 
	{
	    public function __construct()
	    {
	        parent::__construct();
	    }

		// --------------------------------------------------------------------
		
        /**
         * 기본세팅 리비젼 리스트
         * @author KangMin
         * @since 2011.11.09
         * 
		 * @param int
		 * @param int
		 * @param int
		 *
         * @return array
         */
		public function get_revision($idx, $page, $per_page)
		{
			$query = ' 
						SELECT
							BBS_SETTING_REVISION.idx
							, BBS_SETTING_REVISION.setting_idx
							, BBS_SETTING_REVISION.parameter
							, BBS_SETTING_REVISION.value
							, BBS_SETTING_REVISION.exec_user_idx
							, USERS.user_id
							, USERS.name
							, USERS.nickname
							, BBS_SETTING_REVISION.timestamp
							, BBS_SETTING_REVISION.client_ip
						FROM
							tb_bbs_setting_revision AS BBS_SETTING_REVISION
							, tb_users AS USERS
						WHERE
							BBS_SETTING_REVISION.exec_user_idx = USERS.idx 
							AND BBS_SETTING_REVISION.setting_idx = ?
						ORDER BY
							BBS_SETTING_REVISION.idx DESC
						LIMIT 
							?, ?
					';
			
			$query = $this->db->query($query, array($idx, $page, $per_page));
			$rows = $query->result();

			return $rows;
		}

		// --------------------------------------------------------------------

        /**
         * 기본세팅 리비젼 총 카운트
         * @author KangMin
         * @since 2011.11.23
         * 
		 * @param int
		 *
         * @return int
         */
		public function get_revision_total_cnt($idx)
		{
			$query = ' 
						SELECT
							COUNT(BBS_SETTING_REVISION.idx) AS cnt
						FROM
							tb_bbs_setting_revision AS BBS_SETTING_REVISION
							, tb_users AS USERS
						WHERE
							BBS_SETTING_REVISION.exec_user_idx = USERS.idx 
							AND BBS_SETTING_REVISION.setting_idx = ?
					';
			
			$query = $this->db->query($query, array($idx));
			$row = $query->row();

			if(isset($row->cnt) == TRUE && (int)$row->cnt > 0)
			{
				return $row->cnt;
			}

			return 0;
		}
	}

//END