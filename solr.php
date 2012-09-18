<?php

class Solr {

    /**
     * Connection details
     *
     * @var array
     */
    private $connectionDetails = '';

    private $queryPattern = '';

    /**
     * The current query
     *
     * @var string
     */
    private $query = '';


    /**
     * Set the connection details as a DSN
     *
     * @param string $connectionDetails
     *
     * @return Solr
     */
    public function setConnection($connectionDetails) {
        $this->connectionDetails = Solr::parseDSN($connectionDetails);

        return $this;
    }

    /**
     * Set the fields to perform the query on
     *
     * @param array $queryFields
     *
     * @return Solr
     */
    public function setQueryFields($queryFields) {

        return $this;
    }

    /**
     * Set the query to do the sorting on
     * The parameter should contain values like (field => direction)
     *
     * @param array $orderFields
     *
     * @return Solr
     */
    public function setOrderFields($orderFields) {

        return $this;
    }

    /**
     * Set the facet fields, if any
     *
     * @param array $facetFields
     *
     * @return Solr
     */
    public function setFacetFields($facetFields) {

        return $this;
    }

    /**
     * Set the fields that we want back from SOLR
     *
     * @param array $returnFields
     *
     * @return Solr
     */
    public function setReturnFields($returnFields) {

        return $this;
    }

    /**
     * Set the query itself
     *
     * @param string $query
     *
     * @return Solr
     */
    public function setQuery($query) {

        return $this;
    }

    /**
     * Get the results of the given query
     *
     * @param int $page
     * @param int $resultsPerPage
     *
     * @return array
     */
    public function getResults($page = 0, $resultsPerPage = 0) {

        return array();
    }

    /**
     * Fetch a page
     *
     * @param string $url
     *
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
