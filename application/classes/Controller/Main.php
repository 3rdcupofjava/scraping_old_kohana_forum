<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Main extends Controller {

    public function action_index() {
        include 'simple_html_dom.php';
        
        header( 'Content-type: text/html; charset=utf-8' );
        
        $ii = 0;
        for($i = 171; $i <= 184; $i++){
            $html = file_get_html('http://forum.kohanaframework.org/discussions/p'.$i);
            
            foreach ($html->find('[id=Content] a.Title') as $element) {
                //$ii++;
                //if($ii < 30){
                    ob_end_flush();
                    $url = $element->href;
                    $title = $element->plaintext;
                    
//                    if($ii < 20)
//                        $content = 'fake';
//                    else
                        $content = $this->curl('http://forum.kohanaframework.org'.$url);
                    
                    
                    
                    $page = str_get_html($content);
                    echo 'page='.$i.' url='.$url.'<br />';
                    
                    $no_sub_pages = true;
                    foreach ($page->find('[id=PagerAfter] a') as $page_element) {
                        $no_sub_pages = false;
                        $page_e_url = $page_element->href;
                        $page_e_text = $page_element->plaintext;
                        if($page_e_text != '›'){
                            
                            $page_e_content = $this->curl('http://forum.kohanaframework.org'.$page_e_url);
                            $query = DB::insert('pages', array('url', 'title', 'content'))->values(array($page_e_url, $title, $page_e_content))->execute();
                            
                            echo '--- subnav url='.$page_e_url.' - '.$page_e_text.'<br />';
                        }
                    }
                    if($no_sub_pages){
                        $query = DB::insert('pages', array('url', 'title', 'content'))->values(array($url, $title, $content))->execute();
                    }
                    ob_flush();
                    flush();
                    ob_start();
                //}
            }
        }

        $this->response->body('DONE!!!');
    }
    
    public function action_out(){
        $query = DB::select()->from('pages');
        $results = $query->execute();
        $index = '';
        foreach ($results as $result) {
            $content = $result['content'];
            $url = explode('/', $result['url']);
            $filename = $url[2].'_'.$url[3];
            if (isset($url[4])) {
                $filename .= '_'.$url[4];
                echo 'href="/discussion/'.$url[2].'/'.$url[3].'/'.$url[4];
                for($i = 1; $i < 100; $i++){
                    $content = str_replace('href="/discussion/'.$url[2].'/'.$url[3].'/p'.$i, 'href="'.URL::base().'out/'.$url[2].'_'.$url[3].'_p'.$i.'.html', $content);
                }
            }
            $filename .= '.html';
            
            
            //echo $content;
            
            $content = str_replace('<link rel="stylesheet" type="text/css" href="/applications/dashboard/design/style.css?v=2.0.18.10" media="all" />', '<link rel="stylesheet" type="text/css" href="'.URL::base().'style.css" media="all" />', $content);
            
            
            //echo $result['content'];
            
            $this->file($filename, $content);
            $index .= '<a href="'.URL::base().'out/'.$filename.'">'.$result['title'].'</a>'.'<br />';
        }
        echo $index;
        $this->file('index.html', $index);
    }
    
    function file($fileName, $content) {
        //echo mb_detect_encoding($content).' ';
        if (!file_exists("out/".$fileName)) {
            $file = DOCROOT . "out/".$fileName;

            file_put_contents($file, $content);
            
//            $file_handle = fopen($file, "w") or die("can't open file");
//            fwrite($file_handle, $content);
//            fclose($file_handle);
            
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