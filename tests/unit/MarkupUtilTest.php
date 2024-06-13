<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../src/MarkupUtil.php';

use PHPUnit\Framework\TestCase;
use SML\Html2JsonLd\Util\MarkupUtil;

class MarkupUtilTest extends TestCase
{
    // Use this workaround to "var_dump" the result in phpunit 10:
    // fwrite(STDERR, print_r($this->result, true));

    private String $source;
    private String $url = "https://www.example.com";

    private $result;

    /**
     * Resets the result and source after each test.
     */
    public function tearDown(): void
    {
        $this->result = null;
        $this->source = "";
    }

    /**
     * Generates the result as an assoc array from the set source as an HTML file.
     * 
     * @param String $source The absolute filepath to the test data.
     */
    private function generateResult(String $source = __DIR__ . "/../resources/jsonld-email.html")
    {
        $this->source = $source;

        $this->result = json_decode(MarkupUtil::getJsonLdFromFile($this->source, $this->url), true);
    }

    /**
     * Test if the result after parsing a source is a valid json string.
     */
    public function testResultIsJson(): void
    {
        $this->source = __DIR__ . "/../resources/jsonld-website-real-example.html";

        $this->result = MarkupUtil::getJsonLdFromFile($this->source, $this->url);

        $this->assertGreaterThan(0, strlen($this->result));
        $this->assertNotNull(json_decode($this->result));
    }

    /**
     * Simple check, that no errors/exceptions are thrown and not caught when parsing an html
     * file which does not contain any form of markup.
     */
    public function testResultIsNull(): void
    {
        $this->generateResult(__DIR__ . "/../resources/website-without-markup.html");

        $this->assertNull($this->result);
    }

    /**
     * Test if the correct Schema tags get parsed from a basic email as a file.
     */
    public function testJsonLdFromEmail(): void
    {
        $this->generateResult(__DIR__ . "/../resources/jsonld-email.html");


        $this->assertIsArray($this->result);
        
        $this->assertArrayHasKey("underName", $this->result);
        $this->assertIsArray($this->result["underName"]);

        $this->assertArrayHasKey("reservationFor", $this->result);
        $this->assertIsArray($this->result["reservationFor"]);
        $this->assertArrayHasKey("location", $this->result["reservationFor"]);
        $this->assertIsArray($this->result["reservationFor"]["location"]);

        // Quick check that values are not lost.
        $this->assertEquals(
            "John Smith",
            $this->result["underName"]["name"]
        );
    }

    /**
     * Test if the correct Schema tags get parsde from a basic website as a file.
     */
    public function testJsonLdFromWebsite(): void
    {
        $this->generateResult(__DIR__ . "/../resources/jsonld-website.html");

        $this->assertIsArray($this->result);

        $this->assertArrayHasKey("author", $this->result);
        $this->assertIsArray($this->result["author"]);

        $this->assertArrayHasKey('image', $this->result);
        $this->assertStringStartsWith('data:image/jpg;base64,/9j/4AAQSkZJRgABAQEASABIAAD', $this->result['image']);
    }

    public function testJsonLdFromArticleZeit(): void
    {
        $this->generateResult(__DIR__ . "/../resources/jsonld-article-zeit.html");
        
        $this->assertIsArray($this->result);
        $this->assertCount(4, $this->result);
        $this->assertArrayHasKey("@context", $this->result[3]);
    }

    /**
     * Test if the correct JSON gets parsed from a website recieved from a URL.
     */
    public function testJsonLdFromRealWebsite(): void
    {
        $this->generateResult(__DIR__ . "/../resources/jsonld-website-real-example.html");

        $this->assertIsArray($this->result);
        $this->assertCount(4, $this->result);
        $this->assertIsArray($this->result[1]);
        
        $this->assertArrayHasKey("logo", $this->result[1]);
        $this->assertIsArray($this->result[1]["logo"]);
        $this->assertArrayHasKey("height", $this->result[1]["logo"]);
    }

    /**
     * Check if a website containing multiple ld+json script and microdata tags gets parsed properly.
     */
    public function testMultipleJsonLdTagsAndMicrodata(): void
    {
        $this->generateResult(__DIR__ . "/../resources/mixed-jsonld-microdata.html");

        $this->assertIsArray($this->result);
        $this->assertCount(12, $this->result);
    }
    
    /**
     * Test if Microdata gets parsed and written as a JSON string correctly.
     */
    public function testMicrodataFromEmail(): void
    {
        $this->generateResult(__DIR__ . "/../resources/microdata-email.html");

        $this->assertIsArray($this->result);
        $this->assertCount(5, $this->result);


        $this->assertArrayHasKey("underName", $this->result);
        $this->assertIsArray($this->result["underName"]);
        $this->assertCount(2, $this->result["underName"]);
    }
    
    /**
     * Test if inline Microdata is read correctly from websites as a file.
     */
    public function testMicrodataInlineFromWebsite(): void
    {
        $this->generateResult(__DIR__ . "/../resources/microdata-inline-website.html");

        $this->assertIsArray($this->result);
        $this->assertCount(8, $this->result);

        $this->assertArrayHasKey("author", $this->result);
        $this->assertIsArray($this->result["author"]);
        $this->assertCount(2, $this->result["author"]);

        // Quick check that values are not lost.
        $this->assertEquals(
            "Example Author",
            $this->result["author"]["name"]
        );
    }
    
    /**
     * Test if inline Microdata is read correctly from emails a file.
     */
    public function testMicrodataInlineFromEmail(): void
    {
        $this->generateResult(__DIR__ . "/../resources/microdata-inline-email.html");

        $this->assertIsArray($this->result);
        $this->assertCount(4, $this->result);

        $this->assertArrayHasKey("underName", $this->result);
        $this->assertisArray($this->result["underName"]);
        $this->assertCount(2, $this->result["underName"]);

        // Quick check that values are not lost.
        $this->assertEquals(
            "John Smith",
            $this->result["underName"]["name"]
        );
    }

    /**
     * Test if a slightly broken (missing closing body tag) still works fine.
     * 
     * According to PHP Docs, the HTML does not have to be well formed to be
     * turned into a DomDocument: https://www.php.net/manual/en/domdocument.loadhtmlfile.php
     */
    public function testBrokenHtml(): void
    {
        $this->generateResult(__DIR__ . "/../resources/jsonld-website-broken-tag.html");

        $this->assertIsArray($this->result);

        $this->assertArrayHasKey("author", $this->result);
        $this->assertIsArray($this->result["author"]);
    }

    /**
     * Test website which caused issues for nextcloud/cookbook's import function: hellofresh.de
     */
    public function testJsonLdFromHelloFresh(): void
    {
        $this->generateResult(__DIR__ . "/../resources/cookbook-issue-hello-fresh.html");

        $this->assertIsArray($this->result);
        $this->assertCount(2, $this->result);

        $this->assertIsArray($this->result[0]);
        $this->assertArrayHasKey('@type', $this->result[0]);
        $this->assertEquals('Recipe', $this->result[0]["@type"]);
        
    }

/**
     * Test website which caused issues for nextcloud/cookbook's import function: marmiton.org
     */
    public function testJsonLdFromMarmiton(): void
    {
        $this->generateResult(__DIR__ . "/../resources/cookbook-issue-marmiton.html");

        $this->assertIsArray($this->result);
        $this->assertCount(4, $this->result);

        $this->assertIsArray($this->result[1]);
        $this->assertArrayHasKey('@type', $this->result[1]);
        $this->assertEquals('Recipe', $this->result[1]["@type"]);
    }

/**
     * Test website which caused issues for nextcloud/cookbook's import function: rezeptwelt.de
     */
    public function testJsonLdFromRezeptwelt(): void
    {
        $this->generateResult(__DIR__ . "/../resources/cookbook-issue-rezeptwelt.html");

        $this->assertIsArray($this->result);
        $this->assertCount(2, $this->result);
        $this->assertIsArray($this->result[1]);
        $this->assertArrayHasKey('@type', $this->result[1]);
        $this->assertEquals('Recipe', $this->result[1]["@type"]);
        
    }
}
