<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Bbs_comment_model extends CI_Model
	{
        protected $agent;

        public function __construct()
        {
            parent::__construct();

            $this->agent = substr(strtoupper($this->viewport), 0, 1);
        }

		// --------------------------------------------------------------------

        /**
         * 해당 게시판의 해당회원의 마지막 댓글 등록 시각
         * bbs_article_model 의 함수와 테이블명만 다름
         *
         * @author KangMin
         * @since 2012.01.15
         *
         * @param int
         *
         * @return int
         */
        public function get_last_timestamp($bbs_idx)
        {
            $query = '
                        SELECT
                            timestamp_insert
                        FROM
                            tb_bbs_comment
                        WHERE
                            user_idx = ?
                            AND bbs_idx = ?
                        ORDER BY
                            idx DESC
                        LIMIT
                            0, 1
                    ';

			$query = $this->db->query($query, array(USER_INFO_idx
                                                    , $bbs_idx)
                                    );
			$row = $query->row();

			if(count($row) > 0)
			{
				return (int)$row->timestamp_insert;
			}

			return 0;
        }

		// --------------------------------------------------------------------

		/**
		 * 댓글 작성
		 *
		 * @author KangMin
		 * @since 2012.01.18
		 *
		 * @param int
		 * @param int
		 * @param string
		 *
		 * @return bool
		 */
		public function insert_comment($bbs_idx, $article_idx, $comment)
		{
			$query = '
						INSERT INTO
							tb_bbs_comment
							(
							bbs_idx
							, article_idx
							, user_idx
							, exec_user_idx
							, comment
							, timestamp_insert
							, client_ip_insert
							, agent_insert
							)
						VALUES
							(
							?
							, ?
							, ?
							, ?
							, ?
							, UNIX_TIMESTAMP(NOW())
							, ?
							, ?
							)
					';

			$query = $this->db->query($query, array(
													$bbs_idx
													, $article_idx
													, USER_INFO_idx
													, USER_INFO_idx
													, $comment
													, $this->input->ip_address()
													, $this->agent
													)
									);

			return $query;
		}

		// --------------------------------------------------------------------

		/**
		 * 댓글 리스트 (리스트하단)
		 *
		 * @author KangMin
		 * @since 2012.01.19
		 *
		 * @param int
		 * @param int
		 * @param int
		 * @param int
		 *
		 * @return array
		 */
		public function lists($article_idx, $page, $per_page, $sort, $add_where='', $select_exec_user_info=FALSE)
		{
			//어드민에서는 페이징없이 전체..
			//page, per_page 에 0,0 을 넣으면 무시로 본다
			if($page == 0 && $per_page == 0)
			{
				$limit = '';
			}
			else
			{
				$limit = ' LIMIT ?, ? ';
			}

			$add_select = '';

			//필요할때만 액션을 가한 회원 정보 셀렉트
			if($select_exec_user_info == TRUE)
			{
				$add_select = '
                            , (SELECT user_id FROM tb_users WHERE idx = BBS_COMMENT.exec_user_idx) AS exec_user_id
                            , (SELECT name FROM tb_users WHERE idx = BBS_COMMENT.exec_user_idx) AS exec_name
                            , (SELECT nickname FROM tb_users WHERE idx = BBS_COMMENT.exec_user_idx) AS exec_nickname
							';
			}

			$query = '
						SELECT
							BBS_COMMENT.idx
							, BBS_COMMENT.bbs_idx
							, BBS_COMMENT.article_idx
							, BBS_COMMENT.user_idx
							, USERS.user_id
							, USERS.name
							, USERS.nickname
							, USERS.avatar_used
                        , BBS_COMMENT.exec_user_idx
							, BBS_COMMENT.comment
							, BBS_COMMENT.vote_count
							, BBS_COMMENT.timestamp_insert
							, BBS_COMMENT.timestamp_update
							, BBS_COMMENT.client_ip_insert
							, BBS_COMMENT.client_ip_update
							, BBS_COMMENT.is_deleted
							, BBS_COMMENT.agent_insert
							, BBS_COMMENT.agent_last_update
							'.$add_select.'
						FROM
							tb_bbs_comment AS BBS_COMMENT
							, tb_users AS USERS
						WHERE
							USERS.idx = BBS_COMMENT.user_idx
							AND BBS_COMMENT.article_idx = ? '.$add_where.'
						ORDER BY
                            BBS_COMMENT.idx '.$sort.'
						'.$limit.'
					';

			//어드민에서는 페이징없이 전체..
			//page, per_page 에 0,0 을 넣으면 무시로 본다
			if($page == 0 && $per_page == 0)
			{
				$query = $this->db->query($query, array($article_idx));
			}
			else
			{
				$query = $this->db->query($query, array($article_idx
														, $page
														, $per_page));
			}

			$rows = $query->result();

			return $rows;
		}

		// --------------------------------------------------------------------

		/**
		 * 댓글 리스트 총 갯수
		 *
		 * @author KangMin
		 * @since 2012.01.19
		 *
		 * @param int
		 *
		 * @return int
		 */
		public function lists_total_cnt($article_idx, $add_where='')
		{
			$query = '
						SELECT
							COUNT(BBS_COMMENT.idx) AS cnt
						FROM
							tb_bbs_comment AS BBS_COMMENT
							, tb_users AS USERS
						WHERE
							USERS.idx = BBS_COMMENT.user_idx
							AND BBS_COMMENT.article_idx = ? '.$add_where.'
					';

			$query = $this->db->query($query, array($article_idx));
			$row = $query->row();

			if(isset($row->cnt) == TRUE && (int)$row->cnt > 0)
			{
				return $row->cnt;
			}

			return 0;
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
                            tb_bbs_comment
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

		// --------------------------------------------------------------------

        /**
         * 댓글 호출
         *
         * @author KangMin
         * @since 2012.01.21
         *
         * @param int
         *
         * @return array
         */
        public function get_comment($req_idx, $add_where='')
        {
            $query = '
                        SELECT
                            idx
                            , bbs_idx
                            , article_idx
                            , user_idx
                            , exec_user_idx
                            , comment
                            , vote_count
                            , timestamp_insert
                            , timestamp_update
                            , client_ip_insert
                            , client_ip_update
                            , is_deleted
							, agent_insert
							, agent_last_update
                        FROM
                            tb_bbs_comment
                        WHERE
                            idx = ? '.$add_where.'
                    ';

			$query = $this->db->query($query, array($req_idx));
			$row = $query->row();

			return $row;
        }

		// --------------------------------------------------------------------

        /**
         * 댓글 수정
         *
         * @author KangMin
         * @since 2012.01.21
         *
         * @param int
         * @param string
         *
         * @return bool
         */
        public function update_comment($req_idx, $comment, $add_where='')
        {
            $query = '
                        UPDATE
                            tb_bbs_comment
                        SET
                            comment = ?
                            , exec_user_idx = ?
                            , timestamp_update = UNIX_TIMESTAMP(NOW())
                            , client_ip_update = ?
							, agent_last_update = ? 
                        WHERE
                            idx = ? '.$add_where.'
                    ';

			$query = $this->db->query($query, array(
													$comment
                                                    , USER_INFO_idx
                                                    , $this->input->ip_address()
													, $this->agent
                                                    , $req_idx
													)
									);

			return $query;
        }

		// --------------------------------------------------------------------

        /**
         * 댓글 수정 (관리자)
         *
         * @author KangMin
         * @since 2012.01.21
         *
         * @param int
         * @param string
		 * @param int
		 * @param int
         *
         * @return bool
         */
        public function update_comment_admin($req_idx, $comment, $vote_count, $is_deleted, $add_where='')
        {
            $query = '
                        UPDATE
                            tb_bbs_comment
                        SET
                            comment = ?
                            , exec_user_idx = ?
                            , timestamp_update = UNIX_TIMESTAMP(NOW())
                            , client_ip_update = ?
							    , vote_count = ?
							    , is_deleted = ?
                        WHERE
                            idx = ? '.$add_where.'
                    ';

			$query = $this->db->query($query, array(
													$comment
                                                    , USER_INFO_idx
                                                    , $this->input->ip_address()
													, $vote_count
													, $is_deleted
                                                    , $req_idx
													)
									);

			return $query;
        }

		// --------------------------------------------------------------------

        /**
         * 댓글 삭제
         *
         * @author KangMin
         * @since 2012.01.21
         *
         * @param int
         *
         * @return bool
         */
        public function delete_comment($req_idx, $add_where='')
        {
            $query = '
                        UPDATE
                            tb_bbs_comment
                        SET
                            exec_user_idx = ?
                            , timestamp_update = UNIX_TIMESTAMP(NOW())
                            , client_ip_update = ?
                            , is_deleted = 1
                        WHERE
                            idx = ? '.$add_where.'
                    ';

			$query = $this->db->query($query, array(
                                                    USER_INFO_idx
                                                    , $this->input->ip_address()
                                                    , $req_idx
													)
									);

			return $query;
        }

		// --------------------------------------------------------------------

		/**
		 * count update (vote)
		 * bbs_article_model 의 update_count_article 와 거의 동일
		 * comment에는 count 가 vote_count 밖에 없지만 동일 방식으로 사용하기 위해서
		 *
		 *
		 * @author KangMin
		 * @since 2011.12.28
		 *
		 * @param int
		 * @param string
		 * @param int (+, -)
		 *
		 * @return bool
		 */
		public function update_count_comment($idx, $field, $value)
		{
			$query = '
						UPDATE
							tb_bbs_comment
						SET
							'.$field.' = '.$field.' + '.(int)+$value.'
						WHERE
							idx = ?
					';

			$query = $this->db->query($query, array(
													$idx
													)
									);

			return $query;
		}

		// --------------------------------------------------------------------

		/**
		 * 글쓴이 idx 호출
		 * bbs_article_model 의 get_user_idx 와 동일
		 *
		 * @author KangMin
		 * @since 2012.01.22
		 *
		 * @param int
		 *
		 * @return int
		 */
		public function get_user_idx($idx, $add_where='')
		{
			$query = '
						SELECT
							user_idx
						FROM
							tb_bbs_comment
						WHERE
							idx = ? '.$add_where.'
					';

			$query = $this->db->query($query, array($idx));
			$row = $query->row();

			if(count($row) > 0)
			{
				return (int)$row->user_idx;
			}

			return 0;
		}

		// --------------------------------------------------------------------

		/**
		 * 코멘트 갯수
		 *
		 * @desc 실제 DB를 조회해서 삭제되지 않은 코멘트 갯수를 연산해낸다. (검수용)
		 * @author KangMin
		 * @since 2012.02.24
		 *
		 * @param int
		 *
		 * @return int
		 */
		public function get_comment_count($article_idx)
		{
			$query = '
						SELECT
							COUNT(idx) AS cnt
						FROM
							tb_bbs_comment
						WHERE
							article_idx = ?
							AND is_deleted = 0
					';

			$query = $this->db->query($query, array($article_idx));
			$row = $query->row();

			if(isset($row->cnt) == TRUE && (int)$row->cnt > 0)
			{
				return $row->cnt;
			}

			return 0;
		}

		// --------------------------------------------------------------------

		/**
		 * 회원별 코멘트 갯수
		 *
		 * @desc 실제 DB를 조회해서 삭제되지 않은 코멘트 갯수를 연산해낸다. (검수용)
		 * @desc get_comment_count 와 거의 동일하긴한데...흠.
		 * @author KangMin
		 * @since 2012.02.24
		 *
		 * @param int
		 *
		 * @return int
		 */
		public function get_comment_count_user($user_idx)
		{
			$query = '
						SELECT
							COUNT(idx) AS cnt
						FROM
							tb_bbs_comment
						WHERE
							user_idx = ?
							AND is_deleted = 0
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
		 * 최근 댓글
		 *
		 * @author KangMin
		 * @since 2012.02.25
		 *
		 * @param int
		 *
		 * @return array
		 */
		public function recently_comment($limit, $add_where='', $select_exec_user_info=FALSE)
		{
			$add_select = '';

			//필요할때만 액션을 가한 회원 정보 셀렉트
			if($select_exec_user_info == TRUE)
			{
				$add_select = '
                            , (SELECT user_id FROM tb_users WHERE idx = BBS_COMMENT.exec_user_idx) AS exec_user_id
                            , (SELECT name FROM tb_users WHERE idx = BBS_COMMENT.exec_user_idx) AS exec_name
                            , (SELECT nickname FROM tb_users WHERE idx = BBS_COMMENT.exec_user_idx) AS exec_nickname
							';
			}

			$query = '
						SELECT
							BBS_COMMENT.idx
							, BBS_COMMENT.bbs_idx
							, (SELECT bbs_id FROM tb_bbs WHERE idx = BBS_COMMENT.bbs_idx) AS bbs_id
							, BBS_COMMENT.article_idx
							, BBS_COMMENT.user_idx
							, USERS.user_id
							, USERS.name
							, USERS.nickname
							, USERS.avatar_used
                        , BBS_COMMENT.exec_user_idx
							, BBS_COMMENT.comment
							, BBS_COMMENT.vote_count
							, BBS_COMMENT.timestamp_insert
							, BBS_COMMENT.timestamp_update
							, BBS_COMMENT.client_ip_insert
							, BBS_COMMENT.client_ip_update
							, BBS_COMMENT.is_deleted
							, (SELECT value FROM tb_bbs_setting WHERE bbs_idx = BBS_COMMENT.bbs_idx AND parameter = \'bbs_comment_sort\') AS bbs_comment_sort
							, (SELECT value FROM tb_bbs_setting WHERE bbs_idx = BBS_COMMENT.bbs_idx AND parameter = \'bbs_count_list_comment' . (strtoupper($this->viewport) == 'PC' ? '_pc' : '') . '\') AS bbs_count_list_comment
							'.$add_select.'
						FROM
							(
								SELECT
									*
								FROM
									tb_bbs_comment
								WHERE
									bbs_idx IN (SELECT bbs_idx FROM tb_bbs_setting WHERE parameter = \'bbs_allow_group_view_comment\' AND value LIKE \'%"0"%\')
								ORDER BY
									idx DESC
								LIMIT
									0, ?
							) AS BBS_COMMENT
							, tb_users AS USERS
							, tb_bbs_article AS BBS_ARTICLE
						WHERE
							USERS.idx = BBS_COMMENT.user_idx
							AND BBS_ARTICLE.is_deleted = 0
							AND BBS_ARTICLE.idx = BBS_COMMENT.article_idx '.$add_where.'
						ORDER BY
                            BBS_COMMENT.idx DESC
						LIMIT
							0, ?
					';

            $query = $this->db->query($query, array($limit*10, $limit));

			$rows = $query->result('array');

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
							tb_bbs_comment
						SET
							bbs_idx = ?
							, client_ip_update = ?
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

		// --------------------------------------------------------------------

		/**
		 * 최근댓글 캐싱을 위한 테이블 마지막 수정시각
		 *
		 * @author KangMin
		 * @since 2012.03.07
		 *
		 * @return int
		 */
		public function lastest_update_time()
		{
			$query = '
						SELECT
							MAX(timestamp_insert) AS timestamp_insert
							, IFNULL(MAX(timestamp_update), 0) AS timestamp_update
						FROM
							tb_bbs_comment
					';

			$query = $this->db->query($query);
			$row = $query->row();

			if(isset($row->timestamp_insert) == TRUE && (int)$row->timestamp_insert > 0) //일단 한개라도 글이 있으면..
			{
				return (int)max($row->timestamp_insert, $row->timestamp_update);
			}

			return 0;
		}
	}

//EOF