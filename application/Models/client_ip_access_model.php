<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Client_ip_access_model extends CI_Model 
	{
	    public function __construct()
	    {
	        parent::__construct();
	    }

		// --------------------------------------------------------------------

        /**
         * 접근내역 삭제
         * @author KangMin
         * @since 2011.11.17
         * 
		 * @param int
		 *
         * @return bool
         */
		public function del_client_ip_access($term)
		{
			$query = ' 
						DELETE
						FROM
							tb_client_ip_access
						WHERE
							timestamp < (UNIX_TIMESTAMP(NOW()) - ?) 
						LIMIT 1000
					';
			
			$query = $this->db->query($query, array($term));

			return $query;
		}

		// --------------------------------------------------------------------

        /**
         * 마지막접근 시각
         * @author KangMin
         * @since 2011.11.17
         *
         * @return int
         */
		public function get_client_ip_access_last_timestamp()
		{
			$query = '
						SELECT
							timestamp
						FROM
							tb_client_ip_access
						WHERE
							client_ip = ?
						ORDER BY
							idx DESC
						LIMIT 
							0, 1
					';

			$query = $this->db->query($query, array($this->input->ip_address()));
			$row = $query->row();

			if(isset($row->timestamp) == TRUE)
			{
				return $row->timestamp;
			}

			return 0;				
		}

		// --------------------------------------------------------------------
		
        /**
         * 접근내역 저장
         * @author KangMin
         * @since 2011.11.17
         * 
         * @return bool
         */
		public function set_client_ip_access()
		{
			$query = ' 
						INSERT INTO
							tb_client_ip_access
							(
							client_ip
							, timestamp
							)
						VALUES
							(
							?
							, UNIX_TIMESTAMP(NOW())
							)
					';
			
			$query = $this->db->query($query, array($this->input->ip_address()));

			return $query;
		}

		// --------------------------------------------------------------------

        /**
         * 접근내역 리스트
         * @author KangMin
         * @since 2011.11.17
         * 
		 * @param int
		 * @param int
		 *
         * @return array
         */
		public function get_client_ip_access($page, $per_page)
		{
			$query = '
						SELECT
							v.client_ip
							, v.cnt
						FROM (
								SELECT
									client_ip
									, COUNT(client_ip) AS cnt
								FROM
									tb_client_ip_access
								GROUP BY
									client_ip
							) v
						ORDER BY
							v.cnt DESC
						LIMIT
							?, ?
					';

			$query = $this->db->query($query, array($page, $per_page));
			$rows = $query->result();

			return $rows;						
		}		

		// --------------------------------------------------------------------

        /**
         * 접근내역 총 건수
         * @author KangMin
         * @since 2011.11.23
         * 
         * @return int
         */
		public function get_client_ip_access_total_cnt()
		{
			$query = '
						SELECT
							COUNT(v.client_ip) AS cnt
						FROM (
								SELECT
									client_ip
									, COUNT(client_ip) AS cnt
								FROM
									tb_client_ip_access
								GROUP BY
									client_ip
							) v
					';

			$query = $this->db->query($query);
			$row = $query->row();

			if(isset($row->cnt) == TRUE && (int)$row->cnt > 0)
			{
				return $row->cnt;
			}

			return 0;					
		}	
	}

//END