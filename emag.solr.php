<?php

$query = 'http://devel.site:8080/solr-example/core0/select?';
$params = array(
    "wt" => "json",
    "indent" => "on",
    "q" => "G700",
    "start" => "0",
    "rows" => "10",
    "fl" => "id,score,type,exact_title,exact_part_number,description",

//    "fq" => "category_id:91",
//    "fq" => "main_category_id:(\"452\")",
//    "fq" => "filter:((\"1938-y-2771\"))",
//    "fq" => "price:[1500 TO 2000]",
//    "fq" => "is_rsg:0",
//    "fq" => "type:product",
//    "defType" => "dismax",
//    "mm" => "50%",
//    "qf" => "exact_title^3 exact_part_number^2",

);


echo $query . http_build_query($params);
echo "\n\n";

//query:"{!dismax qf=searchText bq='(: -siteId:1 -siteId:2 -siteId:16)^1.5 OR canEmbed:1^1.005'}lesbian"

$search = "http://dev-sk.freeporn.com/florin/solrbj.php?q={!boost+b%3Drecip%28ms%28NOW%2CdateAdded%29%2C3.16e-1%2C1%2C1%29}canEmbed%3A%280^0.1+OR+1^1000%29+AND+searchText%3A%28eva+and+angelina%29&start=0&rows=100";
//        'http://dev-sk.freeporn.com/florin/solrbj.php?q={!boost b=recip(ms(NOW,dateAdded),3.16e-1,1,1)} canEmbed:(0^0.1 OR 1^1000) AND searchText:(eva and angelina)&start=0&rows=100';
parse_str($search,  $searchArr);
echo '<pre>';
print_r($searchArr);
echo '</pre>';
