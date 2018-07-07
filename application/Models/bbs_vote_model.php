<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Bbs_vote_model extends CI_Model 
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
		 * @since 2012.01.22
		 *
		 * @param string
		 * @param int
		 * @param int
		 *
		 * @param bool
		 */
		public function insert($type, $bbs_idx, $idx)
		{
			$query = '
						INSERT INTO
							tb_bbs_vote
							(
							bbs_idx
							, '.$type.'_idx
							, user_idx_sender
							)
						VALUES
							(
							?
							, ?
							, ?
							)
					';
			
			$query = $this->db->query($query, array(
													$bbs_idx
                                                    , $idx
                                                    , USER_INFO_idx
													)
									);                                   

			return $query;         
		}

		// --------------------------------------------------------------------

		/**
		 * 추천 중복여부 확인
		 *
		 * @author KangMin
		 * @since 2012.01.22
		 *
		 * @param string
		 * @param int
		 *
		 * @return bool
		 */
		public function check_duplicate($type, $idx)
		{
			$query = '
						SELECT
							COUNT(idx) AS cnt
						FROM
							tb_bbs_vote
						WHERE
							'.$type.'_idx = ?
							AND user_idx_sender = ?
					';

			$query = $this->db->query($query, array($idx
													, USER_INFO_idx));
			$row = $query->row();

			if(isset($row->cnt) == TRUE && (int)$row->cnt > 0)
			{
				return TRUE;
			}

			return FALSE;    			
		}

		// --------------------------------------------------------------------

		/**
		 * 추천 갯수
		 * 
		 * @desc 실제 DB를 조회해서 갯수를 연산해낸다. (검수용)
		 *
		 * @author KangMin
		 * @since 2012.02.24
		 *
		 * @param string
		 * @param int
		 * 
		 * @return int
		 */
		public function get_vote_count($type, $idx)
		{
			$query = '
						SELECT 
							COUNT(idx) AS cnt
						FROM
							tb_bbs_vote
						WHERE
							'.$type.'_idx = ?
					';

			$query = $this->db->query($query, array($idx));
			$row = $query->row();

			if(isset($row->cnt) == TRUE && (int)$row->cnt > 0)
			{
				return $row->cnt;
			}

			return 0;
		}

		// --------------------------------------------------------------------

		/**
		 * 추천 갯수 멀티형
		 * 
		 * @desc 실제 DB를 조회해서 갯수를 연산해낸다. (검수용)
		 *
		 * @author KangMin
		 * @since 2012.02.24
		 *
		 * @param string
		 * @param array
		 * 
		 * @return array
		 */
		public function get_vote_count_multi($type, $idxs)
		{
			$query = '
						SELECT 
							'.$type.'_idx AS idx
							, COUNT(idx) AS cnt
						FROM
							tb_bbs_vote
						WHERE
							'.$type.'_idx IN ('.join(',',$idxs).') 
						GROUP BY
							idx
					';

			$query = $this->db->query($query);
			$rows = $query->result();

			return $rows;
		}

		// --------------------------------------------------------------------

		/**
		 * 회원별 추천한 갯수
		 * @desc 실제 DB를 조회해서 갯수를 연산해낸다. (검수용)
		 *
		 * @author KangMin
		 * @since 2012.02.24
		 *
		 * @param int
		 * 
		 * @return int
		 */
		public function get_vote_send_count_user($user_idx)
		{
			$query = '
						SELECT
							COUNT(idx) AS cnt
						FROM
							tb_bbs_vote
						WHERE
							user_idx_sender = ?
					';

			$query = $this->db->query($query, array($user_idx));
			$row = $query->row();

			if(isset($row->cnt) == TRUE && (int)$row->cnt > 0)
			{
				return $row->cnt;
			}

			return 0;
		}

		// --------------------------------------------------------------------

		/**
		 * 회원별 추천받은 갯수
		 * @desc 실제 DB를 조회해서 갯수를 연산해낸다. (검수용)
		 * @desc 삭제한 글의 추천수도.. 뭐.. 그냥 포함
		 *
		 * @author KangMin
		 * @since 2012.02.24
		 *
		 * @param int
		 * 
		 * @return int
		 */
		public function get_vote_receive_count_user($user_idx)
		{
			$query = '
						SELECT
							COUNT(idx) AS cnt
						FROM
							tb_bbs_vote
						WHERE
							article_idx IN (SELECT idx FROM tb_bbs_article WHERE user_idx = ?)
							OR comment_idx IN (SELECT idx FROM tb_bbs_comment WHERE user_idx = ?)
					';

			$query = $this->db->query($query, array($user_idx, $user_idx));
			$row = $query->row();

			if(isset($row->cnt) == TRUE && (int)$row->cnt > 0)
			{
				return $row->cnt;
			}

			return 0;
		}

		// --------------------------------------------------------------------

		/**
		 * 게시판 이동
		 *
		 * @author KangMin
		 * @since 2012.03.07
		 *
		 * @param int
		 * @param int
		 *
		 * @return bool
		 */
		public function move_bbs($article_idx, $move_bbs_idx)
		{
			$query = '
						UPDATE
							tb_bbs_vote
						SET
							bbs_idx = ?
						WHERE
							article_idx = ?
							OR comment_idx IN (SELECT idx FROM tb_bbs_comment WHERE article_idx = ?)
					';	

			$query = $this->db->query($query, array(
													$move_bbs_idx
                                                    , $article_idx
													, $article_idx
													)
									);                                    

			return $query;   
		}
	}

//EOF