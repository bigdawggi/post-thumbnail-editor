<?php

/**
 * Disable error reporting
 *
 * Set this to error_reporting( E_ALL ) or error_reporting( E_ALL | E_STRICT ) for debugging
 */
error_reporting(0);

/** Set ABSPATH for execution */
define( 'ABSPATH', urldecode($_GET['abs_path']));
define( 'WPINC', 'wp-includes' );
define( 'PLUGINPATH', urldecode($_GET['plugin_path']));
/**
 * @ignore
 */
function __() {}

/**
 * @ignore
 */
function _x() {}

/**
 * @ignore
 */
function add_filter() {}

/**
 * @ignore
 */
function esc_attr() {}

/**
 * @ignore
 */
function apply_filters() {}

/**
 * @ignore
 */
function get_option() {}

/**
 * @ignore
 */
function is_lighttpd_before_150() {}

/**
 * @ignore
 */
function add_action() {}

/**
 * @ignore
 */
function do_action_ref_array() {}

/**
 * @ignore
 */
function get_bloginfo() {}

/**
 * @ignore
 */
function is_admin() {return true;}

/**
 * @ignore
 */
function site_url() {}

/**
 * @ignore
 */
function admin_url() {}

/**
 * @ignore
 */
function wp_guess_url() {}

function get_file($path) {

   if ( function_exists('realpath') )
      $path = realpath($path);

   if ( ! $path || ! @is_file($path) )
      return '';

   return @file_get_contents($path);
}

require(ABSPATH . '/wp-includes/script-loader.php');
require(ABSPATH . '/wp-includes/version.php');

$load = preg_replace( '/[^a-z0-9,_-]+/i', '', $_GET['load'] );
$load = explode(',', $load);

if ( empty($load) )
   exit;

header('Content-Type: text/css');
header('Expires: ' . gmdate( "D, d M Y H:i:s", time() + $expires_offset ) . ' GMT');
header("Cache-Control: public, max-age=$expires_offset");


$compress = ( isset($_GET['c']) && $_GET['c'] );
$force_gzip = ( $compress && 'gzip' == $_GET['c'] );
$rtl = ( isset($_GET['dir']) && 'rtl' == $_GET['dir'] );
$expires_offset = 31536000;
$out = '';
$suffix = ( isset($_GET['d']) && $_GET['d'] ) ? '.dev' : '';
//print("/** SUFFIX: '$suffix' **/\n");

$wp_styles = new WP_Styles();
wp_default_styles($wp_styles);
wp_register_style( 'pte'
   , PLUGINPATH . "/css/pte${suffix}.css"
   , array('imgareaselect')
);

foreach( $load as $handle ) {
   if ( !array_key_exists($handle, $wp_styles->registered) ){
      print( "/* Couldn't find ${handle} */\n" );
      continue;
   }

   $style = $wp_styles->registered[$handle];
   $path = ((strpos($src, WPINC) !== false) ? ABSPATH : '') . $style->src;
   $content = get_file($path) . "\n";

   if ( $rtl && isset($style->extra['rtl']) && $style->extra['rtl'] ) {
      $rtl_path = is_bool($style->extra['rtl']) ? str_replace( '.css', '-rtl.css', $path ) : ABSPATH . $style->extra['rtl'];
      $content .= get_file($rtl_path) . "\n";
   }

   // Replace wp-includes links
   if ( strpos( $style->src, '/wp-includes/' ) === 0 ) {
      $content = str_replace( 'border-anim-v.gif', '../../../../wp-includes/js/imgareaselect/border-anim-v.gif', $content);
      $content = str_replace( 'border-anim-h.gif', '../../../../wp-includes/js/imgareaselect/border-anim-h.gif', $content);
   }
   $out .= $content;
}

if ( $compress && ! ini_get('zlib.output_compression') && 'ob_gzhandler' != ini_get('output_handler') && isset($_SERVER['HTTP_ACCEPT_ENCODING']) ) {
   header('Vary: Accept-Encoding'); // Handle proxies
   if ( false !== stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') && function_exists('gzdeflate') && ! $force_gzip ) {
      header('Content-Encoding: deflate');
      $out = gzdeflate( $out, 3 );
   } elseif ( false !== stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') && function_exists('gzencode') ) {
      header('Content-Encoding: gzip');
      $out = gzencode( $out, 3 );
   }
}

echo $out;
exit;
