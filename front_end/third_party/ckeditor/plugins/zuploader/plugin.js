CKEDITOR.plugins.add(
    'zuploader',
    {
        icons : 'zuploader',
        init  : function (editor)
        {
            editor.addCommand(
                'zuploaderCommand',
                new CKEDITOR.command(
                    editor,
                    {
                        exec : function (editor)
                        {
                            var editor_type = editor.name === 'comment' ? 'comment' : '';
                            var action_url = BASE_URL + 'bbs/upload_file/' + $('#bbs_id').val();
                            if (editor_type === 'comment') {
                                action_url += '/comment';
                            }
                            $('body').append('<div id="image_uploader_container" style="display: none"></div>');
                            var image_uploader = new qq.FileUploader(
                                {
                                    is_image          : true,
                                    element           : document.getElementById('image_uploader_container'),
                                    action            : action_url,
                                    allowedExtensions : [
                                        'gif',
                                        'jpg',
                                        'png'
                                    ],
                                    maxConnections    : 3,
                                    onComplete        : function (id, filename, json)
                                    {
                                        if (json.success == true) {

                                            $('#wysiwyg_files').val($('#wysiwyg_files').val() + base64_encode(rawurlencode(filename)) + ':' + json.real_file_name + '|');
                                            var img = CKEDITOR.dom.element.createFromHtml('<p><img src="' + json.base_url + 'uploads/' + json.real_file_name + '"></p>');

                                            if (editor_type !== 'comment') {
                                                CKEDITOR.instances.contents.insertElement(img);
                                            } else {
                                                CKEDITOR.instances.comment.insertElement(img);
                                            }
                                        }
                                        $('#image_uploader_container').find('.qq-upload-list').hide();
                                    },
                                    messages          : {
                                        //typeError: lang['upload_file_error_extension']+"\n§extensions§",
                                        sizeError    : "§file§ is too large, maximum file size is §sizeLimit§.",
                                        minSizeError : "§file§ is too small, minimum file size is §minSizeLimit§.",
                                        emptyError   : "§file§ is empty, please select files again without it." //onLeave: lang['upload_file_being']
                                    },
                                    debug             : false
                                }
                            );

                            var $uploader = $('#image_uploader_container');
                            $uploader.hide();
                            $uploader.find('input[type="file"]').click();
                        }
                    }
                )
            );

            editor.ui.addButton(
                'zuploader',
                {
                    label   : 'ZzanLAB Uploader',
                    command : 'zuploaderCommand',
                    toolbar : 'zuploader'
                }
            );
        }
    }
);