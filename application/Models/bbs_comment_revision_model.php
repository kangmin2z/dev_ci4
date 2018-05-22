<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Bbs_comment_revision_model extends CI_Model 
	{
	    public function __construct()
	    {
	        parent::__construct();
	    }

		// --------------------------------------------------------------------

		/**
		 * 댓글 리비전 리스트
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
							BBS_COMMENT_REVISION.idx
							, BBS_COMMENT_REVISION.bbs_idx
							, BBS_COMMENT_REVISION.article_idx
							, BBS_COMMENT_REVISION.comment_idx
							, BBS_COMMENT_REVISION.exec_user_idx
							, USERS.user_id
							, USERS.name
							, USERS.nickname
							, BBS_COMMENT_REVISION.comment
							, BBS_COMMENT_REVISION.timestamp
							, BBS_COMMENT_REVISION.client_ip
							, BBS_COMMENT_REVISION.is_deleted
						FROM
							tb_bbs_comment_revision AS BBS_COMMENT_REVISION
							, tb_users AS USERS
						WHERE
							BBS_COMMENT_REVISION.exec_user_idx = USERS.idx 
							AND BBS_COMMENT_REVISION.comment_idx = ?
						ORDER BY
							BBS_COMMENT_REVISION.idx DESC
						LIMIT 
							?, ?
					';

			$query = $this->db->query($query, array($idx, $page, $per_page));
			$rows = $query->result();

			return $rows;
		}

		// --------------------------------------------------------------------

        /**
         * 댓글 리비젼 총 카운트
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
							COUNT(BBS_COMMENT_REVISION.idx) AS cnt
						FROM
							tb_bbs_comment_revision AS BBS_COMMENT_REVISION
							, tb_users AS USERS
						WHERE
							BBS_COMMENT_REVISION.exec_user_idx = USERS.idx 
							AND BBS_COMMENT_REVISION.comment_idx = ?
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