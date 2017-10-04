<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Bbs extends MY_Controller
{
    private $upload_path = 'uploads/'; //파일업로드 경로
    public $allowed_list = array();

    public function __construct()
    {
        parent::__construct();

        $this->load->model('bbs_model');

        //게시판 유효성
        $this->bbs_id = $this->uri->segment(3);

        //사용중이면서 존재하는지
        $check_bbs_id = $this->bbs_model->check_bbs_id((string)$this->bbs_id);

        if($check_bbs_id === FALSE) //없으면 false, 있으면 idx
        {
            show_error(lang('none_bbs'));
            exit();
        }
        else
        {
            $this->bbs_idx = $check_bbs_id;
        }

        //게시판 세팅값 define
        $define_bbs_setting = $this->define_bbs_setting($this->bbs_idx);

        if($define_bbs_setting == FALSE)
        {
            show_error(lang('fatal_error'));
            exit();
        }

        //권한
        $this->set_bbs_allow();

        //RSS 권한
        $this->set_rss_allow();

        $this->assign['bbs_id'] = (!empty($this->bbs_id)) ? $this->bbs_id : '';
    }

    // --------------------------------------------------------------------

    public function index()
    {
        redirect('/', 'refresh');
    }

    // --------------------------------------------------------------------

    /**
     * 게시판 세팅값 호출 및 define
     *
     * @author KangMin
     * @since  2011.12.27
     *
     * @param int
     *
     * @return bool
     */
    private function define_bbs_setting($bbs_idx)
    {
        //필수 int 형변환
        $must_int = array(
            'bbs_idx',
            'bbs_used'
        );

        $this->load->driver('cache');

        //캐쉬 있으면
        if($this->cache->file->get('bbs_setting_' . $this->bbs_idx))
        {
            //데이터
            $bbs_setting = $this->cache->file->get('bbs_setting_' . $this->bbs_idx);

            $bbs_setting = json_decode($bbs_setting);
        }
        //캐쉬 없으면
        else
        {
            $this->load->model('bbs_setting_model');

            $bbs_setting = $this->bbs_setting_model->get_bbs_setting_detail($this->bbs_idx);

            //캐쉬저장
            $this->cache->file->save('bbs_setting_' . $this->bbs_idx, json_encode($bbs_setting), 60 * 60 * 2); //2시간, 설정으로 뺄것까진 없을듯..
        }

        $bbs_setting_list = array();
        $define_list = array();

        foreach ($bbs_setting as $k => $v)
        {
            $bbs_setting_list[$v->viewport][$v->parameter] = $v;
        }

        $define_list = $bbs_setting_list['mobile'];

        if($this->viewport == 'pc')
        {
            foreach ($bbs_setting_list['pc'] as $k => $v)
            {

                $k = substr($v->parameter, 0, -3);
                $define_list[$k] = $v;
            }
        }

        foreach ($define_list as $k => $v)
        {
            $this->assign['BBS_SETTING_' . $k] = (in_array($k, $must_int) == TRUE ? (int)$v->value : $v->value);
            define('BBS_SETTING_' . $k, (in_array($k, $must_int) == TRUE ? (int)$v->value : $v->value));
        }

        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * 게시판별 권한으로 현재 회원이 가능한 권한 세팅
     *
     * @author KangMin
     * @since  2011.12.30
     *
     * @return array
     */
    private function get_bbs_allow()
    {
        $rtn = array();

        //작성쪽은 무조건 회원만
        if(defined('USER_INFO_group_idx'))
        {
            //글작성 권한 그룹
            $allow = $this->get_allow(BBS_SETTING_bbs_allow_group_write_article);

            if(in_array(USER_INFO_group_idx, $allow) == TRUE)
            {
                $rtn[] = 'write_article';
            }

            //댓글 작성 권한 그룹
            $allow = $this->get_allow(BBS_SETTING_bbs_allow_group_write_comment);

            if(in_array(USER_INFO_group_idx, $allow) == TRUE)
            {
                $rtn[] = 'write_comment';
            }

            //파일업로드 권한 그룹
            $allow = $this->get_allow(BBS_SETTING_bbs_allow_group_upload);

            if(in_array(USER_INFO_group_idx, $allow) == TRUE)
            {
                $rtn[] = 'upload';
            }

            /*
            //파일다운로드 권한 그룹
            $allow = $this->get_allow(BBS_SETTING_bbs_allow_group_download);

            if(in_array(USER_INFO_group_idx, $allow) == TRUE)
            {
                $rtn[] = 'download';
            }
            */
        }

        //뷰에는 비회원도 있으므로.. 0 세팅
        if(!defined('USER_INFO_group_idx'))
        {
            define('USER_INFO_group_idx', 0);
        }

        //글내용 보기 권한 그룹
        $allow = $this->get_allow(BBS_SETTING_bbs_allow_group_view_article);

        if(in_array(USER_INFO_group_idx, $allow) == TRUE)
        {
            $rtn[] = 'view_article';
        }

        //리스트보기 권한 그룹
        $allow = $this->get_allow(BBS_SETTING_bbs_allow_group_view_list);

        if(in_array(USER_INFO_group_idx, $allow) == TRUE)
        {
            $rtn[] = 'view_lists';
        }

        //코멘트 보기 권한 그룹
        $allow = $this->get_allow(BBS_SETTING_bbs_allow_group_view_comment);

        if(in_array(USER_INFO_group_idx, $allow) == TRUE)
        {
            $rtn[] = 'view_comment';
        }

        //파일다운로드 권한 그룹
        $allow = $this->get_allow(BBS_SETTING_bbs_allow_group_download);

        if(in_array(USER_INFO_group_idx, $allow) == TRUE)
        {
            $rtn[] = 'download';
        }

        return $rtn;
    }

    private function set_bbs_allow()
    {
        $allowed_list = $this->get_bbs_allow();
        $check_list   = array(
            'write_article',
            'write_comment',
            'upload',
            'download',
            'view_article',
            'view_lists',
            'view_comment'
        );

        foreach($check_list as $item)
        {
            $this->allowed_list[$item] = in_array($item, $allowed_list);
        }

        $this->assign['allowed_list'] = $this->allowed_list;
    }

    // --------------------------------------------------------------------

    /**
     * 카테고리 선택시 사용할 쿼리와 유효성 리턴
     *
     * @author 배강민
     *
     * @return array
     */
    private function get_view_category($category_idx = NULL)
    {
        if($category_idx == NULL)
        {
            $category_idx = $this->input->get_post('view_category');
        }

        $return = array(
            'query_where_as' => '',
            'query_where'    => '',
            'view_category'  => NULL,
            'param_add'      => '',
            'param_single'   => '',
            'result'         => FALSE
        );

        if(isset($category_idx) == TRUE && trim($category_idx) !== '')
        {
            $this->load->model('bbs_category_model');

            //카테고리 유효성
            $check_category_idx = $this->bbs_category_model->check_idx((int)$category_idx, ' AND bbs_idx = ' . $this->bbs_idx . ' AND is_used = 1 ');

            if($check_category_idx == TRUE)
            {
                $return = array(
                    'query_where_as' => ' AND BBS_ARTICLE.category_idx = ' . (int)$category_idx . ' ',
                    'query_where'    => ' AND category_idx = ' . (int)$category_idx . ' ',
                    'view_category'  => (int)$category_idx,
                    'param_add'      => '&view_category=' . (int)$category_idx,
                    'param_single'   => '?view_category=' . (int)$category_idx,
                    'result'         => TRUE
                );
            }
        }

        return $return;
    }

    // --------------------------------------------------------------------

    /**
     * 목록
     *
     * @author KangMin
     */
    public function lists()
    {
        $this->add_language_pack($this->language_pack('bbs_lists'));

        $assign           = array();
        $assign['bbs_id'] = $this->bbs_id;

        //권한이 없으면 (리스트는 비회원도 권한부여되므로 회원체킹 없음)
        if($this->allowed_list['view_lists'] !== TRUE)
        {
            $assign['message']  = lang('deny_allow');
            $assign['redirect'] = '/';

            $this->alert($assign);
        }
        else
        {
            $this->load->helper('text'); //욕필터링때문에

            //욕필터링
            $assign['block_string'] = array();

            if(BBS_SETTING_bbs_block_string_used == 1)
            {
                $assign['block_string'] = unserialize(BBS_SETTING_bbs_block_string);
            }

            //page
            $assign['page'] = ((int)$this->input->get('page') > 0) ? (int)$this->input->get('page') : 1;

            //category
            $this->load->model('bbs_category_model');
            $view_category           = $this->get_view_category();
            $assign['view_category'] = $view_category['view_category'];
            $assign['lists_style'] = $this->input->get_post('lists_style');
            $assign['category']      = $this->bbs_category_model->get_categorys_simple($this->bbs_idx, ' AND is_used = 1 ');

            foreach ($assign['category'] as &$v)
            {
                $v->selected = ($assign['view_category'] == $v->idx) ? ' selected="selected"' : '';
            }

            $assign['is_use_category'] = FALSE;
            if(BBS_SETTING_bbs_category_used == 1 && count($assign['category']) > 0)
            {
                $assign['is_use_category'] = TRUE;
            }

            $this->load->library('pagination');

            $this->load->model('bbs_article_model');

            $assign['total_cnt'] = $this->bbs_article_model->lists_total_cnt($this->bbs_idx, ' AND BBS_ARTICLE.is_deleted = 0 ' . $view_category['query_where_as']);

            $this->config->load('pagination');
            $pagination_config = $this->config->item($this->viewport);

            unset($config);

            // http://codeigniter-kr.org/user_guide_2.1.0/libraries/pagination.html
            $config['base_url']             = BASE_URL . 'bbs/lists/' . $this->bbs_id . '?' . $view_category['param_add'] . '&amp;lists_style=' . $this->input->get_post('lists_style');
            $config['enable_query_strings'] = TRUE; // ?page=10 이런 일반 get 방식
            $config['page_query_string']    = TRUE;
            $config['use_page_numbers']     = TRUE;
            $config['num_links']            = (int)BBS_SETTING_bbs_count_page_article;
            $config['query_string_segment'] = 'page';
            $config['total_rows']           = $assign['total_cnt'];
            $config['per_page']             = (int)BBS_SETTING_bbs_count_list_article;

            $config = array_merge($config, $pagination_config);

            $this->pagination->initialize($config);

            $assign['pagination'] = $this->pagination->create_links();

            //lists
            $assign['lists'] = $this->bbs_article_model->lists($this->bbs_idx, ($assign['page'] - 1) * $config['per_page'], $config['per_page'], ' AND BBS_ARTICLE.is_deleted = 0 ' . $view_category['query_where_as']);

            //lists style
            $lists_style = array('webzine', 'gallery');

            if( ! in_array($this->input->get_post('lists_style'), $lists_style))
            {
                $this->lists_style = '';
                $get_image = FALSE;
            }
            else
            {
                $this->lists_style = '_' . $this->input->get_post('lists_style');
                $get_image = TRUE;
                $this->load->model('bbs_file_model');
            }

            foreach($assign['lists'] as $k => &$v)
            {
                $v->print_title       = cut_string(word_censor($v->title, $assign['block_string']), BBS_SETTING_bbs_cut_length_title);
                $v->print_contents    = strip_tags(htmlspecialchars_decode($v->contents));
                $v->print_name        = name($v->user_id, $v->name, $v->nickname);
                $v->print_insert_date = time2date($v->timestamp_insert);
                $v->print_webzine_insert_date = substr($v->print_insert_date, 0, 10);

                $v->new_article_icon = '';

                //사용여부
                if(BBS_SETTING_bbs_hour_new_icon_used_article == 1)
                {
                    //파일 존재
                    //if(file_exists('.' . BBS_SETTING_bbs_hour_new_icon_path_article))
                    //{
                    //시간차
                    if((int)$v->timestamp_insert >= time() - ((int)BBS_SETTING_bbs_hour_new_icon_value_article * 60 * 60))
                    {
                        $v->new_article_icon = BBS_SETTING_bbs_hour_new_icon_path_article;
                    }
                    //}
                }

                //조회수에 따른 색변경
                $v->hit_color = '';

                //사용여부
                if(BBS_SETTING_bbs_hit_article_title_color_used == 1)
                {
                    if((int)$v->hit >= (int)BBS_SETTING_bbs_hit_article_title_color_count)
                    {
                        $v->hit_color = ' style = "color:' . BBS_SETTING_bbs_hit_article_title_color_value . '" ';
                    }
                }

                //webzine, gallery 면 이미지 파일 하나 가져온다
                if($get_image === TRUE)
                {
                    $image = $this->bbs_file_model->get_image($v->idx);

                    if($image)
                    {
                        $thumb_filepath = explode('.', $image[0]->conversion_filename);
                        $thumb_filepath = $thumb_filepath[0] . '_thumb.' . $thumb_filepath[1];

                        if(file_exists($this->upload_path . $thumb_filepath))
                        {
                            $v->image = BASE_URL . $this->upload_path . $thumb_filepath;
                        }
                        else if(file_exists($this->upload_path . $image[0]->conversion_filename))
                        {
                            $v->image = BASE_URL.$this->upload_path.$image[0]->conversion_filename;
                        }
                        else
                        {
                            $v->image = FRONTEND.'img/noimage.gif';
                        }
                    }
                    else
                    {
                        $v->image = FRONTEND.'img/noimage.gif';
                    }
                }
            }

            $this->scope('contents', 'contents/bbs/lists' . $this->lists_style, $assign);
            $this->display('layout');
        }
    }

    // --------------------------------------------------------------------

    /**
     * 글작성 연속등록 체크
     *
     * @author KangMin
     * @since  2012.01.08
     *
     * @param string $type
     *
     * @return bool
     */
    private function check_write_delay($type = 'article')
    {
        $bbs_limit_insert_delay = TRUE;

        //로그아웃 상태면 통과되지만. 이때는 글쓸 권한이 없으니 괜찮다.
        if(defined('USER_INFO_idx') && BBS_SETTING_bbs_limit_insert_delay_used == 1)
        {
            $this->load->model('bbs_' . $type . '_model');

            //게시판별 로그인회원의 마지막 글 등록시각
            //회원테이블의 timestamp_post는 종합이라서 안된다.
            $last_timestamp = $this->{'bbs_' . $type . '_model'}->get_last_timestamp($this->bbs_idx);

            if($last_timestamp > 0)
            {
                if(BBS_SETTING_bbs_limit_insert_delay_type == 'D') //일자단위
                {
                    // 좀 복잡..으..
                    //아래 주석처럼 타임존까지 하려고 했지만, 타임존은 회원이 바꿀수 있는거라 시간차가 큰 날짜가 바뀌는 타임존으로 변경하면 또 쓸수가 있게되서 그냥 서버타임으로만..
                    //if( (int)BBS_SETTING_bbs_limit_insert_delay_value > ceil((strtotime(time2date(time(), 'Y-m-d 00:00:00')) - strtotime(time2date($last_timestamp, 'Y-m-d 00:00:00'))) / (60*60*24)) )
                    if((int)BBS_SETTING_bbs_limit_insert_delay_value > ceil((strtotime(date('Y-m-d 00:00:00')) - strtotime(date('Y-m-d 00:00:00', $last_timestamp))) / (60 * 60 * 24)))
                    {
                        $bbs_limit_insert_delay = FALSE;
                    }
                }
                else //초단위 S
                {
                    if($last_timestamp >= (time() - (int)BBS_SETTING_bbs_limit_insert_delay_value))
                    {
                        $bbs_limit_insert_delay = FALSE;
                    }
                }
            }
        }

        return $bbs_limit_insert_delay;
    }

    // --------------------------------------------------------------------

    /**
     * 글작성
     *
     * @author KangMin
     */
    public function write()
    {
        $this->add_language_pack($this->language_pack('bbs_write'));

        $assign       = array();
        $post_success = FALSE;

        $assign['bbs_id']                 = $this->bbs_id;
        $assign['result_msg']             = '';
        $assign['upload_allowed_extension'] = unserialize(BBS_SETTING_bbs_upload_allow_extension);

        //연속등록 차단
        //TRUE : 작성가능, FALSE : 작성불가 .. 좀 오해소지가 있지만..정한다
        $check_write_delay = $this->check_write_delay();

        //로그인 상태가 아니거나 권한이 없으면
        if(!defined('USER_INFO_idx') OR $this->allowed_list['write_article'] !== TRUE OR $check_write_delay == FALSE)
        {
            $post_success = TRUE;

            if($check_write_delay == FALSE)
            {
                $assign['message'] = sprintf(lang('write_delay_' . BBS_SETTING_bbs_limit_insert_delay_type), BBS_SETTING_bbs_limit_insert_delay_value);
            }
            else
            {
                $assign['message'] = lang('deny_allow');
            }
            $assign['redirect'] = '/bbs/lists/' . $this->bbs_id . '?view_category='.$this->input->get_post('view_category').'&lists_style='.$this->input->get_post('lists_style');

            $this->alert($assign);
        }
        else
        {
            $this->load->model('bbs_category_model');

            //category
            $assign['category']      = $this->bbs_category_model->get_categorys_simple($this->bbs_idx, ' AND is_used = 1 ');
            $view_category           = $this->get_view_category();
            $assign['view_category'] = $view_category['view_category'];
            $assign['lists_style'] = $this->input->get_post('lists_style');

            //rules
            $this->form_validation->set_rules('title', lang('title'), 'trim|required|htmlspecialchars|xss_clean|min_length[' . BBS_SETTING_bbs_length_minimum_article_title . ']|max_length[255]');
            $this->form_validation->set_rules('contents', lang('contents'), 'trim|required|htmlspecialchars|min_length[' . BBS_SETTING_bbs_length_minimum_contents . ']');
            $this->form_validation->set_rules('tags[]', lang('tags'), 'trim|htmlspecialchars|xss_clean|max_length[64]');
            $this->form_validation->set_rules('tags_pc', lang('tags'), 'trim|htmlspecialchars|xss_clean');
            $this->form_validation->set_rules('urls[]', lang('urls'), 'trim|htmlspecialchars|xss_clean|prep_url|max_length[255]');
            $this->form_validation->set_rules('is_secret', lang('is_secret'), 'trim|xss_clean|is_natural|less_than[2]');
            $this->form_validation->set_rules('upload_files', 'upload_files', 'trim|htmlspecialchars|xss_clean');
            $this->form_validation->set_rules('wysiwyg_files', 'wysiwyg_files', 'trim|htmlspecialchars|xss_clean');
            $this->form_validation->set_rules('html_used', lang('html_used'), 'trim|xss_clean|is_natural|less_than[2]');

            if(USER_INFO_group_idx === SETTING_admin_group_idx)
            {
                $this->form_validation->set_rules('is_notice', lang('is_notice'), 'trim|xss_clean|is_natural|less_than[2]');
            }

            if(BBS_SETTING_bbs_category_used == 1 && count($assign['category']) > 0)
            {
                $this->form_validation->set_rules('category', lang('category'), 'trim|required|xss_clean|is_natural_no_zero');
            }

            //폼검증 성공이면
            if($this->form_validation->run() == TRUE)
            {
                if(BBS_SETTING_bbs_category_used == 1 && count($assign['category']) > 0)
                {
                    $req_category_idx = $this->form_validation->set_value('category');

                    //카테고리 유효성
                    $check_category_idx = $this->bbs_category_model->check_idx($req_category_idx, ' AND bbs_idx = ' . $this->bbs_idx . ' AND is_used = 1 ');
                }
                else
                {
                    $check_category_idx = TRUE;
                }

                //view_category 있는 상태에서 카테고리를 변경한다면 조작
                if($view_category['result'] == TRUE && $check_category_idx == TRUE)
                {
                    $view_category           = $this->get_view_category($req_category_idx);
                    $assign['view_category'] = $view_category['view_category'];
                }

                if($check_category_idx !== TRUE)
                {
                    $assign['result_msg'] = lang('none_category');
                }
                else
                {
                    $this->load->model('bbs_article_model');
                    $this->load->model('bbs_contents_model');
                    $this->load->model('bbs_tag_model');
                    $this->load->model('bbs_url_model');
                    $this->load->model('bbs_file_model');
                    $this->load->model('users_point_model');

                    $this->db->trans_start();

                    $values['bbs_idx']      = $this->bbs_idx;
                    $values['category_idx'] = (BBS_SETTING_bbs_category_used == 1 && count($assign['category']) > 0) ? $req_category_idx : NULL;
                    $values['title']        = $this->form_validation->set_value('title');
                    $values['contents']     = $this->form_validation->set_value('contents');
                    if ($this->viewport === 'mobile') {
                        $values['contents'] = str_replace(array('&lt', '&gt'), array('&amp;lt', '&amp;gt'), $values['contents']);
                    }
                    $values['is_notice']    = (USER_INFO_group_idx === SETTING_admin_group_idx) ? (($this->form_validation->set_value('is_notice')) ? $this->form_validation->set_value('is_notice') : 0) : 0; //관리자그룹만 공지쓸수있음.
                    $values['is_secret']    = $this->form_validation->set_value('is_secret') ? $this->form_validation->set_value('is_secret') : 0;
                    $values['html_used']    = $this->form_validation->set_value('html_used') ? $this->form_validation->set_value('html_used') : 0;

                    //article
                    $result_article = $this->bbs_article_model->insert_article($values);

                    $values['article_idx'] = $this->db->insert_id();

                    //contents
                    $result_contents = $this->bbs_contents_model->insert_contents($values);

                    //tags
                    //true/false 체킹이 좀 애매하긴한데, 크게 중요한건 아니라 대충...
                    if(BBS_SETTING_bbs_tags_used == 1)
                    {
                        //pc면...
                        $tags_pc = $this->form_validation->set_value('tags_pc');
                        if($tags_pc)
                        {
                            $tags = explode(',', $tags_pc);
                        }
                        else
                        {
                            $tags = $this->input->post('tags');
                        }

                        for($i = 0; $i < BBS_SETTING_bbs_tags_limit_count; $i++)
                        {
                            $tag = (!empty($tags[$i])) ? trim($tags[$i]) : '';

                            if(!empty($tag))
                            {
                                $this->bbs_tag_model->insert_tag($values['bbs_idx'], $values['article_idx'], htmlspecialchars($tag));
                            }
                        }
                    }

                    //urls
                    //true/false 체킹이 좀 애매하긴한데, 크게 중요한건 아니라 대충...
                    if(BBS_SETTING_bbs_urls_used == 1)
                    {
                        $urls = $this->input->post('urls');

                        for($i = 0; $i < BBS_SETTING_bbs_urls_limit_count; $i++)
                        {
                            if(trim($urls[$i]) !== '')
                            {
                                $this->bbs_url_model->insert_url($values['bbs_idx'], $values['article_idx'], htmlspecialchars(prep_url($urls[$i])));
                            }
                        }
                    }

                    //files 정리
                    $upload_files_result = $this->get_upload_files_result($values['bbs_idx'], $values['article_idx'], $this->form_validation->set_value('upload_files'), BBS_SETTING_bbs_upload_limit_count);
                    $wysiwyg_files_result = $this->get_upload_files_result($values['bbs_idx'], $values['article_idx'], $this->form_validation->set_value('wysiwyg_files'), 99999999, TRUE);

                    $result_file = TRUE;
                    $result_wysiwyg_file = TRUE;

                    //files DB insert
                    foreach($upload_files_result as $k => $v)
                    {
                        $result_file = $this->bbs_file_model->insert($v); //흠.. 각각의 true여부를 다 하기에는 좀.. 그냥 마지막..
                    }

                    //wysiwyg files DB insert
                    foreach($wysiwyg_files_result as $k => $v)
                    {
                        $result_wysiwyg_file = $this->bbs_file_model->insert($v); //흠.. 각각의 true여부를 다 하기에는 좀.. 그냥 마지막..
                    }

                    //users (글작성수)
                    $result_users = $this->users_model->update_count_users(USER_INFO_idx, 'article_count', 1);

                    //users (마지막 글작성시각)
                    $result_users_timestamp_post = $this->users_model->update_last_post(USER_INFO_idx);

                    //point
                    if(BBS_SETTING_bbs_point_article_used == 1)
                    {
                        //users (포인트)
                        $result_users_point = $this->users_model->update_count_users(USER_INFO_idx, 'point', (int)BBS_SETTING_bbs_point_article);

                        //BBS_SETTING_bbs_point_article
                        $result_point = $this->users_point_model->insert_point('article', $values['article_idx'], (int)BBS_SETTING_bbs_point_article);
                    }
                    else
                    {
                        $result_users_point = TRUE;
                        $result_point       = TRUE;
                    }

                    $this->db->trans_complete();

                    // 그런데.. myisam이면 트랜젝션이 의미가 없어서뤼...쩝
                    if($result_article == TRUE
                        AND $result_contents == TRUE
                        AND $result_users == TRUE
                        AND $result_users_timestamp_post == TRUE
                        AND $result_users_point == TRUE
                        AND $result_point == TRUE
                        AND $result_file == TRUE
                        AND $result_wysiwyg_file == TRUE
                    )
                    {
                        //글 작성후 이동할 페이지를 재계산해서 보내기
                        $article_total_cnt = $this->bbs_article_model->lists_total_cnt($this->bbs_idx, ' AND BBS_ARTICLE.idx > ' . $values['article_idx'] . ' AND BBS_ARTICLE.is_deleted = 0 ' . $view_category['query_where_as']);

                        $assign['page'] = floor($article_total_cnt / BBS_SETTING_bbs_count_list_article) + 1;

                        $post_success       = TRUE;
                        $assign['message']  = lang('write_success');
                        $assign['redirect'] = '/bbs/view/' . $this->bbs_id . '?idx=' . $values['article_idx'] . '&page=' . $assign['page'] . $view_category['param_add'] . '&lists_style=' . $this->input->get_post('lists_style');

                        $this->alert($assign);
                    }
                    else
                    {
                        $assign['result_msg'] = lang('write_fail_msg');
                    }
                }
            }

            if($post_success === FALSE)
            {
                $assign['is_use_category'] = FALSE;
                if(BBS_SETTING_bbs_category_used == 1 && count($assign['category']) > 0)
                {
                    $assign['is_use_category'] = TRUE;
                }

                $assign['validation_result']  = validation_errors();
                $assign['validation_message'] = ($assign['validation_result'] !== '') ? str_replace("\n", '', $assign['validation_result']) : '';

                $assign['form_null_check'] = "title^{$this->assign['lang']['title']}|contents^{$this->assign['lang']['contents']}";
                if($assign['is_use_category'] === TRUE)
                {
                    $assign['form_null_check'] .= "|category^{$this->assign['lang']['category']}";
                }

                $assign['form_minimum_check'] = "title^{$this->assign['lang']['title']}^" . BBS_SETTING_bbs_length_minimum_article_title . "|contents^{$this->assign['lang']['contents']}^" . BBS_SETTING_bbs_length_minimum_contents;

                $value_key_list = array(
                    'title',
                    'contents'
                );
                foreach($value_key_list as $v)
                {
                    $assign['value_list'][$v] = set_value($v);
                }

                $select_key_list = array('category');
                foreach($select_key_list as $v)
                {

                    foreach($assign[$v] as &$item)
                    {
                        $item->selected = set_select($v, $item->idx);
                    }
                }

                $checkbox_key_list = array(
                    'is_secret' => '1',
                    'is_notice' => '1'
                );
                foreach($checkbox_key_list as $k => $default)
                {
                    $assign['checkbox_list'][$k] = set_checkbox($k, $default);
                }

                $assign['tags_post']      = '';
                $assign['tags_collapsed'] = 'false';
                if(BBS_SETTING_bbs_tags_used == 1)
                {
                    $assign['tags_post'] = unset_empty_array($this->input->post('tags')); //공백 배열 제거
                    //$assign['tags_collapsed'] = (is_array($assign['tags_post']) && count($assign['tags_post']) > 0) ? 'false' : 'true';
                    for($i = 0; $i < BBS_SETTING_bbs_tags_limit_count; $i++)
                    {
                        if(isset($assign['tags_post'][$i]) === FALSE)
                        {
                            $assign['tags_post'][$i] = '';
                        }
                    }
                    $assign['tags_post'] = array_slice($assign['tags_post'], 0, BBS_SETTING_bbs_tags_limit_count);
                }

                $assign['urls_post']         = '';
                $assign['urls_is_collapsed'] = 'false';
                if(BBS_SETTING_bbs_urls_used == 1)
                {

                    $assign['urls_post'] = unset_empty_array($this->input->post('urls'));
                    //$assign['urls_collapsed'] = (is_array($assign['urls_post']) === TRUE AND count($assign['urls_post']) > 0) ? 'true' : 'false';
                    for($i = 0; $i < BBS_SETTING_bbs_urls_limit_count; $i++)
                    {
                        if(isset($assign['urls_post'][$i]) === FALSE)
                        {
                            $assign['urls_post'][$i] = '';
                        }
                    }
                    $assign['urls_post'] = array_slice($assign['urls_post'], 0, BBS_SETTING_bbs_urls_limit_count);
                }

                $this->scope('contents', 'contents/bbs/write', $assign);
                $this->display('layout');
            }

        }
        //로그인,권한 체크 end if
    }

    // --------------------------------------------------------------------

    /**
     * 글수정
     *
     * @author KangMin
     */
    public function modify()
    {
        $this->add_language_pack($this->language_pack('bbs_modify'));

        $assign       = NULL;
        $post_success = FALSE;

        $assign['bbs_id']                 = $this->bbs_id;
        $assign['result_msg']             = '';
        $assign['upload_allowed_extension'] = unserialize(BBS_SETTING_bbs_upload_allow_extension);

        $req_idx  = $assign['idx'] = ((int)$this->input->get_post('idx') > 0) ? (int)$this->input->get_post('idx') : NULL;
        $req_page = $assign['page'] = ((int)$this->input->get_post('page') > 0) ? (int)$this->input->get_post('page') : 1;

        $this->load->model('bbs_article_model');

        //유효성
        $check_idx = $this->bbs_article_model->check_idx($req_idx, ' AND bbs_idx = ' . $this->bbs_idx . ' AND user_idx = ' . (defined('USER_INFO_idx') ? USER_INFO_idx : 0) . ' AND is_deleted = 0 ');

        if($check_idx !== TRUE)
        {
            $req_idx = NULL;
        }

        //게시물 유효성 검사 실패거나 권한이 없으면
        if(!defined('USER_INFO_idx')
            OR $this->allowed_list['write_article'] !== TRUE
            OR $req_idx == NULL
        )
        {
            if($req_idx == NULL)
            {
                $assign['message'] = lang('none_article');
            }
            else
            {
                $assign['message'] = lang('deny_allow');
            }
            $assign['redirect'] = '/bbs/lists/' . $this->bbs_id . '?page=' . $req_page;

            $this->alert($assign);
        }
        else
        {
            $this->load->model('bbs_category_model');
            $this->load->model('bbs_contents_model');
            $this->load->model('bbs_tag_model');
            $this->load->model('bbs_url_model');
            $this->load->model('bbs_file_model');

            //article, contents, hit
            $assign['view'] = $this->bbs_article_model->view($req_idx);

            //files
            $files             = $this->bbs_file_model->get_files($req_idx);

            $assign['files'] = array();
            foreach ($files as $k => $v) {
                if ($v->is_wysiwyg === '0') {
                    $assign['files'][] = $v;
                }
            }
            $assign['files_count'] = count($assign['files']);

            $wysiwyg_file_list = $this->bbs_file_model->get_files($req_idx, 1);
            $assign['wysiwyg_files'] = '';
            foreach ($wysiwyg_file_list as $k => $v) {
                if ($v->is_wysiwyg === '1') {
                    $assign['wysiwyg_files'] .= "{$v->idx}:{$v->conversion_filename}|";
                }
            }

            //category
            $assign['category']      = $this->bbs_category_model->get_categorys_simple($this->bbs_idx, ' AND is_used = 1 ');
            $view_category           = $this->get_view_category();
            $assign['view_category'] = $view_category['view_category'];
            $assign['lists_style'] = $this->input->get_post('lists_style');

            //rules
            $this->form_validation->set_rules('title', lang('title'), 'trim|required|htmlspecialchars|xss_clean|min_length[' . BBS_SETTING_bbs_length_minimum_article_title . ']|max_length[255]');
            $this->form_validation->set_rules('contents', lang('contents'), 'trim|required|htmlspecialchars|min_length[' . BBS_SETTING_bbs_length_minimum_contents . ']');
            $this->form_validation->set_rules('tags[]', lang('tags'), 'trim|htmlspecialchars|xss_clean|max_length[64]');
            $this->form_validation->set_rules('tags_pc', lang('tags'), 'trim|htmlspecialchars|xss_clean');
            $this->form_validation->set_rules('urls[]', lang('urls'), 'trim|htmlspecialchars|xss_clean|prep_url|max_length[255]');
            $this->form_validation->set_rules('is_secret', lang('is_secret'), 'trim|xss_clean|is_natural|less_than[2]');
            $this->form_validation->set_rules('delete_file[]', 'delete_file', 'trim|xss_clean|is_natural_no_zero');
            $this->form_validation->set_rules('upload_files', 'upload_files', 'trim|htmlspecialchars|xss_clean');
            $this->form_validation->set_rules('wysiwyg_files', 'wisywig_files', 'trim|htmlspecialchars|xss_clean');
            $this->form_validation->set_rules('html_used', lang('html_used'), 'trim|xss_clean|is_natural|less_than[2]');

            if(USER_INFO_group_idx === SETTING_admin_group_idx)
            {
                $this->form_validation->set_rules('is_notice', lang('is_notice'), 'trim|xss_clean|is_natural|less_than[2]');
            }

            if(BBS_SETTING_bbs_category_used == 1 && count($assign['category']) > 0)
            {
                $this->form_validation->set_rules('category', lang('category'), 'trim|required|xss_clean|is_natural_no_zero');
            }

            //폼검증 성공이면
            if($this->form_validation->run() == TRUE)
            {
                if(BBS_SETTING_bbs_category_used == 1 && count($assign['category']) > 0)
                {
                    $req_category_idx = $this->form_validation->set_value('category');

                    //카테고리 유효성
                    $check_category_idx = $this->bbs_category_model->check_idx($req_category_idx, ' AND bbs_idx = ' . $this->bbs_idx . ' AND is_used = 1 ');
                }
                else
                {
                    $check_category_idx = TRUE;
                }

                //view_category 있는 상태에서 카테고리를 변경한다면 조작
                if($view_category['result'] == TRUE && $check_category_idx == TRUE)
                {
                    $view_category           = $this->get_view_category($req_category_idx);
                    $assign['view_category'] = $view_category['view_category'];
                }

                if($check_category_idx !== TRUE)
                {
                    $assign['result_msg'] = lang('none_category');
                }
                else
                {
                    $this->db->trans_start();

                    $values['bbs_idx']      = $this->bbs_idx;
                    $values['category_idx'] = (BBS_SETTING_bbs_category_used == 1 && count($assign['category']) > 0) ? $req_category_idx : NULL;
                    $values['title']        = $this->form_validation->set_value('title');
                    $values['contents']     = $this->form_validation->set_value('contents');
                    if ($this->viewport === 'mobile') {
                        $values['contents'] = str_replace(array('&lt', '&gt'), array('&amp;lt', '&amp;gt'), $values['contents']);
                    }
                    //$values['contents'] = $this->form_validation->set_value('contents', FALSE, TRUE);
                    $values['is_notice']    = (USER_INFO_group_idx === SETTING_admin_group_idx) ? (($this->form_validation->set_value('is_notice')) ? $this->form_validation->set_value('is_notice') : 0) : 0;
                    $values['is_secret']    = $this->form_validation->set_value('is_secret') ? $this->form_validation->set_value('is_secret') : 0;
                    $values['html_used']    = $this->form_validation->set_value('html_used') ? $this->form_validation->set_value('html_used') : 0;

                    //article
                    $result_article = $this->bbs_article_model->update_article($req_idx, $values, ' AND user_idx = ' . USER_INFO_idx . ' ');

                    //contents
                    //$result_contents = $this->bbs_contents_model->update_contents($req_idx, $values, ' AND exec_user_idx = ' . USER_INFO_idx . ' ');
                    $result_contents = $this->bbs_contents_model->update_contents($req_idx, $values, ' AND (SELECT count(idx) FROM tb_bbs_article WHERE idx = ' . $req_idx . ' AND user_idx = ' . USER_INFO_idx . ') = 1 ');

                    //tags
                    //true/false 체킹이 좀 애매하긴한데, 크게 중요한건 아니라 대충...
                    //만약 10개를 넣을 수 있을때 넣었다가 수정하면 3개만 들어가지만 뭐.. 정책으로
                    if(BBS_SETTING_bbs_tags_used == 1)
                    {
                        //pc면...
                        $tags_pc = $this->form_validation->set_value('tags_pc');
                        if($tags_pc)
                        {
                            $tags = explode(',', $tags_pc);
                        }
                        else
                        {
                            $tags = $this->input->post('tags');
                        }

                        //태그 삭제 후 다시 삽입
                        $this->bbs_tag_model->delete_tags($req_idx);

                        for($i = 0; $i < BBS_SETTING_bbs_tags_limit_count; $i++)
                        {
                            $tag = (!empty($tags[$i])) ? trim($tags[$i]) : '';

                            if(!empty($tag))
                            {
                                $this->bbs_tag_model->insert_tag($values['bbs_idx'], $req_idx, htmlspecialchars($tag));
                            }
                        }
                    }

                    //urls
                    //true/false 체킹이 좀 애매하긴한데, 크게 중요한건 아니라 대충...
                    //만약 10개를 넣을 수 있을때 넣었다가 수정하면 3개만 들어가지만 뭐.. 정책으로
                    if(BBS_SETTING_bbs_urls_used == 1)
                    {
                        $urls = $this->input->post('urls');

                        //관련링크 삭제 후 다시 삽입
                        $this->bbs_url_model->delete_urls($req_idx);

                        for($i = 0; $i < BBS_SETTING_bbs_urls_limit_count; $i++)
                        {
                            if(trim($urls[$i]) !== '')
                            {
                                $this->bbs_url_model->insert_url($values['bbs_idx'], $req_idx, htmlspecialchars(prep_url($urls[$i])));
                            }
                        }
                    }

                    //files
                    //우선 삭제
                    $result_delete_files = TRUE; //일단 true

                    if($this->input->post('delete_file'))
                    {
                        $post_delete_file = $this->input->post('delete_file');

                        $delete_files = join(',', $post_delete_file);

                        //실제 파일 삭제
                        //삭제를 위한 실제 파일명 호출
                        $delete_filenames = $this->bbs_file_model->get_filenames($req_idx, $delete_files, ' AND user_idx = ' . USER_INFO_idx . ' ');

                        $result_delete_files = $this->bbs_file_model->delete_files($req_idx, $delete_files, ' AND user_idx = ' . USER_INFO_idx . ' '); //결과를 계속 뒤집어 쓰지만 뭐 크게 중요하진 않을듯해서

                        foreach($delete_filenames as $k => $v)
                        {
                            $thumb_filepath = explode('.', $v->conversion_filename);
                            $thumb_filepath = $thumb_filepath[0] . '_thumb.' . $thumb_filepath[1];

                            if(file_exists($this->upload_path . $v->conversion_filename)) @unlink($this->upload_path . $v->conversion_filename); //실제 파일 삭제
                            if(file_exists($this->upload_path . $thumb_filepath)) @unlink($this->upload_path . $thumb_filepath); //실제 파일 삭제 (섬네일)
                        }
                    }

                    $wysiwyg_files = $this->form_validation->set_Value('wysiwyg_files');
                    $wysiwyg_files_arr = explode('|', $wysiwyg_files);

                    $delete_wysiwyg_files = array();
                    foreach ($wysiwyg_files_arr as $v) {

                        $temp = explode(':', $v);
                        if (!empty($temp[1])) {

                            if (strpos($values['contents'], $temp[1]) === FALSE) {
                                $delete_wysiwyg_files[] = $temp[0];
                                if(file_exists($this->upload_path . $temp[1])) @unlink($this->upload_path . $temp[1]);
                            }
                        }
                    }

                    $result_delete_files = true;
                    if (count($delete_wysiwyg_files) > 0) {

                        $delete_wysiwyg_files = implode(',', $delete_wysiwyg_files);
                        $result_delete_files = $this->bbs_file_model->delete_files($req_idx, $delete_wysiwyg_files, ' AND user_idx = ' . USER_INFO_idx . ' '); //결과를 계속 뒤집어 쓰지만 뭐 크게 중요하진 않을듯해서
                    }

                    //추가
                    //files 정리
                    $upload_files_result = $this->get_upload_files_result($values['bbs_idx'], $req_idx, $this->form_validation->set_value('upload_files'), (BBS_SETTING_bbs_upload_limit_count - count($assign['files'])));
                    $wysiwyg_files_result = $this->get_upload_files_result($values['bbs_idx'], $req_idx, $this->form_validation->set_value('wysiwyg_files'), 99999999, TRUE);

                    $result_file = TRUE;
                    $result_wysiwyg_file = TRUE;

                    //files DB insert
                    foreach($upload_files_result as $k => $v)
                    {
                        $result_file = $this->bbs_file_model->insert($v); //흠.. 각각의 true여부를 다 하기에는 좀.. 그냥 마지막..
                    }

                    //wysiwyg files DB insert
                    foreach($wysiwyg_files_result as $k => $v)
                    {
                        if (!empty($v['original_filename'])) {
                            $result_wysiwyg_file = $this->bbs_file_model->insert($v); //흠.. 각각의 true여부를 다 하기에는 좀.. 그냥 마지막..
                        }
                    }

                    $this->db->trans_complete();

                    // 그런데.. myisam이면 트랜젝션이 의미가 없어서뤼...쩝
                    if($result_article == TRUE
                        AND $result_contents == TRUE
                        AND $result_delete_files == TRUE
                        AND $result_file == TRUE
                        AND $result_wysiwyg_file = TRUE
                    )
                    {
                        //글 수정후 이동할 페이지를 재계산해서 보내기
                        $article_total_cnt = $this->bbs_article_model->lists_total_cnt($this->bbs_idx, ' AND BBS_ARTICLE.idx > ' . $req_idx . ' AND BBS_ARTICLE.is_deleted = 0 ' . $view_category['query_where_as']);

                        $assign['page'] = floor($article_total_cnt / BBS_SETTING_bbs_count_list_article) + 1;

                        $post_success       = TRUE;
                        $assign['message']  = lang('update_success');
                        $assign['redirect'] = '/bbs/view/' . $this->bbs_id . '?idx=' . $req_idx . '&page=' . $assign['page'] . '&hit=not' . $view_category['param_add'] . '&lists_style=' . $this->input->get_post('lists_style');

                        $this->alert($assign);
                    }
                    else
                    {
                        $assign['result_msg'] = lang('update_fail_msg');
                    }
                }
            }

            if($post_success === FALSE)
            {
                $assign['tags_collapsed'] = 'true';
                $assign['urls_collapsed'] = 'true';

                //tags
                if(BBS_SETTING_bbs_tags_used == 1)
                {
                    $tags = $this->bbs_tag_model->get_tags($req_idx);
                    $tags_count = 0;

                    for ($i = 0; $i < BBS_SETTING_bbs_tags_limit_count; $i++)
                    {
                        if(isset($tags[$i]) == TRUE)
                        {
                            $assign['tags'][$i] = $tags[$i]->tag;
                            $tags_count++;
                        }
                        else
                        {
                            $assign['tags'][$i] = '';
                        }
                    }

                    if($tags_count > 0)
                    {
                        $assign['tags_collapsed'] = 'false';
                    }
                }

                //urls
                if(BBS_SETTING_bbs_urls_used == 1)
                {
                    $urls = $this->bbs_url_model->get_urls($req_idx);
                    $urls_count = 0;

                    for ($i = 0; $i < BBS_SETTING_bbs_urls_limit_count; $i++)
                    {
                        if(isset($urls[$i]) == TRUE)
                        {
                            $assign['urls'][$i] = $urls[$i]->url;
                            $urls_count++;
                        }
                        else
                        {
                            $assign['urls'][$i] = '';
                        }
                    }

                    if($urls_count > 0)
                    {
                        $assign['urls_collapsed'] = 'false';
                    }
                }

                $assign['form_null_check'] = "title^{$this->assign['lang']['title']}|contents^{$this->assign['lang']['contents']}";
                $assign['is_use_category'] = FALSE;
                if(BBS_SETTING_bbs_category_used == 1 && count($assign['category']) > 0)
                {
                    $assign['is_use_category'] = TRUE;
                    $assign['form_null_check'] .= "|category^{$this->assign['lang']['category']}";
                }

                $assign['form_minimum_check'] = "title^{$this->assign['lang']['title']}^" . BBS_SETTING_bbs_length_minimum_article_title . "|contents^{$this->assign['lang']['contents']}^" . BBS_SETTING_bbs_length_minimum_contents;

                $assign['validation_result']  = validation_errors();
                $assign['validation_message'] = ($assign['validation_result'] !== '') ? str_replace("\n", '', $assign['validation_result']) : '';

                $value_key_list = array(
                    'title',
                    'contents'
                );

                foreach($value_key_list as $v)
                {
                    //if($v == 'contents') $assign['view']->{$v} = html_purify($assign['view']->{$v});
                    $chk                      = set_value($v);
                    $assign['value_list'][$v] = ($chk) ? $chk : $assign['view']->{$v};
                }

                $select_key_list = array('category');
                foreach($select_key_list as $v)
                {

                    $chk = set_value($v);

                    foreach($assign[$v] as &$item)
                    {
                        $item->selected = (isset($item->selected) === FALSE) ? NULL : $item->selected;
                        if($chk)
                        {
                            $item->selected = set_select($v, $item->idx);
                        }
                        else
                        {
                            if($item->idx == $assign['view']->category_idx)
                            {
                                $item->selected = ' selected="selected"';
                            }
                        }
                    }
                }

                $checkbox_key_list = array(
                    array('is_secret' => '1'),
                    array('is_notice' => '1')
                );
                foreach($checkbox_key_list as &$item)
                {
                    foreach($item as $k => $v)
                    {
                        $checked = '';
                        $check_value= set_value($k);
                        //$assign['checkbox_list'][$k] = ($chk) ? set_checkbox($k, $v) : set_checkbox($k, $assign['view']->{$k});
                        if (!empty($check_value)) {
                            $checked = set_checkbox($k, $v);
                        } else {
                            if ($v === $assign['view']->{$k}) {
                                $checked = ' checked="checkecd"';
                            }
                        }
                        $assign['checkbox_list'][$k] = $checked;
                    }
                }
                $this->scope('contents', 'contents/bbs/modify', $assign);
                $this->display('layout');

            }
        }
        //로그인,권한 체크 end if
    }

    // --------------------------------------------------------------------

    /**
     * 글작성/수정 파일추가시 정리하는 부분 리팩
     *
     * @desc 좀 보기 흉하다..
     *
     * @author KangMin
     * @since  2012.03.05
     *
     * @param int
     * @param int
     * @param string
     * @param int
     * @param array
     *
     * @return array
     */
    private function get_upload_files_result($bbs_idx, $article_idx, $upload_files, $upload_files_cnt_limit, $is_wysiwyg = FALSE)
    {
        //files 정리
        $this->load->helper('file');

        $upload_files_result = array();

        if(defined('USER_INFO_idx') && $this->allowed_list['upload'] === TRUE && BBS_SETTING_bbs_upload_used == 1)
        {
            $upload_files = explode('|', $upload_files);
            $upload_files_cnt = 0;

            $this->config->load('gd');
            $gd_type = $this->config->item('gd_type');
            $this->load->library('image_lib');
            //$this->config->load('thumb');
            //$thumb_width = $this->config->item('thumb_width');
            //$thumb_quality = $this->config->item('thumb_quality');
            $thumb_width = BBS_SETTING_bbs_thumbnail_width;
            $thumb_quality = BBS_SETTING_bbs_thumbnail_quality;

            foreach($upload_files as $k => $v)
            {
                if($v)
                {
                    $temp = explode(':', $v);

                    if(count($temp) == 2)
                    {
                        if($upload_files_cnt < $upload_files_cnt_limit)
                        {
                            $temp[0] = trim($temp[0]);
                            $temp[1] = trim($temp[1]);

                            if($temp[0] !== '' && $temp[1] !== '')
                            {
                                //파일유효성
                                if(file_exists($this->upload_path . $temp[1]))
                                {

                                    if($this->check_image_type($this->upload_path . $temp[1]) === TRUE && $is_wysiwyg === FALSE)
                                    {
                                        //이미지인 경우 설정에 따라 퀄리티, 사이즈 조작
                                        $config['image_library']  = $gd_type; //요건 환경에 따라 정해야함.. 기본은 gd2 임
                                        $config['source_image']   = $this->upload_path . $temp[1];
                                        $config['thumb_marker']	  = '_resize';
                                        $config['create_thumb']   = TRUE;
                                        $config['maintain_ratio'] = TRUE;
                                        $config['quality']        = BBS_SETTING_bbs_upload_image_quality;

                                        $imagesize = getimagesize($this->upload_path . $temp[1]);
                                        $width     = (int)$imagesize[0];

                                        if(BBS_SETTING_bbs_upload_limit_image_size_width == 0 OR $width < (int)BBS_SETTING_bbs_upload_limit_image_size_width)
                                        {
                                            $config['width']  = '';
                                            $config['height'] = '';
                                        }
                                        else
                                        {
                                            $config['width']  = BBS_SETTING_bbs_upload_limit_image_size_width;
                                            $config['height'] = 1; //(BBS_SETTING_bbs_upload_limit_image_size_height == 0) ? '' : BBS_SETTING_bbs_upload_limit_image_size_height; //2012.11.25 주석처리 //이상하게 가로 값이 있을라면 세로값도 있어야 되네...
                                        }

                                        $config['master_dim'] = 'width';

                                        $this->image_lib->initialize($config);

                                        $this->image_lib->resize();

                                        //$this->image_lib->clear(); //이걸하니 이상하게 2번째 파일부터 용량이 커진다..ㅠ

                                        //thumnail
                                        //위 리사이즈는 원본 자체를 처리한다.
                                        //ci의 섬네일은 원본과 섬네일 크기를 같이 지정할 수 없어서...
                                        //이미지인 경우 설정에 따라 퀄리티, 사이즈 조작
                                        $config['image_library']  = $gd_type; //요건 환경에 따라 정해야함.. 기본은 gd2 임
                                        $config['source_image']   = $this->upload_path . $temp[1];
                                        $config['thumb_marker']	  = '_thumb';
                                        $config['create_thumb']   = TRUE;
                                        $config['maintain_ratio'] = TRUE;
                                        $config['quality']        = $thumb_quality;

                                        if($width < $thumb_width)
                                        {
                                            $config['width']  = '';
                                            $config['height'] = '';
                                        }
                                        else
                                        {
                                            $config['width']  = $thumb_width;
                                            $config['height'] = 1; //(BBS_SETTING_bbs_upload_limit_image_size_height == 0) ? '' : BBS_SETTING_bbs_upload_limit_image_size_height; //2012.11.25 주석처리 //이상하게 가로 값이 있을라면 세로값도 있어야 되네...
                                        }

                                        $config['master_dim'] = 'width';

                                        $this->image_lib->initialize($config);

                                        $this->image_lib->resize();

                                        //$this->image_lib->clear(); //이걸하니 이상하게 2번째 파일부터 용량이 커진다..ㅠ

                                        //resize 파일 경로
                                        $resize_filepath = explode('.', $temp[1]);
                                        $resize_filepath = $resize_filepath[0] . '_resize.' . $resize_filepath[1];

                                        if(file_exists($this->upload_path . $temp[1])) @unlink($this->upload_path . $temp[1]);
                                        rename($this->upload_path . $resize_filepath, $this->upload_path . $temp[1]);
                                    }

                                    $upload_files_result[$upload_files_cnt]['bbs_idx']             = $bbs_idx;
                                    $upload_files_result[$upload_files_cnt]['article_idx']         = $article_idx;
                                    $upload_files_result[$upload_files_cnt]['user_idx']            = USER_INFO_idx;
                                    $upload_files_result[$upload_files_cnt]['is_wysiwyg']          = ($is_wysiwyg === TRUE) ? 1 : 0;
                                    $upload_files_result[$upload_files_cnt]['mime']                = get_mime_by_extension($this->upload_path . $temp[1]);
                                    $upload_files_result[$upload_files_cnt]['original_filename']   = htmlspecialchars(urldecode(base64_decode($temp[0])));
                                    $upload_files_result[$upload_files_cnt]['conversion_filename'] = $temp[1];
                                    $upload_files_size                                             = get_file_info($this->upload_path . $temp[1], 'size');
                                    $upload_files_result[$upload_files_cnt]['capacity']            = $upload_files_size['size'];
                                }
                                else
                                {
                                    //파일 없으면 뭐.. 가뿐히 무시
                                }
                            }
                            else
                            {
                                //일단 딱히 할게..
                            }
                        }
                        else //파일첨부 갯수만큼만 순서대로 정리하고 그 이후는 삭제해버린다.
                        {
                            if(file_exists($this->upload_path . $temp[1])) @unlink($this->upload_path . $temp[1]);
                        }
                    }
                    else
                    {
                        //딱히 할 수 있는게...
                    }
                }
                else
                {
                    //끝 한개나 비정상적인 접근인데 딱히 할 수 있는게...
                }

                $upload_files_cnt++;
            }
        }

        return $upload_files_result;
    }

    function check_image_type($path = '')
    {
        $images_mime = array(
            'image/gif',
            'image/jpeg',
            'image/pjpeg',
            'image/png',
            'image/x-png'
        );

        return in_array(get_mime_by_extension($path), $images_mime);
    }

    // --------------------------------------------------------------------

    /**
     * 삭제
     *
     * @author KangMin
     */
    public function delete()
    {
        $data = NULL;

        $req_idx = $data['idx'] = ((int)$this->input->post('idx') > 0) ? (int)$this->input->post('idx') : NULL;

        $this->load->model('bbs_article_model');

        //유효성
        $check_idx = $this->bbs_article_model->check_idx($req_idx, ' AND bbs_idx = ' . $this->bbs_idx . ' AND user_idx = ' . (defined('USER_INFO_idx') ? USER_INFO_idx : 0) . ' AND is_deleted = 0 ');

        if($check_idx !== TRUE)
        {
            $req_idx = NULL;
        }

        //권한
        $data['allow'] = $this->get_bbs_allow();

        //게시물 유효성 검사 실패거나 권한이 없으면
        if(!defined('USER_INFO_idx')
            OR in_array('write_article', $data['allow']) !== TRUE
            OR $req_idx == NULL
        )
        {
            if($req_idx == NULL)
            {
                $data['message'] = lang('none_article');
            }
            else
            {
                $data['message'] = lang('deny_allow');
            }
            $data['redirect'] = '/bbs/lists/' . $this->bbs_id;

            $this->alert($data);
        }
        else
        {
            //category
            $view_category         = $this->get_view_category();
            $data['view_category'] = $view_category['view_category'];
            $data['lists_style'] = $this->input->get_post('lists_style');

            $this->load->model('users_point_model');

            $this->db->trans_start();

            $result = $this->bbs_article_model->delete_article($req_idx, ' AND user_idx = ' . USER_INFO_idx . ' ');

            //users (글작성수)
            $result_users = $this->users_model->update_count_users(USER_INFO_idx, 'article_count', -1);

            //point
            if(BBS_SETTING_bbs_point_article_used == 1)
            {
                //해당 글 작성시 포인트를 찾아서 삭제
                //만약 글작성 포인트를 5줄때 쓴 글인데 10줄때 삭제한다고 10을 깍으면 안되징...
                //혹 포인트내역이 없거나 등하면 현재의 글작성 포인트로 삭제
                $point = $this->users_point_model->get_point('article', $req_idx, (int)BBS_SETTING_bbs_point_article);

                //글 삭제시 해당글 안의 본인 코멘트에 부여된 포인트도 삭제한다.
                //글 하나 써두고 뻘짓 코멘트 열라게 달고 지우면 그 포인트는 남아있게된다.
                $point_own_comments = $this->users_point_model->get_point_own_comments($req_idx);

                //users (포인트)
                $result_users_point = $this->users_model->update_count_users(USER_INFO_idx, 'point', ($point + $point_own_comments) * -1);

                $result_point = $this->users_point_model->insert_point('article', $req_idx, ($point + $point_own_comments) * -1);
            }
            else
            {
                $result_users       = TRUE;
                $result_users_point = TRUE;
                $result_point       = TRUE;
            }

            $this->db->trans_complete();

            if($result == TRUE
                AND $result_users == TRUE
                AND $result_point == TRUE
            )
            {
                $data['message'] = lang('delete_success');
            }
            else
            {
                $data['message'] = lang('delete_fail_msg');
            }

            $data['redirect'] = '/bbs/lists/' . $this->bbs_id . '?lists_style=' . $this->input->get_post('lists_style') . $view_category['param_add'];
            $this->alert($data);
        }
        //로그인,권한 체크 end if
    }

    // --------------------------------------------------------------------

    /**
     * 뷰
     *
     * @author KangMin
     */
    public function view()
    {
        $this->add_language_pack($this->language_pack('bbs_view'));

        $req_idx  = ((int)$this->input->get_post('idx') > 0) ? (int)$this->input->get_post('idx') : NULL;
        $req_page = ((int)$this->input->get('page') > 0) ? (int)$this->input->get('page') : 1;

        $this->load->model('bbs_article_model');

        //유효성
        $check_idx = $this->bbs_article_model->check_idx($req_idx, ' AND bbs_idx = ' . $this->bbs_idx . ' AND is_deleted = 0 ');

        if($check_idx !== TRUE)
        {
            $req_idx = NULL;
        }

        $assign            = array();
        $assign['bbs_id']  = $this->bbs_id;
        $assign['bbs_idx'] = $this->bbs_idx;
        $assign['idx']     = $req_idx;

        //게시물 유효성 검사 실패거나 권한이 없으면
        if($this->allowed_list['view_article'] === FALSE OR $req_idx == NULL)
        {
            if($req_idx == NULL)
            {
                $assign['message'] = lang('none_article');
            }
            else
            {
                $assign['message'] = lang('deny_allow');
            }
            $assign['redirect'] = '/bbs/lists/' . $this->bbs_id . '?page=' . $req_page;

            $this->alert($assign);
        }
        else
        {
            //category
            $view_category           = $this->get_view_category();
            $assign['view_category'] = $view_category['view_category'];
            $assign['lists_style'] = $this->input->get_post('lists_style');

            //article, contents, hit
            $assign['view'] = $this->bbs_article_model->view($req_idx, ' AND BBS_ARTICLE.is_deleted = 0 ');

            $block_secret = FALSE;

            //비밀글이면 본인과 관리자만
            if($assign['view']->is_secret == 1 && USER_INFO_group_idx !== SETTING_admin_group_idx)
            {
                if(!defined('USER_INFO_idx') OR USER_INFO_idx !== (int)$assign['view']->user_idx)
                {
                    $block_secret = TRUE;
                }
            }

            if($block_secret == TRUE)
            {
                $assign['message']  = lang('is_secret');
                $assign['redirect'] = '/bbs/lists/' . $this->bbs_id . '?page=' . $req_page;

                $this->alert($assign);
            }
            else
            {
                $this->load->helper('text'); //욕필터링때문에

                $this->load->model('bbs_comment_model');
                $this->load->model('bbs_tag_model');
                $this->load->model('bbs_url_model');
                $this->load->model('bbs_file_model');
                $this->load->model('bbs_vote_model');
                $this->load->model('bbs_hit_model');

                //욕필터링
                $block_string = array();

                if(BBS_SETTING_bbs_block_string_used == 1)
                {
                    $block_string = unserialize(BBS_SETTING_bbs_block_string);
                }

                $assign['view']->print_name = name($assign['view']->user_id, $assign['view']->name, $assign['view']->nickname);
                if($assign['view']->avatar_used == 1
                    AND SETTING_avatar_used == 1
                    AND file_exists("./avatars/{$assign['view']->user_id}.gif") === TRUE)
                {
                    $assign['view']->avatar_used = TRUE;
                }
                else
                {
                    $assign['view']->avatar_used = FALSE;
                }

                //$assign['view']->contents = nl2br(auto_link(word_censor(html_purify($assign['view']->contents), $block_string), 'url', TRUE)); //  url, email, both 3가지가 있는데, jquerymbolie  출동나서..
                $assign['view']->contents = auto_link(word_censor(htmlspecialchars_decode($assign['view']->contents), $block_string), 'url', TRUE); //  url, email, both 3가지가 있는데, jquerymbolie  출동나서..
                if ($assign['view']->agent_insert != 'P') {
                    $assign['view']->contents = nl2br($assign['view']->contents);
                }

                $assign['view']->print_insert_date = time2date($assign['view']->timestamp_insert);
                $assign['view']->print_update_date = time2date($assign['view']->timestamp_update);

                //tags
                $assign['print_tags'] = NULL;
                if(BBS_SETTING_bbs_tags_used == 1)
                {
                    $assign['tags'] = $this->bbs_tag_model->get_tags($req_idx);
                    $tags_temp      = array();
                    foreach($assign['tags'] as $k => &$v)
                    {
                        $tags_temp[] = word_censor($v->tag, $block_string);
                    }
                    if(count($tags_temp) > 0)
                    {
                        $assign['print_tags'] = join(',', $tags_temp);
                    }
                }

                //urls
                if(BBS_SETTING_bbs_urls_used == 1)
                {
                    $assign['urls'] = $this->bbs_url_model->get_urls($req_idx);

                    foreach($assign['urls'] as $k => &$v)
                    {
                        $v->print_url = anchor_popup($v->url, word_censor($v->url, $block_string));
                    }
                }

                //files
                //다운로드 권한은 뷰에서 처리
                $assign['files'] = $this->bbs_file_model->get_files($req_idx);
                $assign['images'] = array();
                foreach($assign['files'] as $k => &$v)
                {
                    if($this->allowed_list['download'] === TRUE)
                    {
                        //$v->print = anchor_popup(BASE_URL . $this->upload_path . $v->conversion_filename, word_censor($v->original_filename, $block_string) . ' (' . byte_format($v->capacity) . ')');
                        $v->print = anchor_popup(BASE_URL . 'bbs/download/' . $this->bbs_id . '?idx=' . $v->idx , word_censor($v->original_filename, $block_string) . ' (' . byte_format($v->capacity) . ')');
                    }
                    else
                    {
                        $v->print = word_censor($v->original_filename, $block_string) . ' (' . byte_format($v->capacity) . ') - ' . lang('deny_allow');
                    }

                    $v->is_image = in_array($v->mime, array(
                        'image/gif',
                        'image/jpeg',
                        'image/pjpeg',
                        'image/png',
                        'image/x-png'
                    ));
                    if($v->is_image)
                    {
                        if(file_exists($this->upload_path . $v->conversion_filename))
                        {
                            $assign['images'][] = BASE_URL . $this->upload_path . $v->conversion_filename;

                            $thumb_filepath = explode('.', $v->conversion_filename);
                            $thumb_filepath = $thumb_filepath[0] . '_thumb.' . $thumb_filepath[1];

                            if(file_exists($this->upload_path . $thumb_filepath))
                            {
                                $assign['thumbs'][] = BASE_URL . $this->upload_path . $thumb_filepath;
                            }
                            else
                            {
                                $assign['thumbs'][] = BASE_URL . $this->upload_path . $v->conversion_filename;
                            }
                        }
                    }
                }

                //hit update
                //글쓸때 넣어도 되겠지만, 한번도 읽히지 않을수도 있고 쓰자마자 지우면 delete는 안할거니 불필요 row가 생길수있어서 여기서...
                //코멘트 페이지 넘어갈때도 히트수가 올라가서 간단히 코멘트 페이지 링크에 hit=not 을 붙인다.
                //국내정서에는 세션으로 시각체크해서 hit를 올리고 하긴하는데.. 그냥 하자.. 큰 의미없다.. 올릴라면 올려라
                //hit=not을 붙인채로 즐겨찾기된 상태에서 들어온거면 똥이되지만.. hit는 그냥 대충...
                if($this->input->get('hit') != 'not')
                {
                    if($this->bbs_hit_model->check($this->bbs_idx, $req_idx) == TRUE)
                    {
                        $this->bbs_hit_model->update($this->bbs_idx, $req_idx);
                    }
                    else
                    {
                        $this->bbs_hit_model->insert($this->bbs_idx, $req_idx);
                    }
                }

                //추천/스크랩 중복여부 확인
                //버튼을 안보이게 하려다가 그냥 보이고 중복체크하는게 나을듯...
                //뭐는 보이고 뭐는 안보이고 헤깔려할수도 있고
                //추천/스크랩을 많이 사용하지 않는다면 오히려 필요없는 select 가 될듯해서
                //추후 변경가능
                /*
                $assign['check_duplicate_vote'] = TRUE;

                if(defined('USER_INFO_idx'))
                {
                    $assign['check_duplicate_vote'] = $this->bbs_vote_model->check_duplicate('article', $req_idx);
                }

                $assign['check_duplicate_scrap'] = TRUE

                if(defined('USER_INFO_idx'))
                {
                    $assign['check_duplicate_scrap'] = $this->users_url_model->check_duplicate_scrap($req_idx);
                }
                */

                //브라우저타이틀 = 제목
                if(SETTING_browser_title_type == 1)
                {
                    $browser_title = word_censor(strip_tags(htmlspecialchars_decode($assign['view']->title)), $block_string) . ' - ' . SETTING_browser_title_fix_value; //적절히?

                    $this->assign['browser_title'] = str_replace("'", "\'", $browser_title);
                }

                //이전글,다음글
                //비밀글 스킵처리
                if(USER_INFO_group_idx !== SETTING_admin_group_idx)
                {
                    if(defined('USER_INFO_idx'))
                    {
                        $add_where_pre_next = ' AND (is_secret = 0 OR (is_secret = 1 AND user_idx = ' . USER_INFO_idx . ')) ';
                    }
                    else
                    {
                        $add_where_pre_next = ' AND is_secret = 0 ';
                    }
                }
                else
                {
                    $add_where_pre_next = '';
                }

                $assign['pre_next'] = $this->bbs_article_model->get_pre_next($this->bbs_idx, $req_idx, ' AND is_deleted = 0 ' . $add_where_pre_next . $view_category['query_where']);

                $assign['pre_next']->is_exists_pre  = (!empty($assign['pre_next']->idx_pre)) ? TRUE : FALSE;
                $assign['pre_next']->is_exists_next = (!empty($assign['pre_next']->idx_next)) ? TRUE : FALSE;
                $assign['pre_next']->title_pre      = cut_string(word_censor($assign['pre_next']->title_pre, $block_string), BBS_SETTING_bbs_cut_length_title);
                $assign['pre_next']->title_next     = cut_string(word_censor($assign['pre_next']->title_next, $block_string), BBS_SETTING_bbs_cut_length_title);

                //comment
                if($this->allowed_list['view_comment'] === TRUE && BBS_SETTING_bbs_comment_used == 1)
                {
                    //page
                    $assign['page_comment'] = ((int)$this->input->get('page_comment') > 0) ? (int)$this->input->get('page_comment') : 1;

                    $this->load->library('pagination');

                    $assign['total_cnt_comment'] = $this->bbs_comment_model->lists_total_cnt($req_idx, ' AND BBS_COMMENT.is_deleted = 0 ');

                    $this->config->load('pagination');
                    $pagination_config = $this->config->item($this->viewport);

                    unset($config);

                    // http://codeigniter-kr.org/user_guide_2.1.0/libraries/pagination.html
                    $config['base_url']             = BASE_URL . 'bbs/view/' . $this->bbs_id . '?idx=' . $req_idx . '&amp;page=' . $req_page . '&amp;hit=not' . $view_category['param_add'] . '&amp;lists_style=' . $this->input->get_post('lists_style');
                    $config['enable_query_strings'] = TRUE; // ?page=10 이런 일반 get 방식
                    $config['page_query_string']    = TRUE;
                    $config['use_page_numbers']     = TRUE;
                    $config['num_links']            = (int)BBS_SETTING_bbs_count_page_comment;
                    $config['query_string_segment'] = 'page_comment';
                    $config['total_rows']           = $assign['total_cnt_comment'];
                    $config['per_page']             = (int)BBS_SETTING_bbs_count_list_comment;

                    $config = array_merge($config, $pagination_config);

                    $this->pagination->initialize($config);

                    $assign['comment_pagination'] = $this->pagination->create_links();

                    //lists_comment
                    $assign['lists_comment'] = $this->bbs_comment_model->lists($req_idx, ($assign['page_comment'] - 1) * $config['per_page'], $config['per_page'], BBS_SETTING_bbs_comment_sort, ' AND BBS_COMMENT.is_deleted = 0 ');
                    $cnt                     = 0;
                    foreach($assign['lists_comment'] as $k => &$v)
                    {
                        $v->even_class = ($cnt % 2 == 0) ? 'comment_even_row' : '';

                        if((int)$v->timestamp_insert >= time() - ((int)BBS_SETTING_bbs_hour_new_icon_value_comment * 60 * 60))
                        {
                            $v->new_comment_icon = BBS_SETTING_bbs_hour_new_icon_path_comment;
                        }
                        else
                        {
                            $v->new_comment_icon = '';
                        }

                        $v->print_name        = name($v->user_id, $v->name, $v->nickname);
                        $v->print_insert_date = time2date($v->timestamp_insert);
                        $v->print_update_date = time2date($v->timestamp_update);

                        //$v->comment = nl2br(auto_link(word_censor(html_purify($v->comment), $block_string), 'url', TRUE)); //  url, email, both 3가지가 있는데, jquerymbolie  충돌나서..
                        //$v->comment = nl2br(auto_link(word_censor($v->comment, $block_string), 'url', TRUE)); //  url, email, both 3가지가 있는데, jquerymbolie  충돌나서..

                        $v->comment = auto_link(word_censor(htmlspecialchars_decode($v->comment), $block_string), 'url', TRUE); //  url, email, both 3가지가 있는데, jquerymbolie  충돌나서..
                        if ($v->agent_insert !== 'P') {
                            $v->comment = nl2br($v->comment);
                        }

                        $cnt++;
                    }
                }

                //이동할 페이지를 재계산해서 보내기
                $article_total_cnt = $this->bbs_article_model->lists_total_cnt($this->bbs_idx, ' AND BBS_ARTICLE.idx > ' . $req_idx . ' AND BBS_ARTICLE.is_deleted = 0 ' . $view_category['query_where_as']);

                $assign['page'] = floor($article_total_cnt / BBS_SETTING_bbs_count_list_article) + 1;
                $assign['view'] = $this->word_censor(array(
                    'title',
                    'contents'
                ), $assign['view'], $block_string);
                //$this->layout->view('bbs/view_view', $data);

                $this->assign['title'] = $assign['view']->title;
                $this->assign['contents'] = mb_substr(strip_tags($assign['view']->contents), 0, 150);
                $this->assign['article_uri'] = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

                $this->scope('contents', 'contents/bbs/view', $assign);
                $this->display('layout');
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * 접근권한 unserialize (int 형변환)
     *
     * @author KangMin
     * @since  2011.12.27
     *
     * @param string
     *
     * @return array
     */
    static private function get_allow($serialize)
    {
        $temp = unserialize($serialize);

        foreach($temp as $k => $v)
        {
            $temp[$k] = (int)$v;
        }

        return $temp;
    }

    // --------------------------------------------------------------------

    /**
     * 코멘트 작성 (ajax)
     *
     * @author KangMin
     * @since  2012.01.15
     */
    public function write_comment()
    {
        $data = NULL;
        $json = NULL;

        $data['bbs_id'] = $this->bbs_id;

        $req_article_idx = ((int)$this->input->post('article_idx') > 0) ? (int)$this->input->post('article_idx') : NULL;

        $this->load->model('bbs_article_model');

        //유효성
        $check_idx = $this->bbs_article_model->check_idx($req_article_idx, ' AND bbs_idx = ' . $this->bbs_idx . ' AND is_deleted = 0 ');

        if($check_idx !== TRUE)
        {
            $req_article_idx = NULL;
        }

        //권한
        $data['allow'] = $this->get_bbs_allow();

        //연속등록 차단
        //TRUE : 작성가능, FALSE : 작성불가 .. 좀 오해소지가 있지만..정한다
        $check_write_delay = $this->check_write_delay('comment');

        //로그인 상태가 아니거나 권한이 없으면
        if(!defined('USER_INFO_idx')
            OR in_array('write_comment', $data['allow']) !== TRUE
            OR $check_write_delay == FALSE
            OR $req_article_idx == NULL
            OR BBS_SETTING_bbs_comment_used != 1
        )
        {
            if($check_write_delay == FALSE)
            {
                $json['message'] = sprintf(lang('write_delay_' . BBS_SETTING_bbs_limit_insert_delay_type), BBS_SETTING_bbs_limit_insert_delay_value);
            }
            else if($req_article_idx == NULL)
            {
                $json['message'] = lang('none_article');
            }
            else
            {
                $json['message'] = lang('deny_allow');
            }

            $json['success'] = FALSE;
        }
        else
        {
            //rules
            $this->form_validation->set_rules('comment', lang('comment'), 'trim|required|htmlspecialchars|min_length[' . BBS_SETTING_bbs_length_minimum_comment . ']');

            //폼검증 성공이면
            if($this->form_validation->run() == TRUE)
            {
                $this->load->model('bbs_comment_model');
                $this->load->model('users_point_model');

                $this->db->trans_start();

                $comment = $this->form_validation->set_value('comment');

                if ($this->viewport === 'mobile') {
                    $comment = str_replace(array('&lt', '&gt'), array('&amp;lt', '&amp;gt'), $comment);
                }

                $result = $this->bbs_comment_model->insert_comment($this->bbs_idx, $req_article_idx, $comment);

                $data['comment_idx'] = $this->db->insert_id();

                //코멘트 갯수 업데이트
                //현재 전체 코멘트 갯수를 카운트 하고 싶지만 너무 무거워질거 같아서 그냥 +/-
                //이로인해 싱크로 틀어질 가능성이 있지만, 이는 싱크툴을 만들던가...
                $result_comment_count = $this->bbs_article_model->update_count_article($req_article_idx, 'comment_count', 1);

                //users (댓글작성수)
                $result_users = $this->users_model->update_count_users(USER_INFO_idx, 'comment_count', 1);

                //users (마지막 글작성시각)
                $result_users_timestamp_post = $this->users_model->update_last_post(USER_INFO_idx);

                //포인트
                //point
                if(BBS_SETTING_bbs_point_comment_used == 1)
                {
                    //users (포인트)
                    $result_users_point = $this->users_model->update_count_users(USER_INFO_idx, 'point', (int)BBS_SETTING_bbs_point_comment);

                    //BBS_SETTING_bbs_point_comment
                    $result_point = $this->users_point_model->insert_point('comment', $data['comment_idx'], (int)BBS_SETTING_bbs_point_comment);
                }
                else
                {
                    $result_users_point = TRUE;
                    $result_point       = TRUE;
                }

                //댓글작성하면 원문 작성자에게 메일을 보낸다.
                if (SETTING_by_write_comment_send_mail_used == 1) {
                    //SETTING_by_write_comment_send_mail_used = '코멘트 등록 메일링 사용여부 (0:미사용, 1:사용)';
                    //SETTING_by_write_comment_send_mail_from_user_idx = '코멘트 등록 메일링 발송자';
                    //SETTING_by_write_comment_send_mail_title = '코멘트 등록 메일링 제목';
                    //SETTING_by_write_comment_send_mail_contents = '코멘트 등록 메일링 내용';
                    //SETTING_by_write_comment_send_mail_contents_cut_length = '코멘트 등록 메일링 내용자를 글자수 (0:미사용)';

                    $sender_user_info = $this->users_model->get_user_info(SETTING_by_write_comment_send_mail_from_user_idx);
                    $target_article = $this->bbs_article_model->view($req_article_idx);

                    //본인의 글이 아닐때만
                    if ($target_article->user_idx != USER_INFO_idx) {
                        $target_user_info = $this->users_model->get_user_info($target_article->user_idx);

                        //$headers  = 'MIME-Version: 1.0' . "\r\n";
                        //$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                        $headers = 'To: ' . name($target_user_info->user_id, $target_user_info->name, $target_user_info->nickname) . ' <' . $target_user_info->email. '>' . "\r\n";
                        $headers .= 'From: ' . name($sender_user_info->user_id, $sender_user_info->name, $sender_user_info->nickname) . ' <' . $sender_user_info->email . '>' . "\r\n";

                        //메일전송
                        $this->load->helper('email');
                        send_email(
                            $target_user_info->email,
                            str_replace('{title}', $target_article->title, SETTING_by_write_comment_send_mail_title),
                            str_replace(
                                array(
                                    '{nickname}',
                                    '{user_id}',
                                    '{name}',
                                    '{date}',
                                    '{contents}',
                                    '{link}',
                                ),
                                array(
                                    USER_INFO_nickname,
                                    USER_INFO_user_id,
                                    USER_INFO_name,
                                    time2date(time()),
                                    nl2br(cut_string(strip_tags(htmlspecialchars_decode($comment)), SETTING_by_write_comment_send_mail_contents_cut_length)),
                                    BASE_URL . 'bbs/view/' . $this->bbs_id . '?idx=' . $req_article_idx,
                                ),
                                nl2br(SETTING_by_write_comment_send_mail_contents)),
                            $headers
                        );
                    }
                }

                $this->db->trans_complete();

                // 그런데.. myisam이면 트랜젝션이 의미가 없어서뤼...쩝
                if($result == TRUE
                    AND $result_comment_count == TRUE
                    AND $result_users == TRUE
                    AND $result_users_timestamp_post == TRUE
                    AND $result_users_point == TRUE
                    AND $result_point == TRUE
                )
                {
                    $json['message'] = lang('write_comment_success');
                    $json['success'] = TRUE;

                    //댓글 작성후 오른차순/내림차순 정렬에 따른 페이지로 보내기
                    if(BBS_SETTING_bbs_comment_sort == 'ASC')
                    {
                        $comment_total_cnt = $this->bbs_comment_model->lists_total_cnt($req_article_idx, ' AND BBS_COMMENT.idx < ' . $data['comment_idx'] . ' AND BBS_COMMENT.is_deleted = 0 ');
                    }
                    else
                    {
                        $comment_total_cnt = $this->bbs_comment_model->lists_total_cnt($req_article_idx, ' AND BBS_COMMENT.idx > ' . $data['comment_idx'] . ' AND BBS_COMMENT.is_deleted = 0 ');
                    }

                    $json['page_comment'] = floor($comment_total_cnt / BBS_SETTING_bbs_count_list_comment) + 1;
                }
                else
                {
                    $json['message'] = lang('write_comment_fail_msg');
                    $json['success'] = FALSE;
                }
            }
            else
            {
                $json['message'] = str_replace("\n", '', validation_errors());
                $json['success'] = FALSE;
            }
        }
        //로그인,권한 체크 end if

        echo json_encode($json);
    }

    // --------------------------------------------------------------------

    /**
     * 댓글 호출 (ajax)
     *
     * @author KangMin
     * @since  2012.01.21
     */
    public function get_comment()
    {
        $json = NULL;

        $req_idx = ((int)$this->input->post('idx') > 0) ? (int)$this->input->post('idx') : NULL;

        $this->load->model('bbs_comment_model');

        //유효성
        $check_idx = $this->bbs_comment_model->check_idx($req_idx, ' AND bbs_idx = ' . $this->bbs_idx . ' AND user_idx = ' . (defined('USER_INFO_idx') ? USER_INFO_idx : 0) . ' AND is_deleted = 0 ');

        if($check_idx !== TRUE)
        {
            $req_idx = NULL;
        }

        //권한
        $data['allow'] = $this->get_bbs_allow();

        //로그인 상태가 아니거나 권한이 없으면
        if(!defined('USER_INFO_idx')
            OR in_array('write_comment', $data['allow']) !== TRUE
            OR $req_idx == NULL
            OR BBS_SETTING_bbs_comment_used != 1
        )
        {
            if($req_idx == NULL)
            {
                $json['message'] = lang('none_comment');
            }
            else
            {
                $json['message'] = lang('deny_allow');
            }

            $json['success'] = FALSE;
        }
        else
        {
            $result = $this->bbs_comment_model->get_comment($req_idx, ' AND user_idx = ' . USER_INFO_idx . ' ');

            //본인글 겸 true 확인
            if($result == TRUE && (int)$result->user_idx === USER_INFO_idx)
            {
                $result->comment = htmlspecialchars_decode($result->comment);
                if($this->viewport == 'mobile'
                    && ($result->agent_insert == 'P' OR $result->agent_last_update == 'P'))
                {
                    $result->comment = strip_tags($result->comment);
                }
                $json['comment'] = $result->comment;
                $json['agent_insert'] = $result->agent_insert;
                $json['agent_last_update'] = $result->agent_last_update;
                $json['success'] = TRUE;
            }
            else
            {
                $json['message'] = lang('deny_allow');
                $json['success'] = FALSE;
            }
        }
        //로그인,권한 체크 end if

        echo json_encode($json);
    }

    // --------------------------------------------------------------------

    /**
     * 댓글 수정 (ajax)
     *
     * @author KangMin
     * @since  2012
     */
    public function modify_comment()
    {
        $data = NULL;
        $json = NULL;

        $data['bbs_id'] = $this->bbs_id;

        $req_idx = ((int)$this->input->post('idx') > 0) ? (int)$this->input->post('idx') : NULL;

        $this->load->model('bbs_comment_model');

        //유효성 & 본인글
        $check_idx = $this->bbs_comment_model->check_idx($req_idx, ' AND bbs_idx = ' . $this->bbs_idx . ' AND user_idx = ' . (defined('USER_INFO_idx') ? USER_INFO_idx : 0) . ' AND is_deleted = 0 ');

        if($check_idx !== TRUE)
        {
            $req_idx = NULL;
        }

        //권한
        $data['allow'] = $this->get_bbs_allow();

        //로그인 상태가 아니거나 권한이 없으면
        if(!defined('USER_INFO_idx')
            OR in_array('write_comment', $data['allow']) !== TRUE
            OR $req_idx == NULL
            OR BBS_SETTING_bbs_comment_used != 1
        )
        {
            if($req_idx == NULL)
            {
                $json['message'] = lang('none_comment');
            }
            else
            {
                $json['message'] = lang('deny_allow');
            }

            $json['success'] = FALSE;
        }
        else
        {
            //rules
            $this->form_validation->set_rules('comment', lang('comment'), 'trim|required|htmlspecialchars|min_length[' . BBS_SETTING_bbs_length_minimum_comment . ']');

            //폼검증 성공이면
            if($this->form_validation->run() == TRUE)
            {

                $comment = $this->form_validation->set_value('comment');

                if ($this->viewport === 'mobile') {
                    $comment = str_replace(array('&lt', '&gt'), array('&amp;lt', '&amp;gt'), $comment);
                }

                $result = $this->bbs_comment_model->update_comment($req_idx, $comment, ' AND user_idx = ' . USER_INFO_idx . ' ');

                // 그런데.. myisam이면 트랜젝션이 의미가 없어서뤼...쩝
                if($result == TRUE)
                {
                    $json['message'] = lang('modify_comment_success');
                    $json['success'] = TRUE;

                    //댓글 수정후 새로고침을 하는데 새글/삭제등으로 인해 페이지가 바뀔 수 있으므로
                    //오른차순/내림차순 정렬에 따른 페이지로 보내기

                    $comment_info = $this->bbs_comment_model->get_comment($req_idx, ' AND user_idx = ' . USER_INFO_idx . ' ');

                    if(BBS_SETTING_bbs_comment_sort == 'ASC')
                    {
                        $comment_total_cnt = $this->bbs_comment_model->lists_total_cnt($comment_info->article_idx, ' AND BBS_COMMENT.idx < ' . $req_idx . ' AND BBS_COMMENT.is_deleted = 0 ');
                    }
                    else
                    {
                        $comment_total_cnt = $this->bbs_comment_model->lists_total_cnt($comment_info->article_idx, ' AND BBS_COMMENT.idx > ' . $req_idx . ' AND BBS_COMMENT.is_deleted = 0 ');
                    }

                    $json['page_comment'] = floor($comment_total_cnt / BBS_SETTING_bbs_count_list_comment) + 1;
                }
                else
                {
                    $json['message'] = lang('modify_comment_fail_msg');
                    $json['success'] = FALSE;
                }
            }
            else
            {
                $json['message'] = str_replace("\n", '', validation_errors());
                $json['success'] = FALSE;
            }
        }
        //로그인,권한 체크 end if

        echo json_encode($json);
    }

    // --------------------------------------------------------------------

    /**
     * 댓글 삭제 (ajax)
     *
     * @author KangMin
     * @since  2012
     */
    public function delete_comment()
    {
        $data = NULL;
        $json = NULL;

        $data['bbs_id'] = $this->bbs_id;

        $req_idx         = ((int)$this->input->post('idx') > 0) ? (int)$this->input->post('idx') : NULL;
        $req_article_idx = ((int)$this->input->post('article_idx') > 0) ? (int)$this->input->post('article_idx') : NULL;

        $this->load->model('bbs_comment_model');

        //유효성 & 본인글
        $check_idx = $this->bbs_comment_model->check_idx($req_idx, ' AND bbs_idx = ' . $this->bbs_idx . ' AND user_idx = ' . (defined('USER_INFO_idx') ? USER_INFO_idx : 0) . ' AND is_deleted = 0 ');

        if($check_idx !== TRUE)
        {
            $req_idx = NULL;
        }

        //권한
        $data['allow'] = $this->get_bbs_allow();

        //로그인 상태가 아니거나 권한이 없으면
        if(!defined('USER_INFO_idx')
            OR in_array('write_comment', $data['allow']) !== TRUE
            OR $req_idx == NULL
            OR BBS_SETTING_bbs_comment_used != 1
        )
        {
            if($req_idx == NULL)
            {
                $json['message'] = lang('none_comment');
            }
            else
            {
                $json['message'] = lang('deny_allow');
            }

            $json['success'] = FALSE;
        }
        else
        {
            $this->load->model('bbs_article_model');
            $this->load->model('users_point_model');

            $this->db->trans_start();

            $result = $this->bbs_comment_model->delete_comment($req_idx, ' AND user_idx = ' . USER_INFO_idx . ' ');

            //코멘트 갯수 업데이트
            //현재 전체 코멘트 갯수를 카운트 하고 싶지만 너무 무거워질거 같아서 그냥 +/-
            //이로인해 싱크로 틀어질 가능성이 있지만, 이는 싱크툴을 만들던가...
            $result_comment_count = $this->bbs_article_model->update_count_article($req_article_idx, 'comment_count', -1);

            //users (댓글작성수)
            $result_users = $this->users_model->update_count_users(USER_INFO_idx, 'comment_count', -1);

            //포인트
            //point
            if(BBS_SETTING_bbs_point_comment_used == 1)
            {
                //해당 글 작성시 포인트를 찾아서 삭제
                //만약 글작성 포인트를 5줄때 쓴 글인데 10줄때 삭제한다고 10을 깍으면 안되징...
                //혹 포인트내역이 없거나 등하면 현재의 글작성 포인트로 삭제
                $point = $this->users_point_model->get_point('comment', $req_idx, (int)BBS_SETTING_bbs_point_comment);

                //users (포인트)
                $result_users_point = $this->users_model->update_count_users(USER_INFO_idx, 'point', $point * -1);

                //BBS_SETTING_bbs_point_comment
                $result_point = $this->users_point_model->insert_point('comment', $req_idx, $point * -1);
            }
            else
            {
                $result_users_point = TRUE;
                $result_point       = TRUE;
            }

            $this->db->trans_complete();

            // 그런데.. myisam이면 트랜젝션이 의미가 없어서뤼...쩝
            if($result == TRUE
                AND $result_comment_count == TRUE
                AND $result_users == TRUE
                AND $result_users_point == TRUE
                AND $result_point == TRUE
            )
            {
                $json['message'] = lang('delete_comment_success');
                $json['success'] = TRUE;
            }
            else
            {
                $json['message'] = lang('delete_comment_fail_msg');
                $json['success'] = FALSE;
            }
        }
        //로그인,권한 체크 end if

        echo json_encode($json);
    }

    // --------------------------------------------------------------------

    /**
     * 추천 (ajax) - article, comment 공용
     *
     * @author KangMin
     * @since  2012
     */
    public function vote()
    {
        $data = NULL;
        $json = NULL;

        $data['bbs_id'] = $this->bbs_id;

        $req_idx  = ((int)$this->input->post('idx') > 0) ? (int)$this->input->post('idx') : NULL;
        $req_type = ($this->input->post('type') == 'comment') ? 'comment' : 'article'; //추천 공용사용으로 위해

        $this->load->model('bbs_' . $req_type . '_model');

        //유효성 & 본인글아닐때만 추천가능
        if(defined('USER_INFO_idx'))
        {
            $check_idx = $this->{'bbs_' . $req_type . '_model'}->check_idx($req_idx, ' AND bbs_idx = ' . $this->bbs_idx . ' AND user_idx <> ' . USER_INFO_idx . ' AND is_deleted = 0 ');
            if($check_idx !== TRUE)
            {
                $req_idx = NULL;
            }
        }
        else
        {
            $req_idx = NULL; //로그아웃상태에서 들어오면 위 유효성 검사에서 오류발생하여.. 어차피 이런 접근은 비정상이다.
        }

        //권한
        $data['allow'] = $this->get_bbs_allow();

        //로그인 상태가 아니거나 권한이 없으면
        if(!defined('USER_INFO_idx')
            OR in_array('view_' . $req_type, $data['allow']) !== TRUE //글 볼수있어야 추천할수있는거니까...
            OR $req_idx == NULL
            OR ($req_type == 'article' && BBS_SETTING_bbs_vote_article_used != 1)
            OR ($req_type == 'comment' && BBS_SETTING_bbs_vote_comment_used != 1)
        )
        {
            if($req_idx == NULL)
            {
                $json['message'] = lang('none_' . $req_type);
            }
            else
            {
                $json['message'] = lang('deny_allow');
            }

            $json['success'] = FALSE;
        }
        else
        {
            $this->load->model('bbs_vote_model');

            //추천 중복여부 확인
            $check_duplicate = $this->bbs_vote_model->check_duplicate($req_type, $req_idx);

            if($check_duplicate == TRUE) //중복이면 TRUE
            {
                $json['message'] = lang('vote_duplicate');
                $json['success'] = FALSE;
            }
            else
            {
                $this->load->model('users_point_model');

                $this->db->trans_start();

                //bbs_vote insert
                $result = $this->bbs_vote_model->insert($req_type, $this->bbs_idx, $req_idx);

                $data['vote_idx'] = $this->db->insert_id();

                //bbs_article or bbs_comment > vote_count update
                $result_vote_count = $this->{'bbs_' . $req_type . '_model'}->{'update_count_' . $req_type}($req_idx, 'vote_count', 1);

                //users > vote_send_count update
                $result_vote_send_count = $this->users_model->update_count_users(USER_INFO_idx, 'vote_send_count', 1);

                //추천한 사람에게 포인트
                if(BBS_SETTING_bbs_point_vote_sender_used == 1)
                {
                    //users (포인트)
                    $result_users_point_sender = $this->users_model->update_count_users(USER_INFO_idx, 'point', (int)BBS_SETTING_bbs_point_vote_sender);

                    $result_point_sender = $this->users_point_model->insert_point('vote', $data['vote_idx'], (int)BBS_SETTING_bbs_point_vote_sender);
                }
                else
                {
                    $result_users_point_sender = TRUE;
                    $result_point_sender       = TRUE;
                }

                //users > vote_receive_count update
                //추천받는사람의 인덱스
                $user_idx_vote_receiver = $this->{'bbs_' . $req_type . '_model'}->get_user_idx($req_idx, ' AND is_deleted = 0 ');

                $result_vote_receive_count = $this->users_model->update_count_users($user_idx_vote_receiver, 'vote_receive_count', 1);

                //추천받은사람에게 포인트
                if(BBS_SETTING_bbs_point_vote_receiver_used == 1)
                {
                    //users (포인트)
                    $result_users_point_receiver = $this->users_model->update_count_users($user_idx_vote_receiver, 'point', (int)BBS_SETTING_bbs_point_vote_receiver);

                    $result_point_receiver = $this->users_point_model->insert_point('vote', $data['vote_idx'], (int)BBS_SETTING_bbs_point_vote_receiver, $user_idx_vote_receiver);
                }
                else
                {
                    $result_users_point_receiver = TRUE;
                    $result_point_receiver       = TRUE;
                }

                $this->db->trans_complete();

                if($result == TRUE
                    AND $result_vote_count == TRUE
                    AND $result_vote_send_count == TRUE
                    AND $result_users_point_sender == TRUE
                    AND $result_point_sender == TRUE
                    AND $result_vote_receive_count == TRUE
                    AND $result_users_point_receiver == TRUE
                    AND $result_point_receiver == TRUE
                )
                {
                    $json['message'] = lang('vote_success');
                    $json['success'] = TRUE;
                }
                else
                {
                    $json['message'] = lang('vote_fail_msg');
                    $json['success'] = FALSE;
                }
            }
        }
        //로그인,권한 체크 end if

        echo json_encode($json);
    }

    // --------------------------------------------------------------------

    /**
     * 스크랩
     *
     * @author KangMin
     * @since  2012
     */
    public function scrap()
    {
        $data = NULL;
        $json = NULL;

        $data['bbs_id'] = $this->bbs_id;

        $req_idx = ((int)$this->input->post('idx') > 0) ? (int)$this->input->post('idx') : NULL;

        $this->load->model('bbs_article_model');

        //유효성
        $check_idx = $this->bbs_article_model->check_idx($req_idx, ' AND bbs_idx = ' . $this->bbs_idx . ' AND is_deleted = 0 ');

        if($check_idx !== TRUE)
        {
            $req_idx = NULL;
        }

        //권한
        $data['allow'] = $this->get_bbs_allow();

        //로그인 상태가 아니거나 권한이 없으면
        if(!defined('USER_INFO_idx')
            OR in_array('view_article', $data['allow']) !== TRUE //글 볼수있어야 스크랩할수있는거니까...
            OR $req_idx == NULL
        )
        {
            if($req_idx == NULL)
            {
                $json['message'] = lang('none_article');
            }
            else
            {
                $json['message'] = lang('deny_allow');
            }

            $json['success'] = FALSE;
        }
        else
        {
            $this->load->model('users_url_model');

            //중복차단
            $check_duplicate_scrap = $this->users_url_model->check_duplicate_scrap($req_idx);

            if($check_duplicate_scrap == TRUE) //중복이면 TRUE
            {
                $json['message'] = lang('scrap_duplicate');
                $json['success'] = FALSE;
            }
            else
            {
                //해당 게시물의 제목
                $title = $this->bbs_article_model->get_title($req_idx);

                //values
                $values                = array();
                $values['article_idx'] = $req_idx;
                $values['title']       = $title;
                $values['type']        = 0; //0:scrap, 1:favorite
                $values['url']         = NULL; //favorite

                $this->db->trans_start();

                $result = $this->users_url_model->insert($values);

                //스크랩카운트
                $result_scrap_count = $this->bbs_article_model->update_count_article($req_idx, 'scrap_count', 1);

                $this->db->trans_complete();

                if($result == TRUE && $result_scrap_count == TRUE)
                {
                    $json['message'] = lang('scrap_success');
                    $json['success'] = TRUE;
                }
                else
                {
                    $json['message'] = lang('scrap_fail_msg');
                    $json['success'] = FALSE;
                }
            }

        }
        //로그인,권한 체크 end if

        echo json_encode($json);
    }

    // --------------------------------------------------------------------

    /**
     * RSS
     *
     * @author KangMin
     * @since  2012.01.24
     */
    public function rss()
    {
        $data = NULL;

        $data['bbs_id'] = $this->bbs_id;

        //권한
        $data['allow'] = $this->get_bbs_allow();

        $this->load->helper('text'); //욕필터링때문에

        //욕필터링
        $data['block_string'] = array();

        if(BBS_SETTING_bbs_block_string_used == 1)
        {
            $data['block_string'] = unserialize(BBS_SETTING_bbs_block_string);
        }

        //리스트,뷰 둘다 비회원권한 있는 게시판만
        if($this->get_rss_allow() == TRUE && BBS_SETTING_bbs_rss_used == 1)
        {
            $this->load->model('bbs_article_model');

            $data['rss'] = $this->bbs_article_model->lists($this->bbs_idx, 0, (int)BBS_SETTING_bbs_count_list_article, ' AND BBS_ARTICLE.is_deleted = 0 ');

            //$this->load->view('bbs/rss_view', $data);

            header("Content-Type: text/xml; charset=UTF-8;");

            echo '<?xml version="1.0" encoding="utf-8"?>'."\n";

            echo '<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:slash="http://purl.org/rss/1.0/modules/slash/">';
            echo '<channel>';
            echo '<title>' . SETTING_browser_title_fix_value . ' - ' . BBS_SETTING_bbs_name . '</title>';
            echo '<link>' . BASE_URL . 'bbs/lists/' . $data['bbs_id'] . '</link>';
            echo '<language>ko</language>';
            echo '<generator>TapBBS</generator>';
            echo '<copyright>' . BASE_URL .'</copyright>';

            foreach($data['rss'] as $k=>$v)
            {
                ?>
                <item>
                    <title><?php echo word_censor(strip_tags(htmlspecialchars_decode($v->title)), $data['block_string']); ?></title>
                    <link><?php echo base_url(); ?>bbs/view/<?php echo $data['bbs_id']; ?>?idx=<?php echo $v->idx; ?></link>
                    <description><?php echo htmlspecialchars(nl2br(word_censor(strip_tags(htmlspecialchars_decode($v->contents)), $data['block_string']))); ?></description>
                    <pubDate><?php echo date('D, d M Y H:i:s O', $v->timestamp_insert); ?></pubDate>
                    <dc:creator><?php echo name($v->user_id, $v->name, $v->nickname); ?></dc:creator>
                </item>
            <?php
            }

            echo '</channel>';
            echo '</rss>';
        }
    }

    // --------------------------------------------------------------------

    /**
     * rss 권한체크
     * 여기저기서 쓰게되서리 분리
     *
     * @author KangMin
     * @since  2012.01.27
     *
     * @return bool
     */
    private function get_rss_allow()
    {
        //일단 rss 사용여부
        if((int)BBS_SETTING_bbs_rss_used == 0) return FALSE;

        //뷰,리스트에 둘다 비회원권한이 있는지..
        $rss_allow = 0;

        //글내용 보기 권한 그룹
        $allow = $this->get_allow(BBS_SETTING_bbs_allow_group_view_article);

        if(in_array(0, $allow) == TRUE)
        {
            $rss_allow++;
        }

        //리스트보기 권한 그룹
        $allow = $this->get_allow(BBS_SETTING_bbs_allow_group_view_list);

        if(in_array(0, $allow) == TRUE)
        {
            $rss_allow++;
        }

        if($rss_allow == 2)
        {
            return TRUE;
        }

        return FALSE;
    }

    private function set_rss_allow()
    {
        $this->assign['rss_allow'] = $this->get_rss_allow();
    }

    // --------------------------------------------------------------------

    /**
     * 파일업로드
     * http://valums.com/ajax-upload/
     */
    public function upload_file()
    {
        //개인별 최대용량
        $allow_user_capacity = TRUE;

        //근데, 만약 29메가를 올렸는데 개인 제한이 30메가라면 1메가 초과파일도 한번을 올릴 수 있다.
        //로그인상태일때만 체크하지만, 로그아웃상태에서 접근이라면 아래서 어차피 걸림.
        if(SETTING_bbs_upload_limit_user_capacity_used == 1 && defined('USER_INFO_idx'))
        {
            $this->load->model('bbs_file_model');
            $capacity = $this->bbs_file_model->get_total_capacity();

            if(SETTING_bbs_upload_limit_user_capacity < $capacity)
            {
                $allow_user_capacity = FALSE;
            }
        }

        $type = $this->uri->segment(4);

        //로그인 상태가 아니거나 권한이 없으면
        if(!defined('USER_INFO_idx')
            OR ($type == 'comment' && $this->allowed_list['write_comment'] !== TRUE)
            OR ($type !== 'comment' && $this->allowed_list['upload'] !== TRUE)
            OR BBS_SETTING_bbs_upload_used != 1)
        {
            $result = array('error' => lang('deny_allow'));
            echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
        }
        else if($allow_user_capacity == FALSE) //개인 첨부 총용량 설정 여부 확인
        {
            $result = array('error' => sprintf(lang('upload_file_allow_user_capacity'), byte_format(SETTING_bbs_upload_limit_user_capacity)));
            echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
        }
        else
        {
            // list of valid extensions, ex. array("jpeg", "xml", "bmp")
            $allowedExtensions = unserialize(BBS_SETTING_bbs_upload_allow_extension);
            // max file size in bytes
            $sizeLimit = BBS_SETTING_bbs_upload_limit_capacity;

            $uploader = new qqFileUploader($allowedExtensions, $sizeLimit);

            //게시판idx/Ymd 폴더 생성
            if ($type == 'comment') {
                $uploadDirectory = $this->upload_path . $this->bbs_idx . '/' . date('Ymd') . '/comment/';
            } else {
                $uploadDirectory = $this->upload_path . $this->bbs_idx . '/' . date('Ymd') . '/';
            }

            if(file_exists($uploadDirectory) == FALSE)
            {
                @mkdir($uploadDirectory, 0777, TRUE);
            }
            $result = $uploader->handleUpload($uploadDirectory);

            //업로드 정로를 DB에 넣는다. (추후 쓰레기 파일 지우기 위한 비교용이다) (코멘트 위지윅의 그것은 일단 제외한다.)
            if($type !== 'comment' && $result['success'] == TRUE)
            {
                $this->load->model('bbs_file_temporary_model');
                $this->bbs_file_temporary_model->insert(USER_INFO_idx, $result['real_file_name']);
            }

            // to pass data through iframe you will need to encode all html tags
            echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
        }
    }

    // --------------------------------------------------------------------

    /**
     * 최근게시물 API성 함수
     *
     * http://주소/bbs/recently/게시판ID 하면 json으로 리턴한다.
     */
    public function recently()
    {
        $this->load->library('bbs_common');

        //인덱스의 것은 사용자가 원하는거만 고르는거니 상관없지만. 이건 아무나 호출할 수 있으므로
        //리스트 볼 권한이 있는거만 해야한다.

        if($this->allowed_list['view_lists'] == TRUE)
        {
            $recently = $this->bbs_common->recently(array($this->bbs_id));

            echo $recently[$this->bbs_id];
        }
        else
        {
            echo json_encode((object)array('lists'=>array()));
        }
    }

    // --------------------------------------------------------------------

    /**
     * 다운로드
     * @param seq @seq
     */
    public function download()
    {
        //check allow
        if($this->allowed_list['download'] != TRUE) show_error(lang('deny_allow'));

        $idx = $this->input->get('idx');
        //validation
        if( ! $idx) show_error(lang('unusual_approach'));

        $this->load->model('bbs_file_model');
        $file = $this->bbs_file_model->get_file_by_download($idx);
        //check
        if( ! $file) show_error(lang('unusual_approach'));

        if( ! file_exists($this->upload_path . $file[0]->conversion_filename)) show_error(lang('unusual_approach'));

        $data = file_get_contents($this->upload_path . $file[0]->conversion_filename); // Read the file's contents
        $name = $file[0]->original_filename;

        $this->load->helper('download');
        force_download($name, $data);
    }
} // end Bbs class

// --------------------------------------------------------------------
// --------------------------------------------------------------------
// --------------------------------------------------------------------

// 아래는 파일업로드를 위한 클래스
// 코드 컨벤션과 일부만 수정했음.

// http://valums.com/ajax-upload/
// http://valums.com/ajax-upload/
// http://valums.com/ajax-upload/

/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr
{
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path)
    {
        $input    = fopen('php://input', 'r');
        $temp     = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);

        if($realSize != $this->getSize())
        {
            return FALSE;
        }

        $target = fopen($path, 'w');
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);

        return TRUE;
    }

    function getName()
    {
        return $_GET['qqfile'];
    }

    function getSize()
    {
        if(isset($_SERVER['CONTENT_LENGTH']))
        {
            return (int)$_SERVER['CONTENT_LENGTH'];
        }
        else
        {
            throw new Exception(lang('upload_file_error_CONTENT_LENGTH'));
        }
    }
} // 파일업로드 qqUploadedFileXhr 클래스 끝

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm
{
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path)
    {
        if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path))
        {
            return FALSE;
        }

        return TRUE;
    }

    function getName()
    {
        return $_FILES['qqfile']['name'];
    }

    function getSize()
    {
        return $_FILES['qqfile']['size'];
    }
} // 파일업로드 qqUploadedFileForm 클래스 끝

class qqFileUploader
{
    private $allowedExtensions = array();
    private $sizeLimit = 10485760;
    private $file;

    function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760)
    {
        $allowedExtensions = array_map('strtolower', $allowedExtensions);

        $this->allowedExtensions = $allowedExtensions;
        $this->sizeLimit         = $sizeLimit;

        $this->checkServerSettings();

        if(isset($_GET['qqfile']))
        {
            $this->file = new qqUploadedFileXhr();
        }
        else if(isset($_FILES['qqfile']))
        {
            $this->file = new qqUploadedFileForm();
        }
        else
        {
            $this->file = FALSE;
        }
    }

    private function checkServerSettings()
    {
        $postSize   = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));

        if($postSize < $this->sizeLimit OR $uploadSize < $this->sizeLimit)
        {
            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';
            die("{'error':'" . lang('upload_file_error_increase_size') . "'}");
        }
    }

    private function toBytes($str)
    {
        $val  = trim($str);
        $last = strtolower($str[strlen($str) - 1]);

        switch($last)
        {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }

    /**
     * Returns array('success'=>true) or array('error'=>'error message')
     */
    function handleUpload($uploadDirectory, $replaceOldFile = FALSE)
    {
        if(!is_writable($uploadDirectory))
        {
            return array('error' => lang('upload_file_error_cant_writable'));
        }

        if(!$this->file)
        {
            return array('error' => lang('upload_file_error_no_file'));
        }

        $size = $this->file->getSize();

        if($size == 0)
        {
            return array('error' => lang('upload_file_error_no_file'));
        }

        if($size > $this->sizeLimit)
        {
            return array('error' => lang('upload_file_error_over_limit'));
        }

        $pathinfo = pathinfo($this->file->getName());
        $filename = $pathinfo['filename'];
        $filename = md5(uniqid()); //암호화된 강제 파일명으로 한다. 활성화
        $ext      = $pathinfo['extension'];

        if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions))
        {
            $these = implode(',', $this->allowedExtensions);

            return array('error' => lang('upload_file_error_extension') . ' ' . $these);
        }

        if(!$replaceOldFile)
        {
            /// don't overwrite previous files that were uploaded
            while(file_exists($uploadDirectory . $filename . '.' . $ext))
            {
                $filename .= rand(10, 99);
            }
        }

        if($this->file->save($uploadDirectory . $filename . '.' . $ext))
        {
            $temp = explode('/', $uploadDirectory);
            unset($temp[0]);
            $uploadDirectory = join('/', $temp);

            return array(
                'success'        => TRUE,
                'base_url'       => BASE_URL,
                'real_file_name' => $uploadDirectory . $filename . '.' . $ext
            );
        }
        else
        {
            return array('error' => lang('upload_file_error_fail'));
        }

    }
} // 파일업로드 qqFileUploader 클래스 끝

//EOF