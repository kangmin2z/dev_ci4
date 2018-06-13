<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Bbs_hit_model extends CI_Model 
	{
	    public function __construct()
	    {
	        parent::__construct();
	    }

		// --------------------------------------------------------------------

		/**
		 * hit 업데이트
		 *
		 * @author KangMin
		 * @since 2012.01.06
		 *
		 * @param int
		 * @parma int
		 *
		 * @return bool
		 */
		public function update($bbs_idx, $article_idx)
		{
			$query = '
						UPDATE
							tb_bbs_hit
						SET
							hit = hit + 1
						WHERE
							bbs_idx = ?
							AND article_idx = ?							
					';

			$query = $this->db->query($query, array(
													$bbs_idx
													, $article_idx
													)
									);                                    

			return $query;   
		}	

		// --------------------------------------------------------------------

		/**
		 * hit 삽입
		 *
		 * @author KangMin
		 * @since 2012.01.06
		 *
		 * @param int
		 * @parma int
		 *
		 * @return bool
		 */
		public function insert($bbs_idx, $article_idx)
		{
			$query = '
						INSERT INTO
							tb_bbs_hit
							(
							bbs_idx
							, article_idx
							, hit
							)
						VALUES
							(
							?
							, ?
							, 1
							)					
					';

			$query = $this->db->query($query, array(
													$bbs_idx
													, $article_idx
													)
									);                                    

			return $query;   
		}

		// --------------------------------------------------------------------
		
		/**
		 * hit 체크
		 *
		 * @author KangMin
		 * @since 2012.01.06
		 *
		 * @param int
		 * @parma int
		 *
		 * @return bool
		 */
		public function check($bbs_idx, $article_idx)
		{
			$query = '
						SELECT
							COUNT(idx) AS cnt
						FROM
							tb_bbs_hit
						WHERE
							bbs_idx = ?
							AND article_idx = ?
					';

			$query = $this->db->query($query, array(
													$bbs_idx
													, $article_idx
													)
									);              
			$row = $query->row();

			if(isset($row->cnt) == TRUE && (int)$row->cnt > 0)
			{
				return TRUE;
			}

			return FALSE;        
		}

		// --------------------------------------------------------------------

		/**
		 * 게시판 이동
		 *
		 * @author KangMin
		 * @since 2012.03.07
		 *
		 * @param int
		 * @parma int
		 *
		 * @return bool
		 */
		public function move_bbs($article_idx, $move_bbs_idx)
		{
			$query = '
						UPDATE
							tb_bbs_hit
						SET
							bbs_idx = ?
						WHERE
							article_idx = ?
					';	

			$query = $this->db->query($query, array(
													$move_bbs_idx
                                                    , $article_idx
													)
									);                                    

			return $query;   
		}
	}

//EOF