<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	class Bbs_tag_model extends CI_Model 
	{
	    public function __construct()
	    {
	        parent::__construct();
	    }

		// --------------------------------------------------------------------

        /**
         * 태그 삽입
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
        public function insert_tag($bbs_idx, $article_idx, $tag)
        {
            $query = '
                        INSERT INTO
                            tb_bbs_tag
                            (
                            bbs_idx
                            , article_idx
                            , tag
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
                                                    , $tag
													)
									);                                   

			return $query;                       
        }

		// --------------------------------------------------------------------
        
        /**
         * 태그 호출
         * 
         * @author KangMin
         * @since 2012.01.01
         * 
         * @param int
         * 
         * @return array
         */
        public function get_tags($article_idx)
        {
            $query = '
                        SELECT 
                            idx
                            , tag
                        FROM
                            tb_bbs_tag
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
         * 태그 삭제
         * 
         * @author KangMin
         * @since 2012.01.01
         * 
         * @param int
         * 
         * @return bool
         */
        public function delete_tags($article_idx)
        {
            $query = '
                        DELETE FROM
                            tb_bbs_tag
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
							tb_bbs_tag
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