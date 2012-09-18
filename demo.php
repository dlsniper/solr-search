<?php

include 'solr.php';

$solrQuery = new Solr;

$solrQuery
    ->setConnection('valid_dsn_here')
    ->setQueryFields(array('nume', 'categorie', 'pret', 'rating'))
    ->setOrderFields(array('relevance' => 'desc', 'pret' => 'asc', 'nume' => 'asc'))
    ->setReturnFields(array('id'))
    ->setQuery('tastatura MS');

var_dump($solrQuery->getResults());

