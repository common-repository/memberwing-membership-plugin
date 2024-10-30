<?php
/*
Plugin Name: MemberWing Membership Website Free Edition
Plugin URI: http://www.memberwing.com/
Version: 4.62
Author: Gleb Esman, http://www.memberwing.com/
Author URI: http://www.memberwing.com/
Description: <a href="http://www.memberwing.com/">MemberWing</a> converts any wordpress based blog/site into powerful membership site. Simplicity, Strong SEO (search engine optimization) features and 4 levels of membership are included in free version. Just use tag: <code> {+++} </code> after 'free teaser' to make your post/page premium. Premium features: SEO-optimized Digital Content Protection enhanced with PromoFusion logic, Gradual Content Delivery (dripping content), support for automated recurring payments with Paypal, Clickbank, 2Checkout and other payment processors and affiliate services.
*/

define('MEMBERWING_VERSION',  '4.62');

define('MW_PLUGIN_FILENAME',  basename(__FILE__));
require_once (dirname(__FILE__) . '/memberwing.php');

// Include possible extensions
require_once (dirname(__FILE__) . '/ext_manager.php');

?>
