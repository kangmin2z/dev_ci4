/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {

    // Define changes to default configuration here.
    // For the complete reference:
    // http://docs.ckeditor.com/#!/api/CKEDITOR.config

    config.toolbarGroups = [
        { name: 'document',	   groups: [ 'mode', 'document' ] },
        { name: 'clipboard',   groups: [ 'clipboard', 'undo' ] },
        { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
        { name: 'links' }
    ];

    config.autoParagraph = false;

    //config.enterMode = 2;
    config.shiftEnterMode = 3;
    config.allowedContent = true;

    config.extraPlugins = 'zuploader';

    config.height = 100;

    config.language = 'ko';
};
