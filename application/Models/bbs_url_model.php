<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Bbs_url_model extends CI_Model 
	{
	    public function __construct()
	    {
	        parent::__construct();
	    }

		// --------------------------------------------------------------------

        /**
         * 관련링크 삽입
         * 
         * @author KangMin
         * @since 2011.12.30
         * 
         * @param int
         * @param int
         * @param string
         * 
         * @return bool
         */
        public function insert_url($bbs_idx, $article_idx, $url)
        {
            $query = '
                        INSERT INTO
                            tb_bbs_url
                            (
                            bbs_idx
                            , article_idx
                            , url
                            )
                        VALUES
                            (
                            ?
                            , ?
                            , ?
                            )                            
                    ';
                    
			$query = $this->db->query($query, array(
													$bbs_idx
                                                    , $article_idx
                                                    , $url
													)
									);                                   

			return $query;                       
        }
        
		// --------------------------------------------------------------------

        /**
         * 관련링크 호출
         * 
         * @author KangMin
         * @since 2012.01.01
         * 
         * @param int
         * 
         * @return array
         */
        public function get_urls($article_idx)
        {
            $query = '
                        SELECT 
                            idx
                            , url
                        FROM
                            tb_bbs_url
                        WHERE
                            article_idx = ?
                        ORDER BY 
                            sequence ASC
                            , idx ASC
                    ';
                    
			$query = $this->db->query($query, array($article_idx));
			$rows = $query->result();

			return $rows;            
        }

		// --------------------------------------------------------------------
        
        /**
         * 관련링크 삭제
         * 
         * @author KangMin
         * @since 2012.01.01
         * 
         * @param int
         * 
         * @return bool
         */
        public function delete_urls($article_idx)
        {
            $query = '
                        DELETE FROM
                            tb_bbs_url
                        WHERE
                            article_idx = ?
                    ';
                    
			$query = $this->db->query($query, array($article_idx));                                   

			return $query;                        
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
							tb_bbs_url
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
	}

//EOF