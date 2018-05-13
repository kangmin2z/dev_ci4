<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Bbs_category_model extends CI_Model 
	{
	    public function __construct()
	    {
	        parent::__construct();
	    }

		// --------------------------------------------------------------------
        
        /**
         * 카테고리명 중복체크
         * 
         * @author KangMin
         * @since 2011.12.11
         * 
         * @param int
         * @param string
         * 
         * @return bool
         */
        public function check_category_name($req_bbs_idx, $req_category_name, $add_where='')
        {
            $query = '
                        SELECT
                            COUNT(idx) AS cnt
                        FROM
                            tb_bbs_category
                        WHERE
                            bbs_idx = ?
                            AND category_name = ? '.$add_where.' 
                    ';
                    
			$query = $this->db->query($query, array($req_bbs_idx, $req_category_name));
			$row = $query->row();

			if(isset($row->cnt) == TRUE && (int)$row->cnt > 0)
			{
				return TRUE;
			}

			return FALSE;            
        }

		// --------------------------------------------------------------------
        
        /**
         * 카테고리 추가
         * 
         * @author KangMIn
         * @since 2011.12.11
         * 
         * @param int
         * @param string
         * 
         * @return bool
         */
        public function insert_category($req_bbs_idx, $req_category_name)
        {
            $query = '
                        INSERT INTO
                            tb_bbs_category
                            (
                            bbs_idx
                            , category_name
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
                                                    $req_bbs_idx
                                                    , $req_category_name
                                                    , USER_INFO_idx
                                                    , $this->input->ip_address()
													)
									);
			return $query;                                
        }  

		// --------------------------------------------------------------------
        
        /**
         * 카테고리 수정
         * 
         * @author KangMin
         * @since 2011.12.11
         * 
         * @param int
         * @param string
         * @param int
         * 
         * @return bool
         */
        public function update_category($req_idx, $req_category_name, $req_is_used)
        {       
			$query = '
						UPDATE
							tb_bbs_category
						SET
							category_name = ?
							, exec_user_idx = ?
							, client_ip = ?
							, is_used = ?
						WHERE
							idx = ?
					';
                        
			$query = $this->db->query($query, array(
													$req_category_name
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
         * 카테고리 호출 (rows) 관리용
         * 
         * @author KangMin
         * @since 2011.12.11
         * 
         * @param int
         * 
         * @return array
         */
        public function get_categorys($req_bbs_idx)
        {
            $query = '
                        SELECT 
                            BBS_CATEGORY.idx
                            , BBS_CATEGORY.bbs_idx
                            , BBS_CATEGORY.category_name
                            , BBS_CATEGORY.exec_user_idx
                            , BBS_CATEGORY.client_ip
                            , BBS_CATEGORY.is_used
                            , (
                                SELECT 
                                    COUNT(idx) AS cnt 
                                FROM 
                                    tb_bbs_article 
                                WHERE
                                    bbs_idx = ?
                                    AND category_idx = BBS_CATEGORY.idx
                                ) AS article_cnt 
                        FROM
                            tb_bbs_category AS BBS_CATEGORY
                        WHERE
                            BBS_CATEGORY.bbs_idx = ?
                        ORDER BY
                            BBS_CATEGORY.sequence ASC
                            , BINARY(BBS_CATEGORY.category_name) ASC
                    ';
                    
			$query = $this->db->query($query, array($req_bbs_idx, $req_bbs_idx));
			$rows = $query->result();

			return $rows;                       
        }

		// --------------------------------------------------------------------
        
        /**
         * 카테고리 호출 (단일) 관리용
         * 
         * @author KangMin
         * @since 2011.12.11
         * 
         * @param int
         * 
         * @return array
         */
        public function get_category($req_idx)
        {
            $query = '
                        SELECT 
                            BBS_CATEGORY.idx
                            , BBS_CATEGORY.bbs_idx
                            , BBS_CATEGORY.category_name
                            , BBS_CATEGORY.exec_user_idx
							, USERS.user_id
							, USERS.name
							, USERS.nickname                            
                            , BBS_CATEGORY.client_ip
                            , BBS_CATEGORY.is_used
                            , (
                                SELECT 
                                    COUNT(idx) AS cnt 
                                FROM 
                                    tb_bbs_article 
                                WHERE
                                    bbs_idx = BBS_CATEGORY.bbs_idx
                                    AND category_idx = BBS_CATEGORY.idx
                                ) AS article_cnt 
                        FROM
                            tb_bbs_category AS BBS_CATEGORY
                            , tb_users AS USERS
                        WHERE
                            BBS_CATEGORY.exec_user_idx = USERS.idx
                            AND BBS_CATEGORY.idx = ?
                        LIMIT
                            0, 1
                    ';
                    
			$query = $this->db->query($query, array($req_idx));
			$row = $query->result();

			return $row;                       
        }      
		
		// --------------------------------------------------------------------
        
        /**
         * idx 유효성 체크
         * 
         * @author KangMin
         * @since 2011.12.11
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
                            tb_bbs_category
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
                            tb_bbs_category
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
         * 카테고리 순서 변경
         * 
         * @author KangMin
         * @since 2011.12.11
         * 
         * @param int
         * @param int
         * 
         * @return bool
         */
        public function update_order($idx, $sequence)
        {
            $query = '
                        UPDATE
                            tb_bbs_category
                        SET
                            sequence = ?
                            , exec_user_idx = ?
                            , client_ip = ?
                        WHERE
                            idx = ?
                    ';
                    
			$query = $this->db->query($query, array(
                                                    $sequence
                                                    , USER_INFO_idx
                                                    , $this->input->ip_address()
                                                    , $idx
													));

			return $query;                                                                          
        }

		// --------------------------------------------------------------------
        
        /**
         * 카테고리 호출 (rows) 간단버젼
         * 
         * @author KangMin
         * @since 2011.12.30
         * 
         * @param int
         * 
         * @return array
         */
        public function get_categorys_simple($req_bbs_idx, $add_where='')
        {
            $query = '
                        SELECT
                            idx
                            , category_name
							    , is_used
                        FROM
                            tb_bbs_category
                        WHERE
							    bbs_idx = ? '.$add_where.'
                        ORDER BY
                            sequence ASC
                            , category_name ASC
                    ';
                    
			$query = $this->db->query($query, array($req_bbs_idx));
			$rows = $query->result();

			return $rows;                         
        }

	}

//EOF