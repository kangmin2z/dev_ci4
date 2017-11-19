<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'third_party/phpass-0.3/PasswordHash.php';

class User extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    // --------------------------------------------------------------------

    public function index()
    {
        redirect('/', 'refresh');
    }

    // --------------------------------------------------------------------

    /**
     * 회원가입
     * @author KangMin
     * @since  2011.11.09
     */
    public function join()
    {
        $this->add_language_pack($this->language_pack('user_join'));

        $assign               = NULL;
        $assign['result_msg'] = '';
        $post_success         = FALSE; //DB insert/update 성공여부

        //로그인 상태면
        if(defined('USER_INFO_idx'))
        {
            redirect('/', 'refresh');
        }
        else if(SETTING_join_used == 0)
        {
            $assign['message'] = lang('join_block');

            $assign['redirect'] = '/';

            $this->alert($assign);
        }
        else
        {
            //rules
            $this->form_validation->set_rules('user_id', lang('user_id'), 'trim|required|xss_clean|alpha_dash|min_length[' . SETTING_user_id_length_minimum . ']|max_length[' . SETTING_user_id_length_maximum . ']');
            $this->form_validation->set_rules('password', lang('password'), 'trim|required|xss_clean|min_length[' . SETTING_user_password_length_minimum . ']|max_length[' . SETTING_user_password_length_maximum . ']');
            $this->form_validation->set_rules('password_confirm', lang('password_confirm'), 'trim|required|xss_clean|matches[password]');
            $this->form_validation->set_rules('name', lang('name'), 'trim|required|xss_clean|min_length[' . SETTING_user_name_length_minimum . ']|max_length[' . SETTING_user_name_length_maximum . ']');
            $this->form_validation->set_rules('nickname', lang('nickname'), 'trim|required|xss_clean|min_length[' . SETTING_user_nickname_length_minimum . ']|max_length[' . SETTING_user_nickname_length_maximum . ']');
            $this->form_validation->set_rules('email', lang('email'), 'trim|required|htmlspecialchars|xss_clean|valid_email|max_length[128]');

            //폼검증 성공이면
            if($this->form_validation->run() == TRUE)
            {
                //request
                $req_user_id          = $this->form_validation->set_value('user_id');
                $req_password         = $this->form_validation->set_value('password');
                $req_password_confirm = $this->form_validation->set_value('password_confirm');
                $req_name             = htmlspecialchars($this->form_validation->set_value('name')); //룰에서 htmlspecialchars하면 max_length가 늘어난 스트링으로 계산해서
                $req_nickname         = htmlspecialchars($this->form_validation->set_value('nickname'));
                $req_email            = $this->form_validation->set_value('email');

                $assign['result_msg'] = NULL;
                $join_fail_msg        = array();

                //user_id 중복 확인
                $check_user_id = $this->users_model->check('user_id', $req_user_id);

                if($check_user_id == TRUE) //회원아이디가 있으면(TRUE) 가입못함
                {
                    $join_fail_msg[] = lang('user_id_duplicate');
                }

                //닉네임 중복 확인
                $check_nickname = $this->users_model->check('nickname', $req_nickname);

                if($check_nickname == TRUE) //닉네임 있으면(TRUE) 가입못함
                {
                    $join_fail_msg[] = lang('nickname_duplicate');
                }

                //이메일 중복 확인
                $check_email = $this->users_model->check('email', $req_email);

                if($check_email == TRUE) //이메일 있으면(TRUE) 가입못함
                {
                    $join_fail_msg[] = lang('email_duplicate');
                }

                if(count($join_fail_msg) > 0)
                {
                    //user_id, 닉네임, 이메일 중 1개 이상 오류
                    $assign['result_msg'] = join('<br />', $join_fail_msg);
                }
                else
                {
                    //captcha
                    // First, delete old captchas
                    $expiration = time() - SETTING_captcha_timeout;
                    $this->db->query("DELETE FROM captcha WHERE captcha_time < " . $expiration);

                    // Then see if a captcha exists:
                    $sql   = "SELECT COUNT(*) AS count FROM captcha WHERE word = ? AND ip_address = ? AND captcha_time > ?";
                    $binds = array(
                        $_POST['captcha'],
                        $this->input->ip_address(),
                        $expiration
                    );
                    $query = $this->db->query($sql, $binds);
                    $row   = $query->row();

                    if($row->count == 0)
                    {
                        //captcha 오류
                        $assign['result_msg'] = lang('captcha_fail');
                    }
                    else
                    {
                        $hash = new PasswordHash(8, FALSE);
                        $super_secured_password = $hash->HashPassword($req_password);

                        //정상가입
                        $values = array();

                        $values['user_id']  = $req_user_id;
                        $values['password'] = $super_secured_password;
                        $values['name']     = $req_name;
                        $values['nickname'] = $req_nickname;
                        $values['email']    = $req_email;

                        $result = $this->users_model->join($values);

                        if($result == TRUE)
                        {
                            if (SETTING_by_join_send_message_used == 1) {
                                //SETTING_by_join_send_message_used = '회원가입 축하쪽지 사용여부 (0:미사용, 1:샤용)';
                                //SETTING_by_join_send_message_from_user_idx = '회원가입 축하쪽지 발송자';
                                //SETTING_by_join_send_message_contents = '회원가입 축하쪽지 내용';

                                //회원가입 축하쪽지 전송
                                $this->load->model('users_message_model');

                                @$this->users_message_model->send_message($this->db->insert_id(), SETTING_by_join_send_message_contents, NULL, SETTING_by_join_send_message_from_user_idx);
                            }

                            $post_success       = TRUE;
                            $assign['message']  = lang('join_success');
                            $assign['redirect'] = '/user/login';

                            $this->alert($assign);
                        }
                        else
                        {
                            $assign['result_msg'] = lang('join_fail_msg');
                        }
                    }
                }
            }

            if($post_success == FALSE)
            {
                //captcha
                $this->load->helper('captcha');

                $vals = array(
                    'img_path'   => './captcha/',
                    'img_url'    => BASE_URL . 'captcha/',
                    'font_path'  => './captcha/fonts/3.ttf',
                    'img_width'  => 150,
                    'img_height' => 30,
                    'expiration' => SETTING_captcha_timeout,
                    'pool'       => '0123456789'
                );

                $cap = create_captcha($vals);

                $assign_captcha = array(
                    'captcha_time' => $cap['time'],
                    'ip_address'   => $this->input->ip_address(),
                    'word'         => $cap['word']
                );

                $query = $this->db->insert_string('captcha', $assign_captcha);
                $this->db->query($query);

                $assign['captcha'] = $cap['image'];

                $assign['form_null_check']    = "user_id^{$this->assign['lang']['user_id']}|password^{$this->assign['lang']['password']}|password_confirm^{$this->assign['lang']['password_confirm']}|name^{$this->assign['lang']['name']}|nickname^{$this->assign['lang']['nickname']}|email^{$this->assign['lang']['email']}|captcha^CAPTCHA";
                $assign['validation_result']  = validation_errors();
                $assign['validation_message'] = ($assign['validation_result'] !== '') ? str_replace("\n", '', $assign['validation_result']) : '';

                $value_key_list = array(
                    'user_id',
                    'name',
                    'nickname',
                    'email'
                );
                foreach($value_key_list as $v)
                {
                    $assign['value_list'][$v] = set_value($v);
                }

                $this->scope('contents', 'contents/user/join', $assign);
                $this->display('layout');
                //$this->layout->view('user/join_view', $data);
            }
        }
        //로그인,권한 체크 end if
    }

    // --------------------------------------------------------------------

    /**
     * 로그인
     * @author KangMin
     * @since  2011.11.09
     */
    public function login()
    {
        $this->add_language_pack($this->language_pack('user_login'));

        $assign               = array();
        $assign['result_msg'] = '';

        //로그인 상태면
        if(IS_USER_LOGIN === TRUE)
        {
            redirect('/', 'refresh');
        }
        else
        {
            //rules
            //로그인할때는 길이 설정 체크를 하면 안된다.
            //기가입자가 있는 상태에서 설정 바꿔도 로그인은 해야지...
            $this->form_validation->set_rules('user_id', lang('user_id'), 'trim|required|xss_clean|alpha_dash');
            $this->form_validation->set_rules('password', lang('password'), 'trim|required|xss_clean');

            //폼검증 성공이면
            if($this->form_validation->run() == TRUE)
            {
                $hash = new PasswordHash(8, FALSE);

                //request
                $req_user_id  = $this->form_validation->set_value('user_id');
                $req_password = $this->form_validation->set_value('password');

                //$result = $this->users_model->login($req_user_id, md5($req_password));

                $result = $this->users_model->get_user_info_for_login($req_user_id);

                //비밀번호 체크
                $check_password = FALSE;

                //일단 구 md5 비번이 있는지
                if($result && $result->password)
                {
                    if(md5($req_password) === $result->password)
                    {
                        $check_password = TRUE;

                        //비번을 보안강화로 변경하고 구 비번은 삭제한다.
                        $super_secured_password = $hash->HashPassword($req_password);

                        $result_set_super_secured_password = $this->users_model->set_super_secured_password($req_user_id, $super_secured_password);
                    }
                }
                else
                {
                    //$super_secured_password = $hash->HashPassword($req_password);

                    if($result && $hash->CheckPassword($req_password, $result->super_secured_password)) //성공
                    {
                        $check_password = TRUE;
                    }
                }

                //임시 비빌먼호 체크
                //보안강화 비번을 적용하는 시점에 임시비밀번호를 발급받은 회원이 많다면 그 비번으로는 로그인되지 않지만, 다시 비번찾기 하면 되므로..
                //좀 그렇지만, 거의 문제 없을듯
                if($result && $result->new_password_timestamp - time() <= SETTING_new_password_timeout)
                {
                    if($hash->CheckPassword($req_password, $result->new_password)) //성공
                    {
                        $check_password = TRUE;
                    }
                }

                if($check_password == TRUE && $result !== FALSE && $result->status == 1)
                {
                    //로그인 화면에서 로그인 유지를 선택하면, select 에서 off 가 선택되어 넘어온다.
                    // pc 화면에서 checkbox로  할때도 off 라는 문자열을 받아야 작동함.
                    $keep_login=$this->input->post('keep_login');
                    if($keep_login=='off'){

                        $this->session->sess_expire_on_close = TRUE;
                    }
                    $this->session->set_userdata(array('user_cookie' => $result->user_cookie));

                    $this->users_model->set_user_info_after_login($result->idx); //마지막 로그인시각/아이피 업데이트

                    $referer = @unserialize(stripslashes(base64_decode(strtr($this->input->post('referer'), '-_.', '+/='))));

                    redirect($referer);
                }
                else
                {
                    if($result !== FALSE && $result->status !== 1)
                    {
                        if($result->status == 0) //탈퇴회원
                        {
                            $assign['result_msg'] = lang('login_fail_msg_delete');
                        }
                        else if($result->status == 2) //차단회원
                        {
                            $assign['result_msg'] = lang('login_fail_msg_block');
                        }
                        else
                        {
                            $assign['result_msg'] = lang('login_fail_msg');
                        }
                    }
                    else
                    {
                        $assign['result_msg'] = lang('login_fail_msg');
                    }
                }
            }

            $assign['form_null_check']    = "user_id^{$this->assign['lang']['user_id']}|password^{$this->assign['lang']['password']}";
            $assign['validation_result']  = validation_errors();
            $assign['validation_message'] = ($assign['validation_result'] !== '') ? str_replace("\n", '', $assign['validation_result']) : '';

            $value_key_list = array(
                'user_id'
            );
            foreach($value_key_list as $v)
            {
                $assign['value_list'][$v] = set_value($v);
            }

            $this->scope('contents', 'contents/user/login', $assign);
            $this->display('layout');
        }
        //로그인,권한 체크 end if
    }

    // --------------------------------------------------------------------

    /**
     * 로그아웃
     * @author KangMin
     * @since  2011.11.09
     */
    public function logout()
    {
        //session destroy
        $this->session->set_userdata(array('user_cookie' => ''));
        $this->session->sess_destroy();

        //define('IS_USER_LOGIN', FALSE);

        redirect('/', 'refresh');
    }

    // --------------------------------------------------------------------

    /**
     * 비밀번호 찾기
     * @author KangMin
     * @since  2011.11.09
     */
    public function find_password()
    {
        $this->add_language_pack($this->language_pack('user_find_password'));

        $assign       = NULL;
        $post_success = FALSE; //DB insert/update 성공여부

        $assign['result_msg'] = '';

        //로그인 상태면
        if(IS_USER_LOGIN === TRUE)
        {
            redirect('/', 'refresh');
        }
        else
        {
            //rules
            $this->form_validation->set_rules('user_id', lang('user_id'), 'trim|required|xss_clean|alpha_dash');
            $this->form_validation->set_rules('email', lang('email_join'), 'trim|required|htmlspecialchars|xss_clean|valid_email|max_length[128]');

            //폼검증 성공이면
            if($this->form_validation->run() == TRUE)
            {
                //request
                $req_user_id = $this->form_validation->set_value('user_id');
                $req_email   = $this->form_validation->set_value('email');

                //captcha
                // First, delete old captchas
                $expiration = time() - SETTING_captcha_timeout;
                $this->db->query("DELETE FROM captcha WHERE captcha_time < " . $expiration);

                // Then see if a captcha exists:
                $sql   = "SELECT COUNT(*) AS count FROM captcha WHERE word = ? AND ip_address = ? AND captcha_time > ?";
                $binds = array(
                    $_POST['captcha'],
                    $this->input->ip_address(),
                    $expiration
                );
                $query = $this->db->query($sql, $binds);
                $row   = $query->row();

                if($row->count == 0)
                {
                    //captcha 오류
                    $assign['result_msg'] = lang('captcha_fail');
                }
                else
                {
                    //해당 회원의 이메일
                    $email = $this->users_model->email($req_user_id, $req_email);

                    if($email !== FALSE && $email !== '')
                    {
                        $new_password = substr(rand() . microtime(), 0, 16); //설정에서 맥시멈을 16글자로 하고 있다.

                        $hash = new PasswordHash(8, FALSE);
                        $super_secured_password = $hash->HashPassword($new_password);

                        //새로운 비번 세팅
                        $result = $this->users_model->set_new_password($req_user_id, $super_secured_password);

                        if($result !== FALSE)
                        {
                            //메일전송
                            $this->load->helper('email');
                            send_email($email, SETTING_new_password_mail_title, str_replace(array(
                                '{user_id}',
                                '{new_password}'
                            ), array(
                                $req_user_id,
                                $new_password
                            ), SETTING_new_password_mail_contents));

                            $post_success = TRUE;

                            $assign['message']  = lang('find_password_success');
                            $assign['redirect'] = '/user/login';

                            $this->alert($assign);
                        }
                        else
                        {
                            $assign['result_msg'] = lang('find_password_fail_msg');
                        }
                    }
                    else
                    {
                        $assign['result_msg'] = lang('info_none');
                    }
                }
            }

            if($post_success == FALSE)
            {
                $assign['validation_result']  = validation_errors();
                $assign['validation_message'] = ($assign['validation_result'] !== '') ? str_replace("\n", '', $assign['validation_result']) : '';

                $assign['form_null_check'] = "user_id^{$this->assign['lang']['user_id']}|email^{$this->assign['lang']['email_join']}|captcha^CAPTCHA";

                $value_key_list = array(
                    'user_id',
                    'email_join'
                );
                foreach($value_key_list as $v)
                {
                    $assign['value_list'][$v] = set_value($v);
                }

                //captcha
                $this->load->helper('captcha');

                $vals = array(
                    'img_path'   => './captcha/',
                    'img_url'    => BASE_URL . 'captcha/',
                    'font_path'  => './captcha/fonts/3.ttf',
                    'img_width'  => 150,
                    'img_height' => 30,
                    'expiration' => SETTING_captcha_timeout,
                    'pool'       => '0123456789'
                );

                $cap = create_captcha($vals);

                $data_captcha = array(
                    'captcha_time' => $cap['time'],
                    'ip_address'   => $this->input->ip_address(),
                    'word'         => $cap['word']
                );

                $query = $this->db->insert_string('captcha', $data_captcha);
                $this->db->query($query);

                $assign['captcha_img'] = $cap['image'];

                //$this->layout->view('user/find_password_view', $data);

                $this->scope('contents', 'contents/user/find_password', $assign);
                $this->display('layout');
            }
        }
        //로그인,권한 체크 end if
    }

    // --------------------------------------------------------------------

    /**
     * 회원정보 수정
     * @author KangMin
     * @since  2011.11.09
     */
    public function modify()
    {
        $this->add_language_pack($this->language_pack('user_modify'));
        $assign                     = NULL;
        $post_success               = FALSE; //DB insert/update 성공여부
        $assign['result_msg']       = '';
        $assign['avatar_file_fail'] = NULL;

        //로그인 상태가 아니면
        if(IS_USER_LOGIN === FALSE)
        {
            $this->login_view();
        }
        else
        {
            //rules
            //비밀번호 입력했으면 수정, 아니면 통과
            //각 설정 길이들은 수정시에는 다른 항목 수정에서도 걸릴 수 있지만.. 이는 정책으로다가
            if($this->input->post('password'))
            {
                $this->form_validation->set_rules('password', lang('password'), 'trim|required|xss_clean|min_length[' . SETTING_user_password_length_minimum . ']|max_length[' . SETTING_user_password_length_maximum . ']');
                $this->form_validation->set_rules('password_confirm', lang('password_confirm'), 'trim|required|xss_clean|matches[password]');
            }

            $this->form_validation->set_rules('name', lang('name'), 'trim|required|xss_clean|min_length[' . SETTING_user_name_length_minimum . ']|max_length[' . SETTING_user_name_length_maximum . ']');
            $this->form_validation->set_rules('nickname', lang('nickname'), 'trim|required|xss_clean|min_length[' . SETTING_user_nickname_length_minimum . ']|max_length[' . SETTING_user_nickname_length_maximum . ']');
            $this->form_validation->set_rules('email', lang('email'), 'trim|required|htmlspecialchars|xss_clean|valid_email|max_length[128]');
            $this->form_validation->set_rules('message_receive_type', lang('message_receive_type'), 'trim|required|xss_clean|is_natural|less_than[3]');
            if(SETTING_avatar_used == 1)
            {
                $this->form_validation->set_rules('avatar_used', lang('avatar_used'), 'trim|required|xss_clean|is_natural|less_than[2]');
            }
            $this->form_validation->set_rules('timezones', lang('timezone'), 'trim|required|xss_clean');
            $this->form_validation->set_rules('memo', lang('memo'), 'trim|htmlspecialchars|xss_clean');

            //폼검증 성공이면
            if($this->form_validation->run() == TRUE)
            {
                //request
                if($this->input->post('password'))
                {
                    $req_password         = $this->form_validation->set_value('password');
                    $req_password_confirm = $this->form_validation->set_value('password_confirm');
                }
                $req_name                 = htmlspecialchars($this->form_validation->set_value('name'));
                $req_nickname             = htmlspecialchars($this->form_validation->set_value('nickname'));
                $req_email                = $this->form_validation->set_value('email');
                $req_message_receive_type = $this->form_validation->set_value('message_receive_type');
                if(SETTING_avatar_used == 1)
                {
                    $req_avatar_used = $this->form_validation->set_value('avatar_used');
                }
                else
                {
                    $req_avatar_used = 0; //설정이 사용중이 아닐때 회원수정하면 그냥 미사용으로하자... 나중에 추가된 설정이라..으.. 귀차니즘..
                }
                $req_timezones   = $this->form_validation->set_value('timezones');
                $req_memo        = $this->form_validation->set_value('memo');
                $req_avatar_file = ($_FILES) ? $_FILES['avatar_file']['name'] : NULL;

                $assign['result_msg'] = NULL;
                $modify_fail_msg      = array();

                if($req_avatar_file)
                {
                    $_FILES['avatar_file']['name'] = strtolower($_FILES['avatar_file']['name']);

                    //아바타 파일 업로드
                    $config['upload_path']   = './avatars/';
                    $config['allowed_types'] = 'gif';
                    $config['overwrite']     = TRUE;
                    $config['file_name']     = USER_INFO_user_id;
                    $config['max_size']      = SETTING_avatar_limit_capacity / 1024;
                    $config['max_width']     = SETTING_avatar_limit_image_size_width;
                    $config['max_height']    = SETTING_avatar_limit_image_size_height;

                    $this->load->library('upload', $config);

                    if(!$this->upload->do_upload('avatar_file'))
                    {
                        $assign['avatar_file_fail'] = $this->upload->display_errors();
                    }
                    else
                    {
                        $avatar_file_data = $this->upload->data(); //파일명을 user_id로 올리고 체크하므로 일단 사용할 일이 없음.
                    }
                }

                //닉네임 중복 확인
                $check_nickname = $this->users_model->check('nickname', $req_nickname, ' AND idx <> ' . USER_INFO_idx);

                if($check_nickname == TRUE) //닉네임 있으면(TRUE) 가입못함
                {
                    $modify_fail_msg[] = lang('nickname_duplicate');
                }

                //이메일 중복 확인
                $check_email = $this->users_model->check('email', $req_email, ' AND idx <> ' . USER_INFO_idx);

                if($check_email == TRUE) //이메일 있으면(TRUE) 가입못함
                {
                    $modify_fail_msg[] = lang('email_duplicate');
                }
                if(count($modify_fail_msg) > 0 OR $assign['avatar_file_fail'] !== NULL)
                {
                    //닉네임, 이메일 중 1개 이상 오류
                    $assign['result_msg'] = join('<br />', $modify_fail_msg);
                }
                else
                {
                    if($this->input->post('password'))
                    {
                        $hash = new PasswordHash(8, FALSE);
                        $super_secured_password = $hash->HashPassword($req_password);
                    }

                    //정상 수정
                    $values = array();

                    $values['password']             = ($this->input->post('password')) ? $super_secured_password : '';
                    $values['name']                 = $req_name;
                    $values['nickname']             = $req_nickname;
                    $values['email']                = $req_email;
                    $values['message_receive_type'] = $req_message_receive_type;
                    $values['avatar_used']          = $req_avatar_used;
                    $values['timezones']            = $req_timezones;
                    $values['memo']                 = $req_memo;

                    $result = $this->users_model->modify($values);

                    if($result == TRUE)
                    {
                        $post_success       = TRUE; //최종 성공 여부
                        $assign['message']  = lang('update_success');
                        $assign['redirect'] = '/user/modify';

                        $this->alert($assign);
                    }
                    else
                    {
                        $assign['result_msg'] = lang('modify_fail_msg');
                    }
                }
            }

            if($post_success == FALSE)
            {
                for($i = 0; $i <= 2; $i++)
                {
                    $assign['message_receive_type'][$i] = array(
                        'checked' => '',
                        'text'    => $this->assign['lang']['message_receive_type_' . $i]
                    );
                    if(trim(set_value('message_receive_type')) !== '')
                    {
                        $assign['message_receive_type'][$i]['checked'] = set_radio('message_receive_type', $i);
                    }
                    else
                    {
                        if(USER_INFO_message_receive_type == $i)
                        {
                            $assign['message_receive_type'][$i]['checked'] = 'checked="checked"';
                        }
                        else
                        {
                            $assign['message_receive_type'][$i]['checked'] = '';
                        }
                    }
                }

                $assign['avatar'] = array(
                    'file'     => '',
                    'width'    => SETTING_avatar_limit_image_size_width,
                    'height'   => SETTING_avatar_limit_image_size_height,
                    'capacity' => byte_format(SETTING_avatar_limit_capacity),
                    'used'     => array()
                );
                if(file_exists('./avatars/' . USER_INFO_user_id . '.gif') === TRUE)
                {
                    $assign['avatar']['file'] = BASE_URL . 'avatars/' . USER_INFO_user_id . '.gif';
                }
                for($i = 0; $i <= 1; $i++)
                {
                    $assign['avatar']['used'][$i] = array(
                        'checked' => '',
                        'text'    => $this->assign['lang']['avatar_used_' . $i]
                    );

                    /**
                    // 왜 이렇게 해뒀었는지 기억이 않는다. 아래로 수정
                    if(trim(set_value('avatar_used')) !== '')
                    {
                        $assign['avatar']['used'][$i]['checked'] = set_radio('avatar_used', $i);
                    }
                    else
                    {
                        if(USER_INFO_avatar_used == $i)
                        {
                            $assign['avatar']['used'][$i]['checked'] = 'checked="checked"';
                        }
                        else
                        {
                            $assign['avatar']['used'][$i]['checked'] = '';
                        }
                    }
                    */

                    if(@(int)USER_INFO_avatar_used == $i)
                    {
                        $assign['avatar']['used'][$i]['checked'] = 'checked="checked"';
                    }
                    else
                    {
                        $assign['avatar']['used'][$i]['checked'] = '';
                    }
                }

                $assign['timezone_selectbox'] = '';
                if(set_value('timezones'))
                {
                    $selected_timezone = set_value('timezones');
                }
                else
                {
                    $selected_timezone = USER_INFO_timezone;
                }
                $assign['timezone_selectbox'] = timezone_menu($selected_timezone);

                $assign['memo'] = '';
                if(set_value('memo'))
                {
                    $assign['memo'] = set_value('memo');
                }
                else
                {
                    $assign['memo'] = USER_INFO_memo;
                }

                $assign['insert_date'] = time2date(USER_INFO_timestamp_insert);

                $assign['user_id']  = (defined('USER_INFO_user_id') === TRUE) ? USER_INFO_user_id : '';
                $assign['name']     = (set_value('name') !== '') ? set_value('name') : USER_INFO_name;
                $assign['nickname'] = (set_value('nickname') !== '') ? set_value('nickname') : USER_INFO_nickname;
                $assign['email']    = (set_value('email') !== '') ? set_value('email') : USER_INFO_email;

                $assign['form_null_check']    = "user_id^{$this->assign['lang']['user_id']}|password^{$this->assign['lang']['password']}|password_confirm^{$this->assign['lang']['password_confirm']}|name^{$this->assign['lang']['name']}|nickname^{$this->assign['lang']['nickname']}|email^{$this->assign['lang']['email']}|captcha^CAPTCHA";
                $assign['validation_result']  = validation_errors();
                $assign['validation_message'] = ($assign['validation_result'] !== '') ? str_replace("\n", '', $assign['validation_result']) : '';

                $this->scope('contents', 'contents/user/modify', $assign);
                $this->display('layout');
            }
        }
        //로그인,권한 체크 end if
    }

    // --------------------------------------------------------------------

    /**
     * 포인트 내역
     * @author KangMin
     * @since  2011.11.09
     */
    public function point()
    {
        $this->add_language_pack($this->language_pack('user_point'));
        $assign = NULL;

        $req_page = ((int)$this->input->get('page') > 0) ? (int)$this->input->get('page') : 1;

        //로그인 상태가 아니면
        if(!defined('USER_INFO_idx'))
        {
            $this->login_view();
        }
        else
        {
            $this->load->model('users_point_model');
            $this->load->library('pagination');

            $req_operator       = $this->input->get('operator');
            $add_where_operator = '';

            if($req_operator == 'plus') $add_where_operator = ' AND USERS_POINT.point >= 0 ';
            else if($req_operator == 'minus') $add_where_operator = ' AND USERS_POINT.point < 0 ';

            $assign['total_cnt'] = $this->users_point_model->get_point_info_total_cnt(USER_INFO_idx, $add_where_operator, ' AND USERS_POINT.is_deleted = 0 ');

            // http://codeigniter-kr.org/user_guide_2.1.0/libraries/pagination.html
            $config['base_url']             = BASE_URL . 'user/point?operator=' . $req_operator;
            $config['enable_query_strings'] = TRUE; // ?page=10 이런 일반 get 방식
            $config['page_query_string']    = TRUE;
            $config['use_page_numbers']     = TRUE;
            $config['num_links']            = (int)SETTING_count_page_point;
            $config['query_string_segment'] = 'page';
            $config['total_rows']           = $assign['total_cnt'];
            $config['per_page']             = (int)SETTING_count_list_point;

            $this->config->load('pagination');
            $pagination_config = $this->config->item($this->viewport);
            $config            = array_merge($config, $pagination_config);

            $this->pagination->initialize($config);

            $assign['pagination']  = $this->pagination->create_links();
            $assign['users_point'] = array();

            if($assign['total_cnt'] > 0)
            {
                $assign['users_point'] = $this->users_point_model->get_point_info(USER_INFO_idx, ($req_page - 1) * $config['per_page'], $config['per_page'], $add_where_operator, ' AND USERS_POINT.is_deleted = 0 ');
            }

            foreach($assign['users_point'] as $k => &$v)
            {
                $alliance = '';

                //연관글
                //좀 길어지겠군...
                if($v->article_idx)
                {
                    $alliance_link = '<a href = "' . BASE_URL . 'bbs/view/' . $v->article_bbs_id . '?idx=' . $v->article_idx . '" target = "_blank">[' . lang('view') . ']</a>';

                    if($v->point >= 0)
                    {
                        $alliance = lang('article') . ' ' . lang('write') . ' ' . $alliance_link;
                    }
                    else
                    {
                        $alliance = lang('article') . ' ' . lang('delete') . ' ' . $alliance_link;
                    }
                }
                else if($v->comment_idx)
                {
                    $alliance_link = '<a href = "' . BASE_URL . 'bbs/view/' . $v->comment_article_bbs_id . '?idx=' . $v->comment_article_idx . '" target = "_blank">[' . lang('view') . ']</a>';

                    if($v->point >= 0)
                    {
                        $alliance = lang('comment') . ' ' . lang('write') . ' ' . $alliance_link;
                    }
                    else
                    {
                        $alliance = lang('comment') . ' ' . lang('delete') . ' ' . $alliance_link;
                    }
                }
                else if($v->vote_article_idx)
                {
                    $alliance_link = '<a href = "' . BASE_URL . 'bbs/view/' . $v->vote_article_bbs_id . '?idx=' . $v->vote_article_idx . '" target = "_blank">[' . lang('view') . ']</a>';

                    if((int)$v->exec_user_idx === (int)$v->user_idx)
                    {
                        $alliance = lang('article') . ' ' . lang('vote_send') . ' ' . $alliance_link;
                    }
                    else
                    {
                        $alliance = lang('article') . ' ' . lang('vote_receive') . ' ' . $alliance_link;
                    }
                }
                else if($v->vote_comment_article_idx)
                {
                    $alliance_link = '<a href = "' . BASE_URL . 'bbs/view/' . $v->vote_comment_article_bbs_id . '?idx=' . $v->vote_comment_article_idx . '" target = "_blank">[' . lang('view') . ']</a>';

                    if((int)$v->exec_user_idx === (int)$v->user_idx)
                    {
                        $alliance = lang('comment') . ' ' . lang('vote_send') . ' ' . $alliance_link;
                    }
                    else
                    {
                        $alliance = lang('comment') . ' ' . lang('vote_receive') . ' ' . $alliance_link;
                    }
                }
                else
                {
                    $alliance = $v->comment;
                }
                $v->alliance  = $alliance;
                $v->exec_date = time2date($v->exec_timestamp);
            }

            $operator                        = $this->input->get('operator');
            $assign['operator_all_active']   = ($operator === 'all' OR $operator === FALSE) ? 'ui-btn-active' : '';
            $assign['operator_plus_active']  = ($operator === 'plus') ? 'ui-btn-active' : '';
            $assign['operator_minus_active'] = ($operator === 'minus') ? 'ui-btn-active' : '';
            $assign['operator'] = ($operator === FALSE) ? '' : $operator;

            $this->scope('contents', 'contents/user/point', $assign);
            $this->display('layout');
            //$this->layout->view('user/point_view', $assign);
        }
    }

    // --------------------------------------------------------------------

    /**
     * 스크랩
     *
     * @author KangMin
     * @since  2011.11.09
     */
    public function scrap()
    {
        $this->add_language_pack($this->language_pack('user_scrap'));
        $assign = NULL;

        $req_page = ((int)$this->input->get('page') > 0) ? (int)$this->input->get('page') : 1;

        //로그인 상태가 아니면
        if(!defined('USER_INFO_idx'))
        {
            $this->login_view();
        }
        else
        {
            $this->load->model('users_url_model');
            $this->load->library('pagination');

            $assign['total_cnt'] = $this->users_url_model->get_scrap_total_cnt();

            // http://codeigniter-kr.org/user_guide_2.1.0/libraries/pagination.html
            $config['base_url']             = BASE_URL . 'user/scrap?';
            $config['enable_query_strings'] = TRUE; // ?page=10 이런 일반 get 방식
            $config['page_query_string']    = TRUE;
            $config['use_page_numbers']     = TRUE;
            $config['num_links']            = (int)SETTING_count_page_scrap;
            $config['query_string_segment'] = 'page';
            $config['total_rows']           = $assign['total_cnt'];
            $config['per_page']             = (int)SETTING_count_list_scrap;

            $this->config->load('pagination');
            $pagination_config = $this->config->item($this->viewport);
            $config            = array_merge($config, $pagination_config);
            $this->pagination->initialize($config);

            $assign['pagination'] = $this->pagination->create_links();
            $assign['users_url']  = array();

            if($assign['total_cnt'] > 0)
            {
                $assign['users_url'] = $this->users_url_model->get_scrap(($req_page - 1) * $config['per_page'], $config['per_page']);
            }
            foreach($assign['users_url'] as $k => &$v)
            {
                $v->title = cut_string($v->title, SETTING_cut_length_title_scrap);
            }

            $this->scope('contents', 'contents/user/scrap', $assign);
            $this->display('layout');
            #$this->layout->view('user/scrap_view', $data);
        }
    }

    // --------------------------------------------------------------------

    /**
     * 스크랩/즐겨찾기 삭제 (ajax)
     *
     * @author KangMin
     * @since  2012.02.24
     */
    public function delete_url()
    {
        $data = NULL;
        $json = NULL;

        $this->load->model('users_url_model');

        $req_idx = ((int)$this->input->post('idx') > 0) ? (int)$this->input->post('idx') : NULL;

        //유효성
        //중요하지 않아서 안할려다가 해당 글의 스크랩카운트 삭제가 뚫릴 수 있어서 막는다.
        $check_idx = $this->users_url_model->check_idx($req_idx, ' AND user_idx = ' . (defined('USER_INFO_idx') ? USER_INFO_idx : 0) . ' ');

        if($check_idx !== TRUE) $req_idx = NULL;

        //로그인 상태가 아니거나 권한이 없으면
        if(!defined('USER_INFO_idx')
            OR $req_idx == NULL
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
            $this->db->trans_start();

            //스크랩 삭제면 해당 글의 스크랩 카운트를 줄인다
            if($this->input->post('type') == 'scrap')
            {
                $this->load->model('bbs_article_model');

                $article_idx = $this->users_url_model->get_article_idx($req_idx);

                //스크랩카운트
                $result_scrap_count = $this->bbs_article_model->update_count_article($article_idx, 'scrap_count', -1);
            }
            else
            {
                $result_scrap_count = TRUE;
            }

            $result = $this->users_url_model->delete_url($req_idx, ' AND user_idx = ' . USER_INFO_idx . ' ');

            $this->db->trans_complete();

            if($result == TRUE && $result_scrap_count == TRUE)
            {
                $json['message'] = lang('delete_success');
                $json['success'] = TRUE;
            }
            else
            {
                $json['message'] = lang('delete_fail_msg');
                $json['success'] = FALSE;
            }
        }
        //로그인,권한 체크 end if

        echo json_encode($json);
    }

    // --------------------------------------------------------------------

    /**
     * 메시지 보내기 폼 (dialog)
     * @author KangMin
     * @since  2012.04.12
     */
    public function send_message()
    {
        $this->add_language_pack($this->language_pack('user_send_message'));

        $assign = NULL;

        //로그인 상태가 아니면
        if(!defined('USER_INFO_idx'))
        {
            $this->login_view();
        }
        else
        {
            $assign = $this->check_allow_send_message();

            $assign['print_receiver_name'] = name($assign['receiver_user_id'], $assign['receiver_name'], $assign['receiver_nickname']);

            $this->scope('contents', 'contents/user/send_message', $assign);
            $this->display('contents');
        }
        //로그인,권한 체크 end if
    }

    // --------------------------------------------------------------------

    /**
     * 메시지 보내기 실행 (ajax)
     * @author KangMin
     * @since  2012.04.16
     */
    public function send_message_exec()
    {
        $data = NULL;
        $json = NULL;

        //로그인 상태가 아니면
        if(!defined('USER_INFO_idx'))
        {
            $json['message'] = lang('deny_allow');
            $json['success'] = FALSE;
        }
        else
        {
            $data = $this->check_allow_send_message();

            if($data['error_msg'] == NULL && $data['receiver_user_idx'] !== NULL) //정상 발송
            {
                //rules
                $this->form_validation->set_rules('contents', lang('contents'), 'trim|required|htmlspecialchars|xss_clean');

                //폼검증 성공이면
                if($this->form_validation->run() == TRUE)
                {
                    $this->load->model('users_message_model');

                    $result = $this->users_message_model->send_message($data['receiver_user_idx'], $this->form_validation->set_value('contents'));

                    if($result == TRUE)
                    {
                        $json['message'] = lang('send_message_success');
                        $json['success'] = TRUE;
                    }
                    else
                    {
                        $json['message'] = lang('send_message_fail');
                        $json['success'] = FALSE;
                    }
                }
                else
                {
                    $json['message'] = str_replace("\n", '', validation_errors());
                    $json['success'] = FALSE;
                }
            }
            else
            {
                $json['message'] = $data['error_msg'];
                $json['success'] = FALSE;
            }
        }
        //로그인,권한 체크 end if

        echo json_encode($json);
    }

    // --------------------------------------------------------------------

    /**
     * 메시지 발송 허용 체크
     * @author KangMin
     * @since  2012.04.16
     *
     * @return array
     */
    private function check_allow_send_message()
    {
        $data['receiver_user_status']               = NULL;
        $data['receiver_user_message_receive_type'] = NULL;
        $data['error_msg']                          = NULL;
        $data['receiver_user_idx']                  = NULL;
        $data['receiver_user_id']                   = NULL;
        $data['receiver_nickname']                  = NULL;
        $data['receiver_name']                      = NULL;

        if(!defined('USER_INFO_idx')) //로그인 풀린상태거나하면 빠꾸
        {
            $data['error_msg'] = lang('deny_allow');

            return $data;
        }

        $data['receiver_user_idx'] = (int)$this->input->get_post('receiver');

        //수신자 정상 회원 확인
        $check_receiver_user_info = $this->users_model->get_user_info($data['receiver_user_idx']);

        if($check_receiver_user_info)
        {
            $data['receiver_user_id']                   = $check_receiver_user_info->user_id;
            $data['receiver_nickname']                  = $check_receiver_user_info->nickname;
            $data['receiver_name']                      = $check_receiver_user_info->name;
            $data['receiver_user_status']               = (int)$check_receiver_user_info->status; //0:탈퇴, 1:정상, 2:차단
            $data['receiver_user_message_receive_type'] = (int)$check_receiver_user_info->message_receive_type; //0:전체거부, 1:전체수신, 2:친구만수신

            //단, 최고관리자그룹의 쪽지는 무조건 보낸다.
            //이름,닉네임등 세팅 후여야하므로 여기에 있어야한다. 위치가 좀 그렇지만...
            if(USER_INFO_group_idx === SETTING_admin_group_idx)
            {
                return $data;
            }

            if($data['receiver_user_status'] === 1) //정상
            {
                if($data['receiver_user_message_receive_type'] === 1) //전체수신
                {
                    ///////////////////////////////////////////
                    //메시지 발신 허용
                }
                else if($data['receiver_user_message_receive_type'] === 0) //전체거부
                {
                    $data['error_msg'] = lang('send_message_fail_type_0');
                }
                else if($data['receiver_user_message_receive_type'] === 2) //친구만수신
                {
                    //발신자가 수신자 친구목록에 있는지 확인
                    $this->load->model('users_friend_model');

                    $check_sender_in_friend = $this->users_friend_model->check_sender_in_friend($data['receiver_user_idx'], USER_INFO_idx);

                    if($check_sender_in_friend === TRUE) //친구목록에 있음.
                    {
                        ///////////////////////////////////////////
                        //메시지 발신 허용
                    }
                    else //친구목록에 없음.
                    {
                        $data['error_msg'] = lang('send_message_fail_type_2');
                    }
                }
                else //error
                {
                    $data['error_msg'] = lang('fatal_error');
                }
            }
            else
            {
                //탈퇴, 차단
                $data['error_msg'] = lang('unusual_user');
            }
        }
        else
        {
            //null
            $data['error_msg'] = lang('unknown_user');
        }

        if($data['error_msg'] !== NULL) $data['receiver_user_idx'] = NULL; //에러있으면 다시 null로.. 크게 상관없지만 혹시 몰라서..

        return $data;
    }

    // --------------------------------------------------------------------

    /**
     * 메시지 목록
     * @author KangMin
     * @since  2011.11.09
     */
    public function message()
    {
        $this->add_language_pack($this->language_pack('user_message'));
        $assign = NULL;

        $req_page = ((int)$this->input->get('page') > 0) ? (int)$this->input->get('page') : 1;

        //로그인 상태가 아니면
        if(!defined('USER_INFO_idx'))
        {
            $this->login_view($assign);
        }
        else
        {
            $search                          = $this->input->get('search'); //receive box, send box 구분
            $search                          = $assign['search'] = ($search == 'send') ? 'send' : 'receive'; //validation
            $assign['search_send_active']    = ($search == 'send') ? 'ui-btn-active' : '';
            $assign['search_receive_active'] = ($search == 'receive') ? 'ui-btn-active' : '';
            $assign['search'] = $search;

            $this->load->model('users_message_model');
            $this->load->library('pagination');

            $assign['total_cnt'] = $this->users_message_model->get_message_total_cnt($search);

            // http://codeigniter-kr.org/user_guide_2.1.0/libraries/pagination.html
            $config['base_url']             = BASE_URL . 'user/message?search=' . $search . '&';
            $config['enable_query_strings'] = TRUE; // ?page=10 이런 일반 get 방식
            $config['page_query_string']    = TRUE;
            $config['use_page_numbers']     = TRUE;
            $config['num_links']            = (int)SETTING_count_page_message;
            $config['query_string_segment'] = 'page';
            $config['total_rows']           = $assign['total_cnt'];
            $config['per_page']             = (int)SETTING_count_list_message;

            $this->config->load('pagination');
            $pagination_config = $this->config->item($this->viewport);
            $config            = array_merge($config, $pagination_config);

            $this->pagination->initialize($config);

            $assign['pagination']    = $this->pagination->create_links();
            $assign['users_message'] = array();

            if($assign['total_cnt'] > 0)
            {
                $assign['users_message'] = $this->users_message_model->get_message($search, ($req_page - 1) * $config['per_page'], $config['per_page']);
            }

            foreach($assign['users_message'] as $k => &$v)
            {

                $v->title           = cut_string(($v->title) ? $v->title : $v->contents, SETTING_cut_length_title_message);
                $v->print_name      = name($v->user_id, $v->name, $v->nickname);
                $v->is_read_class   = "is_read_{$v->is_read}";
                $v->is_read_text = lang($v->is_read_class);
                $v->print_send_date = time2date($v->timestamp_send);
            }

            $this->scope('contents', 'contents/user/message', $assign);
            $this->display('layout');

            #$this->layout->view('user/message_view', $data);
        }
    }

    // --------------------------------------------------------------------

    /**
     * 메시지 상세내용
     * @author KangMin
     * @since  2011.11.09
     */
    public function message_detail()
    {
        $this->add_language_pack($this->language_pack('user_message_detail'));
        $assign = NULL;

        //로그인 상태가 아니면
        if(!defined('USER_INFO_idx'))
        {
            $this->login_view();
        }
        else
        {
            $search                          = $this->input->get('search'); //receive box, send box 구분
            $search                          = ($search == 'send') ? 'send' : 'receive'; //validation
            $assign['search_send_active']    = ($search == 'send') ? 'ui-btn-active' : '';
            $assign['search_receive_active'] = ($search == 'receive') ? 'ui-btn-active' : '';

            $this->load->model('users_message_model');

            $req_idx = (int)$this->input->get('idx');

            //존재유무와 본인이 보내거나 받은건지 검증과 같이 메시지 호출
            //리턴이 없으면 비정상적인 접근이거나 삭제된것으로 본다.
            $assign                     = $this->users_message_model->view_message($search, $req_idx);
            $assign->print_name         = name($assign->user_id, $assign->name, $assign->nickname);
            $assign->contents           = nl2br($assign->contents);
            $assign->print_receive_date = time2date($assign->timestamp_receive);

            $assign->search = $search; //위 쿼리 결과가 stdclass 여서
            $this->scope('contents', 'contents/user/message_detail', (array)$assign);
            $this->display('contents');

            //읽음처리
            //최소한만 호출
            if($search == 'receive' && $assign->is_read == 0 && $assign->receiver_user_idx == USER_INFO_idx)
            {
                //리턴값으로 처리까지는 없다.
                $result = $this->users_message_model->read_message($req_idx);
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * 메시지 삭제 (ajax)
     *
     * @author KangMin
     * @since  2012.04.17
     */
    public function delete_message()
    {
        $data = NULL;
        $json = NULL;

        $this->load->model('users_message_model');

        $req_idx = ((int)$this->input->post('idx') > 0) ? (int)$this->input->post('idx') : NULL;

        //유효성
        $check_idx = $this->users_message_model->check_idx($req_idx);

        if($check_idx !== TRUE) $req_idx = NULL;

        //로그인 상태가 아니거나 권한이 없으면
        if(!defined('USER_INFO_idx')
            OR $req_idx == NULL
        )
        {
            if($req_idx == NULL)
            {
                $json['message'] = lang('none_message');
            }
            else
            {
                $json['message'] = lang('deny_allow');
            }

            $json['success'] = FALSE;
        }
        else
        {
            $search = $this->input->post('search'); //receive box, send box 구분

            $search = ($search == 'send') ? 'send' : 'receive'; //validation

            $result = $this->users_message_model->delete_message($search, $req_idx);

            if($result == TRUE)
            {
                $json['message'] = lang('delete_success');
                $json['success'] = TRUE;
            }
            else
            {
                $json['message'] = lang('delete_fail_msg');
                $json['success'] = FALSE;
            }
        }
        //로그인,권한 체크 end if

        echo json_encode($json);
    }

    // --------------------------------------------------------------------

    /**
     * 읽지않은 메시지 갯수 리턴 (ajax)
     *
     * @author KangMin
     * @since  2012.04.17
     */
    public function get_message_count()
    {
        $data = NULL;
        $json = NULL;

        //로그인 상태가 아니면
        if(!defined('USER_INFO_idx'))
        {
            $json['message_count'] = 0;
        }
        else
        {
            $this->load->model('users_message_model');

            $json['message_count'] = $this->users_message_model->get_message_count();
        }

        echo json_encode($json);
    }

    public function get_message()
    {
        $params = $this->input->post();
        $idx = $params['idx'];
        $search = $params['search'];

        $this->load->model('users_message_model');

        $result                     = $this->users_message_model->view_message($search, $idx);
        $result->kind               = $search;
        $result->print_name         = name($result->user_id, $result->name, $result->nickname);
        $result->contents           = nl2br($result->contents);
        $result->print_receive_date         = ($search === 'send') ? time2date($result->timestamp_receive) : '';

        //읽음처리
        //최소한만 호출
        if($search == 'receive' && $result->is_read == 0 && $result->receiver_user_idx == USER_INFO_idx)
        {
            $result->timestamp_receive = time();
            //리턴값으로 처리까지는 없다.
            $this->users_message_model->read_message($idx);
        }

        echo json_encode($result);
    }

    // --------------------------------------------------------------------

    /**
     * 친구관리
     * @author KangMin
     * @since  2012.04.18
     */
    public function friend()
    {
        $this->add_language_pack($this->language_pack('user_friend'));

        $assign = NULL;

        $req_page = ((int)$this->input->get('page') > 0) ? (int)$this->input->get('page') : 1;

        //로그인 상태가 아니면
        if(!defined('USER_INFO_idx'))
        {
            $this->login_view();
        }
        else
        {
            $this->load->model('users_friend_model');
            $this->load->library('pagination');

            $assign['total_cnt'] = $this->users_friend_model->get_friend_total_cnt();

            // http://codeigniter-kr.org/user_guide_2.1.0/libraries/pagination.html
            $config['base_url']             = BASE_URL . 'user/friend?';
            $config['enable_query_strings'] = TRUE; // ?page=10 이런 일반 get 방식
            $config['page_query_string']    = TRUE;
            $config['use_page_numbers']     = TRUE;
            $config['num_links']            = (int)SETTING_count_page_friend;
            $config['query_string_segment'] = 'page';
            $config['total_rows']           = $assign['total_cnt'];
            $config['per_page']             = (int)SETTING_count_list_friend;

            $this->config->load('pagination');
            $pagination_config = $this->config->item($this->viewport);
            $config            = array_merge($config, $pagination_config);

            $this->pagination->initialize($config);

            $assign['pagination'] = $this->pagination->create_links();

            if($assign['total_cnt'] > 0)
            {
                $assign['users_friend'] = $this->users_friend_model->get_friend(($req_page - 1) * $config['per_page'], $config['per_page']);

                foreach($assign['users_friend'] as &$v)
                {
                    $v->print_name = name($v->user_id, $v->name, $v->nickname);
                }
            }

            $this->scope('contents', 'contents/user/friend', $assign);
            $this->display('layout');
            #$this->layout->view('user/friend_view', $assign);
        }
    }

    // --------------------------------------------------------------------

    /**
     * 친구 삭제 (ajax)
     *
     * @author KangMin
     * @since  2012.04.18
     */
    public function delete_friend()
    {
        $data = NULL;
        $json = NULL;

        $this->load->model('users_friend_model');

        $req_idx = ((int)$this->input->post('idx') > 0) ? (int)$this->input->post('idx') : NULL;

        //유효성
        $check_idx = $this->users_friend_model->check_idx($req_idx, ' AND user_idx = ' . (defined('USER_INFO_idx') ? USER_INFO_idx : 0) . ' ');

        if($check_idx !== TRUE) $req_idx = NULL;

        //로그인 상태가 아니거나 권한이 없으면
        if(!defined('USER_INFO_idx')
            OR $req_idx == NULL
        )
        {
            if($req_idx == NULL)
            {
                $json['message'] = lang('none_friend');
            }
            else
            {
                $json['message'] = lang('deny_allow');
            }

            $json['success'] = FALSE;
        }
        else
        {
            $result = $this->users_friend_model->delete_friend($req_idx, ' AND user_idx = ' . USER_INFO_idx . ' ');

            if($result == TRUE)
            {
                $json['message'] = lang('delete_success');
                $json['success'] = TRUE;
            }
            else
            {
                $json['message'] = lang('delete_fail_msg');
                $json['success'] = FALSE;
            }
        }
        //로그인,권한 체크 end if

        echo json_encode($json);
    }

    // --------------------------------------------------------------------

    /**
     * 친구 추가
     *
     * @author KangMin
     * @since  2012.04.18
     */
    public function add_friend()
    {
        $data = NULL;
        $json = NULL;

        $req_friend_user_idx = ((int)$this->input->post('friend') > 0) ? (int)$this->input->post('friend') : NULL;

        //수신자 정상 회원 확인
        $check_friend_user_info = $this->users_model->get_user_info($req_friend_user_idx);

        if(!$check_friend_user_info OR (int)$check_friend_user_info->status !== 1)
        {
            $req_friend_user_idx = NULL;
        }

        //로그인 상태가 아니거나 권한이 없으면
        if(!defined('USER_INFO_idx')
            OR $req_friend_user_idx == NULL
        )
        {
            if($req_friend_user_idx == NULL)
            {
                $json['message'] = lang('unknown_user');
            }
            else
            {
                $json['message'] = lang('deny_allow');
            }

            $json['success'] = FALSE;
        }
        else
        {
            $this->load->model('users_friend_model');

            //중복차단
            $check_duplicate_friend = $this->users_friend_model->check_duplicate_friend($req_friend_user_idx);

            if($check_duplicate_friend == TRUE) //중복이면 TRUE
            {
                $json['message'] = lang('friend_duplicate');
                $json['success'] = FALSE;
            }
            else
            {

                $result = $this->users_friend_model->insert($req_friend_user_idx);

                if($result == TRUE)
                {
                    $json['message'] = lang('friend_success');
                    $json['success'] = TRUE;
                }
                else
                {
                    $json['message'] = lang('friend_fail_msg');
                    $json['success'] = FALSE;
                }
            }

        }
        //로그인,권한 체크 end if

        echo json_encode($json);
    }

    // --------------------------------------------------------------------

    private function login_view($assign = array())
    {
        $assign['form_null_check']    = "user_id^{$this->assign['lang']['user_id']}|password^{$this->assign['lang']['password']}";
        $assign['validation_result']  = validation_errors();
        $assign['validation_message'] = ($assign['validation_result'] !== '') ? str_replace("\n", '', $assign['validation_result']) : '';

        $assign['value_list'] = array();
        $assign['value_list']['user_id'] = '';
        $assign['result_msg'] = '';
        $_GET['referer'] = NULL;

        $assign['login_fail_msg'] = lang('deny_allow');
        $this->scope('contents', 'contents/user/login', $assign);
        $this->display('layout');
    }

    // --------------------------------------------------------------------

    /**
     * 탈퇴
     *
     * @author KangMin
     * @since 2014.07.13
     */
    public function unregistered()
    {
        $data = NULL;

        //로그인 상태가 아니거나 권한이 없으면
        if(!defined('USER_INFO_idx'))
        {
            $assign['message'] = lang('unusual_approach');
        }
        else
        {
            $result = $this->users_model->unregistered();

            if($result == TRUE)
            {
                $assign['message'] = lang('success_unregistered');
            }
            else
            {
                $assign['message'] = lang('fatal_error');
            }
        }

        delete_session();

        $assign['redirect'] = '/';

        $this->alert($assign);
    }
}

//EOF
