<?php

/* **************************************************************************
This software is provided "as is" without any express or implied warranties,
including, but not limited to, the implied warranties of merchantibility and
fitness for any purpose.
In no event shall the copyright owner, website owner or contributors be liable
for any direct, indirect, incidental, special, exemplary, or consequential
damages (including, but not limited to, procurement of substitute goods or services;
loss of use, data, rankings with any search engines, any penalties for usage of
this software or loss of profits; or business interruption) however caused and
on any theory of liability, whether in contract, strict liability, or
tort(including negligence or otherwise) arising in any way out of the use of
this software, even if advised of the possibility of such damage.
To request source code for MemberWing please contact http://www.memberwing.com/contact
************************************************************************** */
/*
      These functions are called from outside of wordpress, but they want to use Wordpress API's
      Make sure Wordpress is initialized here.
*/

//------------------------------------------
// Load wordpress
if (!defined('WP_USE_THEMES') && !defined('ABSPATH'))
   {
   $g_blog_dir = preg_replace ('|(/+[^/]+){4}$|', '', str_replace ('\\', '/', __FILE__)); // For love of the art of regex-ing
   define('WP_USE_THEMES', false);
   require_once ($g_blog_dir . '/wp-blog-header.php');
   require_once ($g_blog_dir . '/wp-admin/includes/admin.php');
   }
//------------------------------------------

//===========================================================================
//
// Function converts long item name to it's normalized short representation:
// Premium Gold subscription => GOLD
// Some silver membership    => SILVER
// Some wierd name           => GOLD
//
function NU__Normalize_Item_Name ($item_name)
{
   $product_purchased = strtoupper(preg_replace ('#.*?(bronze|silver|gold|platinum).*#i', "$1", $item_name));
   if ($product_purchased != 'BRONZE' && $product_purchased != 'SILVER' && $product_purchased != 'GOLD' && $product_purchased != 'PLATINUM')
       {
       $product_purchased = 'GOLD';
       }

   return ($product_purchased);
}
//===========================================================================

//===========================================================================
function NU__Get_Role_by_Item_Name ($item_name)
{
    // Purchased product -> role.
    $products_roles = array (
       'BRONZE'=>'bronze_member',
       'SILVER'=>'silver_member',
       'GOLD'=>'gold_member',
       'PLATINUM'=>'platinum_member'
       );

    $role = $products_roles[NU__Normalize_Item_Name ($item_name)];

    return ($role);
}
//===========================================================================

//===========================================================================
//
// Determine valid email address of administrator.

function NU__Get_Amin_Email ()
{
   $admin_email = get_settings('admin_email');
   if (!$admin_email)
      $admin_email = get_option('admin_email');

   return ($admin_email);
}
//===========================================================================

//===========================================================================
// Add new user to WP dbase.

function NU__Add_New_User ()
{
   global $_inputs;

   // Sanitize username and actual password.
   // Create new user and assign him to requested level.
   // Make sure username will be unique, and add it to WP dbase.
   $i=1;
   $actual_username = $_inputs['desired_username'];
   while (username_exists($actual_username))
      $actual_username = $_inputs['desired_username'] . $i++;

   if ($_inputs['desired_password'])
      $actual_password = $_inputs['desired_password'];
   else
      $actual_password = substr(md5(microtime()), -8);  // If user did not specified password - generate random 8-chars password.

   $blog_root_url  = rtrim(get_bloginfo ('wpurl'), '/');
   $blog_login_url = $blog_root_url . '/wp-login.php?redirect_to=/' . preg_replace ('|^.*?((/)([^\.]+))?$|', "$3", $blog_root_url);

   // See if user already exists.
   $user_id = email_exists ($_inputs['payer_email']);

   // Generate email body. Process these variables:
   // {FIRST_NAME}, {LAST_NAME}, {ITEM_NAME}, {USERNAME}, {PASSWORD}, {BLOG_ROOT_URL}, {BLOG_LOGIN_URL}
   if ($user_id)
      {
      // User already exists - use existing credentials.
      $user_data = get_userdata ($user_id);
      $actual_username = $user_data->user_login;
      $actual_password = '(EXISTING-PASSWORD)';
      }
   $mw_options = get_option ("MemberWingAdminOptions");
   $welcome_email_subject = $mw_options ['welcome_email_subject'];
   $welcome_email_body = base64_decode ($mw_options ['welcome_email_body']);
   $welcome_email_body = preg_replace ('|\{FIRST_NAME\}|', $_inputs['first_name'],  $welcome_email_body);
   $welcome_email_body = preg_replace ('|\{LAST_NAME\}|', $_inputs['last_name'],    $welcome_email_body);
   $welcome_email_body = preg_replace ('|\{ITEM_NAME\}|', $_inputs['item_name'],    $welcome_email_body);
   $welcome_email_body = preg_replace ('|\{BLOG_LOGIN_URL\}|', $blog_login_url,     $welcome_email_body);
   $welcome_email_body = preg_replace ('|\{BLOG_ROOT_URL\}|', $blog_root_url,       $welcome_email_body);
   $welcome_email_body = preg_replace ('|\{USERNAME\}|', $actual_username,          $welcome_email_body);
   $welcome_email_body = preg_replace ('|\{PASSWORD\}|', $actual_password,          $welcome_email_body);

   if ($user_id)
      {
      // Duplicate subscription IPN check here.
      // NOTE: 'get_usermeta()' unserializes stuff by itself
      $ar_subscriptions = get_usermeta ($user_id, 'subscr_id');
      if (!($_inputs['subscr_id'] && is_array($ar_subscriptions) && in_array ($_inputs['subscr_id'], $ar_subscriptions)))
         {
         //--------------------------------------------
         // Add new subscription id to user's metadata.
         if (count($ar_subscriptions) > 10)
            array_shift ($ar_subscriptions);
         $ar_subscriptions[] = $_inputs['subscr_id'];
         update_usermeta ($user_id, 'subscr_id', serialize ($ar_subscriptions));
         //--------------------------------------------

         if ($user_data && $user_data->user_level==10)
            {
            // User is admin already. Probably admin is testing with his own account...
            // Nothing to do
            log_event (__FILE__, __LINE__, "Success2: Admin(email={$_inputs['payer_email']}) decided to get '{$_inputs['new_user_role']}' to be all he can be. Leaving untouched. Nothing to do");

            // Notify admin
            UTILS__send_email (
               $_inputs['admin_email'],  // To
               $_inputs['admin_email'],  // From
               "Site administrator tried to register as site member: {$_inputs['payer_email']}",
               "{$_inputs['first_name']} {$_inputs['last_name']} ({$_inputs['payer_email']}) tried to registered for '{$_inputs['item_name']}'. Request ignored."
               );
            }
         else
            {
            // Elevate (force-assign) existing user to requested level.
            $user = new WP_User($user_id);
            $user->set_role ($_inputs['new_user_role']);
            log_event (__FILE__, __LINE__, "Success2: Setting existing user to role='{$_inputs['new_user_role']}': email={$_inputs['payer_email']}, L/P: $actual_username / $actual_password");

            // Notify registered user
            //
            UTILS__send_email (
               $_inputs['payer_email'],  // To
               $_inputs['admin_email'],  // From
               $welcome_email_subject,
               $welcome_email_body
               );

            // Notify admin
            UTILS__send_email (
               $_inputs['admin_email'],  // To
               $_inputs['admin_email'],  // From
               "Existing subscriber status update: {$_inputs['payer_email']} ($actual_username)",
               "{$_inputs['first_name']} {$_inputs['last_name']} ({$_inputs['payer_email']}) ($actual_username / $actual_password) is registered for '{$_inputs['item_name']}'"
               );

            // Add user to Aweber list if enabled
            if ($mw_options ['aweber_integration_enabled'] && $mw_options ['aweber_list_email'])
               {
               UTILS__send_email (
                  $mw_options ['aweber_list_email'],  // To
                  'memberwingaweber@memberwing.com',  // From
                  'Subscribe',
                  "New Subscriber (via Wordpress Membership site plugin MemberWing):" .
                  "<br />Subscriber_First_Name: {$_inputs['first_name']}" .
                  "<br />Subscriber_Last_Name:  {$_inputs['last_name']}" .
                  "<br />Subscriber_Email:      {$_inputs['payer_email']}" .
                  "<br />"
                  );
               }
            }
         }
      else
         {
         // Duplicate subscription detected
         log_event (__FILE__, __LINE__, "WARNING: Duplicate SUBSCRIPTION detected for existing user: email={$_inputs['payer_email']}, subscr_id={$_inputs['subscr_id']}. Ignoring...");
         }
      }
   else
      {
      // User does not exist - create new one.
      $user_id = wp_create_user ($actual_username, $actual_password, $_inputs['payer_email']);
      $user = new WP_User($user_id);
      $user->set_role ($_inputs['new_user_role']);
      log_event (__FILE__, __LINE__, "Success2: Registered new user for role='{$_inputs['new_user_role']}': email={$_inputs['payer_email']}, L/P: $actual_username / $actual_password");

      // Notify new user
      //
      UTILS__send_email (
         $_inputs['payer_email'],  // To
         $_inputs['admin_email'],  // From
         $welcome_email_subject,
         $welcome_email_body
         );

      // Notify admin
      UTILS__send_email (
         $_inputs['admin_email'],  // To
         $_inputs['admin_email'],  // From
         "New Subscriber: {$_inputs['payer_email']} ($actual_username)",
         "{$_inputs['first_name']} {$_inputs['last_name']} ({$_inputs['payer_email']}) ($actual_username / $actual_password) just paid and registered for '{$_inputs['item_name']}'"
         );

      // Add user to Aweber list if enabled
      if ($mw_options ['aweber_integration_enabled'] && $mw_options ['aweber_list_email'])
         {
         UTILS__send_email (
            $mw_options ['aweber_list_email'],  // To
            'memberwingaweber@memberwing.com',  // From
            'Subscribe',
            "New Subscriber (via Wordpress Membership site plugin MemberWing):" .
            "<br />Subscriber_First_Name: {$_inputs['first_name']}" .
            "<br />Subscriber_Last_Name:  {$_inputs['last_name']}" .
            "<br />Subscriber_Email:      {$_inputs['payer_email']}" .
            "<br />"
            );
         }
      }

   log_event (__FILE__, __LINE__, "Success3: Done.");
}
//===========================================================================

//===========================================================================
// Track payment for affiliate purposes

function NU__Payment_Received ()
{
   global $_inputs;

   //--------------------------------------------
   // Duplicate transaction IPN check here.
   // See if user already exists.
   $user_id = email_exists ($_inputs['payer_email']);
   if ($user_id)
      {
      // NOTE: 'get_usermeta()' unserializes stuff by itself
      $ar_transactions = get_usermeta ($user_id, 'txn_id');
      if ($_inputs['txn_id'] && is_array($ar_transactions) && in_array ($_inputs['txn_id'], $ar_transactions))
         {
         // Duplicate transaction detected
         log_event (__FILE__, __LINE__, "WARNING: Duplicate TRANSACTION detected for existing user: email={$_inputs['payer_email']}, txn_id={$_inputs['txn_id']}. Ignoring...");
         return;
         }

      // Add new transaction id to user's metadata.
      if (count($ar_transactions) > 10)
         array_shift ($ar_transactions);
      $ar_transactions[] = $_inputs['txn_id'];
      update_usermeta ($user_id, 'txn_id', serialize ($ar_transactions));
      }
   //--------------------------------------------

   // Make sure that inside paypal's HTML button code this line is added to track customer's IP:
   //    <input type="hidden" name="custom" value="__SERVER__REMOTE_ADDR__">
   //
   $mw_options = get_option ("MemberWingAdminOptions");
   $_idevaffiliate_integration_enabled    = $mw_options ['idevaffiliate_integration_enabled'];
   $_idevaffiliate_installation_directory = $mw_options['idevaffiliate_install_dirname'];           // "http://www.YOUR-SITE-NAME.com/idevaffiliate"


   /*
   To add support for iDevAffiliate we need to pass IP address of customer making purchase to iDevAff. Do this:

   -  Inside paypal's HTML button code add this line: <input type="hidden" name="custom" value="__SERVER__REMOTE_ADDR__">
      (this line will get converted to IP address by MemberWing, and will get passed to this script via $_POST['custom'] variable.

   -  Here - DETECT STATE OF '$_idevaffiliate_integration_enabled' (presumably coming from MW Admin panel)

   -  Here - Initialize value of '$_IDEV_DIRECTORY_URL' with installation directory of iDevAffiliate script ("http://www.YOUR-SITE-NAME.com/idevaffiliate")
      (presumably coming from MW Admin panel too)

   -  Enable to code below:
   */

   if ($_idevaffiliate_integration_enabled && $_idevaffiliate_installation_directory && $_inputs['customer_ip'] && $_inputs['customer_ip'] != "0.0.0.0")
      {
      // Prepare information for iDevAffiliate.
      //
      $_IDEV_DIRECTORY_URL = $_idevaffiliate_installation_directory;
      $_IDEV_ORDER_NUM     = $_inputs['txn_id'];            // 'idev_ordernum' to be the paypal transaction ID number (IdevAff support answer).
      $_IDEV_SALE_AMT      = $_inputs['mc_amount3_gross'];
      $_IDEV_IP_ADDR       = $_inputs['customer_ip'];
      log_event (__FILE__, __LINE__, "Notifying iDevAffiliate: Directory URL=$_IDEV_DIRECTORY_URL, Order Number=$_IDEV_ORDER_NUM, Sale Amount=$_IDEV_SALE_AMT, IP Addr=$_IDEV_IP_ADDR");
      NU__notify_idevaffiliate ($_IDEV_DIRECTORY_URL, $_IDEV_SALE_AMT, $_IDEV_ORDER_NUM, $_IDEV_IP_ADDR);
      }
}
//===========================================================================

//===========================================================================
// Downgrade user in WP dbase.

function NU__Cancel_User ($ok_to_delete_user_account)
{
   global $_inputs;

   // See if user actually exists.
   $user_id = email_exists ($_inputs['payer_email']);
   if ($user_id)
      {
      $user_data = get_userdata ($user_id);

      if ($user_data && $user_data->user_level==10)
         {
         // User is admin already. Probably admin is testing with his own account...
         // Nothing to do
         log_event (__FILE__, __LINE__, "Cancel-2: Admin(email={$_inputs['payer_email']}) decided to get rid of his '{$_inputs['new_user_role']}'. Leaving untouched. Nothing to do");
         }
      else
         {
         $mw_options = get_option ("MemberWingAdminOptions");
         if ($ok_to_delete_user_account && isset($mw_options['delete_user_account_when_cancel']) && $mw_options['delete_user_account_when_cancel'])
            {
            wp_delete_user ($user_id);
            log_event (__FILE__, __LINE__, "Cancel-2: Deleted this user account: email={$_inputs['payer_email']}");
            }
         else
            {
            // Downgrade user to 'subscriber' level.
            $user = new WP_User($user_id);
            $user->set_role ('subscriber');
            log_event (__FILE__, __LINE__, "Cancel-2: Downgraded existing user to role='subscriber', email={$_inputs['payer_email']}");
            }
         }
      }
   else
      {
      // User does not exist.
      log_event (__FILE__, __LINE__, "WARNING: Cancellation-type request arrived for unknown user. Ignoring. POST data: payer_email={$_inputs['payer_email']}");
      }

   // Notify administrator about cancellation...
   UTILS__send_email (
      $_inputs['admin_email'],
      $_inputs['admin_email'],
      "Subscription cancelled for {$_inputs['payer_email']}",
      "Subscription cancelled for user: '{$_inputs['payer_email']}' ({$_inputs['first_name']} {$_inputs['last_name']}). Item: {$_inputs['item_name']}. Reason: {$_inputs['txn_type']}."
      );

}
//===========================================================================

//===========================================================================
//
// Notify iDevAffiliate software about money coming in.

function NU__notify_idevaffiliate ($idev_directory_url, $sale_amount, $order_number, $ip_address)
{
   $ch = curl_init();
   curl_setopt ($ch, CURLOPT_URL, $idev_directory_url . "/sale.php?profile=72198&idev_saleamt=$sale_amount&idev_ordernum=$order_number&ip_address=$ip_address");
   curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
   $RetCode = curl_exec   ($ch);
   curl_close  ($ch);

   if ($RetCode)
      log_event (__FILE__, __LINE__, "Successfully notified iDevAffiliate about sale");
   else
      log_event (__FILE__, __LINE__, "WARNING: Notifying iDevAffiliate PROBLEM: curl_exec returned FALSE");
}
//===========================================================================

?>