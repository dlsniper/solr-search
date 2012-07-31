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
        $curlOptions = array(CURLOPT_RETURNTRANSFER => true, // return web page
            CURLOPT_HEADER => false, // don't return headers
            CURLOPT_FOLLOWLOCATION => true, // follow redirects
            CURLOPT_ENCODING => "", // handle all encodings
            CURLOPT_USERAGENT => "SOLR parser 1.0", // who am i but a ghost
            CURLOPT_AUTOREFERER => true, // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 1, // timeout on connect
            CURLOPT_TIMEOUT => 1, // timeout on response
            CURLOPT_MAXREDIRS => 2); // stop after 10 redirects
        //CURLOPT_ENCODING       => "deflate, gzip, x-gzip, identity, *;q=0", //
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
            $terms = "(" . $terms . ")";
        }

        return $terms;
    }

    public function __construct($query = '*', $category = 'all', $rows = 35, $pageNumber = 1, $orientation = '', $production = 'all', $rating = 'all', $date = 'all', $order = 'relevance', $video_type = 'all', $mlt = false) {
        $this->pageNumber = $pageNumber;
        $this->perPage = $rows;
        $start = $pageNumber * $rows - $rows;

        $cols = array(
            'id',
                //  'category',
                //  'title',
                //  'description',
                //  'tags',
                //  'score'
        );

        $priorities = array(
            'ANDtitle' => 20,
            'ANDdesc' => 15,
            'ANDtags' => 5,
            'ORtitle' => 10,
            'ORdesc' => 5,
            'ORtags' => 1,
        );

        if (strlen($query) == 0) {
            $query = "*";
        }

        if ($mlt) {
            $query = "id:" . $query;
        } else {
            /** /
              $quer = $this->termsParser($query);
              /* */
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
        }

        $columns = implode('%2C', $cols);

        if ($category != 'all')
            $query .= ' AND category:' . $category;
        if ($orientation != 'all')
            $query .= ' AND orientation:' . $orientation;
        if ($production != 'all')
            $query .= ' AND production:' . $production;
        //if($rating      != 'all') $query .= ' AND rating:[' . $rating . ' TO *]';
        if ($date != 'all')
            $query .= ' AND video_date:[' . $date . ' TO NOW]';
        if ($video_type != 'all')
            $query .= ' AND video_type:' . $video_type;
        if ($order == 'relevance')
            $order = 'score';

        // boost by rating
        if ($rating == 'all')
            $rating = 0;
        // Rating search is temporarely overridden
        $query .= ' AND rating:([0 TO 3]^0.1 OR [3 TO 4]^5 OR [4 TO 5]^10)';

        $query = urlencode($query);

        $url = "http://209.239.175.38:8080/solr/freeporn/select/?q=" . $query;
        $url .= "&version=2.2&start=" . $start . "&rows=" . $rows;
        $url .= "&wt=json&fl=" . $columns;
        $url .= "&sort=" . $order . "%20desc";
        if (!$mlt)
            $url .= "&facet=true&facet.mincount=1&facet.limit=100&facet.field=category&facet.field=orientation&facet.field=production&facet.field=video_type";
        else
            $url .= "&mlt=true&mlt.fl=spell&mlt.count=10";

        $results = $this->fetchURL($url);

        if ($results['header']['http_code'] != 200) {
            return false;
        }

        $this->json = json_decode($results['content']);

        if ($mlt) {
            $query = explode("+AND", $query);
            $id = str_replace('id%3A', '', $query[0]);

            $docs = $this->json->moreLikeThis->$id->docs;

            $solrIDS = array();
            foreach ($docs as $doc => $id) {
                $solrIDS[] = $id->id;
            }

            $this->query = Doctrine::getTable('Video')->createQuery('b')
                    ->select('b.*, FIELD(b.id, ' . implode(",", $solrIDS) . ') AS field')
                    ->where('id in ?', array($solrIDS))
                    ->useResultCache(true)->setResultCacheLifeSpan(300)
                    ->orderBy('field');

            return $this->query;
        }

        $docs = $this->json->response->docs;

        if (count($docs) < 1) {
            return false;
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
        foreach ($docs as $doc => $id) {
            $solrIDS[] = $id->id;
        }

        $this->query = Doctrine::getTable('Video')->createQuery('b')
                ->select('b.*, FIELD(b.id, ' . implode(",", $solrIDS) . ') AS field')
                ->where('id in ?', array($solrIDS))
                ->useResultCache(true)->setResultCacheLifeSpan(300)
                ->orderBy('field');

        return $this->query;
    }

    public function execute() {
        return $this->query->execute();
    }

    public function getNumResults() {
        return $this->json->response->numFound;
    }

    public function getPreviousPage() {
        return max($this->pageNumber - 1, 1);
    }

    public function getPage() {
        return $this->pageNumber;
    }

    public function getExecuted() {
        return true;
    }

    public function getLastPage() {
        return ceil($this->getNumResults() / $this->perPage);
    }

    public function getNextPage() {
        return min($this->getPage() + 1, $this->getLastPage());
    }

    public function getPagerLayout($url_var, $path=null, $override_url=false) {
        if ($override_url === false) {
            require_once('skeletor/smarty_plugins/function.modify_url.php');
            $null = array();
            $opts = array($url_var => 'PAGE_NUMBER');
            if ($path != null)
                $opts['_path'] = $path;
            $url_format = smarty_function_modify_url($opts, $null);
            $url_format = str_replace('PAGE_NUMBER', '{%page_number}', $url_format);
        } else {
            $url_format = $override_url;
        }
        $pagerLayout = new SKEL_Pager_Layout($this,
                        new Doctrine_Pager_Range_Sliding(array('chunk' => 5)),
                        $url_format
        );
        //default skel paging templates
        $pagerLayout->setTemplate('<li><a href="{%url}">{%page}</a></li>');
        $pagerLayout->setSelectedTemplate('<li><span class="active">{%page}</span></li>');
        return $pagerLayout;
    }

    function getPagerLayoutSeoFp($override_url=false) {
        if ($override_url === false) {
            $url_format = Utils_General_Uri::getSefPagerUrl();
        } else {
            $url_format = $override_url;
        }
        $pagerLayout = new SKEL_Pager_Layout($this,
                        new Doctrine_Pager_Range_Sliding(array('chunk' => 5)),
                        $url_format
        );
        //default skel paging templates
        $pagerLayout->setTemplate('<li><a href="{%url}">{%page}</a></li>');
        $pagerLayout->setSelectedTemplate('<li><span class="active">{%page}</span></li>');
        return $pagerLayout;
    }

}
