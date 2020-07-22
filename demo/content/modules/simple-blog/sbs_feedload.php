<?php

class sbs_feedload {

    static function render($el, $params = [], $vArgs = "") {
        $s = "";
        $s .= "<div id='loadMore' style=''>";
        $s .= " <a href='#'>Load More</a>";
        $s .= "</div>";
        return $s;
    }
}