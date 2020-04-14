<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class xml_site_test extends TestCase
{
    private const PAGES_XML = __DIR__ . "/resources/pages.xml";
    private static $subject = null;

    public static function setUpBeforeClass(): void
    {
            self::$subject = new xml_site(
                self::PAGES_XML, 
                __DIR__ . "/resources"
            );
    }

    public function testXmlSiteLoads()
    {
        $this->assertNotNull(self::$subject);
    }
}