<?php

include 'solr.php';

class SolrDemo {

    private $solr = null;

    public function __construct(Solr $solr) {
        $this->solr = $solr
            ->setConnection('valid_dsn_here')
            ->setQueryFields(array('nume', 'categorie', 'pret', 'rating'))
            ->setOrderFields(array('relevance' => 'desc', 'pret' => 'asc', 'nume' => 'asc'))
            ->setReturnFields(array('id'));
    }

    public function query($query, $page = 0, $resultsPerPage = 0) {
        return $this->solr
            ->setQuery($query)
            ->getResults($page, $resultsPerPage);
    }

}

$solrQuery = new SolrDemo(new Solr());

var_dump($solrQuery->query('tastatura MS'));



// id, name, description, short_description, price, rating, category_id, subcategory_id,
// normalized_name, normalized_description, normalized_short_description, normalized_category, normalized_subcategory

