<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Bbs_contents_revision_model extends CI_Model 
	{
	    public function __construct()
	    {
	        parent::__construct();
	    }

		// --------------------------------------------------------------------

		/**
		 * 글내용 리비전 리스트
		 *
		 * @author KangMin
		 * @since 2012.02.24
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
							BBS_CONTENTS_REVISION.idx
							, BBS_CONTENTS_REVISION.bbs_idx
							, BBS_CONTENTS_REVISION.article_idx
							, BBS_CONTENTS_REVISION.contents_idx
							, BBS_CONTENTS_REVISION.exec_user_idx
							, USERS.user_id
							, USERS.name
							, USERS.nickname
							, BBS_CONTENTS_REVISION.contents
							, BBS_CONTENTS_REVISION.timestamp
							, BBS_CONTENTS_REVISION.client_ip
						FROM
							tb_bbs_contents_revision AS BBS_CONTENTS_REVISION
							, tb_users AS USERS
						WHERE
							BBS_CONTENTS_REVISION.exec_user_idx = USERS.idx 
							AND BBS_CONTENTS_REVISION.article_idx = ?
						ORDER BY
							BBS_CONTENTS_REVISION.idx DESC
						LIMIT 
							?, ?
					';

			$query = $this->db->query($query, array($idx, $page, $per_page));
			$rows = $query->result();

			return $rows;
		}

		// --------------------------------------------------------------------

        /**
         * 글내용 리비젼 총 카운트
		 *
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
							COUNT(BBS_CONTENTS_REVISION.idx) AS cnt
						FROM
							tb_bbs_contents_revision AS BBS_CONTENTS_REVISION
							, tb_users AS USERS
						WHERE
							BBS_CONTENTS_REVISION.exec_user_idx = USERS.idx 
							AND BBS_CONTENTS_REVISION.article_idx = ?
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

//EOF