<?php
error_reporting(E_ALL);
ini_set("display_errors",1);

require 'vendor/autoload.php';
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page;

function fetchResults($found, $isNew){
    global $browser;
    global $page;

    if ($isNew) {
        //wait for content loaded in dom
        sleep(10);
        // $evaluation->waitForPageReload();

        //get html 
        $value = $page->evaluate('document.querySelector("#pane").innerHTML')->getReturnValue();

        //find name
        $pattern = "'<\s*?h3\b[^>]*><\s*?span\b[^>]*>(.*?)</span\b[^></h3\b[^>]*>'";
        preg_match_all($pattern, $value, $matches);
        $result = $matches[1];
        
        //if list is less or greter than 20 there is no more results
        if(count($result) < 20 || count($result) > 20){
            foundResults($result);
        }else{
            fetchResults($result, false);
        }
    }else{
        //click on next
        $evaluation = $page->evaluate('document.querySelector(".gm2-caption button + button").click()');

        //wait for content loaded in dom after next button clicked
        sleep(7);
        $value = $page->evaluate('document.querySelector("#pane").innerHTML')->getReturnValue();

        $pattern = "'<\s*?h3\b[^>]*><\s*?span\b[^>]*>(.*?)</span\b[^></h3\b[^>]*>'";
        preg_match_all($pattern, $value, $matches);
        $result = $matches[1];

        if (count($result) < 20 || count($result) > 20) {
            //return final result
            $newList = array_merge($result, $found);
            foundResults($newList);
        } else {
            //fetch more results
            $newList = array_merge($result, $found);
            fetchResults($newList, false);
        }
    }
}
?>

<form>
<h3>Find Neighborhoods</h3>
<input type="text" name="q" placeholder="city, state" />
<input type="submit" value="Search">
</form>

<?php

if(isset($_GET['q']) && !empty($_GET['q'])){
    $q = str_replace(" ","+", $_GET['q']);
    echo "<a target='_blank' href='https://www.google.co.in/maps/search/neighborhoods+in+$q'>Open In Map</a>";

    $browserFactory = new BrowserFactory('chromium-browser');
    $browser = $browserFactory->createBrowser();
    try {
        $page = $browser->createPage();
        $page->navigate('https://www.google.co.in/maps/search/neighborhoods+in+'.$q)->waitForNavigation();
        
        $foundNeighborhoods = [];
        $isNew = true;
        fetchResults($foundNeighborhoods, $isNew);    
    } finally {
        $browser->close();
    }
}


function foundResults($results){
    echo "<pre>";print_r($results);
}
?>

