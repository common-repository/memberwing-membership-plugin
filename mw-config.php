<?php

$blog_root = preg_replace ('|(/[^/]+){3}$|', '', str_replace ('\\', '/', dirname(__FILE__)));

require_once ("$blog_root/wp-includes/registration.php");
require_once ("$blog_root/wp-config.php");
require_once ("$blog_root/wp-includes/wp-db.php");

include_once (dirname(__FILE__) . '/utils.php');

define('TABLE_SUBSCR_CUSTOMERS', 'mw_subscr_customers');    // Table name for customers
define('TABLE_SUBSCR_PRODUCTS',  'mw_subscr_products');     // Table name for products

function create_products_table ()
{
   global $wpdb;
   if (mwdebug() && !$wpdb) log_event (__FILE__, __LINE__, "WARNING: 'wpdb' is empty.");
   $table_name = $wpdb->prefix . TABLE_SUBSCR_PRODUCTS;
   $query = "CREATE TABLE IF NOT EXISTS `$table_name` (
      `id` int(10) unsigned NOT NULL auto_increment,
      `item_name` varchar(127) NOT NULL,
      `role_name` varchar(64) NOT NULL,
      `mc_currency` char(4) NOT NULL,
      `mc_amount3_gross` float NOT NULL,
      `recurring` char(1) NOT NULL COMMENT '1-recurring, 0-not',
      `period3` varchar(16) NOT NULL default '1 M',
      PRIMARY KEY  (`id`),
      UNIQUE KEY `item_name` (`item_name`)
      )";
   // Create products table. Note: creating and populating it second time (such as during re-activation of plugin) won't hurt because it has UNIQUE KEY "protection".
   $ret_code = $wpdb->query ($query);
   if (mwdebug()) log_event (__FILE__, __LINE__, "create_products_table() returned: $ret_code");
}

function populate_products_table ()
{
   global $wpdb;
   if (mwdebug() && !$wpdb) log_event (__FILE__, __LINE__, "WARNING: 'wpdb' is empty.");
   $table_name = $wpdb->prefix . TABLE_SUBSCR_PRODUCTS;
   // Need to insert items one by one, as the whole query will fail if first entry will found to be duplicate.

   $insert_into_stmt = "INSERT INTO `$table_name` (`item_name`, `role_name`, `mc_currency`, `mc_amount3_gross`, `recurring`, `period3`) VALUES ";
   $queries = array (
      $insert_into_stmt . "('BRONZE',   'bronze_member',   'USD', 0, '1', '1 M')",
      $insert_into_stmt . "('SILVER',   'silver_member',   'USD', 0, '1', '1 M')",
      $insert_into_stmt . "('GOLD',     'gold_member',     'USD', 0, '1', '1 M')",
      $insert_into_stmt . "('PLATINUM', 'platinum_member', 'USD', 0, '1', '1 M')"
      );

   foreach ($queries as $query)
      {
      // This will fail (due to presence of UNIQUE KEY) if table already populated with these products.
      $ret_code = $wpdb->query ($query);
      }

   if (mwdebug()) log_event (__FILE__, __LINE__, "populate_products_table() returned: $ret_code");
}

function create_customers_table ()
{
   global $wpdb;
   if (mwdebug() && !$wpdb) log_event (__FILE__, __LINE__, "WARNING: 'wpdb' is empty.");
   $table_name = $wpdb->prefix . TABLE_SUBSCR_CUSTOMERS;
   $query = "
      CREATE TABLE IF NOT EXISTS `$table_name` (
      `id` int(10) unsigned NOT NULL auto_increment,
      `first_name` varchar(64) NOT NULL,
      `last_name` varchar(64) NOT NULL,
      `payer_email` varchar(127) NOT NULL,
      `subscr_id` varchar(20) NOT NULL,
      `txn_id` varchar(20) NOT NULL,
      `subscr_date` datetime default NULL,
      `recurring` char(1) default NULL,
      `period1` varchar(16) default NULL,
      `period2` varchar(16) default NULL,
      `period3` varchar(16) default NULL,
      `mc_amount1` int(4) default NULL,
      `mc_amount2` int(4) default NULL,
      `mc_amount3_gross` float NOT NULL COMMENT 'mc_gross(1st notification) = mc_amount3(2nd notification) = mc_amount3_gross',
      `mc_currency` char(4) NOT NULL,
      `item_name` varchar(127) NOT NULL,
      `custom` varchar(255) default NULL,
      `invoice` varchar(127) default NULL,
      `memo` varchar(255) default NULL,
      `actual_username` varchar(64) default NULL,
      `subscr_active` int(1) NOT NULL default '0' COMMENT '1-active, 0-inactive for whatever reason',
      `raw_post_payment` varchar(20) NOT NULL default 'CURRENTLY UNUSED' COMMENT 'serialized POST request for subscr_payment notification - CURRENTLY UNUSED',
      `raw_post_signup` varchar(20) NOT NULL default 'CURRENTLY UNUSED' COMMENT 'serialized POST request for subscr_signup notification - CURRENTLY UNUSED',
      PRIMARY KEY  (`id`),
      UNIQUE KEY `txn_id` (`txn_id`),
      UNIQUE KEY `subscr_id` (`subscr_id`)
      )";
   // Create customers table. Note: creating and populating it second time (such as during re-activation of plugin) won't hurt because it has UNIQUE KEY "protection".
   $ret_code = $wpdb->query ($query);

   // Schema updates for 3.20+
   // ALTER TABLE `wp_mw_subscr_customers` ADD `payer_id` VARCHAR( 64 ) NOT NULL default 'NOTSET' AFTER `payer_email` ;
   // ALTER TABLE `wp_mw_subscr_customers` CHANGE `subscr_id` `subscr_id` VARCHAR( 64 ) NOT NULL ;

   $wpdb->query ("ALTER TABLE `$table_name` ADD `payer_id` VARCHAR( 64 ) NOT NULL default 'NOTSET' AFTER `payer_email`"); // Will fail second+ time
   $wpdb->query ("ALTER TABLE `$table_name` CHANGE `subscr_id` `subscr_id` VARCHAR( 64 ) NOT NULL");  // Will succeed every time.

   if (mwdebug()) log_event (__FILE__, __LINE__, "create_customers_table() returned: $ret_code");
}
?>
