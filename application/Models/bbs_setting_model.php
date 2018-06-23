<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Bbs_setting_model extends CI_Model
	{
	    public function __construct()
	    {
	        parent::__construct();
	    }

		// --------------------------------------------------------------------

        /**
         * 게시판관리
         *
         * @author KangMin
         * @since 2011.11.26
		 *
		 * @return array
         */
        public function get_bbs_setting()
        {
            $query = '
                        SELECT
                            BBS.idx AS bbs_idx
                            , BBS.bbs_id
                            , (
                                SELECT
                                    value
                                FROM
                                    tb_bbs_setting
                                WHERE
                                    bbs_idx = BBS.idx
                                    AND parameter = \'bbs_name\'
                                LIMIT
                                    0, 1
                                ) AS bbs_name
                            , (
                                SELECT
                                    value
                                FROM
                                    tb_bbs_setting
                                WHERE
                                    bbs_idx = BBS.idx
                                    AND parameter = \'bbs_used\'
                                LIMIT
                                    0, 1
                                ) AS bbs_used
                            , (
                                SELECT
                                    value
                                FROM
                                    tb_bbs_setting
                                WHERE
                                    bbs_idx = BBS.idx
                                    AND parameter = \'bbs_category_used\'
                                LIMIT
                                    0, 1
                                ) AS bbs_category_used
                        FROM
                            tb_bbs BBS
                        ORDER BY
                            bbs_used DESC
                            , bbs_name ASC
                    ';

			$query = $this->db->query($query);
			$rows = $query->result();

			return $rows;
        }

		// --------------------------------------------------------------------

        /**
         * bbs_id 중복체크
         *
         * @author KangMin
         * @since 2011.11.09
         *
         * @param string
         *
         * @return bool
         */
        public function check_bbs_id($bbs_id)
        {
            $query = '
                        SELECT
                            COUNT(idx) AS cnt
                        FROM
                            tb_bbs
                        WHERE
                            bbs_id = ?
                    ';

			$query = $this->db->query($query, array($bbs_id));
			$row = $query->row();

			if(isset($row->cnt) == TRUE && (int)$row->cnt > 0)
			{
				return TRUE;
			}

			return FALSE;
        }

		// --------------------------------------------------------------------

        /**
         * bbs_name 중복체크
         *
         * @author KangMin
         * @since 2011.11.09
         *
         * @param string
         *
         * @return bool
         */
        public function check_bbs_name($bbs_name)
        {
            $query = '
                        SELECT
                            COUNT(idx) AS cnt
                        FROM
                            tb_bbs_setting
                        WHERE
                            parameter = \'bbs_name\'
                            AND BINARY(value) = ?
                    ';

			$query = $this->db->query($query, array($bbs_name));
			$row = $query->row();

			if(isset($row->cnt) == TRUE && (int)$row->cnt > 0)
			{
				return TRUE;
			}

			return FALSE;
        }

		// --------------------------------------------------------------------

        /**
         * 게시판 추가 (bbs)
         *
         * @author KangMin
         * @since 2011.11.09
         *
         * @param string
         *
         * @return bool
         */
        public function set_bbs($bbs_id)
        {
            $query = '
                        INSERT INTO
                            tb_bbs
                            (
                            bbs_id
                            , exec_user_idx
                            , timestamp
                            , client_ip
                            )
                        VALUES
                            (
                            ?
                            , ?
                            , UNIX_TIMESTAMP(NOW())
                            , ?
                            )
                    ';

			$query = $this->db->query($query, array(
													$bbs_id
													, USER_INFO_idx
													, $this->input->ip_address()
													)
									);

			return $query;
        }

		// --------------------------------------------------------------------

        /**
         * 게시판 추가 (bbs_setting)
         *
         * @author KangMin
         * @since 2011.11.09
         *
         * @param string
         *
         * @return bool
         */
        public function set_bbs_setting($bbs_name)
        {
            $query = '
                        INSERT INTO
                            tb_bbs_setting
                            (
                            bbs_idx
                            , parameter
                            , value
                            , exec_user_idx
                            , client_ip
                            )
                        VALUES
                            (
                            ?
                            , \'bbs_name\'
                            , ?
                            , ?
                            , ?
                            ),
                            (
                            ?
                            , \'bbs_used\'
                            , 0
                            , ?
                            , ?
                            )

                    ';

			$query = $this->db->query($query, array(
                                                    $this->db->insert_id()
													, $bbs_name
													, USER_INFO_idx
													, $this->input->ip_address()
                                                    , $this->db->insert_id()
													, USER_INFO_idx
													, $this->input->ip_address()
													)
									);

			return $query;
        }

		// --------------------------------------------------------------------

        /**
         * 게시판 추가 상세 (bbs_setting)
         *
         * @author KangMin
         * @since 2011.11.09
         *
         * @param string
         *
         * @return bool
         */
        public function set_bbs_setting_detail($req_bbs_id)
        {
            $query = '
						INSERT INTO
							tb_bbs_setting
							(
							bbs_idx
							, parameter
							, value
							, exec_user_idx
							, client_ip
							)
							SELECT
								(SELECT idx FROM tb_bbs WHERE bbs_id = ? LIMIT 1)
								, parameter
								, value
								, ?
								, ?
							FROM
								tb_setting
							WHERE
								default_bbs = 1
                    ';

			$query = $this->db->query($query, array(
                                                    $req_bbs_id
													, USER_INFO_idx
													, $this->input->ip_address()
													)
									);

			return $query;
        }

		// --------------------------------------------------------------------

        /**
         * 게시판 설정 상세
         *
         * @author KangMin
         * @since 2011.11.09
         *
         * @param int
         *
         * @return array
         */
		public function get_bbs_setting_detail($bbs_idx)
		{
			$query = '
						SELECT
							BBS_SETTING.idx
							, BBS_SETTING.parameter
							, BBS_SETTING.value
							, BBS_SETTING.exec_user_idx
							, USERS.user_id
							, USERS.name
							, USERS.nickname
							, BBS_SETTING.client_ip
							, CASE WHEN SUBSTRING(BBS_SETTING.parameter, -3) = \'_pc\' THEN \'pc\' ELSE \'mobile\' END AS viewport
						FROM
							tb_bbs_setting AS BBS_SETTING
							, tb_users AS USERS
						WHERE
							BBS_SETTING.exec_user_idx = USERS.idx
							AND BBS_SETTING.bbs_idx = ?
						ORDER BY
							parameter ASC
					';

			$query = $this->db->query($query, array($bbs_idx, $bbs_idx));
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
		 * @param int
		 *
         * @return bool
         */
		public function update($parameter, $value, $req_bbs_idx)
		{
			$value_compare = (is_null($value)) ? ' IS NOT ' : ' <> '; // value <> NULL 은 안되서...

			$query = '
						UPDATE
							tb_bbs_setting
						SET
							value = ?
							, exec_user_idx = ?
							, client_ip = ?
						WHERE
							parameter = ?
							AND BINARY(value) '.$value_compare.' ?
							AND bbs_idx = ?
					';

			$query = $this->db->query($query, array(
													$value
													, USER_INFO_idx
													, $this->input->ip_address()
													, $parameter
													, $value
													, $req_bbs_idx
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

		// --------------------------------------------------------------------

		/**
		 * 모든 게시판의 욕필터링 호출
		 *
		 * @author KangMin
		 * @since 2012.02.26
		 *
		 * @return array
		 */
		public function get_bbs_block_string()
		{
			$query = '
						SELECT
							bbs_idx
							, value
						FROM
							tb_bbs_setting
						WHERE
							parameter = \'bbs_block_string\'
					';

			$query = $this->db->query($query);
			$rows = $query->result();

			return $rows;
		}

		// --------------------------------------------------------------------

		/**
		 * 모든 게시판의 일부 설정값 호출
		 *
		 * @author KangMin
		 * @since 2012.05.02
		 *
		 * @param array
		 *
		 * @return array
		 */
		public function get_bbs_setting_section($parameters)
		{
			$query = '
						SELECT
							bbs_idx
							, parameter
							, value
						FROM
							tb_bbs_setting
						WHERE
							parameter IN (\''.join('\',\'', $parameters).'\')
					';
			
			$query = $this->db->query($query);
			$rows = $query->result();

			return $rows;
		}
	}
//EOF