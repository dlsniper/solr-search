<?php

class SolrDemo {
    private $connectionDetails = '';

    private $queryPattern = '';


    public function setConnection($connectionDetails) {
        $this->connectionDetails = $connectionDetails;
    }

    public function setQueryFields($queryFields) {

    }

    public function setOrderFields($orderFields) {

    }

    public function setFacetFields($facetFields) {

    }

    public function setReturnFields($returnFields) {

    }

    public function setQuery($query) {

    }

    public function getResults($page = 0, $resultsPerPage = 0) {

    }

    /**
     * Fetch a page
     * @param string $url
     * @return array
     */
    private function fetchURL($url) {
        $curlOptions = array(
            CURLOPT_RETURNTRANSFER => true, // return web page
            CURLOPT_HEADER         => false, // don't return headers
            CURLOPT_FOLLOWLOCATION => true, // follow redirects
            CURLOPT_ENCODING       => "", // handle all encodings
            CURLOPT_USERAGENT      => "SOLR parser 1.0", // who am i but a ghost
            CURLOPT_AUTOREFERER    => true, // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 1, // timeout on connect
            CURLOPT_TIMEOUT        => 1, // timeout on response
            CURLOPT_MAXREDIRS      => 2, // stop after 10 redirects
            //CURLOPT_ENCODING       => "deflate, gzip, x-gzip, identity, *;q=0", //
        );


        // cURL magic
        $ch = curl_init($url);
        curl_setopt_array($ch, $curlOptions);
        $content = curl_exec($ch);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);

        // Assign the values we are going to return
        $result['errno'] = $err;
        $result['errmsg'] = $errmsg;
        $result['header'] = $header;
        $result['content'] = $content;

        // Return the results
        return $result;
    }

    /**
     * Parse the connection DSN to a configuration array
     * A DSN should look like this: solr://guest:guest@192.168.1.72:5672/vhost/promotions#save
     * Note the double '//' before promotion can be used to describe no name for the vhosts
     *
     * @static
     *
     * @param string $dsn
     *
     * @return array
     */
    static public function parseDSN($dsn) {
        if (is_array($dsn)) {
            return $dsn;
        }

        preg_match('/(?<protocol>\w+):\/\/(?<username>\w+):(?<password>\w+)@(?<hostname>(\w|\.)+):(?<port>\d+)(?<vhost>(\w|\/)+)\/(?<exchange>(\w|.)+)(#(?<queue>(\w|.)+))?/iu', $dsn, $matches);

        // Leave only the named parameters inside the array
        foreach($matches as $key => $value) {
            if (is_numeric($key)) {
                unset($matches[$key]);
            }
        }

        $defaults = array(
            'protocol' => '',
            'username' => '',
            'password' => '',
            'hostname' => '',
            'port'     => '',
            'vhost'    => '',
            'exchange' => '',
            'queue'    => '',
        );

        $matches = array_merge($defaults, $matches);

        return $matches;
    }
}
