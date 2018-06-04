<?php
    /**
     * OliveWeb Framework
     * Application Routing Configuration File
     * September 2014 - June 2018
     * 
     * @version 2.1
     * @author Luke Bullard
     */
    
    //make sure we are included securely
    if (!defined("INPROCESS")) { header("HTTP/1.0 403 Forbidden"); exit(0); }
    
    /**
     * @var Array $routing_literal Key: The URL browsed to. Value: The page to load. (Value should be just: abc for the page /pages/abc.page.php)
     */
    $routing_literal = array();
    
    /**
     * @var Array $routing_regex Key: A regular expression to match the URL against. Value: The page to load. (See $routing_literal for formatting the Value)
     */
    $routing_regex = array();
?>