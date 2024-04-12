<?php

/**
 * Small library for parsing markup data form HTML sources.
 * 
 * PHP version 8.1
 * 
 * @author Gerke Frölje <gerke@audriga.com>
 */

namespace SML\Html2JsonLd\Util;

use Brick\StructuredData\Reader\ReaderChain;
use Brick\StructuredData\Reader\JsonLdReader;
use Brick\StructuredData\Reader\MicrodataReader;
use Brick\StructuredData\Reader\RdfaLiteReader;
use Brick\StructuredData\DOMBuilder;

/**
 * Util class used to parse Schema.org markup from HTML content and return them as
 * a JSON string.
 * 
 * @author Gerke Frölje <gerke@audriga.com>
 */
class MarkupUtil
{
    /**
     * Extract any Schema.org markup information found in the URL source as JSON+LD,
     * Microdata (inline) or RDFA. HTML files are retrieved using php curl.
     * 
     * @param string $source          A string representation of the URL under which
     *                                the HTML that is to be scanned is located.
     * @param bool   $downloadImages  Use file_get_contents to retreive and convert
     *                                images found in the markup data to Base64. IF
     *                                the image could not be retreived, the
     *                                original URL is returned.
     * @param int    $downloadTimeout Time in seconds, after which a download will be
     *                                aborted.
     * @param int    $downloadSize    Maximum size in bytes of images when
     *                                downloading.
     * 
     * @return string $result A JSON string containing any markup located in the
     * source. If the source contains multiple markup tags, multiple formats of
     * markup and/or one JSON+LD element using the "@graph" notation to define
     * multiple markup parts, the resulting string will be a JSON array.
     */
    public static function getJsonLdFromUrl(
        string $source,
        bool $downloadImages= true,
        int $downloadTimeout = 5,
        int $downloadSize = 500000
    ) {
        $html = self::getHtmlFromURL($source);

        return self::getJsonLdFromHtmlString(
            $html,
            $source,
            $downloadImages,
            $downloadTimeout,
            $downloadSize
        );
    }

    /**
     * Extract any Schema.org markup information found in the file source as JSON+LD,
     * Microdata (inline) or RDFA.
     * 
     * @param string $source          A string representation of the file directory
     *                                under which the HTML that is to be scanned is
     *                                located.
     * @param string $url             The original URL source of the file. Used to
     *                                resolve relative paths in the markup data.
     *                                The method does not access the URL in any way.
     * @param bool   $downloadImages  Use file_get_contents() to retreive and convert
     *                                images found in the markup data to Base64. IF
     *                                the image could not be retreived, the original
     *                                URL is returned.
     * @param int    $downloadTimeout Time in seconds, after which a download will be
     *                                aborted.
     * @param int    $downloadSize    Maximum size in bytes of images when
     *                                downloading.
     * 
     * @return string $result A JSON string containing any markup located in the
     * source. If the source contains multiple markup tags, multiple formats of
     * markup and/or one JSON+LD element using the "@graph" notation to define
     * multiple markup parts, the resulting string will be a JSON array.
     */
    public static function getJsonLdFromFile(
        string $source,
        string $url,
        bool $downloadImages= true,
        int $downloadTimeout = 5,
        int $downloadSize = 500000
    ) {
        $html = self::getHtmlFromFile($source);

        return self::getJsonLdFromHtmlString(
            $html,
            $url,
            $downloadImages,
            $downloadTimeout,
            $downloadSize
        );
    }

    /**
     * Extract any Schema.org markup information found in the HTML string as JSON+LD,
     * Microdata (inline) or RDFA.
     * 
     * @param string $html            A string representation of the HTML file to be
     *                                scanned.
     * @param string $url             The original URL source of the file. Used to
     *                                resolve
     *                                relative paths in the markup data. The method
     *                                does not access the URL in any way.
     * @param bool   $downloadImages  Use file_get_contents() to retreive and convert
     *                                images found in the markup data to Base64. IF
     *                                the image could not be retreived, the original
     *                                URL is returned.
     * @param int    $downloadTimeout Time in seconds, after which a download will be
     *                                aborted.
     * @param int    $downloadSize    Maximum size in bytes of images when
     *                                downloading.
     * 
     * @return string $result A JSON string containing any markup located in the
     * source. If the source contains multiple markup tags, multiple formats of
     * markup and/or one JSON+LD element using the "@graph" notation to define 
     * multiple markup parts, the resulting string will be a JSON array.
     */
    public static function getJsonLdFromHtmlString(
        string $html,
        string $url,
        bool $downloadImages= true,
        int $downloadTimeout = 5,
        int $downloadSize = 500000
    ) {
        // TODO / For future reference: we might want to check the HTML source for
        // relevant substrings ("http://schema.org", "<aplication/ld+json>",
        // "itemscope", "vocab", etc.) before building the DomDocument or the
        // ReaderChain to counteract performance hits through long, uninteresting
        // for this case, HTML documents or looping over the DomDocument too often.

        $readerChain = new ReaderChain(
            new JsonLdReader(),
            new MicrodataReader(),
            new RdfaLiteReader()
        );

        $domDocument = DOMBuilder::fromHTML($html);

        $items = $readerChain->read($domDocument, $url);

        $jsonLd = self::convertItemArrayToJsonLd(
            $items,
            $downloadImages,
            $downloadTimeout,
            $downloadSize
        );

        return $jsonLd;
    }

    /**
     * Gets an HTML string through a given URL.
     * 
     * @param string $source The URL source the HTML is supposed to come from.
     * 
     * @return string $html The HTML located under the URL as a String.
     */
    protected static function getHtmlFromURL(string $source)
    {
        // Very basic HTTP-Client setup using the built-in php curl client.
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $source);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $html = curl_exec($ch);

        curl_close($ch);

        return $html;
    }

    /**
     * Gets an HTML string through the given file source.
     * 
     * @param string $source The absolute directory and name of the file.
     * 
     * @return string $html The HTML located under the file directory.
     */
    protected static function getHtmlFromFile(String $source)
    {
        return file_get_contents($source);
    }

    /**
     * Converts an Item Array to a JSON String, either as a single JSON
     * object or a JSON Array.
     * 
     * @param ?array $items           An Array of Brick\StructuredData\Item objects.
     * @param bool   $downloadImages  Use file_get_contents() to retreive and convert
     *                                images found in the markup data to Base64. IF
     *                                the image could not be retreived, the original
     *                                URL is returned.
     * @param int    $downloadTimeout Time in seconds, after which a download will be
     *                                aborted.
     * @param int    $downloadSize    Maximum size in bytes of images when
     *                                downloading.
     * 
     * @return string $result The array converted to a JSON string.
     */
    protected static function convertItemArrayToJsonLd(
        ?array $items,
        $downloadImages,
        $downloadTimeout,
        $downloadSize
    ) {
        // The Brick/StructuredData Readers return their own custom Item arrays.
        // These can be written to JSON Strings using their writer.
        $writer = new JsonLdWriter($downloadImages, $downloadTimeout, $downloadSize);
        $jsonLdComponents = [];

        // You cannot write Arrays of Items and need to iterate over them to get
        // the wanted result.
        foreach ($items as $item) {
            array_push($jsonLdComponents, $writer->write($item));
        }

        $result = implode(",\n", $jsonLdComponents);

        // If we have more than one one element, we just turn it into a JSON array
        // by messing with the resulting string.
        if (sizeof($jsonLdComponents) > 1) {
            $result = "[\n" . $result . "\n]";
        }

        return $result;
    }
}