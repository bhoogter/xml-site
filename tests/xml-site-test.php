<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class xml_site_test extends TestCase
{
    private const PAGES_XML = __DIR__ . "/resources/content/pages.xml";
    private static $subject = null;

    public static function setUpBeforeClass(): void
    {
        php_logger::clear_log_levels('alert');
        $pages = realpath(self::PAGES_XML);
        $res = realpath(__DIR__ . "/resources/content");
        self::$subject = new xml_site($res, $pages);
        print_r(self::$subject->get_source("SITE")->saveXML());
    }

/*
    public function testXmlSiteLoads()
    {
        $this->assertNotNull(self::$subject);
        $this->assertTrue(self::$subject->get_source("PAGES")->loaded);
        $this->assertTrue(self::$subject->get_source("SITE")->loaded);
    }

    public function testPageIdLookup()
    {
        $part = self::$subject->server->page_part('/about');
        $result = $part->get("/pagedef/@loc");
        $this->assertEquals('about', $result);
    }

    public function testServerPageRender()
    {
        $result = self::$subject->server->get_page('/about');
        $this->assertNotNull($result);
    }

    public function testXmlPageRender()
    {
        php_logger::clear_log_levels('warning');
        php_logger::set_log_level("page_render", 'all');
        $result = self::$subject->render("/about");
        $this->assertNotNull($result);
    }
*/
}
