<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Setting_revision_model extends CI_Model 
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
							SETTING_REVISION.idx
							, SETTING_REVISION.setting_idx
							, SETTING_REVISION.parameter
							, SETTING_REVISION.value
							, SETTING_REVISION.exec_user_idx
							, USERS.user_id
							, USERS.name
							, USERS.nickname
							, SETTING_REVISION.timestamp
							, SETTING_REVISION.client_ip
						FROM
							tb_setting_revision AS SETTING_REVISION
							, tb_users AS USERS
						WHERE
							SETTING_REVISION.exec_user_idx = USERS.idx 
							AND SETTING_REVISION.setting_idx = ?
						ORDER BY
							SETTING_REVISION.idx DESC
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
							COUNT(SETTING_REVISION.idx) AS cnt
						FROM
							tb_setting_revision AS SETTING_REVISION
							, tb_users AS USERS
						WHERE
							SETTING_REVISION.exec_user_idx = USERS.idx 
							AND SETTING_REVISION.setting_idx = ?
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