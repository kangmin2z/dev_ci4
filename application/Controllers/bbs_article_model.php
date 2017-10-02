<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Bbs_article_model extends CI_Model
{
    protected $agent;

    public function __construct()
    {
        parent::__construct();

        $this->agent = substr(strtoupper($this->viewport), 0, 1);
    }

    // --------------------------------------------------------------------

    /**
     * article insert
     *
     * @author KangMin
     * @since 2011.12.28
     *
     * @param array
     *
     * @return bool
     */
    public function insert_article($values)
    {
        $query = '
						INSERT INTO
							tb_bbs_article
							(
							bbs_idx
							, category_idx
							, user_idx
							, exec_user_idx
							, title
							, timestamp_insert
							, client_ip_insert
							, html_used
							, is_notice
							, is_secret
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
							, ?
							, ?
							, ?
							)
					';

        $query = $this->db->query($query, array(
            $values['bbs_idx']
            , $values['category_idx']
            , USER_INFO_idx
            , USER_INFO_idx
            , $values['title']
            , $this->input->ip_address()
            , $values['html_used']
            , $values['is_notice']
            , $values['is_secret']
            , $this->agent
        )
        );

        return $query;
    }

    // --------------------------------------------------------------------

    /**
     * article update
     *
     * @author KangMin
     * @since 2011.12.28
     *
     * @param int
     * @param array
     *
     * @return bool
     */
    public function update_article($req_idx, $values, $add_where='')
    {
        //카테고리는 사용을 끌수도 있으므로 안넘어오면 수정하지 않는다.
        if($values['category_idx'] == NULL)
        {
            $add_set = '';
        }
        else
        {
            $add_set = ' , category_idx = ? ';
        }

        $query = '
						UPDATE
							tb_bbs_article
						SET
							exec_user_idx = ?
							, title = ?
							, timestamp_update = UNIX_TIMESTAMP(NOW())
							, client_ip_update = ?
							, html_used = ?
							, is_notice = ?
							, is_secret = ? '.$add_set.'
							, agent_last_update = ?
						WHERE
							idx = ? '.$add_where.'
					';

        if($values['category_idx'] == NULL)
        {
            $query = $this->db->query($query, array(
                USER_INFO_idx
                , $values['title']
                , $this->input->ip_address()
                , $values['html_used']
                , $values['is_notice']
                , $values['is_secret']
                , $this->agent
                , $req_idx
            )
            );
        }
        else
        {
            $query = $this->db->query($query, array(
                USER_INFO_idx
                , $values['title']
                , $this->input->ip_address()
                , $values['html_used']
                , $values['is_notice']
                , $values['is_secret']
                , $values['category_idx']
                , $this->agent
                , $req_idx
            )
            );
        }

        return $query;
    }

    // --------------------------------------------------------------------

    /**
     * article update admin
     *
     * @author KangMin
     * @since 2011.12.28
     *
     * @param int
     * @param array
     *
     * @return bool
     */
    public function update_article_admin($req_idx, $values, $add_where='')
    {
        //카테고리는 사용을 끌수도 있으므로 안넘어오면 수정하지 않는다.
        if($values['category_idx'] == NULL)
        {
            $add_set = '';
        }
        else
        {
            $add_set = ' , category_idx = ? ';
        }

        $query = '
						UPDATE
							tb_bbs_article
						SET
							exec_user_idx = ?
							, title = ?
							, timestamp_update = UNIX_TIMESTAMP(NOW())
							, client_ip_update = ?
							, html_used = ?
							, is_notice = ?
							, is_secret = ?
							, comment_count = ?
							, vote_count = ?
							, scrap_count = ?
							, is_deleted = ? '.$add_set.'
						WHERE
							idx = ? '.$add_where.'
					';

        if($values['category_idx'] == NULL)
        {
            $query = $this->db->query($query, array(
                USER_INFO_idx
                , $values['title']
                , $this->input->ip_address()
                , $values['html_used']
                , $values['is_notice']
                , $values['is_secret']
                , $values['comment_count']
                , $values['vote_count']
                , $values['scrap_count']
                , $values['is_deleted']
                , $req_idx
            )
            );
        }
        else
        {
            $query = $this->db->query($query, array(
                USER_INFO_idx
                , $values['title']
                , $this->input->ip_address()
                , $values['html_used']
                , $values['is_notice']
                , $values['is_secret']
                , $values['comment_count']
                , $values['vote_count']
                , $values['scrap_count']
                , $values['is_deleted']
                , $values['category_idx']
                , $req_idx
            )
            );
        }

        return $query;
    }

    // --------------------------------------------------------------------

    /**
     * article delete
     *
     * @author KangMin
     * @since 2011.12.28
     *
     * @param int
     *
     * @return bool
     */
    public function delete_article($req_idx, $add_where='')
    {
        $query = '
						UPDATE
							tb_bbs_article
						SET
							exec_user_idx = ?
							, is_deleted = 1
							, client_ip_update = ?
							, timestamp_update = UNIX_TIMESTAMP(NOW())
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
     * count update (comment, vote, scrap)
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
    public function update_count_article($idx, $field, $value)
    {
        $query = '
						UPDATE
							tb_bbs_article
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
     * view
     *
     * @author KangMin
     * @since 2011.12.30
     *
     * @param int
     *
     * @return array
     */
    public function view($idx, $add_where='', $select_exec_user_info=FALSE)
    {
        $add_select = '';

        //필요할때만 액션을 가한 회원 정보 셀렉트
        if($select_exec_user_info == TRUE)
        {
            $add_select = '
                            , (SELECT user_id FROM tb_users WHERE idx = BBS_ARTICLE.exec_user_idx) AS exec_user_id
                            , (SELECT name FROM tb_users WHERE idx = BBS_ARTICLE.exec_user_idx) AS exec_name
                            , (SELECT nickname FROM tb_users WHERE idx = BBS_ARTICLE.exec_user_idx) AS exec_nickname
							';
        }

        $query = '
                        SELECT
                            BBS_ARTICLE.idx
                            , BBS_ARTICLE.bbs_idx
                            , BBS_ARTICLE.category_idx
							    , (SELECT value FROM tb_bbs_setting WHERE bbs_idx = BBS_ARTICLE.bbs_idx AND parameter = \'bbs_name\') AS bbs_name
                            , (SELECT category_name FROM tb_bbs_category WHERE idx = BBS_ARTICLE.category_idx) AS category_name
                            , BBS_ARTICLE.user_idx
							    , USERS.user_id
							    , USERS.name
							    , USERS.nickname
							    , USERS.avatar_used
							    , USERS.group_idx
                            , BBS_ARTICLE.exec_user_idx
                            , BBS_ARTICLE.title
                            , BBS_ARTICLE.comment_count
                            , BBS_ARTICLE.vote_count
                            , BBS_ARTICLE.scrap_count
                            , BBS_ARTICLE.timestamp_insert
                            , BBS_ARTICLE.timestamp_update
                            , BBS_ARTICLE.client_ip_insert
                            , BBS_ARTICLE.client_ip_update
							    , BBS_ARTICLE.html_used
                            , BBS_ARTICLE.is_notice
                            , BBS_ARTICLE.is_secret
                            , BBS_ARTICLE.is_deleted
                            , BBS_CONTENTS.contents
                            , IFNULL((SELECT hit FROM tb_bbs_hit WHERE bbs_idx = BBS_ARTICLE.bbs_idx AND article_idx = BBS_ARTICLE.idx), 0) AS hit
							    '.$add_select.'
							    , BBS_ARTICLE.agent_insert
							    , BBS_ARTICLE.agent_last_update
							    , CASE WHEN (BBS_ARTICLE.agent_insert = \'M\' AND BBS_ARTICLE.agent_last_update IS NULL) OR BBS_ARTICLE.agent_last_update = \'M\' THEN \'possible\' ELSE \'impossible\' END AS update_by_mobile
                        FROM
                            tb_bbs_article AS BBS_ARTICLE
                            , tb_bbs_contents AS BBS_CONTENTS
                            , tb_users AS USERS
                        WHERE
                            BBS_ARTICLE.bbs_idx = BBS_CONTENTS.bbs_idx
                            AND BBS_ARTICLE.idx = BBS_CONTENTS.article_idx
                            AND USERS.idx = BBS_ARTICLE.user_idx
                            AND BBS_ARTICLE.idx = ? '.$add_where.'
                    ';

        $query = $this->db->query($query, array($idx));
        $row = $query->row();

        return $row;
    }

    // --------------------------------------------------------------------

    /**
     * 이전글, 다음글
     * 공지글은 제외한다
     *
     * @author KangMin
     * @since 2012.02.02
     *
     * @param int
     * @param int
     *
     * @return array
     */
    public function get_pre_next($bbs_idx, $idx, $add_where='')
    {
        $query = '
						SELECT
							IFNULL(PRE.idx, \'\') AS idx_pre
							, IFNULL(PRE.title, \'\') AS title_pre
							, IFNULL(PRE.comment_count, 0) AS comment_count_pre
							, IFNULL(NEXT.idx, \'\') AS idx_next
							, IFNULL(NEXT.title, \'\') As title_next
							, IFNULL(NEXT.comment_count, 0) AS comment_count_next
						FROM
							tb_bbs_article AS BBS_ARTICLE
						LEFT JOIN
							(
							SELECT
								idx
								, title
								, comment_count
							FROM
								tb_bbs_article
							WHERE
								bbs_idx = ?
								AND idx < ?
								AND is_notice = 0 '.$add_where.'
							ORDER BY
								idx DESC
							LIMIT 1
							) PRE
						ON
							PRE.idx < ?
						LEFT JOIN
							(
							SELECT
								idx
								, title
								, comment_count
							FROM
								tb_bbs_article
							WHERE
								bbs_idx = ?
								AND idx > ?
								AND is_notice = 0 '.$add_where.'
							ORDER BY
								idx ASC
							LIMIT 1
							) NEXT
						ON
							NEXT.idx > ?
						WHERE
							BBS_ARTICLE.bbs_idx = ?
							AND BBS_ARTICLE.idx = ?
					';

        $query = $this->db->query($query, array($bbs_idx
                                                , $idx
                                                , $idx
                                                , $bbs_idx
                                                , $idx
                                                , $idx
                                                , $bbs_idx
                                                , $idx
        ));
        $row = $query->row();

        return $row;
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
                            tb_bbs_article
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
     * 게시판 리스트
     *
     * @author KangMin
     * @since 2012.01.04
     *
     * @param int
     * @param int
     * @param int
     *
     * @return array
     */
    public function lists($bbs_idx, $page, $per_page, $add_where='', $ignore_bbs_idx=FALSE, $ignore_notice=FALSE, $select_exec_user_info=FALSE, $search=array('date_start'=>'','date_end'=>'','writer'=>'','search_word'=>''))
    {
        //전체게시판 기준으로
        if($ignore_bbs_idx == TRUE)
        {
            $add_where_bbs_idx = '';
        }
        else
        {
            $add_where_bbs_idx = ' AND BBS_ARTICLE.bbs_idx = ? ';
        }

        //공지사항도 우선순위없이 정렬
        if($ignore_notice == TRUE)
        {
            $order_by = ' BBS_ARTICLE.idx DESC ';
        }
        else
        {
            $order_by = ' CASE WHEN BBS_ARTICLE.is_notice = 1 THEN BBS_ARTICLE.idx * 100000000 ELSE BBS_ARTICLE.idx END DESC ';
        }

        $add_select = '';

        //필요할때만 액션을 가한 회원 정보 셀렉트
        if($select_exec_user_info == TRUE)
        {
            $add_select = '
                            , (SELECT user_id FROM tb_users WHERE idx = BBS_ARTICLE_2.exec_user_idx) AS exec_user_id
                            , (SELECT name FROM tb_users WHERE idx = BBS_ARTICLE_2.exec_user_idx) AS exec_name
                            , (SELECT nickname FROM tb_users WHERE idx = BBS_ARTICLE_2.exec_user_idx) AS exec_nickname
							';
        }

        $query = '
                        SELECT
                            BBS_ARTICLE_2.idx
                            , BBS_ARTICLE_2.bbs_idx
                            , (SELECT value FROM tb_bbs_setting WHERE bbs_idx = BBS_ARTICLE_2.bbs_idx AND parameter = \'bbs_name\') AS bbs_name
                            , BBS_ARTICLE_2.category_idx
                            , (SELECT category_name FROM tb_bbs_category WHERE idx = BBS_ARTICLE_2.category_idx) AS category_name
                            , BBS_ARTICLE_2.user_idx
                            , USERS.user_id
                            , USERS.name
                            , USERS.nickname
                            , USERS.avatar_used
                            , BBS_ARTICLE_2.exec_user_idx
                            , BBS_ARTICLE_2.title
                            , BBS_ARTICLE_2.comment_count
                            , BBS_ARTICLE_2.vote_count
                            , BBS_ARTICLE_2.scrap_count
                            , BBS_ARTICLE_2.timestamp_insert
                            , BBS_ARTICLE_2.timestamp_update
                            , BBS_ARTICLE_2.client_ip_insert
                            , BBS_ARTICLE_2.client_ip_update
                            , BBS_ARTICLE_2.html_used
                            , BBS_ARTICLE_2.is_notice
                            , BBS_ARTICLE_2.is_secret
                            , BBS_ARTICLE_2.is_deleted
                            , BBS_CONTENTS.contents
                            , IFNULL((SELECT hit FROM tb_bbs_hit WHERE bbs_idx = BBS_ARTICLE_2.bbs_idx AND article_idx = BBS_ARTICLE_2.idx), 0) AS hit
							    '.$add_select.'
                        FROM
                            (
                                SELECT
                                    *
                                FROM
                                    tb_bbs_article AS BBS_ARTICLE
                                WHERE
                                    1 = 1
                                    '.$add_where_bbs_idx.'
                                    '.$add_where.'
                                    '.(trim($search['date_start']) !== '' ? ' AND BBS_ARTICLE.timestamp_insert >= '.(int)$search['date_start'].' ' : '').'
                                    '.(trim($search['date_end']) !== '' ? ' AND BBS_ARTICLE.timestamp_insert <= '.(int)$search['date_end'].' ' : '').'
                                    '.(trim($search['writer']) !== '' ? ' AND BBS_ARTICLE.user_idx IN (SELECT DISTINCT(idx) FROM (SELECT idx FROM tb_users WHERE user_id LIKE \'%'.$search['writer'].'%\' OR name LIKE \'%'.$search['writer'].'%\' OR nickname LIKE \'%'.$search['writer'].'%\' OR email LIKE \'%'.$search['writer'].'%\') w)' : '');

        if(trim($search['search_word']) !== '')
        {
            $query .= '
                            AND BBS_ARTICLE.idx IN (
                                                    SELECT
                                                        DISTINCT(article_idx)
                                                    FROM
                                                    (
                                                        SELECT
                                                            idx AS article_idx
                                                        FROM
                                                            tb_bbs_article
                                                        WHERE
                                                            title LIKE \'%'.$search['search_word'].'%\'

                                                        UNION ALL

                                                        SELECT
                                                            article_idx
                                                        FROM
                                                            tb_bbs_contents
                                                        WHERE
                                                            contents LIKE \'%'.$search['search_word'].'%\'

                                                        UNION ALL

                                                        SELECT
                                                            article_idx
                                                        FROM
                                                            tb_bbs_comment
                                                        WHERE
                                                            comment LIKE \'%'.$search['search_word'].'%\'

                                                        UNION ALL

                                                        SELECT
                                                            article_idx
                                                        FROM
                                                            tb_bbs_tag
                                                        WHERE
                                                            tag LIKE \'%'.$search['search_word'].'%\'

                                                        UNION ALL

                                                        SELECT
                                                            article_idx
                                                        FROM
                                                            tb_bbs_url
                                                        WHERE
                                                            url LIKE \'%'.$search['search_word'].'%\'
                                                    ) v
                                                )
                            ';
        }

        $query .= '
						ORDER BY
                            '.$order_by.'
						LIMIT
							?, ?
					';
        $query .= '     ) AS BBS_ARTICLE_2
                            , tb_bbs_contents AS BBS_CONTENTS
                            , tb_users AS USERS
                        WHERE
                            BBS_ARTICLE_2.bbs_idx = BBS_CONTENTS.bbs_idx
                            AND BBS_ARTICLE_2.idx = BBS_CONTENTS.article_idx
                            AND USERS.idx = BBS_ARTICLE_2.user_idx ';

        $query .= '
						ORDER BY
                            '.str_replace('BBS_ARTICLE', 'BBS_ARTICLE_2', $order_by).'
					';

        //전체게시판 기준으로
        if($ignore_bbs_idx == TRUE)
        {
            $query = $this->db->query($query, array($page
                                                    , $per_page));
        }
        else
        {
            $query = $this->db->query($query, array($bbs_idx
                                                    , $page
                                                    , $per_page));
        }

        $rows = $query->result();

        return $rows;
    }

    // --------------------------------------------------------------------

    /**
     * 게시판 리스트 총 카운트
     *
     * @author KangMin
     * @since 2012.01.04
     *
     * @param int
     *
     * @return int
     */
    public function lists_total_cnt($bbs_idx, $add_where='', $ignore_bbs_idx=FALSE, $search=array('date_start'=>'','date_end'=>'','writer'=>'','search_word'=>''))
    {
        //전체게시판 기준으로
        if($ignore_bbs_idx == TRUE)
        {
            $add_where_bbs_idx = '';
        }
        else
        {
            $add_where_bbs_idx = ' AND BBS_ARTICLE.bbs_idx = ? ';
        }

        $query = '
                        SELECT
                            COUNT(BBS_ARTICLE_2.idx) AS cnt
                        FROM
                            (
                                SELECT
                                    *
                                FROM
                                    tb_bbs_article AS BBS_ARTICLE
                                WHERE
                                    1 = 1
                                    '.$add_where_bbs_idx.'
                                    '.$add_where.'
                                    '.(trim($search['date_start']) !== '' ? ' AND BBS_ARTICLE.timestamp_insert >= '.(int)$search['date_start'].' ' : '').'
                                    '.(trim($search['date_end']) !== '' ? ' AND BBS_ARTICLE.timestamp_insert <= '.(int)$search['date_end'].' ' : '').'
                                    '.(trim($search['writer']) !== '' ? ' AND BBS_ARTICLE.user_idx IN (SELECT DISTINCT(idx) FROM (SELECT idx FROM tb_users WHERE user_id LIKE \'%'.$search['writer'].'%\' OR name LIKE \'%'.$search['writer'].'%\' OR nickname LIKE \'%'.$search['writer'].'%\' OR email LIKE \'%'.$search['writer'].'%\') w)' : '');

        if(trim($search['search_word']) !== '')
        {
            $query .= '
                            AND BBS_ARTICLE.idx IN (
                                                    SELECT
                                                        DISTINCT(article_idx)
                                                    FROM
                                                    (
                                                        SELECT
                                                            idx AS article_idx
                                                        FROM
                                                            tb_bbs_article
                                                        WHERE
                                                            title LIKE \'%'.$search['search_word'].'%\'

                                                        UNION ALL

                                                        SELECT
                                                            article_idx
                                                        FROM
                                                            tb_bbs_contents
                                                        WHERE
                                                            contents LIKE \'%'.$search['search_word'].'%\'

                                                        UNION ALL

                                                        SELECT
                                                            article_idx
                                                        FROM
                                                            tb_bbs_comment
                                                        WHERE
                                                            comment LIKE \'%'.$search['search_word'].'%\'

                                                        UNION ALL

                                                        SELECT
                                                            article_idx
                                                        FROM
                                                            tb_bbs_tag
                                                        WHERE
                                                            tag LIKE \'%'.$search['search_word'].'%\'

                                                        UNION ALL

                                                        SELECT
                                                            article_idx
                                                        FROM
                                                            tb_bbs_url
                                                        WHERE
                                                            url LIKE \'%'.$search['search_word'].'%\'
                                                    ) v
                                                )
                            ';
        }

        $query .= '     ) AS BBS_ARTICLE_2
                            , tb_bbs_contents AS BBS_CONTENTS
                            , tb_users AS USERS
                        WHERE
                            BBS_ARTICLE_2.bbs_idx = BBS_CONTENTS.bbs_idx
                            AND BBS_ARTICLE_2.idx = BBS_CONTENTS.article_idx
                            AND USERS.idx = BBS_ARTICLE_2.user_idx ';

        //전체게시판 기준으로
        if($ignore_bbs_idx == TRUE)
        {
            $query = $this->db->query($query);
        }
        else
        {
            $query = $this->db->query($query, array($bbs_idx));
        }

        $row = $query->row();

        if(isset($row->cnt) == TRUE && (int)$row->cnt > 0)
        {
            return $row->cnt;
        }

        return 0;
    }

    // --------------------------------------------------------------------

    /**
     * 해당 게시판의 해당회원의 마지막 글 등록 시각
     *
     * @author KangMin
     * @since 2012.01.08
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
                            tb_bbs_article
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
     * 카테고리 이동 (관리자)
     *
     * @author KangMin
     * @since 2012.01.08
     *
     * @param int
     * @param int
     * @param int
     *
     * @return bool
     */
    public function move_category($bbs_idx, $category_idx_source, $category_idx_target)
    {
        $query = '
                        UPDATE
                            tb_bbs_article
                        SET
                            category_idx = ?
                            , exec_user_idx = ?
                            , client_ip_update = ?
                        WHERE
                            bbs_idx = ?
                            AND category_idx = ?
                    ';

        $query = $this->db->query($query, array(
            $category_idx_target
            , USER_INFO_idx
            , $this->input->ip_address()
            , $bbs_idx
            , $category_idx_source
        )
        );

        return $query;
    }

    // --------------------------------------------------------------------

    /**
     * 글쓴이 idx 호출
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
							tb_bbs_article
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
     * 글 제목 호출 (스크랩용)
     *
     * @author KangMin
     * @since 2012.01.24
     *
     * @param int
     *
     * @return string
     */
    public function get_title($idx, $add_where='')
    {
        $query = '
						SELECT
							title
						FROM
							tb_bbs_article
						WHERE
							idx = ? '.$add_where.'
					';

        $query = $this->db->query($query, array($idx));
        $row = $query->row();

        if(count($row) > 0)
        {
            return $row->title;
        }

        return '-';	//여기에 들어올일은 없지만...
    }

    // --------------------------------------------------------------------

    /**
     * idx로 bbs_idx 호출
     *
     * @author KangMin
     * @since 2011.12.11
     *
     * @param int
     *
     * @return mixed
     */
    public function get_bbs_idx($req_idx)
    {
        $query = '
                        SELECT
                            bbs_idx
                        FROM
                            tb_bbs_article
                        WHERE
                            idx = ?
                        LIMIT
                            0, 1
                    ';

        $query = $this->db->query($query, array($req_idx));
        $row = $query->row();

        if(isset($row->bbs_idx) == TRUE)
        {
            return $row->bbs_idx;
        }

        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * 게시판 검색 리스트
     *
     * @author KangMin
     * @since 2012.01.04
     *
     * @param string
     * @param int
     * @param int
     *
     * @return array
     */
    public function lists_search($search_word, $page, $per_page, $allow, $add_where='', $select_exec_user_info=FALSE)
    {
        $allow_where = ' AND bbs_idx IN ('.join(',', $allow).') ';

        $add_select = '';

        //필요할때만 액션을 가한 회원 정보 셀렉트
        if($select_exec_user_info == TRUE)
        {
            $add_select = '
                            , (SELECT user_id FROM tb_users WHERE idx = BBS_ARTICLE_2.exec_user_idx) AS exec_user_id
                            , (SELECT name FROM tb_users WHERE idx = BBS_ARTICLE_2.exec_user_idx) AS exec_name
                            , (SELECT nickname FROM tb_users WHERE idx = BBS_ARTICLE_2.exec_user_idx) AS exec_nickname
							';
        }

        $query = '
                        SELECT
                            BBS_ARTICLE_2.idx
                            , BBS_ARTICLE_2.bbs_idx
                            , BBS.bbs_id
                            , (SELECT value FROM tb_bbs_setting WHERE bbs_idx = BBS_ARTICLE_2.bbs_idx AND parameter = \'bbs_name\') AS bbs_name
                            , BBS_ARTICLE_2.category_idx
                            , (SELECT category_name FROM tb_bbs_category WHERE idx = BBS_ARTICLE_2.category_idx) AS category_name
                            , BBS_ARTICLE_2.user_idx
                            , USERS.user_id
                            , USERS.name
                            , USERS.nickname
                            , USERS.avatar_used
                            , BBS_ARTICLE_2.exec_user_idx
                            , BBS_ARTICLE_2.title
                            , BBS_ARTICLE_2.comment_count
                            , BBS_ARTICLE_2.vote_count
                            , BBS_ARTICLE_2.scrap_count
                            , BBS_ARTICLE_2.timestamp_insert
                            , BBS_ARTICLE_2.timestamp_update
                            , BBS_ARTICLE_2.client_ip_insert
                            , BBS_ARTICLE_2.client_ip_update
                            , BBS_ARTICLE_2.html_used
                            , BBS_ARTICLE_2.is_notice
                            , BBS_ARTICLE_2.is_secret
                            , BBS_ARTICLE_2.is_deleted
                            , BBS_CONTENTS.contents
                            , IFNULL((SELECT hit FROM tb_bbs_hit WHERE bbs_idx = BBS_ARTICLE_2.bbs_idx AND article_idx = BBS_ARTICLE_2.idx), 0) AS hit
                            '.$add_select.'
                        FROM
                            (
                            SELECT
                                *
                            FROM
                                tb_bbs_article AS BBS_ARTICLE
                            WHERE
                                BBS_ARTICLE.idx IN (
                                    SELECT
                                        DISTINCT(article_idx)
                                    FROM
                                    (
                                        SELECT
                                            idx AS article_idx
                                        FROM
                                            tb_bbs_article
                                        WHERE
                                            title LIKE \'%'.$search_word.'%\' '.$allow_where.'

                                        UNION ALL

                                        SELECT
                                            article_idx
                                        FROM
                                            tb_bbs_contents
                                        WHERE
                                            contents LIKE \'%'.$search_word.'%\' '.$allow_where.'

                                        UNION ALL

                                        SELECT
                                            article_idx
                                        FROM
                                            tb_bbs_comment
                                        WHERE
                                            comment LIKE \'%'.$search_word.'%\' '.$allow_where.'

                                        UNION ALL

                                        SELECT
                                            article_idx
                                        FROM
                                            tb_bbs_tag
                                        WHERE
                                            tag LIKE \'%'.$search_word.'%\' '.$allow_where.'

                                        UNION ALL

                                        SELECT
                                            article_idx
                                        FROM
                                            tb_bbs_url
                                        WHERE
                                            url LIKE \'%'.$search_word.'%\' '.$allow_where.'
                                    ) v
                                )
							    '.$add_where.'
							ORDER BY
							    BBS_ARTICLE.timestamp_insert DESC
							LIMIT
							    ?, ?) AS BBS_ARTICLE_2
                            , tb_bbs_contents AS BBS_CONTENTS
                            , tb_users AS USERS
                            , tb_bbs BBS
                        WHERE
                            BBS_ARTICLE_2.bbs_idx = BBS_CONTENTS.bbs_idx
                            AND BBS_ARTICLE_2.idx = BBS_CONTENTS.article_idx
                            AND USERS.idx = BBS_ARTICLE_2.user_idx
							AND BBS.idx = BBS_ARTICLE_2.bbs_idx
						ORDER BY
                            BBS_ARTICLE_2.timestamp_insert DESC
					';

        $query = $this->db->query($query, array($page, $per_page));

        $rows = $query->result();

        return $rows;
    }

    // --------------------------------------------------------------------

    /**
     * 게시판 검색 리스트 총 카운트
     *
     * @author KangMin
     * @since 2012.01.04
     *
     * @param int
     *
     * @return int
     */
    public function lists_search_total_cnt($search_word, $allow, $add_where='')
    {
        $allow_where = ' AND bbs_idx IN ('.join(',', $allow).') ';

        $query = '
                        SELECT
                            COUNT(BBS_ARTICLE_2.idx) AS cnt
                        FROM
                            (
                            SELECT
                                *
                            FROM
                                tb_bbs_article AS BBS_ARTICLE
                            WHERE
                                BBS_ARTICLE.idx IN (
                                    SELECT
                                        DISTINCT(article_idx)
                                    FROM
                                    (
                                        SELECT
                                            idx AS article_idx
                                        FROM
                                            tb_bbs_article
                                        WHERE
                                            title LIKE \'%'.$search_word.'%\' '.$allow_where.'

                                        UNION ALL

                                        SELECT
                                            article_idx
                                        FROM
                                            tb_bbs_contents
                                        WHERE
                                            contents LIKE \'%'.$search_word.'%\' '.$allow_where.'

                                        UNION ALL

                                        SELECT
                                            article_idx
                                        FROM
                                            tb_bbs_comment
                                        WHERE
                                            comment LIKE \'%'.$search_word.'%\' '.$allow_where.'

                                        UNION ALL

                                        SELECT
                                            article_idx
                                        FROM
                                            tb_bbs_tag
                                        WHERE
                                            tag LIKE \'%'.$search_word.'%\' '.$allow_where.'

                                        UNION ALL

                                        SELECT
                                            article_idx
                                        FROM
                                            tb_bbs_url
                                        WHERE
                                            url LIKE \'%'.$search_word.'%\' '.$allow_where.'
                                    ) v
                                )
							    '.$add_where.') AS BBS_ARTICLE_2
                            , tb_bbs_contents AS BBS_CONTENTS
                            , tb_users AS USERS
                        WHERE
                            BBS_ARTICLE_2.bbs_idx = BBS_CONTENTS.bbs_idx
                            AND BBS_ARTICLE_2.idx = BBS_CONTENTS.article_idx
                            AND USERS.idx = BBS_ARTICLE_2.user_idx
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
     * 게시판 이동
     *
     * @author KangMin
     * @since 2012.03.07
     *
     * @param int
     * @param int
     * @param int
     *
     * @return bool
     */
    public function move_bbs($article_idx, $move_bbs_idx, $move_category_idx)
    {
        $query = '
						UPDATE
							tb_bbs_article
						SET
							bbs_idx = ?
							, category_idx = ?
							, client_ip_update = ?
						WHERE
							idx = ?
					';

        $query = $this->db->query($query, array(
            $move_bbs_idx
            , $move_category_idx
            , $this->input->ip_address()
            , $article_idx
        )
        );

        return $query;
    }

    // --------------------------------------------------------------------

    /**
     * 최근게시물 캐싱을 위한 테이블 마지막 수정시각
     *
     * @desc information_schema 테이블 권한이 없는 경우가 많아서 수정 2012.05.04
     *
     * @author KangMin
     * @since 2012.03.07
     *
     * @return int
     */
    public function lastest_update_time()
    {
        /*
        $query = '
                    SELECT
                        UNIX_TIMESTAMP(MAX(UPDATE_TIME)) AS lastest_update_time
                    FROM
                        information_schema.TABLES
                    WHERE
                        TABLE_NAME IN (\'tb_bbs_article\', \'tb_bbs_contents\')
                ';

        $query = $this->db->query($query);
        $row = $query->row();

        if(isset($row->lastest_update_time) == TRUE && (int)$row->lastest_update_time > 0)
        {
            return $row->lastest_update_time;
        }

        return 0;
        */

        $query = '
						SELECT
							MAX(timestamp_insert) AS timestamp_insert
							, IFNULL(MAX(timestamp_update), 0) AS timestamp_update
						FROM
							tb_bbs_article
					';

        $query = $this->db->query($query);
        $row = $query->row();

        //코멘트쪽 확인 (v0.1.8)
        $query = '
						SELECT
							IFNULL(MAX(timestamp_insert), 0) AS timestamp_insert
							, IFNULL(MAX(timestamp_update), 0) AS timestamp_update
						FROM
							tb_bbs_comment
					';

        $query = $this->db->query($query);
        $row_comment = $query->row();

        return (int)max($row->timestamp_insert, $row->timestamp_update, $row_comment->timestamp_insert, $row_comment->timestamp_update);
    }

    // --------------------------------------------------------------------

    /**
     * 회원별 글 갯수
     *
     * @desc 실제 DB를 조회해서 삭제되지 않은 글 갯수를 연산해낸다. (검수용)
     *
     * @author KangMin
     * @since 2012.02.24
     *
     * @param int
     *
     * @return int
     */
    public function get_article_count_user($user_idx)
    {
        $query = '
						SELECT
							COUNT(idx) AS cnt
						FROM
							tb_bbs_article
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
     * 관리자가 멀티 삭제
     *
     * @param $idxs
     *
     * @return string
     */
    public function delete_admin($idxs)
    {
        $idxs_temp = array();

        foreach($idxs as $k=>$v)
        {
            $temp = explode('^', $v);
            $idxs_temp[] = $temp[0];
        }

        $idxs = join(',', $idxs_temp);

        $query = '
                    UPDATE
                        tb_bbs_article
                    SET
							exec_user_idx = ?
							, is_deleted = 1
							, client_ip_update = ?
						WHERE
							idx IN ('.$idxs.')
					';

        $query = $this->db->query($query, array(
            USER_INFO_idx
            , $this->input->ip_address()
        )
        );

        return $query;
    }
}

//EOF