<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Users_friend_model extends CI_Model 
	{
	    public function __construct()
	    {
	        parent::__construct();
	    }

		// --------------------------------------------------------------------

		/**
		 * 발신자가 수신자 친구목록에 존재하는지 확인
		 *
		 * @author KangMin
		 * @since 2012.04.16
		 * 
		 * @param int
		 * @param int
		 * 
		 * @return bool
		 */
		public function check_sender_in_friend($owner_user_idx, $friend_user_idx)
		{
			$query = '
						SELECT
							COUNT(idx) AS cnt
						FROM
							tb_users_friend
						WHERE
							user_idx = ?
							AND friend_user_idx = ?
					';

			$query = $this->db->query($query, array($owner_user_idx, $friend_user_idx));
			$row = $query->row();

			if(isset($row->cnt) == TRUE && (int)$row->cnt > 0)
			{
				return TRUE;
			}

			return FALSE;
		}

		// --------------------------------------------------------------------

		/**
		 * 친구목록
		 *
		 * @author KangMin
		 * @since 2012.04.18
		 *
		 */
		public function get_friend($page, $per_page, $add_where='')
		{
			$query = '
						SELECT
							USERS_FRIEND.idx
							, USERS_FRIEND.user_idx
							, USERS_FRIEND.friend_user_idx
							, USERS.user_id
							, USERS.name
							, USERS.nickname
							, USERS_FRIEND.timestamp
						FROM
							tb_users_friend AS USERS_FRIEND
							, tb_users AS USERS
						WHERE
							USERS_FRIEND.friend_user_idx = USERS.idx
							AND USERS_FRIEND.user_idx = ? '.$add_where.'
						ORDER BY
							USERS.nickname ASC
						LIMIT
							?, ?
					';

			$query = $this->db->query($query, array(USER_INFO_idx, $page, $per_page));
			$rows = $query->result();

			return $rows;	
		}

		// --------------------------------------------------------------------

		/**
		 * 친구 리스트 총 카운트
		 *
		 * @author KangMin
		 * @since 2012.02.24
		 *
		 * @return array
		 */
		public function get_friend_total_cnt($add_where='')
		{
			$query = '
						SELECT
							COUNT(USERS_FRIEND.idx) AS cnt
						FROM
							tb_users_friend AS USERS_FRIEND
							, tb_users AS USERS
						WHERE
							USERS_FRIEND.friend_user_idx = USERS.idx
							AND USERS_FRIEND.user_idx = ? '.$add_where.'
					';

			$query = $this->db->query($query, array(USER_INFO_idx));
			$row = $query->row();

			if(isset($row->cnt) == TRUE && (int)$row->cnt > 0)
			{
				return $row->cnt;
			}

			return 0;
		}

		// --------------------------------------------------------------------

		/**
		 * 친구 중복 확인
		 *
		 * @author KangMin
		 * @since 2012.01.24
		 *
		 * @param int
		 * 
		 * @return bool
		 */
		public function check_duplicate_friend($friend_user_idx)
		{
			$query = '
						SELECT
							COUNT(idx) AS cnt
						FROM
							tb_users_friend
						WHERE
							user_idx = ?
							AND friend_user_idx = ?
					';

			$query = $this->db->query($query, array(USER_INFO_idx
													, $friend_user_idx));
			$row = $query->row();

			if(isset($row->cnt) == TRUE && (int)$row->cnt > 0)
			{
				return TRUE;
			}

			return FALSE;    	
		}

		// --------------------------------------------------------------------

		/**
		 * insert
		 *
		 * @author KangMin
		 * @since 2012.01.24
		 *
		 * @param array
		 *
		 * @return bool
		 */
		public function insert($friend_user_idx)
		{
			$query = '
						INSERT INTO
							tb_users_friend
							(
							user_idx
							, friend_user_idx
							, timestamp
							)
						VALUES
							(
							?
							, ?
							, UNIX_TIMESTAMP(NOW())
							)
					';

			$query = $this->db->query($query, array(
													USER_INFO_idx
													, $friend_user_idx
													)
									);                                   

			return $query;
		}

		// --------------------------------------------------------------------

		/**
		 * 삭제
		 *
		 * @author KangMin
		 * @since 2012.02.24
		 *
		 * @param int
		 *
		 * @return bool
		 */
		public function delete_friend($idx, $add_where='')
		{
			$query = '
						DELETE FROM 
							tb_users_friend
						WHERE
							idx = ? '.$add_where.' 
					';

			$query = $this->db->query($query, array($idx));                                   

			return $query;
		}

		// --------------------------------------------------------------------

        /**
         * idx 유효성 (그냥 간단히...)
         * 
         * @author KangMin
         * @since 2011.12.31
         * 
         * @param int
         * 
         * @return bool
         */
        public function check_idx($req_idx, $add_where='')
        {
            $query = '
                        SELECT 
                            COUNT(idx) AS cnt
                        FROM
                            tb_users_friend
                        WHERE
                            idx = ? '.$add_where.'
                    ';
                    
			$query = $this->db->query($query, array($req_idx));
			$row = $query->row();

			if(isset($row->cnt) == TRUE && (int)$row->cnt > 0)
			{
				return TRUE;
			}

			return FALSE;                       
        }   
	}

//EOF