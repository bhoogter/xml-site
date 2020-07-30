<?php

class sbs_feedload
{

    //https://stackoverflow.com/questions/49932583/i-want-to-trigger-an-event-once-when-scrolled-into-view-with-jquery
    static function render($el, $params = [], $vArgs = "")
    {
        // php_logger::set_log_level("sbs_feedload", "debug");
        php_logger::call();
        return self::loadmore_div("", "", true);
    }

    static function loadmore_div($key = "", $ref = "", $onload = false)
    {
        $id = zobject::jsid();
        $url = self::loadmore_url($key, $ref);
        $script = self::loadmore_script($id, $url, $onload);
        return <<<DOC
<div id='$id' class='sbs-loadmore'>
    <a href="javascript:zoRefreshUrl('$id', '$url');">Load More</a>
    <script type='text/javascript'>
$script
    </script>
</div>
DOC;
    }

    static function loadmore_script($id, $url, $onload = false)
    {
        return $onload ? 
            "$(document).ready(function() { zoRefreshUrl('$id', '$url'); });" :
            <<<DOC
$(window).scroll(function() {
    if (!$('#$id') || !$('#$id').offset()) {
        $(window).off("scroll", arguments.callee);
        return;
    }
    var v = $('#$id').offset().top - $(window).height() - $(window).scrollTop();
    if (Math.abs(v) != v) {
        zoRefreshUrl('$id', '$url');
        $(window).off("scroll", arguments.callee);
    }
});
DOC;
    }

    static function loadmore_url($ref)
    {
        php_logger::call();
        $loc = sbs_data_key(xml_serve::$url_reference);
        $url = zobject::FetchApiPart("sbs-load-feed", "@loc");
        $url .= "?loc=$loc";
        if (!!$ref) $url .= "&ref=$ref";
        return $url;
    }

    static function feed_post($id)
    {
        return xml_file::toXml(zobject::render_object('sbs-post-list', ['mode' => 'feed'], "id=$id"));
    }

    static function load_feed($a = "", $method = "", $url = "")
    {
        $N = 10;

        php_logger::call();

        // New call stack, so we need to set this.
        sbs_data_key($loc = @$_REQUEST['loc']);
        if (!$loc) return "";

        $ref = @$_REQUEST['ref'];
        php_logger::info("FEED: ref=$ref, loc=$loc [key=" . sbs_data_key() . "]");

        $items = sbs_post_id_list_from($ref, $N);
        php_logger::debug($items);
        if (!sizeof($items)) return "";

        $s = "";
        foreach ($items as $i) {
            $s .= self::feed_post($i);
        }

        if (sizeof($items) == $N)
            $s .= self::loadmore_div($loc, $items[sizeof($items) - 1]);

        return $s;
    }
}
