<?php

class sbs_feedload {

    //https://stackoverflow.com/questions/49932583/i-want-to-trigger-an-event-once-when-scrolled-into-view-with-jquery
    static function render($el, $params = [], $vArgs = "") {
        php_logger::set_log_level("sbs_feedload", "debug");
        php_logger::call();
        return self::loadmore_div('45');
    }

    static function loadmore_div($key = "", $ref = "") {
        $id = zobject::jsid();
        $url = self::loadmore_url($key, $ref);
        $script = self::loadmore_script($id, $url);
        return <<<DOC
<div id='$id' class='sbs-loadmore'>
    <a href="javascript:zoRefreshUrl('$id', '$url');">Load More</a>
    <script type='text/javascript'>
$script
    </script>
</div>
DOC;
    }

    static function loadmore_script($id, $url) {
        return <<<DOC
// $(document).ready(function() { $('#$id').css('background-color', 'red');});
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

    static function loadmore_url($key, $ref) {
        $loc = $key ? $key : sbs_data_key(xml_serve::$url_reference);
        $url = zobject::FetchApiPart("sbs-load-feed", "@loc");
        $url .= "?loc=$loc";
        if (!!$ref) $url .= "&ref=$ref";
        return $url;
    }

    static function load_feed($a = "", $method = "", $url = "") {
        // php_logger::set_log_level("sbs_feedload", "debug");
        php_logger::call();
        
        $loc = @$_REQUEST['loc'];
        $ref = @$_REQUEST['ref'];

        // print "FOUND THIS: loc='$loc' ref='$ref'<br/>";

        return self::loadmore_div($loc, intval($ref) + 1);
    }
}