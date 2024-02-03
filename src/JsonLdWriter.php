<?php

declare(strict_types=1);

namespace SML\Html2JsonLd\Util;

use Brick\StructuredData\Item;

use Sabre\Uri\InvalidUriException;
use function Sabre\Uri\build;
use function Sabre\Uri\parse;

/**
 * Exports Items to JSON-LD.
 */
class JsonLdWriter
{
    protected $context;

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

            $result[$name] = $this->extractIfSingle($values);
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
}
