<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class page_render_test extends TestCase
{
    private static $site;

    public static function setUpBeforeClass(): void
    {
        $path = realpath(__DIR__ . "/resources/content");
        self::$site = new xml_site($path);
    }

    public function candidatePage1()
    {
        $xml = "";
        $xml .= "<pagedef loc='sub1home' default='1' template='main' text='Main Page' description='a new page' keywords='a,b,c'>\n";
        $xml .= "  <content id='content' src='main-content.html' />\n";
        $xml .= "</pagedef>";

        return new xml_file($xml);
    }

    public function testDummy() {
        $this->assertTrue(true);
    }

/*
    public function testDefaultLookup(): void
    {
        // php_logger::set_log_level("xml_serve", "all");
        // php_logger::set_log_level("page_source", "all");
        // php_logger::set_log_level("render_perfect", "all");
        // php_logger::set_log_level("render_linklist", "all");
        // php_logger::set_log_level("xml_serve", "all");
        // php_logger::set_log_level("page_handlers", "all");
        // php_logger::set_log_level("render_linklist", "debug");
        // php_logger::set_log_level("render_content", "debug");
        $candidate = $this->candidatePage1();
        $result = xml_serve::make_page($candidate);

        $xhtml = xml_file::make_tidy_string($result->saveXML(), "xml");
        print "\n---------------------------------\n{$xhtml}\n---------------------------------\n";

        $this->assertTrue(strpos($xhtml, '/content/css/global.css') !== false);
        $this->assertTrue(strpos($xhtml, '/content/templates/main/style.css') !== false);
        $this->assertTrue(strpos($xhtml, '/content/templates/main/color.css') !== false);
        $this->assertTrue(strpos($xhtml, 'a,b,c') !== false);
        $this->assertTrue(strpos($xhtml, 'Main Page') !== false);
        $this->assertTrue(strpos($xhtml, 'a new page') !== false);
    }


    // public function testDefaultLookupLinkLists(): void
    // {
    //     // php_logger::set_log_level("xml_serve", "all");
    //     $candidate = $this->candidatePage1();
    //     $page = xml_serve::make_page($candidate);
    //     $result = xml_file::make_tidy_string($page->saveXML(), "xml");

    //     $this->assertTrue(strpos($result, "google") !== false);
    //     $this->assertTrue(strpos($result, "Church of the Beyond") !== false);
    //     $this->assertTrue(strpos($result, "<a href=\"contact\">Contact Us</a>") !== false);
    // }
*/
}
