<?php

class solrSearch {

    public $query;
    public $facets;
    private $json;
    private $pageNumber;
    private $perPage;

    public function __call($name, $arguments) {
        die('Called: ' . $name . ' with args: <pre>' . print_r($arguments, true) . '</pre>');
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

    private function termsParser($terms) {
        $priorities = array(
            'ANDtitle' => 20,
            'ANDdesc' => 15,
            'ANDtags' => 5,
            'ORtitle' => 10,
            'ORdesc' => 5,
            'ORtags' => 1,
        );

        function space_replacer($matches) {
            return str_replace(" ", "##__ShPACE__##", $matches[0]);
        }

        function space_replacer2($value) {
            return str_replace("##__ShPACE__##", " ", $value);
        }

        $terms = strtolower($terms);
        if (strpos($terms, " ") !== false) {
            $search = array(
                "@\s+or\s+@",
                "@\s+and\s+@",
                "@'@",
                "@\s+@");
            $replace = array(
                " ",
                " ",
                '"',
                " ");
            $terms = preg_replace($search, $replace, $terms);
            $terms = preg_replace_callback("@\"(\w+\s*)+\"@", "space_replacer", $terms);
            $terms = explode(" ", $terms);
            $quote = array();
            $minus = array();
            $cnt = count($terms);
            for ($i = 0; $i < $cnt; $i++) {
                $stletter = $terms[$i];
                if ($stletter[0] == '"') {
                    $t = array_splice($terms, $i, 1);
                    $quote[] = $t[0];
                    $i--;
                    $cnt--;
                } elseif ($stletter[0] == "-") {
                    $t = array_splice($terms, $i, 1);
                    $minus[] = $t[0];
                    $i--;
                    $cnt--;
                }
            }

            $ANDterms = '(' . implode(' AND ', $terms) . ')';
            $ORterms = '(' . implode(' OR ', $terms) . ')';

            $terms = "title:" . $ANDterms . "^" . $priorities['ANDtitle'] . " OR description:" . $ANDterms . "^" . $priorities['ANDdesc'] . " OR tags:" . $ANDterms . "^" . $priorities['ANDtags'];
            $terms .= " OR ";
            $terms .= "title:" . $ORterms . "^" . $priorities['ORtitle'] . " OR description:" . $ORterms . "^" . $priorities['ORdesc'] . " OR tags:" . $ORterms . "^" . $priorities['ORtags'];

            $terms = "((" . $terms . ")";
            if (count($quote) > 0) {
                $quote = array_map("space_replacer2", $quote);
                $terms .= " AND (title:" . implode(" AND ", $quote) . " OR description:" . implode(" AND ", $quote) . " OR tags:" . implode(" AND ", $quote) . ")";
            }

            if (count($minus) > 0) {
                $minus = array_map("space_replacer2", $minus);
                $terms .= " AND (title:" . implode(" AND ", $minus) . " AND description:" . implode(" AND ", $minus) . " AND tags:" . implode(" AND ", $minus) . ")";
            }

            echo $terms .= ")";
        } else {
            $query = "title:" . $terms . "^" . $priorities['ORtitle'] . " OR description:" . $terms . "^" . $priorities['ORdesc'] . " OR tags:" . $terms . "^" . $priorities['ORtags'];
            $terms = "(" . $query . ")";
        }

        return $terms;
    }

    public function getURL() {
        return 'demo';
    }

    public function __construct($query = '*', $category = 'all', $rows = 35, $pageNumber = 1, $orientation = '', $production = 'all', $rating = 'all', $date = 'all', $order = 'relevance', $video_type = 'all') {
        $this->pageNumber = $pageNumber;
        $this->perPage = $rows;
        $start = $pageNumber * $rows - $rows;

        if (strlen($query) == 0) {
            $query = "*";
        }

        $cols = array(
            'id',
            //'category',
            //'title',
            //'description',
            //'tags',
            //'score'
        );

        /**/
        $quer = $this->termsParser($query);
        /** /

        $priorities = array(
            'ANDtitle' => 20,
            'ANDdesc' => 15,
            'ANDtags' => 5,
            'ORtitle' => 10,
            'ORdesc' => 5,
            'ORtags' => 1,
        );

        $query = strtolower($query);
        $query = preg_replace('%[ -_]%', ' ',$query);
        $terms = explode(' ', $query);
        if (count($terms) > 1 && strlen($query) > 0) {
            $ANDterms = '(' . implode(' AND ', $terms) . ')';
            $ORterms = '(' . implode(' OR ', $terms) . ')';

            $query = "title:" . $ANDterms . "^" . $priorities['ANDtitle'] . " OR description:" . $ANDterms . "^" . $priorities['ANDdesc'] . " OR tags:" . $ANDterms . "^" . $priorities['ANDtags'];
            $query .= " OR ";
            $query .= "title:" . $ORterms . "^" . $priorities['ORtitle'] . " OR description:" . $ORterms . "^" . $priorities['ORdesc'] . " OR tags:" . $ORterms . "^" . $priorities['ORtags'];
        } else {
            $query = "title:" . $terms[0] . "^" . $priorities['ORtitle'] . " OR description:" . $terms[0] . "^" . $priorities['ORdesc'] . " OR tags:" . $terms[0] . "^" . $priorities['ORtags'];
        }

        $query = "(" . $query . ")";
        /**/

        $columns = implode('%2C', $cols);

        if ($order == 'relevance')
            $order = 'score';

        // boost by rating
        if ($rating == 'all') {

            // Rating search is temporarely overridden
            $query .= ' AND rating:([0 TO 3]^0.1 OR [3 TO 4]^5 OR [4 TO 5]^10)';
        }

        $query = urlencode($query);

        $url = $this->getURL() . "/select/?q=" . $query;
        $url .= "&version=2.2&start=" . $start . "&rows=" . $rows;
        $url .= "&wt=json&fl=" . $columns;
        $url .= "&sort=" . $order . "%20desc";
        $url .= "&facet=true&facet.mincount=1&facet.limit=100&facet.field=category&facet.field=orientation&facet.field=production&facet.field=video_type";

        $results = $this->fetchURL($url);

        if ($results['header']['http_code'] != 200) {
            return false;
        }

        $this->json = json_decode($results['content']);

        $docs = $this->json->response->docs;

        if (count($docs) < 1) {
            return array();
        }

        $facets = json_decode($results['content'], true);
        $facets = $facets['facet_counts']['facet_fields'];

        $facets2 = array();
        foreach ($facets as $type => $faces) {
            $cnt = count($faces);
            for ($i = 0; $i < $cnt; $i++) {
                $facets2[$type][$faces[$i]] = $faces[$i + 1];
                $i++;
            }
        }

        $this->facets = $facets2;
        $this->numFound = $this->json->response->numFound;

        unset($facets);
        unset($facets2);

        $solrIDS = array();
        foreach ($docs as $id) {
            $solrIDS[] = $id->id;
        }

        return $solrIDS;
    }

}
