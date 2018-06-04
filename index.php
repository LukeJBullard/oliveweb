<?php
    /**
     * OliveWeb Framework
     * Entry Point, Routing, and Module Management Code
     * August 2014 - June 2018
     * 
     * @version 2.1
     * @author Luke Bullard
     */
    
    /*
        The INPROCESS constant is checked by other scripts
        to verify that they are not being accessed directly.
        This script needs to be the entry point.
    */
    define("INPROCESS", TRUE);
    
    //execution state constants
    define("EXESTATE_ERROR", -1);
    define("EXESTATE_NOT_RAN", 0);
    define("EXESTATE_INIT", 1);
    define("EXESTATE_RUN", 2);
    define("EXESTATE_DONE", 3);
    
    /**
     * The Olive static-only class, responsible for routing, application flow (before the page code is loaded), and security.
     */
    class Olive
    {
        //private constructor and clone method to disable instantiation
        private function __construct(){}
        private function __clone(){}
        
        //execution state. based on the EXESTATE_* constants
        private static $m_exeState = EXESTATE_NOT_RAN;
        
        //base URL
        private static $m_baseURL;
        
        //request parameters (from URL)
        private static $m_requestParams;
        
        /**
         * Retrieves the base URL of the site. The base URL is typically the root of the OliveWeb installation.
         * 
         * @return String The Base URL
         */
        public static function baseURL()
        {
            return self::$m_baseURL;
        }
        
        /**
         * Retrieves the request parameter at the given index.
         * In a request to /abc/123/page requestParam 0 is abc, 1 is 123, and 2 is page.
         * If a match is found in the router for abc and a page named abc is loaded, abc is still a request parameter.
         * 
         * @param integer $a_index The index of the request parameter to retrieve.
         * @return string The retrieved request parameter, or an empty string if one was not found.
         */
        public static function requestParam($a_index)
        {
            //make sure
            // 1. the request params are set
            // 2. the request params variable is an array
            // 3. the index exists (the count is greater than the index, since index '0' == count '1')
            if (!isset(self::$m_requestParams) ||
                !is_array(self::$m_requestParams) ||
                !(count(self::$m_requestParams) > $a_index))
            {
                //one or more of the conditions isn't met. return an empty string
                return "";
            }
			
            //return the request param
            return self::$m_requestParams[$a_index];
        }
        
        /**
         * Displays a 404 error message, then exits successfully.
         * The script will first look for a page under /pages/errors/404/404.page.php
         * If this page is not found, it will look under /pages/404.php
         * If neither pages are found, it will produce a minimal, html-based 404 error.
         */
        public static function display404()
        {
            //http header for 404 errors
            header("HTTP/1.0 404 Object Not Found");
            
            //check if custom 404 page exists
            if (file_exists("pages/errors/404/404.page.php"))
            {
                //it exists, run it then exit
                require_once("pages/errors/404/404.page.php");
                exit(0);
            }
            
            //check if the custom 404 page exists (here for backwards compatibility)
            if (file_exists("pages/404.php"))
            {
                //it exists, run it then exit
                require_once("pages/404.php");
                exit(0);
            }
            
            //custom 404 page doesn't exist. echo a default 404 message then exit
            echo "<!DOCTYPE html>".
                "<html lang=\"en\">".
                "<head>".
                "<title>404 Page Not Found</title>".
                "<style type=\"text/css\">body,html{font-family:sans-serif;}</style>".
                "</head>".
                "<body>".
                "<center><h1>404 Page Not Found</h1></center>".
                "</body>".
                "</html>";
            exit(0);
        }
        
        //redirect to another $a_page.
        //$a_remote is true if the page is not on this OliveWeb installation
        /**
         * Redirect the user to a different page or website, then exit successfully.
         * This uses a header Location: redirect.
         * 
         * @param String $a_page The page to go to (without the base URL, if it's in the same OliveWeb installation),
         *                      or the URL to go to if it is outside the OliveWeb installation.
         * @param Boolean $a_remote Should be set to true if the page to redirect to is outside this OliveWeb installation.
         */
        public static function redirect($a_page, $a_remote=false)
        {
            //if not remote, add the base URL to the front of the page location
            if (!$a_remote)
            {
                $a_page = Olive::baseURL() . $a_page;
            }
            header("Location: " . $a_page);
            exit();
        }
        
        /**
         * Main entry point for OliveWeb
         */
        public static function execute()
        {
            //make sure we haven't already ran...
            if (self::$m_exeState != EXESTATE_NOT_RAN)
            {
                //this function shouldn't run more than once.
                //abandon function
                return;
            }
            
            //set the state to initializing
            self::$m_exeState = EXESTATE_INIT;
            
            //initialize and get the route request
            $t_routeRequest = self::initialize();
            
            //set the state to running
            self::$m_exeState = EXESTATE_RUN;
            
            //start the page running function
            self::runPage($t_routeRequest);
            
            //set the execution state to shutting down
            self::$m_exeState = EXESTATE_DONE;
            
            //close
            exit(0);
        }
        
        //initialization function
        //  returns the route request.
        private static function initialize()
        {
            //set up the base url constant:
            //1. remove any trailing forward slashes from the end of the
            //   current script's directory name
            //2. append just one forward slash to this new string
            self::$m_baseURL = rtrim(str_ireplace("\\","/",dirname($_SERVER['SCRIPT_NAME'])),"/") . "/";
            
            //retrieve the route request from the get variable `p`
            //(this is probably going to be used only when there is
            //no htaccess support present)
            if (isset($_GET['p']))
            {
                $t_routeRequest = rtrim(trim($_GET['p'],"/"),"/");
				
				//split the request params up into an array, then store them
                //in a private static member variable of this class
                self::$m_requestParams = explode("/",urldecode($t_routeRequest));
            } else {
                //the get variable wasn't used, therefore the
                //seo-friendly url (with htaccess) most likely was
                
                //split the request params and the GET variables into two
                $t_exploded = explode("?", $_SERVER['REQUEST_URI'],2);
                
                //here's the route request...
                $t_routeRequest = $t_exploded[0];
                
                //take the base URL off the beginning of the route request
                //1. confirm the base URL isn't the document root
                if (self::baseURL() != "/")
                {
                    //2. find the location of the base URL in the route request
                    $t_baseURLLocation = strpos($t_routeRequest,self::baseURL());
                    
                    //3. make sure it was found
                    if ($t_baseURLLocation !== false)
                    {
                        //4. it was found, so make sure it is at the beginning
                        if ($t_baseURLLocation == 0)
                        {
                            //5. remove the baseURL
                            $t_routeRequest = substr($t_routeRequest,strlen(self::baseURL()));
                        }
                    }
                }
                
                //trim trailing slashes and spaces off the end of the route request
                $t_routeRequest = rtrim($t_routeRequest," /");
                
                //set route request to index if not set
                if ($t_routeRequest == "")
                {
                    $t_routeRequest = "index";
                }
                
                //now for the GET variables
                if (isset($t_exploded[1]))
                {
                    //iterate through all of the GET pairs
                    foreach (explode("&",$t_exploded[1]) as $t_GETPair)
                    {
                        //split the GET pair into name and val
                        $t_splitGETPair = explode("=",$t_GETPair,2);
                        
                        //if there is an item with index 1 (ie. value),
                        //set the GET pair in the superglobal $_GET array
                        //(this mocks the effect that code not using the
                        //htaccess system would get)
                        if (isset($t_splitGETPair[1]))
                        {
                            $_GET[$t_splitGETPair[0]] = $t_splitGETPair[1];
                        }
                    }
                }
                
                //split the request params up into an array, then store them
                //in a private static member variable of this class
                self::$m_requestParams = explode("/",urldecode($t_routeRequest));
            }
            return urldecode($t_routeRequest);
        }
        
        //page running function
        private static function runPage($t_routeRequest)
        {
            //send the current location in the header,
            //as some javascript routines may find this useful
            header("Current-Location: " . $t_routeRequest);
            
            //lookup the route request in the routing config
            require_once("pages/routing.php");
            
            //variable for the final application page to run
            $t_appPage = "";
            
            //check if a response is available in the literal string routing array
            if (isset($routing_literal) && isset($routing_literal[$t_routeRequest]))
            {
                //it exists, so set the app page variable to it's value.
                $t_appPage = $routing_literal[$t_routeRequest];
            } else {
                //no response exists in the literal string routing array.
                //check the regex string routing array for a response
                //  iterate through the array until a match is found
                foreach ($routing_regex as $t_regexPattern => $t_regexPage)
                {
                    //if the pattern matches, set the app page to the regex page value
                    if (preg_match($t_regexPattern,$t_routeRequest) == 1)
                    {
                        $t_appPage = $t_regexPage;
                        break;
                    }
                }
                
                //if the app page wasn't set, no match was found. call a 404.
                if ($t_appPage == "")
                {
                    self::display404();
                }
            }
            
            //prepend the pages folder location to the app page string, and
            //append the file extension for pages (.page.php)
            $t_appPage = "pages/" . $t_appPage . ".page.php";
            
            //make sure the file exists
            if (!file_exists($t_appPage))
            {
                //the file doesn't exist. display 404 error and quit
                self::display404();
            }
            
            //include_once the file
            include_once($t_appPage);
        }
    }
    
    //Modules registry defined error codes
    define("ERR_MOD_EXISTS",-1); //the module to be loaded already exists
    define("ERR_MOD_NOTFOUND",-2); //the module to be loaded was not found
    define("ERR_MOD_NOINTERFACE",-3); //the class for the module was not found in it's source
    
    /**
     * Modules Singleton
     * Keeps track of all loaded modules, loads modules on demand, and provides array-style access to modules.
     */
    class Modules implements ArrayAccess
    {
        //static instance for singleton
        private static $m_instance = null;
        
        //the actual modules registry array
        private $m_registry = array();
        
        //private constructor and clone method to disable outside instantiation
        private function __construct(){}
        private function __clone(){}
        
        /**
         * Retrieves the sole instance of the Modules Singleton
         */
        public static function getInstance()
        {
            //if the instance doesn't exist, make it
            if (self::$m_instance === null)
            {
                self::$m_instance = new Modules();
            }
            
            return self::$m_instance;
        }
        
        /**
         * Loads a module
         * 
         * @param String $a_key The name of the Module to load.
         * @return Int|Boolean The integer error code or boolean True if the module loaded successfully.
         */
        public function load($a_key)
        {
            //strtolower the module key, so that the keys are case-insensitive
            $t_cleanKey = strtolower($a_key);
            
            //make sure something for the key doesn't already exist
            if (isset($this->m_registry[$t_cleanKey]))
            {
                //something already exists. return the error code
                return ERR_MOD_EXISTS;
            }
            
            //filesystem path to module's main file
            $t_modulePath = "modules/" . $t_cleanKey . "/" . $t_cleanKey . ".mod.php";
            
            //confirm the module exists in the modules directory
            if (!file_exists($t_modulePath))
            {
                return ERR_MOD_NOTFOUND;
            }
            
            //include_once the module file
            include_once($t_modulePath);
            
            //class name for the module
            $t_moduleClass = "MOD_" . $t_cleanKey;
            
            //make sure the module's class exists
            if (!class_exists($t_moduleClass))
            {
                return ERR_MOD_NOINTERFACE;
            }
            
            //instantiate the module, then store the instance in the registry
            $this->m_registry[$t_cleanKey] = new $t_moduleClass();
            return true;
        }
        
        /**
         * Retrieves a module. If the module isn't loaded, it will attempt to load it.
         * 
         * @param String $a_key The name of the module.
         * @return Object|Boolean The module object or Boolean false if it was unable to load
         */
        //get a module instance from the registry
        public function get($a_key)
        {
            //strtolower the module key, so that the keys are case-insensitive
            $t_cleanKey = strtolower($a_key);
            
            //make sure the key exists
            if (!isset($this->m_registry[$t_cleanKey]))
            {
                //it doesn't exist. try to load it
                if (!$this->load($t_cleanKey))
                {
                    //no module able to load with that name either.
                    //return false signifying error
                    return false;
                }
            }
            
            return $this->m_registry[$t_cleanKey];
        }
        
        /**
         * Returns if a module is loaded already.
         * 
         * @param String $a_key The name of the module to test for.
         * @return Boolean If the module is currently loaded.
         */
        public function offsetExists($a_key)
        {
            //strtolower the module key
            $t_cleanKey = strtolower($a_key);
            
            //return whether or not the key exists at all
            return isset($this->m_registry[$t_cleanKey]);
        }
        
        public function offsetGet($a_key)
        {
            //strtolower the module key
            $t_cleanKey = strtolower($a_key);
            
            //return the module if it was sent from the get() method,
            //return null otherwise (ie. when it returned false)
            $t_possibleModule = $this->get($a_key);
            if ($t_possibleModule !== false)
            {
                return $t_possibleModule;
            }
            return null;
        }
        
        //no offsetSet or offsetUnset, since those should be kept
        //safe for the sake of all the modules lifespans
        public function offsetSet($a_key,$a_value){}
        public function offsetUnset($a_key){}
    }
    
    //invoke the page
    Olive::execute();
    
    //we shouldn't get here, as the Olive::execute function should
    //exit the page when it's done. just in case:
    exit(0);
?>
