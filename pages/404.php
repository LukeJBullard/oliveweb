<?php
    /**
     * OliveWeb Framework
     * Default 404 Error Page
     * September 2014 - June 2018
     * 
     * @version 2.1
     * @author Luke Bullard
     */

    //make sure we are included securely
    if (!defined("INPROCESS")) { header("HTTP/1.0 403 Forbidden"); exit(0); }
    
    //send 404 in header
    header("HTTP/1.0 404 Not Found");
    
    $pageVariables = array(
        "homeLink" => Olive::baseURL()
    );
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>404 Page Not Found</title>
        <style type="text/css">
            html,body {font-family: sans-serif;}
        </style>
    </head>
    <body>
        <center>
            <h1>Oh No! It's a 404 Error!</h1>
            <h2>The Page You Requested Was Not Found!</h2>
            <h3>
                <a href="<?php echo $pageVariables['homeLink']; ?>">Home</a>
            </h3>
            <br />
            <br />
            <h4>404 Error: Requested page or resource not found. Please try again later.</h4>
        </center>
    </body>
</html>
