<?php

function sbs_data_folder($sub) {
    return xml_site::$resource_folder . ($sub == '' ? '' : (DIRECTORY_SEPARATOR . $sub));
}

function data_key() {
    
}

function sbs_post_data_file() 
    { 
        return sbs_data_folder('sbs-posts') . DIRECTORY_SEPARATOR . "simple-blog-" .$postid . ".md"; 
    }
function sbs_post_file($postid) { return sbs_data_folder('sbs-posts') . DIRECTORY_SEPARATOR . $postid . ".md"; }
function sbs_comment_file($postid) { return sbs_data_folder('sbs-comments') . DIRECTORY_SEPARATOR . $postid . ".md"; }

function sbs_blog_post_get($postid) {
    return file_get_contents(sbs_post_file($postid));
}

function sbs_blog_post_set($postid, $body) {
    file_put_contents(sbs_post_file($postid), $body);
    return $body;
}


function sbs_page($a = null, $b = null, $c = null, $d = null) {
    php_logger::call();
    $x  = "<?xml version='1.0' ?>\n";
    $x .= "<pagedef id='3'>";
    $x .= "  <content id='content' type='html' src='blog.html' template='main' title='Blog Home'/>";
    $x .= "</pagedef>";
    return xml_file::toDoc($x)->documentElement;
}

