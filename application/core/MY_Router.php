<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.2.4 or newer
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Open Software License version 3.0
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is
 * bundled with this package in the files license.txt / license.rst.  It is
 * also available through the world wide web at this URL:
 * http://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to obtain it
 * through the world wide web, please send an email to
 * licensing@ellislab.com so we can send you a copy immediately.
 *
 * @package     CodeIgniter
 * @author      EllisLab Dev Team
 * @copyright   Copyright (c) 2008 - 2014, EllisLab, Inc. (http://ellislab.com/)
 * @license     http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link        http://codeigniter.com
 * @since       Version 1.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Router Class
 *
 * Parses URIs and determines routing
 *
 * @package     CodeIgniter
 * @subpackage  Libraries
 * @category    Libraries
 * @author      EllisLab Dev Team
 * @link        http://codeigniter.com/user_guide/general/routing.html
 */
class MY_Router extends CI_Router {

    /**
     * List of routes
     *
     * @var array
     */
    public $routes =    array();

    /**
     * List of routes
     *
     * @var array
     */
    protected $closure_routes =    array();

    protected $verbs = array('GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS');


    // --------------------------------------------------------------------

    /**
     * Set route mapping
     *
     * Determines what should be served based on the URI request,
     * as well as any "routes" that have been set in the routing config file.
     *
     * @return  void
     */
    protected function _set_routing()
    {
        // Are query strings enabled in the config file? Normally CI doesn't utilize query strings
        // since URI segments are more search-engine friendly, but they can optionally be used.
        // If this feature is enabled, we will gather the directory/class/method a little differently
        if ($this->enable_query_strings)
        {
            $_d = $this->config->item('directory_trigger');
            $_d = isset($_GET[$_d]) ? trim($_GET[$_d], " \t\n\r\0\x0B/") : '';
            if ($_d !== '')
            {
                $this->set_directory($this->uri->filter_uri($_d));
            }

            $_c = $this->config->item('controller_trigger');
            if ( ! empty($_GET[$_c]))
            {
                $this->set_class(trim($this->uri->filter_uri(trim($_GET[$_c]))));

                $_f = $this->config->item('function_trigger');
                if ( ! empty($_GET[$_f]))
                {
                    $this->set_method(trim($this->uri->filter_uri($_GET[$_f])));
                }

                $this->uri->rsegments = array(
                    1 => $this->class,
                    2 => $this->method
                );
            }
            else
            {
                $this->_set_default_controller();
            }

            // Routing rules don't apply to query strings and we don't need to detect
            // directories, so we're done here
            return;
        }

        // Load the routes.php file.
        if (file_exists(APPPATH.'config/routes.php'))
        {
            include(APPPATH.'config/routes.php');
        }

        if (file_exists(APPPATH.'config/'.ENVIRONMENT.'/routes.php'))
        {
            include(APPPATH.'config/'.ENVIRONMENT.'/routes.php');
        }

        // Validate & get reserved routes
        if (isset($route) && is_array($route))
        {
            isset($route['default_controller']) && $this->default_controller = $route['default_controller'];
            isset($route['translate_uri_dashes']) && $this->translate_uri_dashes = $route['translate_uri_dashes'];
            unset($route['default_controller'], $route['translate_uri_dashes']);

            foreach ($this->verbs as $verb) {
                if (isset($route[$verb])) {
                    $this->closure_routes[$verb] = $route[$verb];

                    unset($route[$verb]);
                }
            }

            $this->routes = $route;
        }

        // Is there anything to parse?
        if ($this->uri->uri_string !== '')
        {
            $this->_parse_routes();
        }
        else
        {
            $this->_set_default_controller();
        }
    }


    public function closure_routes_exists() {
        if (count($this->closure_routes)>0) {
            return true;
        }

        return false;
    }

    public function execute_closure_routes() {
        $http_verb = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : 'cli';
        $http_verb = strtoupper($http_verb);

		$method = null;
		if (isset($this->closure_routes[$http_verb][$this->class])) {
			$method = $this->closure_routes[$http_verb][$this->class];
			if (
				is_object($method) &&
				$method instanceOf Closure &&
				is_callable($method)
			) {
				$method();
				return true;
			}
		}
        return false;
    }
}

/* End of file Router.php */
/* Location: ./application/core/MY_Router.php */