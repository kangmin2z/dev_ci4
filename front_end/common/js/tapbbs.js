var tapbbs_viewport = '';

//shuffle
(function ($)
{

    $.fn.shuffle = function ()
    {
        return this.each(function ()
        {
            var items = $(this).children();
            return (items.length) ? $(this).html($.shuffle(items)) : this;
        });
    }

    $.shuffle = function (arr)
    {
        for (var j, x, i = arr.length; i; j = parseInt(Math.random() * i), x = arr[--i], arr[i] = arr[j], arr[j] = x) {
            ;
        }
        return arr;
    }

    tapbbs_viewport = $.cookie('tapbbs_viewport');
})(jQuery);

//---------------------------------------------------------------------------------------

/**
 * 공용 confirm
 * 글삽입등에서 1차 확인을 위한 confirm
 *
 * @authour KangMin
 *
 * @param string
 * @param string
 */
function confirm_really(form_id, message)
{
    if (!message)
	{
        var message = lang['really'];
    }

    jConfirm(message, lang['alert'], function (r)
    {
        if (r) { $("#" + form_id).submit(); }
    });
}

//---------------------------------------------------------------------------------------

/**
 * 공용 form null check
 *
 * @author KangMin
 *
 * @param string
 * @param string 'user_id^아이디|name^이름|nickname^닉네임' 형식으로 넣는다
 *
 * @return bool
 */
function form_null_check(form_id, items_pipe)
{
    var items = new Array();
    items = items_pipe.split('|');
    var items_length = items.length;

    for (var i = 0; i < items_length; i++)
	{
        var temp = items[i].split('^');

        //validation
        if (temp.length != 2)
		{
            jAlert('Error', lang['alert']);
            return false;
        }

        if (!$('#' + form_id + ' #' + temp[0]).val())
		{
            $('#' + form_id + ' #' + temp[0]).focus();
            jAlert(temp[1] + ' ' + lang['essentialness'], lang['alert']);
            return false;
        }
    }

    return true;
}

//---------------------------------------------------------------------------------------

/**
 * 공용 최소글자수 체크
 *
 * @author 배강민
 *
 * @param string form_id
 * @param string 'title^제목^1|contents^내용^1' 형식으로 넣는다.
 *
 * @return bool
 */
function form_minimum_check(form_id, items_pipe)
{
    var items = new Array();
    items = items_pipe.split('|');
    var items_length = items.length;

    for (var i = 0; i < items_length; i++)
	{
        var temp = items[i].split('^');

        //validation
        if (temp.length != 3)
		{
            jAlert('Error', lang['alert']);
            return false;
        }

        if ($('#' + form_id + ' #' + temp[0]).val().length < parseInt(temp[2]))
		{
            $('#' + form_id + ' #' + temp[0]).focus();
            jAlert(temp[1] + ' ' + sprintf(lang['minimum_length'], temp[2]), lang['alert']);
            return false;
        }
    }

    return true;
}

//---------------------------------------------------------------------------------------

/**
 * 댓글 작성
 *
 * @author KangMin
 *
 * @param string
 * @param string
 * @param int
 */
function write_comment(bbs_id, url, timeout_value)
{
    jConfirm(lang['really'], lang['alert'], function (r)
    {
        if (r) {
            $.ajax({
                    type:'POST'
					, url:BASE_URL + 'bbs/write_comment/' + bbs_id
					, data:$("#write_comment_form").serialize()
					, timeout:timeout_value * 1000
					, success:function (data)
                    {
                        var obj = $.parseJSON(data);

                        if (obj.success == true) //성공
                        {
                            url = url + '&scroll=' + $(document).scrollTop();

                            if (obj.page_comment) {
                                url = url + '&page_comment=' + obj.page_comment;
                            }

                            jAlert(obj.message, lang['alert'], function (r)
                            {
                                if (r) { location.href = url; }
                            });
                        } else //실패
                        {
                            jAlert(obj.message, lang['alert']);
                        }
                    }, error     :function (data)
                    {
                        jAlert('Error', lang['alert']);
                    }, beforeSend:function ()
                    {
                        if (tapbbs_viewport === 'mobile')
                        {
                            $.mobile.showPageLoadingMsg();
                        }
                        else
                        {
                            tapbbs_loader('on');
                        }

                    }, complete  :function ()
                    {
                        if (tapbbs_viewport === 'mobile')
                        {
                            $.mobile.hidePageLoadingMsg();
                        }
                        else
                        {
                            tapbbs_loader('off');
                        }

                    }
                });
        }
    });
}

//---------------------------------------------------------------------------------------

/**
 * 댓글호출 및 수정용 세팅
 *
 * @author KangMin
 *
 * @param string
 * @param int
 * @param int
 */
function set_modify_comment_form(bbs_id, idx, timeout_value)
{
    if ($('#hidden_modify_comment_div #idx').val() != '')
	{
        jAlert(lang['modify_single_comment'], lang['alert']);
        return true;
    }

    $.ajax({
            type:"POST"
			, url:BASE_URL + 'bbs/get_comment/' + bbs_id
			, data:'idx=' + idx
			, timeout:timeout_value * 1000
			, success:function (data)
            {
                var obj = $.parseJSON(data);

                if (obj.success == true) //성공
                {
					$('#hidden_modify_comment_div #idx').val(idx);
					$('#hidden_modify_comment_div #comment').text(obj.comment);

					$('#comment_' + idx).hide();
					$('#btn_modify_comment_form_' + idx).hide();
					$('#comment_' + idx + '_modify').html($('#hidden_modify_comment_div').html());
					$('#comment_' + idx + '_modify').show();

                    if (tapbbs_viewport === 'mobile')
                    {
						if(obj.agent_insert == 'P' || obj.agent_last_update == 'P')
						{
							$('#comment_' + idx + '_modify #btn_modify_comment').html(lang['modify'] + ' (PC)');
							$('#comment_' + idx + '_modify #btn_modify_comment').parent().find('.ui-btn-text').html(lang['modify'] + ' (PC)');
							$('#comment_' + idx + '_modify #btn_modify_comment').attr('disabled', true);
							$('#comment_' + idx + '_modify #btn_modify_comment').parent().addClass('ui-disabled');
						}

                        // Autogrow
                        // jquerymobile.js 에 있는 건데, ajax로 해당 textarea를 조작할때는 동작을 못해서 가져옴
                        var modify_comment_textarea = $('#comment_' + idx + '_modify #modify_comment_div #modify_comment_form #comment');
                        if (modify_comment_textarea.is("textarea")) {
                            var extraLineHeight = 15, keyupTimeoutBuffer = 100, keyup = function ()
                                {
                                    var scrollHeight = modify_comment_textarea[ 0 ].scrollHeight, clientHeight = modify_comment_textarea[ 0 ].clientHeight;

                                    if (clientHeight < scrollHeight) {
                                        modify_comment_textarea.height(scrollHeight + extraLineHeight);
                                    }
                                }, keyupTimeout;

                            modify_comment_textarea.keyup(function ()
                            {
                                clearTimeout(keyupTimeout);
                                keyupTimeout = setTimeout(keyup, keyupTimeoutBuffer);
                            });

                            // binding to pagechange here ensures that for pages loaded via
                            // ajax the height is recalculated without user input
                            $(document).one("pagechange", keyup);

                            // Issue 509: the browser is not providing scrollHeight properly until the styles load
                            if ($.trim(modify_comment_textarea.val())) {
                                // bind to the window load to make sure the height is calculated based on BOTH
                                // the DOM and CSS
                                $(window).load(keyup);
                            }
                        }
                        modify_comment_textarea.addClass('ui-focus');
                        modify_comment_textarea.keyup();
                    }
                    else
                    {
                        //var modify_text_id = 'comment_modify_' + idx;
                        //$('#hidden_modify_comment_div #idx').val(idx);

                        //if ($('#' + modify_text_id).size() < 1) {
                        //    $('#modify_comment_div form').append('<textarea id="' + modify_text_id + '" name="comment" rows="7" style="width:100%"></textarea>');
                        //}

						//$('#hidden_modify_comment_div #comment').text(obj.comment);

                        //$('#comment_' + idx).hide();
                        //$('#btn_modify_comment_form_' + idx).hide();
                        //$('#comment_' + idx + '_modify').html($('#hidden_modify_comment_div').html());

                        var a = $('#comment_' + idx + '_modify #comment').ckeditor({
							customConfig : BASE_URL + "front_end/third_party/ckeditor/config_comment.js",
                        	//enterMode : 2,
                        	shiftEnterMode : 3
                        });
                        //$('#comment_' + idx + '_modify').show();
                    }
                }
                else //실패
                {
                    jAlert(obj.message, lang['alert']);
                }
            }, error     :function (data)
            {
                jAlert('Error', lang['alert']);
            }, beforeSend:function ()
            {
                if (tapbbs_viewport === 'mobile')
                {
                    $.mobile.showPageLoadingMsg();
                }
                else
                {
                    tapbbs_loader('on');
                }

            }, complete  :function ()
            {
                if (tapbbs_viewport === 'mobile')
                {
                    $.mobile.hidePageLoadingMsg();
                }
                else
                {
                    tapbbs_loader('off');
                }

            }
        });
}

//---------------------------------------------------------------------------------------

/**
 * 댓글 수정 폼 제거
 *
 * @author KangMin
 *
 * @param int
 */
function remove_modify_comment_form(idx)
{
    $('#hidden_modify_comment_div #idx').val('');
    $('#hidden_modify_comment_div #comment').text('');

    $('#comment_' + idx + '_modify').hide();
    $('#comment_' + idx).show();
    $('#btn_modify_comment_form_' + idx).show();
}

//---------------------------------------------------------------------------------------

/**
 * 댓글 수정/삭제
 *
 * @author KangMin
 *
 * @param string
 * @param string
 * @param string
 * @param int
 */
function modify_comment(type, bbs_id, url, timeout_value)
{
    jConfirm(lang['really'], lang['alert'], function (r)
    {
        if (r)
		{
            $.ajax({
                    type:'POST'
					, url:BASE_URL + 'bbs/' + type + '_comment/' + bbs_id
					, data:$("#modify_comment_form").serialize()
					, timeout:timeout_value * 1000
					, success:function (data)
                    {
                        var obj = $.parseJSON(data);

                        if (obj.success == true) //성공
                        {
                            url = url + '&scroll=' + $(document).scrollTop();

                            if (obj.page_comment)
							{
                                url = url + '&page_comment=' + obj.page_comment;
                            }

                            jAlert(obj.message, lang['alert'], function (r)
                            {
                                if (r) { location.href = url; }
                            });
                        } else //실패
                        {
                            jAlert(obj.message, lang['alert']);
                        }
                    }, error     :function (data)
                    {
                        jAlert('Error', lang['alert']);
                    }, beforeSend:function ()
                    {
                        if (tapbbs_viewport === 'mobile')
                        {
                            $.mobile.showPageLoadingMsg();
                        }
                        else
                        {
                            tapbbs_loader('on');
                        }

                    }, complete  :function ()
                    {
                        if (tapbbs_viewport === 'mobile')
                        {
                            $.mobile.hidePageLoadingMsg();
                        }
                        else
                        {
                            tapbbs_loader('off');
                        }

                    }
                });
        }
    });
}

//---------------------------------------------------------------------------------------

/**
 * 추천
 * article, comment 공용
 *
 * @author KangMin
 *
 * @param string
 * @param string
 * @param int
 * @param int
 */
function vote(type, bbs_id, idx, timeout_value)
{
    var current_vote_count_article = parseInt($('#vote_article').text());

    if (type == 'comment')
	{
        var current_vote_count_comment = parseInt($('#vote_comment_' + idx).text());
        $('#hidden_vote_comment #idx').val(idx);
    }

    $.ajax({
            type:"POST"
			, url:BASE_URL + 'bbs/vote/' + bbs_id
			, data:$("#vote_" + type + "_form").serialize()
			, timeout:timeout_value * 1000
			, success:function (data)
            {
                var obj = $.parseJSON(data);

                if (obj.success == true) //성공
                {
                    jAlert(obj.message, lang['alert']);

                    if (type == 'article')
					{
                        $('#vote_article').html(current_vote_count_article + 1);
                        //$('#btn_vote_article').hide();
                    } else {
                        if (type == 'comment')
						{
                            $('#vote_comment_' + idx).html(current_vote_count_comment + 1);
                            //$('#btn_vote_comment_'+idx).hide();
                        } else {
                            jAlert('Error', lang['alert']);
                        }
                    }
                } else //실패
                {
                    jAlert(obj.message, lang['alert']);
                }
            }, error     :function (data)
            {
                jAlert('Error', lang['alert']);
            }, beforeSend:function ()
            {
                if (tapbbs_viewport === 'mobile')
                {
                    $.mobile.showPageLoadingMsg();
                }
                else
                {
                    tapbbs_loader('on');
                }
            }, complete  :function ()
            {
                if (tapbbs_viewport === 'mobile')
                {
                    $.mobile.hidePageLoadingMsg();
                }
                else
                {
                    tapbbs_loader('off');
                }

            }
        });
}

//---------------------------------------------------------------------------------------

/**
 * 스크랩
 *
 * @author KangMin
 *
 * @param string
 * @param int
 * @param int
 */
function scrap(bbs_id, idx, timeout_value)
{
    var current_scrap_count = parseInt($('#scrap').text());

    $.ajax({
            type:"POST"
			, url:BASE_URL + 'bbs/scrap/' + bbs_id
			, data:'idx=' + idx
			, timeout:timeout_value * 1000
			, success:function (data)
            {
                var obj = $.parseJSON(data);

                if (obj.success == true) //성공
                {
                    $('#scrap').html(current_scrap_count + 1);
                    jAlert(obj.message, lang['alert']);
                } else //실패
                {
                    jAlert(obj.message, lang['alert']);
                }
            }, error     :function (data)
            {
                jAlert('Error', lang['alert']);
            }, beforeSend:function ()
            {
                if (tapbbs_viewport === 'mobile')
                {
                    $.mobile.showPageLoadingMsg();
                }
                else
                {
                    tapbbs_loader('on');
                }

            }, complete  :function ()
            {
                if (tapbbs_viewport === 'mobile')
                {
                    $.mobile.hidePageLoadingMsg();
                }
                else
                {
                    tapbbs_loader('off');
                }

            }
        });
}

//---------------------------------------------------------------------------------------

/**
 * 스크랩/즐겨찾기 삭제
 *
 * @author KangMin
 *
 * @param int
 * @param int
 * @param int
 */
function delete_url(idx, url, timeout_value)
{
    jConfirm(lang['really'], lang['alert'], function (r)
    {
        if (r)
		{
            $('#delete_url_form #idx').val(idx);

            $.ajax({
                    type:'POST'
					, url:BASE_URL + 'user/delete_url'
					, data:$("#delete_url_form").serialize()
					, timeout:timeout_value * 1000
					, success:function (data)
                    {
                        var obj = $.parseJSON(data);

                        if (obj.success == true) //성공
                        {
                            jAlert(obj.message, lang['alert'], function (r)
                            {
                                if (r) { location.href = url; }
                            });
                        } else //실패
                        {
                            jAlert(obj.message, lang['alert']);
                        }
                    }, error     :function (data)
                    {
                        jAlert('Error', lang['alert']);
                    }, beforeSend:function ()
                    {
                        if (tapbbs_viewport === 'mobile')
                        {
                            $.mobile.showPageLoadingMsg();
                        }
                        else
                        {
                            tapbbs_loader('on');
                        }

                    }, complete  :function ()
                    {
                        if (tapbbs_viewport === 'mobile')
                        {
                            $.mobile.hidePageLoadingMsg();
                        }
                        else
                        {
                            tapbbs_loader('off');
                        }

                    }
                });
        }
    });
}

//---------------------------------------------------------------------------------------

/**
 * html 허용 태그 alert
 *
 * @author KangMin
 *
 * @param string
 * @param string
 */

function allow_html_tag_msg(id, msg)
{
    if ($('#' + id).is(':checked'))
	{
        jAlert(msg, lang['alert']);
    }
}

//---------------------------------------------------------------------------------------

/**
 * 메시지 전송
 *
 * @author KangMin
 *
 * @param int
 */
function send_message_exec(timeout_value)
{
    jConfirm(lang['really'], lang['alert'], function (r)
    {
        if (r) {
            $.ajax({
                    type:'POST'
					, url:BASE_URL + 'user/send_message_exec'
					, data:$("#send_message_form").serialize()
					, timeout:timeout_value * 1000
					, success:function (data)
                    {
                        var obj = $.parseJSON(data);

                        if (obj.success == true) //성공
                        {
                            jAlert(obj.message, lang['alert'], function (r)
                            {
                                $('#send_message_form #contents').val('');
                                if (r) { $('#btn_close').click(); }
                            });
                        } else //실패
                        {
                            jAlert(obj.message, lang['alert'], function (r)
                            {
                                if (r) { $('#btn_close').click(); }
                            });
                        }
                    }, error     :function (data)
                    {
                        jAlert('Error', lang['alert']);
                    }, beforeSend:function ()
                    {
                        if (tapbbs_viewport === 'mobile')
                        {
                            $.mobile.showPageLoadingMsg();
                        }
                        else
                        {
                            tapbbs_loader('on');
                        }

                    }, complete  :function ()
                    {
                        if (tapbbs_viewport === 'mobile')
                        {
                            $.mobile.hidePageLoadingMsg();
                        }
                        else
                        {
                            tapbbs_loader('off');
                        }

                    }
                });
        }
    });
}

//---------------------------------------------------------------------------------------

/**
 * 메시지 삭제
 *
 * @author KangMin
 *
 * @param string
 * @param int
 */
function delete_message(url, timeout_value)
{
    jConfirm(lang['really'], lang['alert'], function (r)
    {
        if (r) {
            $.ajax({
                    type:'POST',
                    url:BASE_URL + 'user/delete_message',
                    data:$("#delete_message_form").serialize(),
                    timeout:timeout_value * 1000,
                    success:function (data)
                    {
                        var obj = $.parseJSON(data);

                        if (obj.success == true) //성공
                        {
                            jAlert(obj.message, lang['alert'], function (r)
                            {
                                if (r) { location.href = url; }
                            });
                        } else //실패
                        {
                            jAlert(obj.message, lang['alert']);
                        }
                    }, error     :function (data)
                    {
                        jAlert('Error', lang['alert']);
                    }, beforeSend:function ()
                    {
                        if (tapbbs_viewport === 'mobile')
                        {
                            $.mobile.showPageLoadingMsg();
                        }
                        else
                        {
                            tapbbs_loader('on');
                        }

                    }, complete  :function ()
                    {
                        if (tapbbs_viewport === 'mobile')
                        {
                            $.mobile.hidePageLoadingMsg();
                        }
                        else
                        {
                            tapbbs_loader('off');
                        }

                    }
                });
        }
    });
}

//---------------------------------------------------------------------------------------

/**
 * 읽지 않은 메시지 갯수 호출해서 알림
 *
 * @author KangMin
 */
function set_message_count(id_div, id_count, timeout_value)
{
    $.ajax({
            type:"POST",
            url:BASE_URL + 'user/get_message_count',
            timeout:timeout_value * 1000,
            success:function (data)
            {
                var obj = $.parseJSON(data);

                if (obj.message_count > 0) {
                    $('#' + id_count).html(obj.message_count);
                    $('#' + id_div).slideDown('slow');
                    //$('#'+id_div).css('display', 'block');
                } else {
                    $('#' + id_count).html(obj.message_count);
                    $('#' + id_div).slideUp('slow');
                    //$('#'+id_div).css('display', 'none');
                }
            }
        });
}

//---------------------------------------------------------------------------------------

/**
 * 친구추가
 *
 * @author KangMin
 *
 * @param int
 * @param int
 */
function add_friend(friend, timeout_value)
{
    $.ajax({
            type:"POST",
            url:BASE_URL + 'user/add_friend',
            data:'friend=' + friend,
            timeout:timeout_value * 1000,
            success:function (data)
            {
                var obj = $.parseJSON(data);

                if (obj.success == true) //성공
                {
                    jAlert(obj.message, lang['alert']);
                } else //실패
                {
                    jAlert(obj.message, lang['alert']);
                }
            }, error     :function (data)
            {
                jAlert('Error', lang['alert']);
            }, beforeSend:function ()
            {
                if (tapbbs_viewport === 'mobile')
                {
                    $.mobile.showPageLoadingMsg();
                }
                else
                {
                    tapbbs_loader('on');
                }

            }, complete  :function ()
            {
                if (tapbbs_viewport === 'mobile')
                {
                    $.mobile.hidePageLoadingMsg();
                }
                else
                {
                    tapbbs_loader('off');
                }

            }
        });
}

//---------------------------------------------------------------------------------------

/**
 * 친구 삭제
 *
 * @author KangMin
 *
 * @param int
 * @param int
 * @param int
 */
function delete_friend(idx, url, timeout_value)
{
    jConfirm(lang['really'], lang['alert'], function (r)
    {
        if (r) {
            $('#delete_friend_form #idx').val(idx);

            $.ajax({
                    type:'POST',
                    url:BASE_URL + 'user/delete_friend',
                    data:$("#delete_friend_form").serialize(),
                    timeout:timeout_value * 1000,
                    success:function (data)
                    {
                        var obj = $.parseJSON(data);

                        if (obj.success == true) //성공
                        {
                            jAlert(obj.message, lang['alert'], function (r)
                            {
                                if (r) { location.href = url; }
                            });
                        } else //실패
                        {
                            jAlert(obj.message, lang['alert']);
                        }
                    }, error     :function (data)
                    {
                        jAlert('Error', lang['alert']);
                    }, beforeSend:function ()
                    {
                        if (tapbbs_viewport === 'mobile')
                        {
                            $.mobile.showPageLoadingMsg();
                        }
                        else
                        {
                            tapbbs_loader('on');
                        }

                    }, complete  :function ()
                    {
                        if (tapbbs_viewport === 'mobile')
                        {
                            $.mobile.hidePageLoadingMsg();
                        }
                        else
                        {
                            tapbbs_loader('off');
                        }

                    }
                });
        }
    });
}

function tapbbs_loader(mode)
{
    if (mode === 'on')
    {
        if ($('#tapbbs_loader').size() < 1) {
            $('<div id="tapbbs_loader"></div>').insertBefore($($('body').children().get(0)));
        }
        $('body').mask("Loading...", 1000);
    }
    else
    {
        $('body').unmask();
    }
}

//---------------------------------------------------------------------------------------

$(document).ready(function(){
    $('#view_category').change(function(){
        var $this = $(this);
        if ($this[0].tagName.toUpperCase() === 'SELECT') {
            window.location.href = '?view_category=' + $this.val() + '&lists_style=' + $('#lists_style').val();
        }
    });

    $('#change_user_cookie').change(function(){
        $.post('/dev/force_login', {change_user_cookie : $(this).val()}, function(){
            window.location.reload();
        });
    });

    $('a[role="friends"]').click(function(){
        var $this = $(this);
        $('#print_receiver_name').html($this.attr('title'));
        $('#receiver').val($this.attr('idx'));
    });

    $('a[role="message_list"]').click(function(){
        var $this = $(this);
        $.post(BASE_URL + 'user/get_message', {idx : $this.attr('idx'), search : $this.attr('search')}, function(response){

            var $container = $('#message_detail');

            if (response['title'] != '') {
                $container.find('span[name="title"]').html(response['title']);
            }

            var search = $('#search').val();
            var kind = (search == 'receive') ? 'FROM' : 'TO';
            var ajax_timeout = $('#ajax_timeout').val();

            $container.find('span[name="kind"]').html(kind);
            $container.find('span[name="name"]').html(response['print_name']);

            if(response['print_receive_date'])
            {
                $container.find('span[name="receive_date"]').html(lang['timestamp_receive'] + ' : ' + response['print_receive_date']);
            }

            $container.find('div[name="contents"]').html(response['contents']);
            var $reply_link = $container.find('a[name="reply_link"]');
            $reply_link.unbind('click').click(function(){
                $container.modal('hide');
                $('#print_receiver_name').html(response['print_name']);
                $('#receiver').val(response['sender_user_idx']);
                $('#send_message').modal('show');
            });
            if (search == 'receive') {
                $reply_link.removeClass('hide');
            } else {
                $reply_link.addClass('hide');
            }
            $container.find('a[name="delete_link"]').unbind('click').click(function(){
                $('#message_idx').val(response['idx']);
                delete_message(BASE_URL + 'user/message/?search=' + search, ajax_timeout);
            });
            $container.modal('show');

        }, 'json');
    });


});

/**
 * URL 이동시 현재 페이지 유지하면서 파라메터만 추가하기
 */
function updateURLParameter(param, paramVal, paramUrl)
{
    var url = (typeof paramUrl == 'undefined') ? window.location.href : paramUrl;

    var TheAnchor = null;
    var newAdditionalURL = "";
    var tempArray = url.split("?");
    var baseURL = tempArray[0];
    var additionalURL = tempArray[1];
    var temp = "";

    if (additionalURL)
    {
        var tmpAnchor = additionalURL.split("#");
        var TheParams = tmpAnchor[0];
        TheAnchor = tmpAnchor[1];
        if(TheAnchor)
            additionalURL = TheParams;

        tempArray = additionalURL.split("&");

        for (i=0; i<tempArray.length; i++)
        {
            if(tempArray[i].split('=')[0] != param)
            {
                newAdditionalURL += temp + tempArray[i];
                temp = "&";
            }
        }
    }
    else
    {
        var tmpAnchor = baseURL.split("#");
        var TheParams = tmpAnchor[0];
        TheAnchor  = tmpAnchor[1];

        if(TheParams)
            baseURL = TheParams;
    }

    if(TheAnchor)
        paramVal += "#" + TheAnchor;

    var rows_txt = temp + "" + param + "=" + paramVal;
    return baseURL + "?" + newAdditionalURL + rows_txt;
}

/**
 * 탈퇴
 */
function unregistered()
{
    jConfirm(lang['really'], lang['alert'], function (r)
    {
        if (r) { location.href = BASE_URL + "user/unregistered"; }
    });
}

//---------------------------------------------------------------------------------------
//---------------------------------------------------------------------------------------
//---------------------------------------------------------------------------------------

//phpjs.org
//phpjs.org
//phpjs.org

function rawurlencode(str)
{
    // http://kevin.vanzonneveld.net
    // +   original by: Brett Zamir (http://brett-zamir.me)
    // +      input by: travc
    // +      input by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: Michael Grier
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Ratheous
    // +      reimplemented by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Joris
    // +      reimplemented by: Brett Zamir (http://brett-zamir.me)
    // %          note 1: This reflects PHP 5.3/6.0+ behavior
    // %        note 2: Please be aware that this function expects to encode into UTF-8 encoded strings, as found on
    // %        note 2: pages served as UTF-8
    // *     example 1: rawurlencode('Kevin van Zonneveld!');
    // *     returns 1: 'Kevin%20van%20Zonneveld%21'
    // *     example 2: rawurlencode('http://kevin.vanzonneveld.net/');
    // *     returns 2: 'http%3A%2F%2Fkevin.vanzonneveld.net%2F'
    // *     example 3: rawurlencode('http://www.google.nl/search?q=php.js&ie=utf-8&oe=utf-8&aq=t&rls=com.ubuntu:en-US:unofficial&client=firefox-a');
    // *     returns 3: 'http%3A%2F%2Fwww.google.nl%2Fsearch%3Fq%3Dphp.js%26ie%3Dutf-8%26oe%3Dutf-8%26aq%3Dt%26rls%3Dcom.ubuntu%3Aen-US%3Aunofficial%26client%3Dfirefox-a'
    str = (str + '').toString();

    // Tilde should be allowed unescaped in future versions of PHP (as reflected below), but if you want to reflect current
    // PHP behavior, you would need to add ".replace(/~/g, '%7E');" to the following.
    return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').replace(/\)/g, '%29').replace(/\*/g, '%2A');
}

function str_replace(search, replace, subject, count)
{
    // http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Gabriel Paderni
    // +   improved by: Philip Peterson
    // +   improved by: Simon Willison (http://simonwillison.net)
    // +    revised by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
    // +   bugfixed by: Anton Ongson
    // +      input by: Onno Marsman
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +    tweaked by: Onno Marsman
    // +      input by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   input by: Oleg Eremeev
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Oleg Eremeev
    // %          note 1: The count parameter must be passed as a string in order
    // %          note 1:  to find a global variable in which the result will be given
    // *     example 1: str_replace(' ', '.', 'Kevin van Zonneveld');
    // *     returns 1: 'Kevin.van.Zonneveld'
    // *     example 2: str_replace(['{name}', 'l'], ['hello', 'm'], '{name}, lars');
    // *     returns 2: 'hemmo, mars'
    var i = 0, j = 0, temp = '', repl = '', sl = 0, fl = 0, f = [].concat(search), r = [].concat(replace), s = subject, ra = Object.prototype.toString.call(r) === '[object Array]', sa = Object.prototype.toString.call(s) === '[object Array]';
    s = [].concat(s);
    if (count) {
        this.window[count] = 0;
    }

    for (i = 0, sl = s.length; i < sl; i++) {
        if (s[i] === '') {
            continue;
        }
        for (j = 0, fl = f.length; j < fl; j++) {
            temp = s[i] + '';
            repl = ra ? (r[j] !== undefined ? r[j] : '') : r[0];
            s[i] = (temp).split(f[j]).join(repl);
            if (count && s[i] !== temp) {
                this.window[count] += (temp.length - s[i].length) / f[j].length;
            }
        }
    }
    return sa ? s : s[0];
}

function base64_encode(data)
{
    // http://kevin.vanzonneveld.net
    // +   original by: Tyler Akins (http://rumkin.com)
    // +   improved by: Bayron Guevara
    // +   improved by: Thunder.m
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Pellentesque Malesuada
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Rafał Kukawski (http://kukawski.pl)
    // *     example 1: base64_encode('Kevin van Zonneveld');
    // *     returns 1: 'S2V2aW4gdmFuIFpvbm5ldmVsZA=='
    // mozilla has this native
    // - but breaks in 2.0.0.12!
    //if (typeof this.window['atob'] == 'function') {
    //    return atob(data);
    //}
    var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    var o1, o2, o3, h1, h2, h3, h4, bits, i = 0, ac = 0, enc = "", tmp_arr = [];

    if (!data) {
        return data;
    }

    do { // pack three octets into four hexets
        o1 = data.charCodeAt(i++);
        o2 = data.charCodeAt(i++);
        o3 = data.charCodeAt(i++);

        bits = o1 << 16 | o2 << 8 | o3;

        h1 = bits >> 18 & 0x3f;
        h2 = bits >> 12 & 0x3f;
        h3 = bits >> 6 & 0x3f;
        h4 = bits & 0x3f;

        // use hexets to index into b64, and append result to encoded string
        tmp_arr[ac++] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
    } while (i < data.length);

    enc = tmp_arr.join('');

    var r = data.length % 3;

    return (r ? enc.slice(0, r - 3) : enc) + '==='.slice(r || 3);

}

function sprintf () {
  // http://kevin.vanzonneveld.net
  // +   original by: Ash Searle (http://hexmen.com/blog/)
  // + namespaced by: Michael White (http://getsprink.com)
  // +    tweaked by: Jack
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +      input by: Paulo Freitas
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +      input by: Brett Zamir (http://brett-zamir.me)
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   improved by: Dj
  // +   improved by: Allidylls
  // *     example 1: sprintf("%01.2f", 123.1);
  // *     returns 1: 123.10
  // *     example 2: sprintf("[%10s]", 'monkey');
  // *     returns 2: '[    monkey]'
  // *     example 3: sprintf("[%'#10s]", 'monkey');
  // *     returns 3: '[####monkey]'
  // *     example 4: sprintf("%d", 123456789012345);
  // *     returns 4: '123456789012345'
  var regex = /%%|%(\d+\$)?([-+\'#0 ]*)(\*\d+\$|\*|\d+)?(\.(\*\d+\$|\*|\d+))?([scboxXuideEfFgG])/g;
  var a = arguments,
    i = 0,
    format = a[i++];

  // pad()
  var pad = function (str, len, chr, leftJustify) {
    if (!chr) {
      chr = ' ';
    }
    var padding = (str.length >= len) ? '' : Array(1 + len - str.length >>> 0).join(chr);
    return leftJustify ? str + padding : padding + str;
  };

  // justify()
  var justify = function (value, prefix, leftJustify, minWidth, zeroPad, customPadChar) {
    var diff = minWidth - value.length;
    if (diff > 0) {
      if (leftJustify || !zeroPad) {
        value = pad(value, minWidth, customPadChar, leftJustify);
      } else {
        value = value.slice(0, prefix.length) + pad('', diff, '0', true) + value.slice(prefix.length);
      }
    }
    return value;
  };

  // formatBaseX()
  var formatBaseX = function (value, base, prefix, leftJustify, minWidth, precision, zeroPad) {
    // Note: casts negative numbers to positive ones
    var number = value >>> 0;
    prefix = prefix && number && {
      '2': '0b',
      '8': '0',
      '16': '0x'
    }[base] || '';
    value = prefix + pad(number.toString(base), precision || 0, '0', false);
    return justify(value, prefix, leftJustify, minWidth, zeroPad);
  };

  // formatString()
  var formatString = function (value, leftJustify, minWidth, precision, zeroPad, customPadChar) {
    if (precision != null) {
      value = value.slice(0, precision);
    }
    return justify(value, '', leftJustify, minWidth, zeroPad, customPadChar);
  };

  // doFormat()
  var doFormat = function (substring, valueIndex, flags, minWidth, _, precision, type) {
    var number;
    var prefix;
    var method;
    var textTransform;
    var value;

    if (substring === '%%') {
      return '%';
    }

    // parse flags
    var leftJustify = false,
      positivePrefix = '',
      zeroPad = false,
      prefixBaseX = false,
      customPadChar = ' ';
    var flagsl = flags.length;
    for (var j = 0; flags && j < flagsl; j++) {
      switch (flags.charAt(j)) {
      case ' ':
        positivePrefix = ' ';
        break;
      case '+':
        positivePrefix = '+';
        break;
      case '-':
        leftJustify = true;
        break;
      case "'":
        customPadChar = flags.charAt(j + 1);
        break;
      case '0':
        zeroPad = true;
        break;
      case '#':
        prefixBaseX = true;
        break;
      }
    }

    // parameters may be null, undefined, empty-string or real valued
    // we want to ignore null, undefined and empty-string values
    if (!minWidth) {
      minWidth = 0;
    } else if (minWidth === '*') {
      minWidth = +a[i++];
    } else if (minWidth.charAt(0) == '*') {
      minWidth = +a[minWidth.slice(1, -1)];
    } else {
      minWidth = +minWidth;
    }

    // Note: undocumented perl feature:
    if (minWidth < 0) {
      minWidth = -minWidth;
      leftJustify = true;
    }

    if (!isFinite(minWidth)) {
      throw new Error('sprintf: (minimum-)width must be finite');
    }

    if (!precision) {
      precision = 'fFeE'.indexOf(type) > -1 ? 6 : (type === 'd') ? 0 : undefined;
    } else if (precision === '*') {
      precision = +a[i++];
    } else if (precision.charAt(0) == '*') {
      precision = +a[precision.slice(1, -1)];
    } else {
      precision = +precision;
    }

    // grab value using valueIndex if required?
    value = valueIndex ? a[valueIndex.slice(0, -1)] : a[i++];

    switch (type) {
    case 's':
      return formatString(String(value), leftJustify, minWidth, precision, zeroPad, customPadChar);
    case 'c':
      return formatString(String.fromCharCode(+value), leftJustify, minWidth, precision, zeroPad);
    case 'b':
      return formatBaseX(value, 2, prefixBaseX, leftJustify, minWidth, precision, zeroPad);
    case 'o':
      return formatBaseX(value, 8, prefixBaseX, leftJustify, minWidth, precision, zeroPad);
    case 'x':
      return formatBaseX(value, 16, prefixBaseX, leftJustify, minWidth, precision, zeroPad);
    case 'X':
      return formatBaseX(value, 16, prefixBaseX, leftJustify, minWidth, precision, zeroPad).toUpperCase();
    case 'u':
      return formatBaseX(value, 10, prefixBaseX, leftJustify, minWidth, precision, zeroPad);
    case 'i':
    case 'd':
      number = +value || 0;
      number = Math.round(number - number % 1); // Plain Math.round doesn't just truncate
      prefix = number < 0 ? '-' : positivePrefix;
      value = prefix + pad(String(Math.abs(number)), precision, '0', false);
      return justify(value, prefix, leftJustify, minWidth, zeroPad);
    case 'e':
    case 'E':
    case 'f': // Should handle locales (as per setlocale)
    case 'F':
    case 'g':
    case 'G':
      number = +value;
      prefix = number < 0 ? '-' : positivePrefix;
      method = ['toExponential', 'toFixed', 'toPrecision']['efg'.indexOf(type.toLowerCase())];
      textTransform = ['toString', 'toUpperCase']['eEfFgG'.indexOf(type) % 2];
      value = prefix + Math.abs(number)[method](precision);
      return justify(value, prefix, leftJustify, minWidth, zeroPad)[textTransform]();
    default:
      return substring;
    }
  };

  return format.replace(regex, doFormat);
}