<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Users_group_revision_model extends CI_Model 
	{
	    public function __construct()
	    {
	        parent::__construct();
	    }

		// --------------------------------------------------------------------
		
        /**
         * 회원그룹 리비젼 리스트
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
							USERS_GROUP_REVISION.idx
                            , USERS_GROUP_REVISION.group_idx
							, USERS_GROUP_REVISION.group_name
							, USERS_GROUP_REVISION.icon_path
							, USERS_GROUP_REVISION.exec_user_idx
							, USERS.user_id
							, USERS.name
							, USERS.nickname
							, USERS_GROUP_REVISION.timestamp
							, USERS_GROUP_REVISION.client_ip
                            , USERS_GROUP_REVISION.is_used
						FROM
							tb_users_group_revision AS USERS_GROUP_REVISION
							, tb_users AS USERS
						WHERE
							USERS_GROUP_REVISION.exec_user_idx = USERS.idx 
							AND USERS_GROUP_REVISION.group_idx = ?
						ORDER BY
							USERS_GROUP_REVISION.idx DESC
						LIMIT 
							?, ?
					';
			
			$query = $this->db->query($query, array($idx, $page, $per_page));
			$rows = $query->result();

			return $rows;
		}

		// --------------------------------------------------------------------

        /**
         * 회원그룹 리비젼 총 카운트
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
							COUNT(USERS_GROUP_REVISION.idx) AS cnt
						FROM
							tb_users_group_revision AS USERS_GROUP_REVISION
							, tb_users AS USERS
						WHERE
							USERS_GROUP_REVISION.exec_user_idx = USERS.idx 
							AND USERS_GROUP_REVISION.group_idx = ?
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