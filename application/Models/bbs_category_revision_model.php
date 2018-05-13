<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Bbs_category_revision_model extends CI_Model 
	{
	    public function __construct()
	    {
	        parent::__construct();
	    }

		// --------------------------------------------------------------------
		
        /**
         * 카테고리 리비젼 리스트
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
							BBS_CATEGORY_REVISION.idx
                            , BBS_CATEGORY_REVISION.bbs_idx
							, BBS_CATEGORY_REVISION.category_idx
							, BBS_CATEGORY_REVISION.category_name
							, BBS_CATEGORY_REVISION.sequence
							, BBS_CATEGORY_REVISION.exec_user_idx
							, USERS.user_id
							, USERS.name
							, USERS.nickname
							, BBS_CATEGORY_REVISION.timestamp
							, BBS_CATEGORY_REVISION.client_ip
                            , BBS_CATEGORY_REVISION.is_used
						FROM
							tb_bbs_category_revision AS BBS_CATEGORY_REVISION
							, tb_users AS USERS
						WHERE
							BBS_CATEGORY_REVISION.exec_user_idx = USERS.idx 
							AND BBS_CATEGORY_REVISION.category_idx = ?
						ORDER BY
							BBS_CATEGORY_REVISION.idx DESC
						LIMIT 
							?, ?
					';
			
			$query = $this->db->query($query, array($idx, $page, $per_page));
			$rows = $query->result();

			return $rows;
		}

		// --------------------------------------------------------------------

        /**
         * 카테고리 리비젼 총 카운트
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
							COUNT(BBS_CATEGORY_REVISION.idx) AS cnt
						FROM
							tb_bbs_category_revision AS BBS_CATEGORY_REVISION
							, tb_users AS USERS
						WHERE
							BBS_CATEGORY_REVISION.exec_user_idx = USERS.idx 
							AND BBS_CATEGORY_REVISION.category_idx = ?
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