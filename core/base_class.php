<?php if( ! defined('ABSPATH')) exit('restricted access');

/**
 * Extracoding Framework
 *
 * An open source extracoding framework
 *
 * @package		Extracoding framework
 * @author		Extracoding team <info@extracoding.com>
 * @copyright	Copyright 2015 © Extracoding - All rights reserved
 * @license		http://extracoding.com/framework/license.html
 * @link		http://extracoding.com
 * @since		Version 1.0
 */

//Load Config class if it's not loaded
if ( ! class_exists( 'eXc_Config_Class' ) )
{
	require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'config_class.php' );
}

/**
 * eXc_Base_Class
 *
 * This class provides the core functionality of extracoding framework
 *
 * @package		Extracoding framework
 * @subpackage	Core
 * @category	Core
 * @author		Hassan R. Bhutta
 * @path 		core/base_class.php
 * @license Copyright 2014 - 2015 © Extracoding. - All rights reserved
 */

if ( ! class_exists('eXc_Base_Class') )
{
	abstract class eXc_base_class extends eXc_Config_Class
	{	
		//Auto load core classes
		private $_autoload = array('core/array_class', 'core/wp_admin_class');
		
		//The path query
		private $_path_query = array('path' => '', 'force_path' => '');
		
		//cache variable for views
		private $_cached_vars = array();
		
		//the status of class
		public $_load_status = true;
		
		//the base path
		//public static $base_path = '';
		
		//exc framework url
		protected $system_url = '';
		
		//exc framework directory path
		protected $system_dir = '';
		
		//Local plugin or theme system directory path		
		protected $local_system_dir = '';
		
		//Local plugin or theme system directory URL
		public $local_system_url = '';
		
		private $fallback_path = '';
		
		public $text_domain = '';
		
		//The version of plugin and theme
		protected $version = '';
		
		/**
		 * Constructor
		 *
		 * The constructor of extracoding framework
		 * 
		 */

		function __construct()
		{
			//Local Environment setup
			$this->env_setup();

			if ( isset( $this->product_name ) )
			{
				$GLOBALS['_eXc'][ $this->product_name ] =& $this;
			}
			
			//Extracoding system directory path settings

			if ( defined( 'THEME_NAME' ) ) // Is loading from theme directory?
			{
				$this->system_dir = trailingslashit( get_template_directory() . '/includes/thirdparty/exc-framework' );
				$this->system_url = get_template_directory_uri() . '/includes/thirdparty/exc-framework/';
			} else
			{
				$this->system_dir = trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) );
				$this->system_url = plugin_dir_url( dirname( __FILE__ ) );
			}
			
			//Autoload classes
			foreach ( $this->_autoload as $class )
			{
				$this->load( $class );
			}
		}
			
		/**
		 * Load PHP classes
		 *
		 * This method includes and auto initiate the constant of PHP classes from includes folder.
		 * These classes are accessible locally through $this
		 *
		 * @access	public
		 * @example	$this->load($class_path, options); it will be accessible through exc_base_class::options
		 * @param	string the path of class to load without .php e.g core/class_name
		 * @param	string the class object name default is the same as class name
		 * @param	boolen load and initialize class
		 * @param	boolen create object of class on given object
		 * @return	object
		 */
		
		//@TODO: USE ReflectionMethod to get the arguments list dynamically
		//static function load($class = '', $objectName = '', $loadonly = false, $deps = array())
		public final function load( $class = '', $objectName = '', $loadonly = false, &$object = array(), $params = array() )
		{
			//@TODO: don't return instant if there are multiple files to load
			//foreach($class as $name)
			//{
				// Get the class name
				$classname = basename( $class );

				// guess system class name
				$system_class = 'eXc_' . $classname;

				/** Load File */
				$is_loaded = ( class_exists( $system_class ) || class_exists( $classname ) ) || $this->get_file_path( $class, true );

				/** Load Dependencies */
				//if( ! empty($this->_path_query['deps'])) $this->_load_deps();

				/** clear temporary path and dependencies list */
				
				if ( ! $is_loaded)
				{
					exc_die(
						sprintf( 
							__('The file %s is not exists.', 'exc-framework'),
							$classname
						)
					);
				}
				
				$this->clear_query();
				
				/** don't do anything if we just have to load file */
				if ( ! $loadonly )
				{
					//make sure load status is true, in case __constructor makes it false, then we will auto unset object
					$this->_load_status = true;
					
					/** check if it's system class */
					//@TODO: the code is lengthy, reduce it
					if ( class_exists( $system_class ) )
					{
						$objectName = ( $objectName ) ? $objectName : str_ireplace( '_class', '', $classname );
						
						if ( is_object( $object ) )
						{
							$object->{ $objectName } = ( isset( $object->{ $objectName } ) ) ? $object->{ $objectName } : new $system_class( $this, $params );
							
							$object->{ $objectName }->_obj_name = $objectName;
													
							if ( ! $this->_load_status )
							{
								return $this->destory( $objectName, $object );
							}
							
							return $object->{ $objectName };
							
						} else
						{
							$this->{ $objectName } = ( isset( $this->{ $objectName } ) ) ? $this->{ $objectName } : new $system_class( $this, $params );
							
							//hack & Pass the object name so we can reset it from class
							$this->{ $objectName }->_obj_name = $objectName;
													
							if ( ! $this->_load_status )
							{
								$this->destory( $objectName );
								return;
							}
							
							return $this->{ $objectName };
						}
						
					} elseif( class_exists( $classname ) )
					{
						$objectName = ( $objectName ) ? $objectName : $classname;

						if ( is_object( $object ) )
						{
							$object->{ $objectName } = ( $object->{ $objectName } ) ? $object->{ $objectName } : new $classname;
							
							$object->{ $objectName }->_obj_name = $objectName;
							
							if( ! $this->_load_status )
							{
								return $this->destory( $objectName, $object );
							}

							return $object->{ $objectName };
							
						} else
						{
							
							$this->{ $objectName } = ( $this->{ $objectName } ) ? $this->{ $objectName } : new $classname;
							
							$this->{ $objectName }->_obj_name = $objectName;
							
		
							if ( ! $this->_load_status )
							{
								return $this->destory( $objectName );
							}

							return $this->{ $objectName };
						}
					}
				}
			//}
			
			return true;
		}
		
		/**
		 * Section settings
		 *
		 * A method to read the settings
		 * if the section list is empty it will call the get_sections.
		 *
		 * @access	public
		 * @param string section name (default is general settings)
		 * @param string array key
		 * @return array
		 */
		 
		final function get_settings( $key = '', $normalize = true, $depth = -2 )
		{
			if ( ! is_array( $key ) )
			{
				if ( ! $key || ! $settings = get_option( $key ) )
				{
					return array();
				}

			} else 
			{
				$settings = $key;
			}
			
			
			if( ! $normalize ) {
				return $settings;
			}
			
			//Normalize data
			return $this->normalize_data( $settings, $depth );
		}
		
		final function normalize_data( $settings, $depth = 0 )
		{
			$data = array();
			
			foreach ( $settings as $k => $v )
			{
				$parts = explode( '-', $k );
				
				$structure = array_slice( $parts, $depth );
				
				$this->array->set_xpath_value( $data, implode( '/', $structure ), $v, 'set' );
				
			}
			
			return $data;
		}
		
		/**
		 * Set value
		 *
		 * A method to check the variable value, if the value is not exists it will return the second variable default value
		 *
		 * @access	public
		 * @param	string defined value or variable
		 * @param	string default value
		 * @param	boolean true if the string is defined
		 * @return string
		 */
		 
		 /*
		final function set_value($constant, $value = '', $is_defined = false)
		{
			if($is_defined)
			{
				$constant_value = constant($constant);
				if ( defined($constant) && ! empty($constant_value)) return $constant_value;
				else return $value;
			}else
			{
				if(empty($constant)) return $value;
				else return $constant;
			}
		}*/
		
		/**
		 * Function to check whether a key is exists in array, Otherwise return default value
		 * @param arr  array an array from which a key need to be checked
		 * @param key  string A string need to be checked either exists in an array or not
		 * @param default string/array If the key is not exists in given array then the default value will be returned
		 * 
		 * @return string/array Either array or string will be returned.
		 */
		 
		 //@TODO: change the name to kv for short access
		 /*
		final function kvalue($array = array(), $keys, $default = false, $echo = false)
		{
			$keys = is_array($keys) ? implode('/', $keys) : $keys;
			$return = $this->array->get_xpath_value($array, $keys, $default);
			
			if($echo) echo $return;
			else return $return;
		}*/
		
		final function get_file_path($file, $autoload = false, $ext = '.php')
		{
			$fileName = $file . $ext;

			if ( isset( $this->_path_query['path']) )
			{
				$path = realpath( $this->_path_query['path'] . DIRECTORY_SEPARATOR . $fileName );
				
				if( ! $path && ! empty($this->_path_query['force_path']) ) {
					return false;
				}
			}
			
			if ( empty( $this->_path_query['path'] ) || empty( $this->_path_query['force_path'] ) )
			{
				$fileName = ( ! empty( $path ) ) ? $path : $fileName;
		
				// prefer the local path
				$path = $this->locate_file( $fileName );
			}

			return ( $autoload && $path ) ? require_once( $path ) : $path;
		}
		
		final function system_url( $path = '', $local_dir = false )
		{
			return ( $local_dir ) ? $this->local_system_url . $path : $this->system_url . $path;
		}
		
		final function system_path( $path, $file = '' /*, $force_local_path = false*/ )
		{
			$file_path = ( $file ) ? $path . '/' . $file . '.php' : $path;

			return str_replace( $file . '.php', '', $this->locate_file( $file_path ) );
		}
		
		final function set_path( $path = '', $force_path = true )
		{
			if ( ! realpath( $path ) && $force_path ) 
			{
				exc_die(
					sprintf( 
						__('The directory path "%s" is not valid.', 'exc-framework'),
						$path
					)
				); //@TODO: move string into lanuage file and support multilingual
			}
			
			$this->_path_query['path'] = $path;
			$this->_path_query['force_path'] = $force_path;
			
			return $this;
		}
		
		final function clear_query()
		{
			$this->_path_query = array( 'path' => '', 'force_path' => '' );
			
			return $this;
		}
		
		final function load_widget( $file )
		{
			//make sure the abstract class is loaded
			if( ! class_exists( 'eXc_Widgets' ) )
			{
				$this->load_abstract( 'core/abstracts/widgets_abstract' );
			}
			
			$classname = 'eXc_' . basename( $file );
			
			$this->load( $file );
			
			register_widget( $classname );
			
			return $this;
		}
			
		final function load_abstract( $classes )
		{
			//@TODO: revalidate if the class loaded or not
			//@TODO: auto set path if it's not there
			$this->load( $classes, '', true );
			
			return $this;
		}
		
		final function load_with_args( $file, $objectName = '', $args )
		{
			$obj = array();
			
			$this->load( $file, $objectName, false, $obj, $args );
		}
		
		final function load_file( $file )
		{
			return $this->load( $file, '', false );
		}
			
		final function load_library( $file, $argument = '' )
		{
			if ( $argument )
			{
				exc_die( __("Arguments passed in load library", 'exc-framework' ) );
			}
			
			$path = $this->system_path( 'libraries', $file );
			
			$this->set_path( $path );
			
			$this->load( $file, '', true );
		}
		
		final function load_template( $file, $vars = array(), $_view_file_return = FALSE )
		{
			if( ! $_view_file_path = locate_template( $file.'.php' ) ) {
				return;
			}

			return $this->load_view( basename( $file ), $vars, $_view_file_return, dirname( $_view_file_path ) );
		}
		
		final function load_view( $file, $vars = array(), $_view_file_return = FALSE, $_view_file_path = '' )
		{
			/** convert object array to standard array */
			$vars = ( is_object( $vars ) ) ? get_object_vars( $vars ) : $vars;
			
			$path = ( $_view_file_path ) ? $_view_file_path : $this->system_path( 'views', $file );

			$this->set_path( $path );
			
			/** Buffer the loaded views one by one */
			//if( ! $this->_path_query['ob_level']) $this->_path_query['ob_level' = ob_get_level();

			if ( $_view_file_path = $this->get_file_path( $file ) )
			{
				$this->_cached_vars = array_merge( $this->_cached_vars, $vars );
				
				extract( $this->_cached_vars );

				ob_start();

				include( $_view_file_path );
				
				if ( $_view_file_return === TRUE )
				{
					$buffer = ob_get_contents();
					@ob_end_clean();
					
					$this->clear_query();
					
					return $buffer;
				}
				
				ob_end_flush();
			}
			
			$this->clear_query();
		}
		
		final function locate_file( $file, $location = array( 'local_dir', 'system_dir' ) )
		{
			if ( ! is_array( $location ) )
			{
				//$location = ( ! empty( $location ) ) ? array( $location ) : array( 'local_dir', 'system_dir' );
				$location = array( $location );
			}

			if ( in_array('local_dir', $location) && defined('CHILD_THEME_PATH') && realpath( CHILD_THEME_PATH . '/includes/' . $file ) )
			{
				return CHILD_THEME_PATH . '/includes/' . $file;

			} elseif( in_array('local_dir', $location) && realpath( $this->local_system_dir . $file ) )
			{
				return $this->local_system_dir . $file;

			} elseif ( ! empty( $this->fallback_path ) && realpath( $this->fallback_path . $file ) )
			{
				return $this->fallback_path . $file;

			} elseif ( in_array('system_dir', $location) && realpath( $this->system_dir . $file ) )
			{
				return $this->system_dir . $file;

			} else
			{
				return false;
			}
		}
		
		final function get_file_url( $file, $location = array() )
		{
			if ( ! is_array( $location ) || ! $location )
			{
				$location = ( ! empty( $location ) ) ? array( $location ) : array( 'local_dir', 'system_dir' );
			}

			if( in_array('local_dir', $location) && realpath( $this->local_system_dir . $file ) )
			{
				return $this->local_system_url . $file;
			} 
			elseif ( in_array('system_dir', $location) && realpath($this->system_dir . $file) )
			{
				return $this->system_url . $file;

			} else
			{
				return false;
			}
		}
		
		final function destory($object_name, &$obj = array())
		{
			if($obj && is_object($obj->{$object_name}))
			{
				unset($obj->{$object_name});
				
			}elseif( ! $obj && $this->{$object_name})
			{
				unset( $this->{$object_name} );
			}
		}

		final function set_fallback_path( $path )
		{
			$this->fallback_path = $path;
		}
		
		final function get_version()
		{
			return $this->version ? $this->version : '1.0';
		}

		final function get_product_name()
		{
			return $this->product_name;
		}

		protected function env_setup()
		{
			$obj = new ReflectionClass( $this );
	    	$filename = $obj->getFileName();
			
			$this->product_name = ( ! empty( $this->product_name ) ) ? $this->product_name : basename( dirname( $filename ) );
			
			// is local theme directory
			// @TODO: add wp native check for theme directory
			if ( defined( 'THEME_NAME' ) )
			{
				$this->local_system_dir = get_template_directory() . '/includes/';
				$this->local_system_url = get_template_directory_uri() . '/includes/';
				
			} else 
			{
				$this->local_system_dir = plugin_dir_path( $filename );
				$this->local_system_url = plugin_dir_url( $filename );			
			}

			$this->view_path = $this->local_system_dir . 'views/';

			// Additional Settings for theme

			if ( isset( $this->is_theme ) )
			{
				// Define Unique Theme Prefix to avoide conflict between multiple themes settings

				$name = ( $this->product_name ) ? $this->product_name : basename( dirname( $filename ) );

				defined( 'THEME_PREFIX' ) or define( 'THEME_PREFIX', $name . '_' );
						
				// Theme directory URL
				defined( 'THEME_URL' ) or define( 'THEME_URL', get_template_directory_uri() );
				
				// Child Theme directory Path
				defined( 'CHILD_THEME_PATH' ) or define( 'CHILD_THEME_PATH', get_stylesheet_directory() );
				
				// Child Theme directory URL
				defined( 'CHILD_THEME_URL' ) or define( 'CHILD_THEME_URL', get_stylesheet_directory_uri() );
				
				// is Child theme active
				defined( 'IS_CHILD_THEME' ) or define( 'IS_CHILD_THEME', (THEME_PATH != CHILD_THEME_PATH) ? true : false );
			}
		}
	}
}

if ( ! function_exists( 'exc_load_plugin' ) )
{
	function exc_load_plugin( $class, $arguments = array() )
	{
		$class = strtolower( $class );

		if ( ! class_exists( $class ) )
		{
			// Die or display error
		} else
		{
			$GLOBALS['_eXc'][ $class ] = new $class( $arguments );
		}
	}
}

if ( ! function_exists( 'exc_get_instance' ) )
{
	function &exc_get_instance( $class )
	{
		$class = strtolower( $class );

		if ( isset( $GLOBALS['_eXc'][ $class ] ) )
		{
			return $GLOBALS['_eXc'][ $class ];
		}
	}
}

/**
 * Print array in readable format
 *
 * A print_r similar function to print the array in readable format with the help of HTML &lt;pre&gt; tag
 *
 * @access	public
 * @param	array to print
 * @param	boolean default is true to auto exit the php script execution
 * @return	void
 */
if( ! function_exists('printr') )
{
	function printr()
	{
		$exit = true;
		$args = func_get_args();
		
		if(func_num_args() > 1)
		{
			end($args);
			
			if(current($args) == 'FALSE')
			{
				$exit = false;
				array_pop($args);
			}
		}
		
		echo '<pre>';
		foreach($args as $argument)
		{
			print_r($argument);
			echo "\n";
		}
		
		if( $exit )
		{
			exit;
		}
	}
}

/**
 * exc_ajax_login_check check through ajax if user is logged in
 *
 * The function check if user is logged in
 *
 * @access	public
 * @param	void
 * @return	JSON
 */
 
if( ! function_exists('exc_ajax_login') )
{
	function exc_ajax_login()
	{
		if ( ! wp_verify_nonce( exc_kv( $_POST, 'security'), 'exc-login-check' ) )
		{
			wp_send_json_error();
		}
		
		if ( is_user_logged_in() )
		{
			wp_send_json_success();
			
		} else 
		{
			exc_die( _x( 'You are not logged in.', 'exc-login', exc_theme_instance()->text_domain ) );
		}
	}
	
	add_action( 'wp_ajax_exc_login_check', 'exc_ajax_login' );
	add_action( 'wp_ajax_nopriv_exc_login_check', 'exc_ajax_login' );
}

if ( ! function_exists( 'exc_kv' ) )
{
	function exc_kv( $array = array(), $keys, $default = '', $echo = false )
	{
		if ( ! is_array( $array ) )
		{
			// Treat array as primary value and keys as default
			$return = ( $array ) ? $array : $keys;
		} else
		{
			$keys = is_array( $keys ) ? implode( '/', $keys ) : $keys;
			$return = exc_array_class::get_xpath_value( $array, $keys, $default );
		}
		
		if ( $echo )
		{
			echo $return;
			
		} else
		{
			return $return;
		}
	}
}

if( ! function_exists('exc_die') )
{
	function exc_die( $error = '' )
	{
		if ( defined('DOING_AJAX') && DOING_AJAX )
		{
			wp_send_json_error( $error );
			
		}else
		{
			wp_die( $error );
		}
	}
}

if ( ! function_exists( 'exc_success' ) )
{
	function exc_success( $message )
	{
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		{
			wp_send_json_success( $message );
		} else
		{
			echo $message . "\n";
		}

	}
}

if( ! function_exists('exc_system_log') )
{
	function exc_system_log()
	{
		
	}
}