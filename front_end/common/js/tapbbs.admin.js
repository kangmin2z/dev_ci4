//팝업들.. 공용으로 쓰게하려다가 쪼개야할 수도 있을거 같아서 그냥 따로.. 뭐 대단한거 아니니 미관상 좋지 않아도 이해해주기

//세팅 리비젼
function setting_revision(idx)
{
	window.open(BASE_URL+'admin/setting/revision?idx='+idx, '_blank', 'width=700,height=700,scrollbars=yes');
}

//게시판 설정 상세
function bbs_setting_detail(bbs_idx)
{
	window.open(BASE_URL+'admin/bbs/setting_detail?bbs_idx='+bbs_idx, '_blank', 'width=900,height=700,scrollbars=yes');
}

//게시판 설정 리비젼
function bbs_setting_revision(idx)
{
	window.open(BASE_URL+'admin/bbs/setting_revision?idx='+idx, '_blank', 'width=700,height=700,scrollbars=yes');
}

//게시판 카테고리
function bbs_category(bbs_idx)
{
	window.open(BASE_URL+'admin/bbs/category?bbs_idx='+bbs_idx, '_blank', 'width=700,height=700,scrollbars=yes');
}

//게시판 카테고리 리비젼
function bbs_category_revision(idx)
{
	window.open(BASE_URL+'admin/bbs/category_revision?idx='+idx, '_blank', 'width=700,height=700,scrollbars=yes');
}

//회원그룹 리비젼
function users_group_revision(idx)
{
	window.open(BASE_URL+'admin/users/group_revision?idx='+idx, '_blank', 'width=700,height=700,scrollbars=yes');
}

//회원정보 상세
function users_detail(idx)
{
	window.open(BASE_URL+'admin/users/detail?idx='+idx, '_blank', 'width=700,height=700,scrollbars=yes');
}

//포인트내역
function users_point(user_idx)
{
	window.open(BASE_URL+'admin/users/point?user_idx='+user_idx, '_blank', 'width=700,height=700,scrollbars=yes');
}

//게시물 상세
function bbs_modify(idx)
{
	window.open(BASE_URL+'admin/bbs/modify?idx='+idx, '_blank', 'width=900,height=700,scrollbars=yes');
}

//댓글 리비젼
function bbs_comment_revision(idx)
{
	window.open(BASE_URL+'admin/bbs/comment_revision?idx='+idx, '_blank', 'width=700,height=700,scrollbars=yes');
}

//글내용 리비젼
function bbs_contents_revision(idx)
{
	window.open(BASE_URL+'admin/bbs/contents_revision?idx='+idx, '_blank', 'width=700,height=700,scrollbars=yes');
}

//아티클 리비젼
function bbs_article_revision(idx)
{
	window.open(BASE_URL+'admin/bbs/article_revision?idx='+idx, '_blank', 'width=700,height=700,scrollbars=yes');
}

//테마 미리보기 쿠키세팅
function set_cookie_theme_preview(type, idx)
{
    url = BASE_URL+'admin/setting/set_cookie_theme_preview';
    param = 'type='+type+'&idx='+idx;

    viewport = (type == 'M') ? 'mobile' : 'pc';

    $.get(url, param, function(result){
        if(result == 'TRUE')
        {
            window.open(BASE_URL+'?viewport='+viewport, '_blank');
        }
        else
        {
            jAlert('Error', lang['alert']);
        }
    });
}

//install db test
function test_db(base_url_param)
{
    $.ajax(
        {
            type:"POST"
            , url:base_url_param+'admin/install/test_db'
            , data:$("#test_db_form").serialize()
            , success:function(data)
                        {
                            var obj = $.parseJSON(data);

                            $('#innodb').hide();
                            $('#admin_account').hide();
                            $('#submit').hide();
                            $('#trigger_usable').html(lang['install_trigger_basic_msg']);

                            if(obj.version)
                            {
                                if(obj.version_usable == true)
                                {
                                    if(obj.utf8 == true)
                                    {
                                        if(obj.innodb == true)
                                        {
                                            $('#innodb').show();
                                            $('input[name="engine"]').filter(function(){
                                                var obj = $(this);
                                                if (obj.val().toUpperCase() === 'INNODB') {
                                                    obj.attr('checked', true);
                                                }
                                            });
                                        }

										$('#admin_account').show();
                                        $('#submit').show();

                                        if(obj.trigger == true)
                                        {
                                            $('#trigger_usable').html(lang['install_trigger_available']);
                                        }
                                        else
                                        {
                                            $('#trigger_usable').html(lang['install_trigger_unavailable']);
                                        }

                                        if($('#check_default').val() == '1')
                                        {
                                            jAlert(lang['install_db_connect_success']);
                                        }
                                        else
                                        {
                                            jAlert(lang['install_db_connect_success_but_minimum_requirements']);
                                        }
                                    }
                                    else
                                    {
                                        jAlert(lang['install_db_utf8']);
                                    }
                                }
                                else
                                {
                                    jAlert(lang['install_db_version']);
                                }
                            }
                            else
                            {
                                jAlert(lang['install_db_connect_failed']);
                            }
                        }
            , error:function(data)
                        {
                            jAlert('Error');
                        }
            , beforeSend:function()
                        {
                            $("#main").mask("Waiting...");
                        }
            , complete:function()
                        {
                            $("#main").unmask();
                        }
        }
    );
}