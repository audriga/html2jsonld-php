<?php

declare(strict_types=1);

namespace SML\Html2JsonLd\Util;

use Brick\StructuredData\Item;

use Sabre\Uri\InvalidUriException;
use function Sabre\Uri\build;
use function Sabre\Uri\parse;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Exports Items to JSON-LD.
 */
class JsonLdWriter
{
    /**
     * @var bool Allow wirter to download images from URLs to convert them to
     * Base64 Data-URLs. 
     */
    protected $downLoadImagesFromSchema;

    /**
     * @var int Time (in seconds) after which getting image for conversion is
     * cancelled. 
     */
    protected $fileDownloadTimeoutWindow;

    /**
     * @var int Max byte-size for images to be downloaded. 
     */
    protected $fileDownloadSizeLimit;

    /**
     * @var LoggerInterface Instance of a Psr's NullLogger 
     */
    protected $logger;

    /**
     * @var string Context of the object(s) being parsed, like 'https://schema.org' 
     */
    protected $context;

    public function __construct(
        $downLoadImagesFromSchema = true,
        $fileDownloadTimeoutWindow = 5,
        $fileDownloadSizeLimit = 500000
    ) {
        $this->downLoadImagesFromSchema = $downLoadImagesFromSchema;
        $this->fileDownloadTimeoutWindow = $fileDownloadTimeoutWindow;
        $this->fileDownloadSizeLimit = $fileDownloadSizeLimit;

        $this->logger = new NullLogger();
    }

    /**
     * Exports a list of Items as JSON-LD.
     *
     * @param Item ...$items Items, which are supposed to be writen to JSON.
     *
     * @return string The JSON-LD representation.
     */
    public function write(Item ...$items) : string
    {
        $this->setContextIfEqual($items);

        $items = array_map(
            function (Item $item) {
                return $this->convertItem($item);
            }, $items
        );

        if ($this->context) {
            foreach ($items as $i => $item) {
                $items[$i] = array_merge(["@context" => $this->context], $item);
            }
        }

        $this->context = false;

        return json_encode(
            $this->extractIfSingle($items),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * Converts an Item to an associative array that can be encoded with json_encode.
     *
     * @param Item $item Item to be converted
     *
     * @return array
     */
    protected function convertItem(Item $item) : array
    {
        $type = $this->extractIfSingle($item->getTypes());

        if ($this->context && !is_array($type)) {
            $type = $this->extractPathFromUrl($type);
        }

        $result = ['@type' => $type];

        if ($item->getId() !== null) {
            $result['@id'] = $item->getId();
        }

        foreach ($item->getProperties() as $name => $values) {
            if ($this->context) {
                $name = $this->extractPathFromUrl($name);
            }

            foreach ($values as $key => $value) {

                if ($value instanceof Item) {
                    $values[$key] = $this->convertItem($value);
                }
            }
            /*
             * Add code to convert image to binary if $name === "image"
             * 
             * $values = convertImageUrlToBinary($values);
             */

            $extractedValues = $this->extractIfSingle($values);

            if (!$this->downLoadImagesFromSchema) {
                continue;
            }

            if ($name == 'thumbnail') {
                $extractedValues
                    = $this->convertImageToBinary($extractedValues)
                    ?? $extractedValues;
            } elseif ($name == 'image') {
                $extractedValues
                    = $this->convertImageToBinary($extractedValues)
                    ?? $extractedValues;
            }

            $result[$name] = $extractedValues;

        }

        return $result;
    }

    /**
     * Returns the value from a list containing a single value, or the array if it
     * does not contain exactly one value.
     *
     * @param array $values Values from which the first is to be extracted.
     *
     * @return mixed First value, if singleton, input if not.
     */
    protected function extractIfSingle(array $values)
    {
        if (count($values) === 1) {
            return $values[0];
        }

        return $values;
    }


    /**
     * This will set the context used for writing the item array into a json string.
     * If the item array contains multiple conflicting context values or none at all,
     * it will be set to false. If it does contain only one value, it will instead be
     * set to the scheme and host that is found. It uses the item's type to determine
     * the context. Therefore, "http://schema.org/EventReservation is will se the
     * context to "http://schema.org
     * 
     * @param $items Set of items to be looped over.
     */
    protected function setContextIfEqual($items)
    {
        if (is_null($items) || !is_array($items)) {
            $this->context = false;
            return;
        }

        $context = "";
        
        foreach ($items as $item) {
            $types = $item->getTypes();
            
            $type = $this->extractIfSingle($types);
            
            if (is_array($type)) {
                $this->context = false;
                return;
            }
            
            $itemContext = $this->getContextUrl($type);

            if (!$itemContext) {
                continue;
            }

            if ($context == "") {
                $context = $itemContext;
            }

            if ($context != $itemContext) {
                $this->context = false;
                return;
            }
        }

        $this->context = $context;
    }

    /**
     * Retreives the scheme and host of the given url.
     * 
     * @param String $url The url from which to extract the context.
     * 
     * @return String|null Will either be the given string, if it cannot be parsed
     * as a url, null if it does not contain a scheme or host part or the scheme and
     * host as one string if it can (e. g. "http://schema.org").
     */
    protected function getContextUrl(string $url) : ?string
    {
        try {
            $parts = parse($url);
        } catch (InvalidUriException $e) {
            return $url;
        }

        if ($parts['scheme'] === null) {
            return null;
        }

        if ($parts['host'] === null) {
            return null;
        }

        $partsToKeep = array_flip(['scheme', 'host']);
        $partsReduced = array_intersect_key($parts, $partsToKeep);

        return build($partsReduced);
    }

    /**
     * Extrat the path of the given URL.
     * 
     * @param $original URL from which to get the path.
     * 
     * @return String|null The path of the URL, if it contains one. The original
     * input, if it is no URL. Null, if it has no path.
     */
    protected function extractPathFromUrl($original)
    {
        try {
            $parts = parse($original);
        } catch (InvalidUriException $e) {
            return $original;
        }
        
        if ($parts["path"] == "") {
            return null;
        }

        // Remove the preceeding / from the path.
        return str_replace("/", "", $parts["path"]);
    }

    protected function convertImageToBinary(array|string $value): mixed
    {
        // First check if we have a simple url string, an array of images
        // or an ImageObject.
        if (is_string($value)) {
            // Simple String that we can convert.
            return $this->convertUrlToBinary($value) ?? $value;
        } elseif (is_array($value)) {
            // Either an ImageObject or an Array of images/ImageObjects.
            if (array_key_exists('@type', $value)
                && $value['@type'] === 'ImageObject'
            ) {
                // ImageObject, we need to find out which field is filled.
                return $this->convertImageObjectUrlToBinary($value);
            } elseif (array_key_exists('@type', $value)) {
                // Some other form of image like a Barcode and ImageObjectSnapshot.
                // See: https://schema.org/ImageObject
                if(empty($value['@type'])){
                    $this->logger->warning('@type is empty. Image will not be converted.');
                } else {
                    $this->logger->warning(
                        "Images of type ".   $value['@type']
                        . " not supported. Image will not be converted."
                    );
                }
            } else {
                // Array of images, so we just call this method on the first element.
                $firstImage = array_shift($value);

                array_unshift($value, $this->convertImageToBinary($firstImage));
            }
        }

        return $value;
    }


    protected function convertImageObjectUrlToBinary(array $imageObject): array
    {
        if (array_key_exists('contentUrl', $imageObject)) {
            $contentUrl = $imageObject['contentUrl'];

            if (is_string($contentUrl)) {
            // If contentUrl is a string, convert it to binary
            $binary = $this->convertUrlToBinary($contentUrl);
            $imageObject['contentUrl'] = $binary ?? $contentUrl;
            } elseif (is_array($contentUrl)) {
                // If contentUrl is an array, convert each URL in the array to binary
                foreach ($contentUrl as $key => $url) {
                    $binary = $this->convertUrlToBinary($url);
                    $imageObject['contentUrl'][$key] = $binary ?? $url;
                }
                
            } 

        } elseif (array_key_exists('url', $imageObject)) {
            $url = $imageObject['url'];

            if (is_string($url)) {
            // If url is a string, convert it to binary
            $binary = $this->convertUrlToBinary($url);
            $imageObject['url'] = $binary ?? $url;
            } elseif (is_array($url)) {
                // If url is an array, convert each URL in the array to binary
                foreach ($url as $key => $imageUrl) {
                    $binary = $this->convertUrlToBinary($imageUrl);
                    $imageObject['url'][$key] = $binary ?? $imageUrl;
                }
            }
        }

        return $imageObject;
    }

    protected function convertUrlToBinary($url): ?string
    {
        // Check if the string is a valid URL.
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        // TODO make this configurable.
        // Set a timeout for the call. 
        $ctx = stream_context_create(
            array('http'=>
            array(
                'timeout' => $this->fileDownloadTimeoutWindow,
            )
            )
        );

        $content = file_get_contents(
            $url,
            true,
            $ctx,
            0,
            $this->fileDownloadSizeLimit
        );

        if (!$content) {
            return $url;
        }

        $encodedContent = base64_encode($content);

        $mimeTypeSignatures = [
            'iVBORw0KGgo'=> 'image/png',
            '/9j/' => 'image/jpg',
            'R0lGODdh' => 'image/gif',
            'R0lGODlh'=> 'image/gif'
        ];

        foreach ($mimeTypeSignatures as $sig => $mimeType) {
            if (str_starts_with($encodedContent, $sig)) {
                return 'data:' . $mimeType . ';base64,' . $encodedContent;
            }
        }


        return $url;
    }
}
