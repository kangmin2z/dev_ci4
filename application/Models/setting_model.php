<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Setting_model extends CI_Model 
	{
	    public function __construct()
	    {
	        parent::__construct();
	    }

		// --------------------------------------------------------------------
		
        /**
         * 기본세팅 호출
         * @author KangMin
         * @since 2011.11.09
         * 
         * @return array
         */
		public function get_setting($add_where='')
		{
			$query = ' 
						SELECT
							SETTING.idx
							, SETTING.parameter
							, SETTING.value
							, SETTING.exec_user_idx
							, USERS.user_id
							, USERS.name
							, USERS.nickname
							, SETTING.client_ip
							, CASE WHEN SUBSTRING(SETTING.parameter, -3) = \'_pc\' THEN \'pc\' ELSE \'mobile\' END AS viewport
						FROM
							tb_setting AS SETTING
							, tb_users AS USERS
						WHERE
							SETTING.exec_user_idx = USERS.idx '.$add_where.' 
						ORDER BY
							SETTING.parameter ASC
					';
			
			$query = $this->db->query($query);
			$rows = $query->result();

			return $rows;
		}

		// --------------------------------------------------------------------

        /**
         * 업데이트 (공용사용)
         * @author KangMin
         * @since 2011.11.20
         * 
		 * @param string
		 * @param mixed
		 *
         * @return bool
         */
		public function update($parameter, $value)
		{
			$value_compare = (is_null($value)) ? ' IS NOT ' : ' <> '; // value <> NULL 은 안되서...

			$query = '
						UPDATE
							tb_setting
						SET
							value = ?
							, exec_user_idx = ?
							, client_ip = ?
						WHERE
							parameter = ?
							AND BINARY(value) '.$value_compare.' ?
					';

			$query = $this->db->query($query, array(
													$value
													, USER_INFO_idx
													, $this->input->ip_address()
													, $parameter
													, $value
													)
									);

			//실제 업데이트를 하지 않아도 TRUE가 떨어져서
			if($this->db->affected_rows() > 0)
			{
				return TRUE;
			}
			else 
			{
				return FALSE;
			}
		}
	}

//END