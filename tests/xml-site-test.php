<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

class xml_site_test extends TestCase
{
    public function testXmlSiteLoads()
    {
        $obj = new xml_site();
        $this->assertNotNull($obj);
        $typ = $obj->type();
        $gid = $obj->gid;

        $this->assertEquals(str_replace("_", "", strtoupper($typ)) . "_", substr($gid, 0, strlen($typ)));
        // $this->assertEquals("xml_source", $typ);
        echo $obj->gid;
    }
}