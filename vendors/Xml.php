<?php

//xml解析成数组
class Xml {

    public static function decode($xml) {
        $values = array();
        $index = array();
        $array = array();
        $parser = xml_parser_create('utf-8');
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parse_into_struct($parser, $xml, $values, $index);
        xml_parser_free($parser);
        $i = 0;
        $name = $values[$i]['tag'];
        $array[$name] = isset($values[$i]['attributes']) ? $values[$i]['attributes'] : '';
        $array[$name] = self::_struct_to_array($values, $i);
        return $array;
    }

    private static function _struct_to_array($values, &$i) {
        $child = array();
        if (isset($values[$i]['value']))
            array_push($child, $values[$i]['value']);

        while ($i++ < count($values)) {
            switch ($values[$i]['type']) {
                case 'cdata':
                    array_push($child, $values[$i]['value']);
                    break;

                case 'complete':
                    $name = $values[$i]['tag'];
                    if (!empty($name)) {
                        $child[$name] = ($values[$i]['value']) ? ($values[$i]['value']) : '';
                        if (isset($values[$i]['attributes'])) {
                            $child[$name] = $values[$i]['attributes'];
                        }
                    }
                    break;

                case 'open':
                    $name = $values[$i]['tag'];
                    $size = isset($child[$name]) ? sizeof($child[$name]) : 0;
                    $child[$name][$size] = self::_struct_to_array($values, $i);
                    break;

                case 'close':
                    return $child;
                    break;
            }
        }
        return $child;
    }

	/**
     * The main function for converting to an XML document.
     * Pass in a multi dimensional array and this recrusively loops through and builds up an XML document.
     *
     * @param array $data
     * @param string $rootNodeName - what you want the root node to be - defaultsto data.
     * @param SimpleXMLElement $xml - should only be used recursively
     * @return string XML
     */
    public static function _array_to_xml($data, $rootNodeName = 'data', $xml=null)
    {
        // turn off compatibility mode as simple xml throws a wobbly if you don't.
        if (ini_get('zend.ze1_compatibility_mode') == 1)
        {
            ini_set ('zend.ze1_compatibility_mode', 0);
        }
         
        if ($xml == null)
        {
            $xml = simplexml_load_string("<?xml version='1.0' encoding='UTF-8'?><$rootNodeName />");
        }
         
        // loop through the data passed in.
        foreach($data as $key => $value)
        {
            // no numeric keys in our xml please!
            if (is_numeric($key))
            {
                // make string key...
                $key = "unknownNode_". (string) $key;
            }
             
            // replace anything not alpha numeric
            $key = preg_replace('/[^a-z]/i', '', $key);
             
            // if there is another array found recrusively call this function
            if (is_array($value))
            {
                $node = $xml->addChild($key);
                // recrusive call.
                Xml::_array_to_xml($value, $rootNodeName, $node);
            }
            else
            {
                // add single node.
//				$value = htmlentities($value);
                $xml->addChild($key,$value);
            }
             
        }
        // pass back as string. or simple xml object if you want!
        return $xml->asXML();
    }

}

?>