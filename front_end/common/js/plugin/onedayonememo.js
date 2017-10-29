//하루한마디 상세내용보기 토글
function toggle_onedayonememo_detail(idx)
{
	$.mobile.showPageLoadingMsg();

	if($('#onedayonememo_detail_'+idx).css('display') == 'none')
	{
		$('#onedayonememo_detail_'+idx).show();
		$('#onedayonememo_'+idx+' span').removeClass('ui-icon-arrow-d').addClass('ui-icon-arrow-u');
	}
	else
	{
		$('#onedayonememo_detail_'+idx).hide();
		$('#onedayonememo_'+idx+' span').removeClass('ui-icon-arrow-u').addClass('ui-icon-arrow-d');
	}

	$.mobile.hidePageLoadingMsg();
}

//---------------------------------------------------------------------------------------

//하루한마디 등록
function write_onedayonememo(url, timeout_value)
{
	jConfirm(lang['really'], lang['alert'], function(r) { if(r)
	{
		$.ajax(
			{
				type:'POST'
				, url:BASE_URL+'plugin/onedayonememo/write'
				, data:$("#onedayonememo_write_form").serialize()
				, timeout:timeout_value*1000
				, success:function(data) 
							{
								var obj = $.parseJSON(data);

								if(obj.success == true) //성공
								{
									jAlert(obj.message, lang['alert'], function(r) { if(r) { location.href=url; } });
								}
								else //실패
								{
									jAlert(obj.message, lang['alert']);
								}
							}
				, error:function(data)
						{
							jAlert('Error', lang['alert']);
						}
				, beforeSend:function()
						{
                            if (tapbbs_viewport === 'mobile')
                            {
                                $.mobile.showPageLoadingMsg();
                            }
                            else
                            {
                                tapbbs_loader('on');
                            }
						}
				, complete:function()
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
			}
		);
	}});
}

$(document).ready(function(){
    var $buttons = $('#onedayonememo_write_div .btn-toolbar').find('button');
    $buttons.click(function(){
        $buttons.removeClass('btn-info');
        var $this = $(this);
        $('#point_gamble').val($this.attr('title'));
        $this.addClass('btn-info');
    });
});