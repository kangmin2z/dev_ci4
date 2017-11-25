<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Search extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    // --------------------------------------------------------------------

    /**
     * 검색결과
     *
     * @desc 게시판 리스트와 동일 (팝업)
     * @author KangMin
     * @since 2012.02.25
     */
    public function index()
    {
        $this->add_language_pack($this->language_pack('bbs_search'));

        $assign = NULL;

        $this->load->helper('security');

        //search_word
        $assign['search_word'] = xss_clean(str_replace(array('"', "'", '?'), '', trim($this->input->get('search_word'))));//따옴표 검색은 필요없겄지..

        //게시판 선택
        $assign['bbs_id'] = $this->input->get('bbs_id');
        $assign['bbs_name'] = '';
        if (!empty($assign['bbs_id'])) {
            $this->load->model('bbs_model');
            $assign['bbs_name'] = $this->bbs_model->get_bbs_name($assign['bbs_id']);
        }

        //접속자가 검색할 수 있는 조건
        //어차피 검색결과가 리스트니까 리스트권한만
        $allow_bbs_list = array(); //네이밍이 좀 이상한데 이게 article 테이블용

        if( ! defined('USER_INFO_group_idx')) define('USER_INFO_group_idx', 0);

        $this->load->model('bbs_setting_model');
        $bbs_allow_group_view_list = $this->bbs_setting_model->get_bbs_setting_section(array('bbs_allow_group_view_list'));

        foreach($bbs_allow_group_view_list as $k=>$v)
        {
            $value = unserialize($v->value);

            if(in_array(USER_INFO_group_idx, $value) == TRUE)
            {
                $allow_bbs_list[] = $v->bbs_idx;
            }
        }

        $allow_bbs_list = array_unique($allow_bbs_list);

        //권한있는 테이블이 없으면
        if( count($allow_bbs_list) < 1 )
        {
            $assign['message'] = lang('deny_allow');
            $assign['redirect'] = '/';

            $this->alert($assign);
        }
        else if(strlen($assign['search_word']) < 1)
        {
            $assign['message'] = lang('need_search_word');
            $assign['redirect'] = '/';

            $this->alert($assign);
        }
        else
        {
            $this->load->helper('text'); //욕필터링때문에

            //욕필터링
            //게시판별 모든 욕필터링을 가져와서
            $assign['block_string'] = array();

            $block_string = $this->bbs_setting_model->get_bbs_block_string();

            foreach($block_string as $k=>$v)
            {
                $assign['block_string'][$v->bbs_idx] = unserialize($v->value);
            }

            //page
            $assign['page'] = ((int)$this->input->get('page') > 0) ? (int)$this->input->get('page') : 1;

            $this->load->library('pagination');

            $this->load->model('bbs_article_model');

            $assign['total_cnt'] = $this->bbs_article_model->lists_search_total_cnt($assign['search_word'], $allow_bbs_list, ' AND BBS_ARTICLE.is_deleted = 0 ' . ($assign['bbs_id'] ? ' AND BBS_ARTICLE.bbs_idx = (SELECT idx FROM tb_bbs WHERE bbs_id = \'' . $assign['bbs_id'] . '\')' : ''));

            $this->config->load('pagination');
            $pagination_config = $this->config->item($this->viewport);

            unset($config);

            // http://codeigniter-kr.org/user_guide_2.1.0/libraries/pagination.html
            $config['base_url'] = BASE_URL.'search?search_word='.$assign['search_word'].'&amp;bbs_id='.$assign['bbs_id'];
            $config['enable_query_strings'] = TRUE; // ?page=10 이런 일반 get 방식
            $config['page_query_string'] = TRUE;
            $config['use_page_numbers'] = TRUE;
            $config['num_links'] = (int)SETTING_count_page_search;
            $config['query_string_segment'] = 'page';
            $config['total_rows'] = $assign['total_cnt'];
            $config['per_page'] = (int)SETTING_count_list_search;

            $config = array_merge($config, $pagination_config);

            $this->pagination->initialize($config);

            $assign['pagination'] = $this->pagination->create_links();

            //lists
            $assign['lists'] = $this->bbs_article_model->lists_search($assign['search_word'], ($assign['page']-1)*$config['per_page'], $config['per_page'], $allow_bbs_list, ' AND BBS_ARTICLE.is_deleted = 0 ' . ($assign['bbs_id'] ? ' AND BBS_ARTICLE.bbs_idx = (SELECT idx FROM tb_bbs WHERE bbs_id = \'' . $assign['bbs_id'] . '\')' : ''));
            foreach ($assign['lists'] as $k => &$v) {

                $v->print_title = cut_string(word_censor($v->title, $assign['block_string'][$v->bbs_idx]), SETTING_cut_length_title_search);
                $v->print_name = name($v->user_id, $v->name, $v->nickname);
                $v->print_insert_date = time2date($v->timestamp_insert);
            }

            $this->scope('contents', 'contents/bbs/search', $assign);
            $this->display('layout');
        }
    }
}

//EOF