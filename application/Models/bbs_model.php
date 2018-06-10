<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Bbs_model extends CI_Model
	{
	    public function __construct()
	    {
	        parent::__construct();
	    }

		// --------------------------------------------------------------------

        /**
         * bbs_idx 존재여부 체크
         *
         * @author KangMin
         * @since 2011.11.09
         *
         * @param int
         *
         * @return bool
         */
        public function check_bbs_idx($req_bbs_idx)
        {
            $query = '
                        SELECT
                            COUNT(idx) AS cnt
                        FROM
                            tb_bbs
                        WHERE
                            idx = ?
                    ';

			$query = $this->db->query($query, array($req_bbs_idx));
			$row = $query->row();

			if(isset($row->cnt) == TRUE && (int)$row->cnt > 0)
			{
				return TRUE;
			}

			return FALSE;
        }

		// --------------------------------------------------------------------

		/**
		 * bbs_id 유효성 체크
		 *
		 * @author KangMin
		 * @since 2011.12.27
		 *
		 * @param string
		 *
		 * @return mixed
		 */
		public function check_bbs_id($bbs_id)
		{
			$query = '
						SELECT
							BBS.idx
						FROM
							tb_bbs AS BBS
							, tb_bbs_setting AS BBS_SETTING
						WHERE
							BBS.idx = BBS_SETTING.bbs_idx
							AND BBS_SETTING.parameter = \'bbs_used\'
							AND BBS_SETTING.value = \'1\'
							AND BBS.bbs_id = ?
					';

			$query = $this->db->query($query, array($bbs_id));
			$row = $query->row();

			if(isset($row->idx) == TRUE && count($row) > 0)
			{
				return $row->idx;
			}

			return FALSE;
		}

		// --------------------------------------------------------------------

		/**
		 * bbs_id 유효성 체크 (다중)
		 *
		 * @author KangMin
		 * @since 2011.12.27
		 *
		 * @param array
		 *
		 * @return mixed
		 */
		public function check_bbs_ids($bbs_ids)
		{
			$query = '
						SELECT
							BBS.idx
							, BBS.bbs_id
						FROM
							tb_bbs AS BBS
							, tb_bbs_setting AS BBS_SETTING
						WHERE
							BBS.idx = BBS_SETTING.bbs_idx
							AND BBS_SETTING.parameter = \'bbs_used\'
							AND BBS_SETTING.value = \'1\'
							AND BBS.bbs_id IN (\''.join('\',\'', $bbs_ids).'\')
					';

			$query = $this->db->query($query);
			$rows = $query->result();

			return $rows;
		}

		// --------------------------------------------------------------------

		/**
		 * bbs_idx 로 bbs_id 호출
		 *
		 * @author KangMin
		 * @since 2012.02.08
		 *
		 * @param int
		 *
		 * @return string
		 */
		public function get_bbs_id($bbs_idx)
		{
			$query = '
						SELECT
							bbs_id
						FROM
							tb_bbs
						WHERE
							idx = ?
					';

			$query = $this->db->query($query, array($bbs_idx));
			$row = $query->row();

			return $row->bbs_id;
		}

		// --------------------------------------------------------------------

		/**
		 * 게시물 이동시 셀렉트용
		 *
		 * @author KangMin
		 * @since 2012.03.07
		 *
		 * @return array
		 */
		public function get_bbs_and_category()
		{
			$query = '
						SELECT
							 BBS.idx AS bbs_idx
							 , BBS.bbs_id
							 , (SELECT value FROM tb_bbs_setting WHERE bbs_idx = BBS.idx AND parameter = \'bbs_name\') AS bbs_name
							 , (SELECT value FROM tb_bbs_setting WHERE bbs_idx = BBS.idx AND parameter = \'bbs_used\') AS bbs_used
							 , BBS_CATEGORY.idx AS category_idx
							 , BBS_CATEGORY.category_name
							 , BBS_CATEGORY.is_used AS category_used
						FROM
							tb_bbs AS BBS
						LEFT JOIN
							tb_bbs_category AS BBS_CATEGORY
						ON
							BBS.idx = BBS_CATEGORY.bbs_idx
						ORDER BY
							bbs_used DESC
							, bbs_name ASC
							, category_used DESC
							, BBS_CATEGORY.sequence ASC
							, BINARY(BBS_CATEGORY.category_name) ASC
					';

			$query = $this->db->query($query);
			$rows = $query->result();

			return $rows;
		}

        public function get_bbs_name($bbs_id = '')
        {
            $return = '';

            if (!empty($bbs_id)) {

                $query = "
                    SELECT
                        `bbs_setting`.`value` AS `bbs_name`
                    FROM
                        `tb_bbs` AS `bbs`,
                        `tb_bbs_setting` AS `bbs_setting`
                    WHERE
                        `bbs`.`bbs_id` = '{$bbs_id}'
                        AND `bbs`.`idx` = `bbs_setting`.`bbs_idx`
                        AND `bbs_setting`.`parameter` = 'bbs_name'
                    LIMIT 1
                ";
                $query = $this->db->query($query);
                $result = $query->result('array');

                if (!empty($result[0]['bbs_name'])) {
                    $return = $result[0]['bbs_name'];
                }
            }

            return $return;
        }
	}

//EOF