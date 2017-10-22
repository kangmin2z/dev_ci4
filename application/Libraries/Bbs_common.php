<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Bbs_common
{
    private $upload_path = 'uploads/'; //파일업로드 경로

	/**
	 * 게시판 최근 게시물 호출
	 *
	 * @desc 원래 /application/controllers/bbs.php > recently 를 curl을 이용해서 호출했는데, 웹소켓이 동시에 여러개 열리게됨에 따라 변경
	 *
	 * @author KangMin
	 * @since 2012.05.02
	 *
	 * @param array
	 *
	 * @return array
	 */
	public function recently($req_bbs_ids)
	{
		//배열 아니거나 빈 배열이면 리턴
		if( ! is_array($req_bbs_ids) OR count($req_bbs_ids) < 1) return FALSE;

		$CI =& get_instance();

		$CI->load->model('bbs_model');

		$bbs_idxs = array();
		$bbs_ids = array();

		//사용중이면서 존재하는지
		$check_bbs_ids = $CI->bbs_model->check_bbs_ids($req_bbs_ids);

		foreach($check_bbs_ids as $k=>$v)
		{
			$bbs_ids[] = $v->bbs_id;
			$bbs_idxs[] = $v->idx;
		}

		//검증후에 다시 체크
		if(count($bbs_idxs) < 1) return FALSE;

		//해당게시판들의 필요한 설정값 일괄 호출
		$CI->load->model('bbs_setting_model');

		//viewport
		$viewport_field = IS_MOBILE ? '' : '_pc';
		$viewport = IS_MOBILE ? 'mobile' : 'pc';

		$bbs_setting = $CI->bbs_setting_model->get_bbs_setting_section(array(
																			'bbs_recently_count'.$viewport_field
																			, 'bbs_block_string_used'
																			, 'bbs_block_string'
																			, 'bbs_hour_new_icon_used_article'
																			, 'bbs_hour_new_icon_path_article'
																			, 'bbs_hour_new_icon_value_article'
																			, 'bbs_cut_length_recently'.$viewport_field
                                                                            , 'bbs_lists_style'.$viewport_field
																			));

		//설정값 배열 정리
		$bbs_setting_temp = array();
		foreach($bbs_setting as $k=>$v)
		{
			$bbs_setting_temp[$v->bbs_idx][$v->parameter] = $v->value;
		}
		$bbs_setting = $bbs_setting_temp;

		//캐싱
		$CI->load->driver('cache');

		$CI->load->model('bbs_article_model');
        $CI->load->model('bbs_file_model');
		$CI->load->helper('text'); //욕필터링때문에

		$lastest_update_time = $CI->bbs_article_model->lastest_update_time();

		foreach($bbs_idxs as $k=>$v)
		{
			$use_cache = FALSE; //캐쉬를 이용여부

			//캐쉬 있으면
			if($CI->cache->file->get('recently_'.$v.'_'.$viewport))
			{
				//캐쉬 생성시각
				$cache_info = $CI->cache->file->get_metadata('recently_'.$v.'_'.$viewport);
				$cache_mtime = $cache_info['mtime'];

				//캐쉬타임이 DB 마지막 업데이트타임보다 작으면 쿼리실행, 아니면 캐쉬이용
				if($cache_mtime < $lastest_update_time)
				{
					$use_cache = FALSE;
				}
				else
				{
					$use_cache = TRUE;
				}
			}

			if($use_cache == TRUE) //캐쉬 이용할 조건이면
			{
				//데이터
				$result[$bbs_ids[$k]] = $CI->cache->file->get('recently_'.$v.'_'.$viewport);
			}
			else
			{
				//갯수
				$req_limit = (int)$bbs_setting[$v]['bbs_recently_count'.$viewport_field];

				//욕필터링
				$block_string = array();

				if($bbs_setting[$v]['bbs_block_string_used'] == 1)
				{
					$block_string = unserialize($bbs_setting[$v]['bbs_block_string']);
				}

                //게시판명
                //글이 하나도 없으면 제목도 없어서 표출할 수 없다.
                $data['bbs_name'] = $CI->bbs_model->get_bbs_name($bbs_ids[$k]);

                $data['bbs_lists_style'] = $bbs_setting[$v]['bbs_lists_style'.$viewport_field];

				//lists
				$data['lists'] = $CI->bbs_article_model->lists($v, 0, $req_limit, ' AND BBS_ARTICLE.is_deleted = 0 AND BBS_ARTICLE.is_secret = 0 ', FALSE, TRUE);

				//욕필터링과 다시 정리
				$lists = array();
				$cnt = 0;
				foreach($data['lists'] as $k2=>$v2)
				{
					//새글 아이콘
					$new_article_icon = '';

					//사용여부
					if($bbs_setting[$v]['bbs_hour_new_icon_used_article'] == 1)
					{
						//파일 존재
						//if(file_exists('.'.$bbs_setting[$v]['bbs_hour_new_icon_path_article']))
						//{
							//시간차
							if( (int)$v2->timestamp_insert >= time() - ((int)$bbs_setting[$v]['bbs_hour_new_icon_value_article']*60*60) )
							{
								$new_article_icon = $bbs_setting[$v]['bbs_hour_new_icon_path_article'];
							}
						//}
					}

					$lists[$cnt]['idx'] = $v2->idx;
					$lists[$cnt]['bbs_id'] = $bbs_ids[$k];
					$lists[$cnt]['bbs_name'] = $v2->bbs_name;
					$lists[$cnt]['category_name'] = $v2->category_name;
					$lists[$cnt]['name'] = name($v2->user_id, $v2->name, $v2->nickname);
					$lists[$cnt]['title'] = cut_string(word_censor($v2->title, $block_string), $bbs_setting[$v]['bbs_cut_length_recently'.$viewport_field]);
					$lists[$cnt]['comment_count'] = $v2->comment_count;
					$lists[$cnt]['vote_count'] = $v2->vote_count;
					$lists[$cnt]['scrap_count'] = $v2->scrap_count;
					$lists[$cnt]['timestamp'] = time2date($v2->timestamp_insert);
					$lists[$cnt]['is_notice'] = $v2->is_notice;
					$lists[$cnt]['hit'] = $v2->hit;
					$lists[$cnt]['new_article_icon'] = $new_article_icon;

                    $image = $CI->bbs_file_model->get_image($v2->idx);

                    if($image)
                    {
                        $thumb_filepath = explode('.', $image[0]->conversion_filename);
                        $thumb_filepath = $thumb_filepath[0] . '_thumb.' . $thumb_filepath[1];

                        if(file_exists($this->upload_path . $thumb_filepath))
                        {
                            $v2->image = BASE_URL . $this->upload_path . $thumb_filepath;
                        }
                        else if(file_exists($this->upload_path . $image[0]->conversion_filename))
                        {
                            $v2->image = BASE_URL.$this->upload_path.$image[0]->conversion_filename;
                        }
                        else
                        {
                            $v2->image = FRONTEND.'img/noimage.gif';
                        }
                    }
                    else
                    {
                        $v2->image = FRONTEND.'img/noimage.gif';
                    }

                    $lists[$cnt]['image'] = $v2->image;

					$cnt++;
				}

				$data['lists'] = $lists;

				$result[$bbs_ids[$k]] = json_encode($data);

				//캐쉬저장
				$CI->cache->file->save('recently_'.$v.'_'.$viewport, $result[$bbs_ids[$k]], 60 * 60 * 2); //2시간, 설정으로 뺄것까진 없을듯..
			}
		}

		return $result;
	}
}

//EOF