<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Bbs_file_model extends CI_Model
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
		 * @since 2012.03.02
		 *
		 * @param array
		 *
		 * @return bool
		 */
		public function insert($values)
		{
			$query = '
						INSERT INTO
							tb_bbs_file
							(
							bbs_idx
							, article_idx
							, user_idx
							, is_wysiwyg
							, original_filename
							, conversion_filename
							, mime
							, capacity
							)
						VALUES
							(
							?
							, ?
							, ?
							, ?
							, ?
							, ?
							, ?
							, ?
							)
					';

			$query = $this->db->query($query, array(
													$values['bbs_idx']
                                                    , $values['article_idx']
                                                    , $values['user_idx']
                                                    , $values['is_wysiwyg']
                                                    , $values['original_filename']
                                                    , $values['conversion_filename']
                                                    , $values['mime']
                                                    , $values['capacity']
													)
									);

			return $query;
		}

		// --------------------------------------------------------------------

		/**
		 * 첨부파일 호출
		 *
		 * @author KangMin
		 * @since 2012.03.04
		 *
		 * @param int
		 *
		 * @return array
		 */
		public function get_files($article_idx, $is_wysiwyg = 0)
		{
			$query = '
						SELECT
							idx
							, bbs_idx
							, article_idx
							, user_idx
							, is_wysiwyg
							, original_filename
							, conversion_filename
							, mime
							, capacity
							, sequence
						FROM
							tb_bbs_file
						WHERE
							article_idx = ?
							AND is_wysiwyg = ?
						ORDER BY
							sequence ASC
							, idx ASC
					';

			$query = $this->db->query($query, array($article_idx, $is_wysiwyg));
			$rows = $query->result();

			return $rows;
		}

		// --------------------------------------------------------------------

		/**
		 * 첨부파일 삭제
		 *
		 * @author KangMin
		 * @since 2012.03.04
		 *
		 * @param int
		 * @param int
		 *
		 * @return bool
		 */
		public function delete_files($article_idx, $idxs, $add_where='')
		{
			$query = '
						DELETE
						FROM
							tb_bbs_file
						WHERE
							article_idx = ?
							AND idx IN ('.$idxs.') '.$add_where.'
					';

			$query = $this->db->query($query, array($article_idx));

			return $query;
		}

		// --------------------------------------------------------------------

		/**
		 * 실제 파일을 삭제하기 위한 호출
		 *
		 * @author KangMin
		 * @since 2012.03.04
		 *
		 * @param int
		 * @param string
		 *
		 * @return array
		 */
		public function get_filenames($article_idx, $idxs, $add_where='')
		{
			$query = '
						SELECT
							conversion_filename
						FROM
							tb_bbs_file
						WHERE
							article_idx = ?
							AND idx IN ('.$idxs.') '.$add_where.'
					';

			$query = $this->db->query($query, array($article_idx));
			$rows = $query->result();

			return $rows;
		}

		// --------------------------------------------------------------------

		/**
		 * 개인 파일 첨부 총용량
		 *
		 * @author KangMin
		 * @since 2012.03.04
		 *
		 * @return int (bytes)
		 */
		public function get_total_capacity()
		{
			$query = '
						SELECT
							IFNULL(SUM(capacity), 0) AS capacity
						FROM
							tb_bbs_file
						WHERE
							user_idx = ?
							AND article_idx IN (SELECT idx FROM tb_bbs_article WHERE is_deleted = 0)
					';

			$query = $this->db->query($query, array(USER_INFO_idx));
			$row = $query->row();

			return (int)$row->capacity;
		}

		// --------------------------------------------------------------------

		/**
		 * 모든 실제 파일명 호출
		 *
		 * @desc 파일만 올리고 글등록은 하지 않은 파일 등을 주기적으로 삭제해주기 위함
		 *
		 * @author KangMin
		 * @since 2012.03.04
		 *
		 * @mode string => all : 글삭제여부 상관없이 모든 파일, live : 삭제되지 않는 글들의 모든 파일
		 *
		 * @return array
		 */
		public function get_all_filenames($mode)
		{
			if($mode == 'live')
			{
				$add_where = ' AND article_idx IN (SELECT idx FROM tb_bbs_article WHERE is_deleted = 0) ';
			}
			else
			{
				$add_where = '';
			}

			$query = '
						SELECT
							conversion_filename
						FROM
							tb_bbs_file
						WHERE
							conversion_filename IS NOT NULL
							AND conversion_filename <> \'\' '.$add_where.'
					';

			$query = $this->db->query($query);
			$rows = $query->result();

			return $rows;
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
							tb_bbs_file
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

        // --------------------------------------------------------------------

        /**
         * webzine, gallery 에서 사용할 이미지 1개를 불러온다.
         *
         * @desc 위지윅에서 올린건 섬네일을 하지 않기 때문에 좀 애매하지만 제외한다.
         *
         * @param $article_idx
         *
         * @return array
         */
        public function get_image($article_idx)
        {
            $query = '
                        SELECT
                            article_idx
                            , conversion_filename
                        FROM
                            tb_bbs_file
                        WHERE
                            is_wysiwyg = 0
                            AND mime IN (\'image/gif\', \'image/jpeg\', \'image/pjpeg\', \'image/png\', \'image/x-png\')
                            AND article_idx = ?
                        ORDER BY idx ASC
                        LIMIT 0, 1
                    ';

            $query = $this->db->query($query, array($article_idx));
            $row = $query->result();

            return $row;
        }

        // --------------------------------------------------------------------

        /**
         * 파일정보
         * @param $idx
         */
        public function get_file_by_download($idx)
        {
            $query = '
                    SELECT
                        original_filename
                        , conversion_filename
                    FROM
                        tb_bbs_file
                    WHERE
                        idx = ?
                    ';

            $query = $this->db->query($query, array($idx));
            $row = $query->result();

            return $row;
        }
	}

//EOF