<?php

function sbs_slug($key) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $key)));
    while(false!==strpos($slug, "--")) $slug = str_replace("--", "-", $slug);
    while(0 === strpos($slug, "-")) $slug = substr($slug, 1);
    while(strlen($slug) - 1 === strpos($slug, "-")) $slug = substr($slug, 0, strlen($slug) - 1);
    return $slug;
}

function sbs_data_key($location = "") { static $loc; $loc = $location ? $location : $loc; return $location ? sbs_slug($location) : "root"; }
function sbs_data_folder($location, $sub = "") { return xml_site::$resource_folder . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "blog" . DIRECTORY_SEPARATOR . sbs_data_key($location) . ($sub == '' ? '' : (DIRECTORY_SEPARATOR . $sub)); }
function sbs_posts_folder($location) { return sbs_data_folder($location, "posts"); }
function sbs_data_file($location) { return sbs_data_folder($location, "posts.xml"); }
function sbs_post_file($location, $postid) { return sbs_posts_folder($location) . DIRECTORY_SEPARATOR . $postid . ".md"; }
function sbs_comment_file($location, $postid) { return sbs_data_folder($location, 'sbs-comments') . DIRECTORY_SEPARATOR . $postid . ".md"; }

function sbs_blog_post_get($postid) { return file_get_contents(sbs_post_file("", $postid)); }
function sbs_blog_post_set($postid, $body) { return file_put_contents(sbs_post_file("", $postid), $body); }

function sbs_post_id_from_slug($location, $slug) {
    php_logger::call();
    $source_id = "sbs-source-" .sbs_data_key($location);
    $datafile = sbs_data_file($location);
    php_logger::log("sbs_post_id_from_slug: source_id=$source_id, datafile=$datafile");
    $f = xml_site::$source->force_document($source_id, $datafile);

    $id = $f->get("/*/post[slug='$slug']/@id");
    php_logger::result($id);
    return $id;
}

function sbs_page_home()
{
    $x  = "<?xml version='1.0' ?>\n";
    $x .= "<pagedef id='3'>";
    $x .= "  <content id='content' type='html' src='blog.html' template='main' title='Blog Home'/>";
    $x .= "</pagedef>";
    return $x;
}

function sbs_post($id, $mode = "display")
{
    $x  = "<?xml version='1.0' ?>\n";
    $x .= "<pagedef id='3'>";
    $x .= "  <content id='content' type='xhtml' src='sbs-post.xml' />";
    $x .= "</pagedef>";
    $t = file_get_contents("C:\Users\bhoogter\Desktop\Personal\php\xml-site\demo\content\modules\simple-blog\sbs-post.xml");
    return $x;
}

function sbs_feed($id)
{
    $x  = "<?xml version='1.0' ?>\n";
    $x .= "<pagedef id='3'>";
    $x .= "  <content id='content' type='xml' src='feed.xml' />";
    $x .= "</pagedef>";
    return $x;
}

function sbs_page($path = null, $location = null)
{
    php_logger::clear_log_levels('debug');
    php_logger::call();

    if ($path == "") return xml_file::toDoc(sbs_page_home())->documentElement;
    if (strpos($path, 'posts/') == 0) {
        $slug = substr($path, 6);
        $id = sbs_post_id_from_slug($location, $slug);
        zobject::set_key_value('id', $id);
        return xml_file::toDoc(sbs_post($id))->documentElement;
    }
    if (strpos($path, 'edit/') == 0) {
        $slug = substr($path, 5);
        $id = sbs_post_id_from_slug($slug);
        return xml_file::toDoc(sbs_post($id, "edit"))->documentElement;
    }
    if (strpos($path, 'posts/feed') == 0) return xml_file::toDoc(sbs_post(substr($path, 6)))->documentElement;
}
