<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Bbs_article_revision_model extends CI_Model 
	{
	    public function __construct()
	    {
	        parent::__construct();
	    }

		// --------------------------------------------------------------------

		/**
		 * 아티클 리비전 리스트
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
							BBS_ARTICLE_REVISION.idx
							, BBS_ARTICLE_REVISION.bbs_idx
							, BBS_ARTICLE_REVISION.article_idx
							, BBS_ARTICLE_REVISION.category_idx
							, BBS_ARTICLE_REVISION.exec_user_idx
							, USERS.user_id
							, USERS.name
							, USERS.nickname
							, BBS_ARTICLE_REVISION.title
							, BBS_ARTICLE_REVISION.timestamp
							, BBS_ARTICLE_REVISION.client_ip
							, BBS_ARTICLE_REVISION.is_notice
							, BBS_ARTICLE_REVISION.is_secret
							, BBS_ARTICLE_REVISION.is_deleted
						FROM
							tb_bbs_article_revision AS BBS_ARTICLE_REVISION
							, tb_users AS USERS
						WHERE
							BBS_ARTICLE_REVISION.exec_user_idx = USERS.idx 
							AND BBS_ARTICLE_REVISION.article_idx = ?
						ORDER BY
							BBS_ARTICLE_REVISION.idx DESC
						LIMIT 
							?, ?
					';

			$query = $this->db->query($query, array($idx, $page, $per_page));
			$rows = $query->result();

			return $rows;
		}

		// --------------------------------------------------------------------

        /**
         * 아티클 리비젼 총 카운트
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
							COUNT(BBS_ARTICLE_REVISION.idx) AS cnt
						FROM
							tb_bbs_article_revision AS BBS_ARTICLE_REVISION
							, tb_users AS USERS
						WHERE
							BBS_ARTICLE_REVISION.exec_user_idx = USERS.idx 
							AND BBS_ARTICLE_REVISION.article_idx = ?
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