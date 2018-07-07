<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Client_ip_block_model extends CI_Model 
	{
	    public function __construct()
	    {
	        parent::__construct();
	    }

		// --------------------------------------------------------------------

        /**
         * 차단 IP인지 체크
         * @author KangMin
         * @since 2011.11.17
         * 
         * @return bool
         */
		public function check_client_ip_block($req_client_ip=NULL)
		{
			if($req_client_ip == NULL) $req_client_ip = $this->input->ip_address();

			$query = '
						SELECT
							idx
						FROM
							tb_client_ip_block
						WHERE
							client_ip = ?
					';

			$query = $this->db->query($query, array($req_client_ip));
			$row = $query->row();

			if(isset($row->idx) == TRUE)
			{
				return TRUE;
			}

			return FALSE;				
		}

		// --------------------------------------------------------------------

        /**
         * 차단 IP 내역
         * @author KangMin
         * @since 2011.11.23
         * 
		 * @param int
		 * @param int
		 *
         * @return array
         */
		public function get_client_ip_block($page, $per_page)
		{
			$query = '
						SELECT
							idx
							, client_ip
							, timestamp
						FROM
							tb_client_ip_block
						ORDER BY
							idx DESC
						LIMIT
							?, ?
					';

			$query = $this->db->query($query, array($page, $per_page));
			$rows = $query->result();

			return $rows;						
		}		

		// --------------------------------------------------------------------

        /**
         * 차단 IP 내역 총 건수
         * @author KangMin
         * @since 2011.11.23
         * 
         * @return int
         */
		public function get_client_ip_block_total_cnt()
		{
			$query = '
						SELECT
							COUNT(idx) AS cnt
						FROM
							tb_client_ip_block
					';

			$query = $this->db->query($query);
			$row = $query->row();

			if(isset($row->cnt) == TRUE && (int)$row->cnt > 0)
			{
				return $row->cnt;
			}

			return 0;					
		}	

		// --------------------------------------------------------------------

        /**
         * 차단 IP 삽입
         * @author KangMin
         * @since 2011.11.23
		 *
         * @return int
         */
		public function set_client_ip_block($req_client_ip)
		{
			$query = '
						INSERT INTO
							tb_client_ip_block
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

			$query = $this->db->query($query, array($req_client_ip));

			return $query;			
		}

		// --------------------------------------------------------------------

        /**
         * 차단 IP 삭제
         * @author KangMin
         * @since 2011.11.23
		 *
         * @return int
         */
		public function del_client_ip_block($idx)
		{
			$query = '
						DELETE
						FROM
							tb_client_ip_block
						WHERE
							idx = ?
					';

			$query = $this->db->query($query, array($idx));

			return $query;			
		}

	}

//END