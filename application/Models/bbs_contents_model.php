<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Bbs_contents_model extends CI_Model 
	{
	    public function __construct()
	    {
	        parent::__construct();
	    }

		// --------------------------------------------------------------------

		/**
		 * contents insert
		 *
		 * @author KangMin
		 * @since 2011.12.28
		 *
		 * @param array
		 *
		 * @return bool
		 */
		public function insert_contents($values)
		{
			$query = '
						INSERT INTO
							tb_bbs_contents
							(
							bbs_idx
							, article_idx
							, exec_user_idx
							, contents
							, client_ip
							)
						VALUES
							(
							?
							, ?
							, ?
							, ?
							, ?
							)
					';

			$query = $this->db->query($query, array(
													$values['bbs_idx']
													, $values['article_idx']
													, USER_INFO_idx
													, $values['contents']
													, $this->input->ip_address()
													)
									);                                    

			return $query;   
		}

		// --------------------------------------------------------------------

		/**
		 * contents update
		 *
		 * @author KangMin
		 * @since 2011.12.28
		 *
		 * @param int
		 * @param array
		 *
		 * @return bool
		 */
		public function update_contents($req_idx, $values, $add_where='')
		{
			$query = '
						UPDATE
							tb_bbs_contents
						SET
							exec_user_idx = ?
							, contents = ?
							, client_ip = ?
						WHERE
							article_idx = ? '.$add_where.'
					';

			$query = $this->db->query($query, array(
													USER_INFO_idx
													, $values['contents']
													, $this->input->ip_address()
													, $req_idx
													)
									);                                    

			return $query;   
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
							tb_bbs_contents
						SET
							bbs_idx = ?
							, client_ip = ?
						WHERE
							article_idx = ?
					';	

			$query = $this->db->query($query, array(
													$move_bbs_idx
													, $this->input->ip_address()
                                                    , $article_idx
													)
									);                                    

			return $query;   
		}
	}

//EOF