<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Page extends MY_Controller
{
    public function index()
    {
        $segments = $this->uri->segment_array();

        $paths = array();
        foreach ($segments as $i => $segment)
        {

            if ($i == 1)
            {
                continue;
            }

            $paths[] = $segment;
        }

        $tpl_path = (!empty($paths[0])) ? implode('/', $paths) : '';

        if (empty($tpl_path) OR file_exists($this->tpl->template_dir . '/' . $tpl_path . $this->tpl_ext) === FALSE) {
            show_404();
        }

        $assign = array();

        $this->scope('contents', $tpl_path, $assign);
        $this->display('layout');
    }
}

//EOF