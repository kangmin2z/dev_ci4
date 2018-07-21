<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Themes_model extends CI_Model
    {
        public function __construct()
        {
            parent::__construct();
        }

		// --------------------------------------------------------------------

        /**
         * 테마 리스트 호출
         *
         * @author 배강민
         * @since 2013.01.26
         *
         * @return array
         */
        public function get_themes_lists()
        {
            $query = '
                        SELECT
                            THEMES.idx
                            , THEMES.type
                            , THEMES.parent_idx
                            , THEMES.title
                            , THEMES.folder_name
                            , THEMES.exec_user_idx
                            , USERS.user_id
							, USERS.name
							, USERS.nickname
                            , THEMES.timestamp_insert
                            , THEMES.timestamp_update
                            , THEMES.client_ip_insert
                            , THEMES.client_ip_update
                            , CASE WHEN THEMES.timestamp_update <> \'\' THEN THEMES.timestamp_update ELSE THEMES.timestamp_insert END AS timestamp
                            , CASE WHEN THEMES.client_ip_update <> \'\' THEN THEMES.client_ip_update ELSE THEMES.client_ip_insert END AS client_ip
                            , THEMES.is_used
                        FROM
                            tb_themes AS THEMES
                            , tb_users AS USERS
                        WHERE
                            THEMES.exec_user_idx = USERS.idx
                        ORDER BY
                            THEMES.type ASC
                            , THEMES.is_used DESC
                            , THEMES.title ASC
                    ';
            $query = $this->db->query($query);
            $rows = $query->result();

            return $rows;
        }

        // --------------------------------------------------------------------

        /**
         * 단일 테마값
         *
         * @desc idx 값을 넣으면 해당 테마의 값, 아니면 사용중인 테마를 가져온다.
         *
         * @author 배강민
         * @since 2013.01.26
         *
         * @param string $type
         * @param null $idx
         *
         * @return array
         */
        public function get_theme($type, $idx=null)
        {
            //idx 값이 있으면 해당 테마, 없으면 사용중인 테마
            $add_where = '';
            if($idx === null)
            {
                $add_where = ' AND THEMES.is_used = 1 ';
            }
            else
            {
                $add_where = ' AND THEMES.idx = '.$idx.' ';
            }

            $query = '
                        SELECT
                            THEMES.idx
                            , THEMES.type
                            , THEMES.parent_idx
                            , THEMES.title
                            , THEMES.folder_name
                            , THEMES.exec_user_idx
                            , USERS.user_id
							    , USERS.name
							    , USERS.nickname
                            , THEMES.timestamp_insert
                            , THEMES.timestamp_update
                            , THEMES.client_ip_insert
                            , THEMES.client_ip_update
                            , THEMES.is_used
                        FROM
                            tb_themes AS THEMES
                            , tb_users AS USERS
                        WHERE
                            THEMES.exec_user_idx = USERS.idx
                            AND THEMES.type = ? '.$add_where.'
                        LIMIT
                            0, 1
                    ';

			$query = $this->db->query($query, array($type));
			$row = $query->row();

            return $row;
        }

        // --------------------------------------------------------------------

        /**
         * 테마 삽입
         *
         * @author 배강민
         * @since 2013.01.26
         *
         * @param string $type
         * @param int $parent_idx
         * @param string $title
         * @param string $folder_name
         *
         * @return void
         */
        public function insert_theme($type, $parent_idx, $title, $folder_name)
        {
            $query = '
                        INSERT INTO
                            tb_themes
                            (
                            type
                            , parent_idx
                            , title
                            , folder_name
                            , exec_user_idx
                            , timestamp_insert
                            , client_ip_insert
                            , is_used
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
                            , 0
                            )
                    ';

            $query = $this->db->query($query, array(
                                                    $type
                                                    , $parent_idx
                                                    , $title
                                                    , $folder_name
                                                    , USER_INFO_idx
                                                    , $this->input->ip_address()
                                                    )
                                                );
            return $query;
        }

        // --------------------------------------------------------------------

        /**
         * 테마 업데이트
         *
         * @author 배강민
         * @since 2013.01.26
         *
         * @param int $idx
         * @param string $title
         * @param string $folder_name
         * @param int $is_used
         *
         * @return bool
         */
        public function update_theme($idx, $title, $folder_name, $is_used)
        {
            $query = '
                        UPDATE
                            tb_themes
                        SET
                            title = ?
                            , folder_name = ?
                            , is_used = ?
                            , exec_user_idx = ?
                            , timestamp_update = UNIX_TIMESTAMP(NOW())
                            , client_ip_update = ?
                        WHERE
                            idx = ?
                    ';

            $query = $this->db->query($query, array(
                                                    $title
                                                    , $folder_name
                                                    , $is_used
                                                    , USER_INFO_idx
                                                    , $this->input->ip_address()
                                                    , $idx
                                                    )
                                    );

            return $query;
        }

        // --------------------------------------------------------------------

        /**
         * 존재여부
         *
         * @author 배강민
         *
         * @param int $idx
         *
         * @return bool
         */
        public function check_idx($idx)
        {
            $query = '
                        SELECT
                            COUNT(idx) AS cnt
                        FROM
                            tb_themes
                        WHERE
                            idx = ?
                    ';

            $query = $this->db->query($query, array($idx));
            $row = $query->row();

            if(isset($row->cnt) == true && (int)$row->cnt > 0)
            {
                return true;
            }

            return false;
        }

        // --------------------------------------------------------------------

        /**
         * idx 를 받아서 그 테마와 같은 타입의 것들을 초기화한다. 해당 테마만 빼고
         *
         * @author 배강민
         *
         * @param int $idx
         * @param string $type
         *
         * @return bool
         */
        public function reset_is_used($idx, $type)
        {
            $query = '
                        UPDATE
                            tb_themes
                        SET
                            is_used = 0
                        WHERE
                            type = ?
                            AND idx <> ?
                    ';

            $query = $this->db->query($query, array(
                                                    $type
                                                    , $idx
                                                    , $idx
                                                    )
                                                );

            return $query;
        }
    }

//EOF