<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Index extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    // --------------------------------------------------------------------

    public function index()
    {
        $this->load->library('bbs_common');

        //최근댓글
        $this->load->model('bbs_comment_model');

        //캐싱
        $this->load->driver('cache');

        $use_cache = FALSE; //캐쉬를 이용여부

        //캐쉬 있으면
        if($this->cache->file->get('recently_comment_'.$this->viewport))
        {
            //캐쉬 생성시각
            $cache_info  = $this->cache->file->get_metadata('recently_comment_'.$this->viewport);
            $cache_mtime = $cache_info['mtime'];

            //article, contents 중 큰 마지막 업데이트타임
            //테이블 단위로 information_schema의 update_time 를 이용하므로 어느 한개던 실행되면 모두 적용되지만 뭐...
            $this->load->model('bbs_article_model');
            $lastest_update_time = $this->bbs_article_model->lastest_update_time(); //원본글 삭제로 인한 시간도 잡아야하므로...

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
            $result = json_decode($this->cache->file->get('recently_comment_'.$this->viewport), TRUE);

            $recently_comment = $result['recently_comment'];

            $recently_comment_page = $result['recently_comment_page'];
        }
        else
        {
            //욕필터링
            $block_string = array();

            if(SETTING_bbs_block_string_used == 1)
            {
                $block_string = unserialize(SETTING_bbs_block_string);
            }

            $this->load->helper('text'); //욕필터링때문에

            $recently_comment = $this->bbs_comment_model->recently_comment((int)SETTING_recently_comment_count, ' AND BBS_COMMENT.is_deleted = 0 AND BBS_ARTICLE.is_secret = 0 ');
            //댓글 페이지 계산
            $recently_comment_page = array();
            foreach($recently_comment as $k => &$comment)
            {
                $new_comment_icon = '';

                //파일 존재
                //if(file_exists('.' . SETTING_bbs_hour_new_icon_path_article)) //이 이미지는 게시판별로 할 수 있는데 여기에서는 기본 최근코멘트이미지로 한다
                //{
                    //시간차
                    if((int)$comment['timestamp_insert'] >= time() - ((int)SETTING_bbs_hour_new_icon_value_comment * 60 * 60)) // 이 시간도 게시판별로 설정할 수 있는데 여기에서는 게시판 기본설정값으로 한다.
                    //if(date('Ymd', strtotime(time2date($v->timestamp_insert))) == date('Ymd', strtotime(time2date(time())))) //오늘
                    {
                        $new_comment_icon = SETTING_bbs_hour_new_icon_path_article;
                    }
               //}

                $comment['comment']          = cut_string(word_censor(trim(strip_tags(htmlspecialchars_decode($comment['comment']))), $block_string), SETTING_cut_length_recently_comment);
                $comment['new_comment_icon'] = $new_comment_icon;
                $comment['print_name']       = name($comment['user_id'], $comment['name'], $comment['nickname']);
                $comment['print_date']       = time2date($comment['timestamp_insert']);

                //댓글 작성후 오른차순/내림차순 정렬에 따른 페이지로 보내기
                if($comment['bbs_comment_sort'] == 'ASC')
                {
                    $comment_total_cnt = $this->bbs_comment_model->lists_total_cnt($comment['article_idx'], ' AND BBS_COMMENT.idx < ' . $comment['idx'] . ' AND BBS_COMMENT.is_deleted = 0 ');
                }
                else
                {
                    $comment_total_cnt = $this->bbs_comment_model->lists_total_cnt($comment['article_idx'], ' AND BBS_COMMENT.idx > ' . $comment['idx'] . ' AND BBS_COMMENT.is_deleted = 0 ');
                }

                $recently_comment_page[$k] = floor($comment_total_cnt / $comment['bbs_count_list_comment']) + 1;
            }

            $result = json_encode(array(
                                       'recently_comment'      => $recently_comment,
                                       'recently_comment_page' => $recently_comment_page
                                  ));

            //캐쉬저장
            $this->cache->file->save('recently_comment_'.$this->viewport, $result, 60 * 60 * 2); //2시간, 설정으로 뺄것까진 없을듯..
        }

        $recently_used = array();
        $assign = array();

        if(trim(SETTING_bbs_recently_used) !== '') $recently_used = unserialize(SETTING_bbs_recently_used);

        $recently      = $this->bbs_common->recently($recently_used);

        foreach($recently_used as $k => $v)
        {
            if(isset($recently[$v])) {
                ${$v} = json_decode($recently[$v]);
                $assign[$v] = ${$v}->lists;
                $assign[$v.'_bbs_name'] = ${$v}->bbs_name;
                $assign[$v.'_bbs_lists_style'] = ${$v}->bbs_lists_style;
            }
        }

        $onedayonememo = json_decode($this->curl->simple_get('/plugin/onedayonememo/recently'), TRUE); //이건 플러그인 형태라서 그냥 curl로 간다.

        $assign = array_merge($assign, array(
            'onedayonememo'         => $onedayonememo['lists'],
            'recently_comment'      => $recently_comment,
            'recently_comment_page' => $recently_comment_page
        ));

        $this->scope('contents', 'contents/index', $assign);
        $this->display('layout');
    }

    public function license()
    {
        $license = $this->curl->simple_get('/license_tapbbs.txt');

        $license = str_replace("\n", "<br />", $license);

        echo $license;
    }
}

//EOF