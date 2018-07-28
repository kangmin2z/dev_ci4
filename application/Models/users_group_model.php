<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Users_group_model extends CI_Model 
	{
	    public function __construct()
	    {
	        parent::__construct();
	    }

		// --------------------------------------------------------------------
		
        /**
         * 회원그룹
         * @author KangMin
         * @since 2011.11.15
         * 
         * @return array
         */
		public function get_users_group($add_where='')
		{
			$query = ' 
						SELECT
							USERS_GROUP.idx
							, USERS_GROUP.group_name
							, USERS_GROUP.icon_path
							, USERS_GROUP.exec_user_idx
							, USERS.user_id
							, USERS.name
							, USERS.nickname
							, USERS_GROUP.client_ip
							, USERS_GROUP.is_used
						FROM
							tb_users_group AS USERS_GROUP
							, tb_users AS USERS
						WHERE
							USERS_GROUP.exec_user_idx = USERS.idx '.$add_where.' 
						ORDER BY
							USERS_GROUP.is_used DESC
							, USERS_GROUP.group_name ASC
							, USERS_GROUP.idx ASC
					';
			
			$query = $this->db->query($query);
			$rows = $query->result();

			return $rows;
		}

		// --------------------------------------------------------------------

        /**
         * 그룹명 중복체크
         * 
         * @author KangMin
         * @since 2011.12.11
         * 
         * @param string
         * 
         * @return bool
         */
        public function check_group_name($req_group_name, $add_where='')
        {
            $query = '
                        SELECT
                            COUNT(idx) AS cnt
                        FROM
                            tb_users_group
                        WHERE
                            group_name = ? '.$add_where.'
                    ';
                    
			$query = $this->db->query($query, array($req_group_name));
			$row = $query->row();

			if(isset($row->cnt) == TRUE && (int)$row->cnt > 0)
			{
				return TRUE;
			}

			return FALSE;            
        }

		// --------------------------------------------------------------------

        /**
         * 그룹 수정
         * 
         * @author KangMin
         * @since 2011.12.19
         * 
         * @param int
		 * @param string
		 * @param string
		 * @param int
         * 
         * @return bool
         */
		public function update_group($req_idx, $req_group_name, $req_icon_path, $req_is_used)
		{
			$query = '
						UPDATE
							tb_users_group
						SET
							group_name = ?
							, icon_path = ?
							, exec_user_idx = ?
							, client_ip = ?
							, is_used = ?
						WHERE
							idx = ?
					';

			$query = $this->db->query($query, array(
                                                    $req_group_name
                                                    , $req_icon_path
                                                    , USER_INFO_idx
                                                    , $this->input->ip_address()
													, $req_is_used
													, $req_idx
													)
									);
			return $query;      
		}

		// --------------------------------------------------------------------

        /**
         * 그룹 추가
         * 
         * @author KangMin
         * @since 2011.12.19
         * 
		 * @param string
		 * @param string
         * 
         * @return bool
         */
		public function insert_group($req_group_name, $req_icon_path)
		{
			$query = '
						INSERT INTO
							tb_users_group
							(
							group_name
							, icon_path
							, exec_user_idx
							, client_ip
							, is_used
							)
						VALUES 
							(
							?
							, ?
							, ?
							, ?
							, 0
							)
					';

			$query = $this->db->query($query, array(
                                                    $req_group_name
                                                    , $req_icon_path
                                                    , USER_INFO_idx
                                                    , $this->input->ip_address()
													)
									);
			return $query;      
		}

		// --------------------------------------------------------------------

        /**
         * 구릅아이콘 
         * 
         * @author KangMin
         * @since 2012.03.14
         * 
         * @return array
         */
		public function get_group_icon()
		{
			$query = '
						SELECT
							idx
							, icon_path
						FROM
							tb_users_group			
						WHERE
							TRIM(icon_path) <> \'\'
							AND icon_path IS NOT NULL
					';

			$query = $this->db->query($query);
			$rows = $query->result();

			return $rows;
		}
	}

//EOF