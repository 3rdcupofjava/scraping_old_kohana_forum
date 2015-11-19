<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Main extends Controller {

    public function action_index() {
        include 'simple_html_dom.php';
        
        header( 'Content-type: text/html; charset=utf-8' );
        
        $ii = 0;
        for($i = 1; $i <= 8; $i++){
            $html = file_get_html('http://forum.kohanaframework.org/discussions/p'.$i);
            
            foreach ($html->find('[id=Content] a.Title') as $element) {
                $ii++;
                if($ii < 30){
                    ob_end_flush();
                    $url = $element->href;
                    $title = $element->plaintext;
                    
                    if($ii < 20)
                        $content = 'fake';
                    else
                        $content = $this->curl('http://forum.kohanaframework.org'.$url);
                    
                    $query = DB::insert('pages', array('url', 'title', 'content'))->values(array($url, $title, $content))->execute();
                    
                    $page = str_get_html($content);
                    echo 'page='.$i.' url='.$url.'<br />';
                    
                    foreach ($page->find('[id=PagerAfter] a') as $page_element) {
                        $page_e_url = $page_element->href;
                        $page_e_text = $page_element->plaintext;
                        if($page_e_text != '›' && $page_e_text != '1'){
                            
                            $page_e_content = $this->curl('http://forum.kohanaframework.org'.$page_e_url);
                            $query = DB::insert('pages', array('url', 'title', 'content'))->values(array($page_e_url, $title, $page_e_content))->execute();
                            
                            echo '--- subnav url='.$page_e_url.' - '.$page_e_text.'<br />';
                        }
                    }
                    ob_flush();
                    flush();
                    ob_start();
                }
            }
        }
        

        $this->response->body('DONE!!!');
    }
    
    public function action_out(){
        //set_charset("utf8mb4");
        $query = DB::select()->from('pages');
        $results = $query->execute();
        $index = '';
        foreach ($results as $result) {
            $url = explode('/', $result['url']);
            $filename = $url[2].'_'.$url[3];
            if (isset($url[4])) {
                $filename .= '_'.$url[4];
            }
            $filename .= '.html';
            $content = $result['content'];
            echo $content;
            
            //$content = str_replace('<link rel="stylesheet" type="text/css" href="/applications/dashboard/design/style.css?v=2.0.18.10" media="all" />', '<link rel="stylesheet" type="text/css" href="'.URL::base().'style.css" media="all" />', $result['content']);
            //$content = str_replace('<a href="/discussion/'.$url[2].'/'.$url[3].'/p2">2</a>)
            
            //echo $result['content'];
            
            $this->file($filename, $content);
            $index .= '<a href="'.URL::base().'out/'.$filename.'">'.$result['title'].'</a>'.'<br />';
        }
        echo $index;
    }
    
    function file($fileName, $content) {
        if (!file_exists("out/".$fileName)) {
            $file_handle = fopen(DOCROOT . "out/".$fileName, "w") or die("can't open file");
            fwrite($file_handle, $content);
            fclose($file_handle);
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    function curl($url) {
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Your application name');
        $query = curl_exec($curl_handle);
        curl_close($curl_handle);
        return $query;
    }

}

// End Main