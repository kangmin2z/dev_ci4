$(document).ready(function(){
    if (document.cookie.indexOf("theme_title") >= 0) {
        if (tapbbs_viewport === 'mobile')
        {
            $('#header').prepend(
                " <div id = 'remove_theme_cookie_bar' style = 'padding-left: 5px'>["+$.cookie('theme_title')+"] "+lang['theme_preview_mode'] +
                " <a href='#' onclick='javascript:this.blur();remove_theme_cookies();$(this).parent().hide();return false;'>["+lang['close']+"]</a></div>"
            );
        }
        else
        {
            $('body').prepend(
                "<div id='remove_theme_cookie_bar' class='alert alert-error' style='margin-bottom: 0;'>" +
                " <strong>["+$.cookie('theme_title')+"] "+lang['theme_preview_mode']+"</strong>" +
                " <a href='#' onclick='javascript:this.blur();remove_theme_cookies();$(this).parent().hide();return false;'>["+lang['close']+"]</a>" +
                "<button type='button' class='close' data-dismiss='alert'>&times;</button></div>");

            $('#remove_theme_cookie_bar').bind('closed', function () {
                remove_theme_cookies();
            });
        }
    }
});

function remove_theme_cookies()
{
    var options = {path : '/'};

    $.removeCookie('theme_type', options);
    $.removeCookie('theme_title', options);
    $.removeCookie('theme_folder_name', options);
}