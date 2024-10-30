<?php


// Load wordpress

require_once (dirname(__FILE__) . '/utils.php');
require_once (dirname(__FILE__) . '/notify_utils.php');


   log_event (__FILE__, __LINE__, "Notify API - Raw Entry Hit");

   // Prevent loading of file directly.
   if ($_SERVER['SCRIPT_NAME'] == basename(__FILE__))
      {
      $var =<<<VAR___VAR
PCFET0NUWVBFIEhUTUwgUFVCTElDICItLy9XM0MvL0RURCBIVE1MIDQuMDEvL0VOIiAiaHR0cDovL3d3dy53My5vcmcvVFIvaHRtbDQvc3RyaWN0LmR0ZCI+DQo8aHRtbD4NCjxoZWFkPg0KPG1ldGEgaHR0cC1lcXVpdj0iQ29udGVudC1UeXBlIiBjb250ZW50PSJ0ZXh0L2h0bWw7IGNoYXJzZXQ9dXRmLTgiPg0KPG1ldGEgbmFtZT0iZGVzY3JpcHRpb24iIGNvbnRlbnQ9IkJ1aWxkIFlvdXIgRnJlZSBNZW1iZXJzaGlwIFNpdGUgd2l0aCBNZW1iZXJXaW5nIiAvPg0KPG1ldGEgbmFtZT0ia2V5d29yZHMiIGNvbnRlbnQ9Im1lbWJlcnNoaXAgc2l0ZSIgLz4NCg0KPHRpdGxlPk1lbWJl
cnNoaXAgU2l0ZSAtIFNvZnR3YXJlIC0gRm9ydW0gLSBSZXNvdXJjZXMuIExlYXJuIGhvdyB0byBzdGFydCBhbmQgbWFuYWdlIG1lbWJlcnNoaXAgc2l0ZTwvdGl0bGU+DQo8L2hlYWQ+DQoNCjxib2R5Pg0KPGgxIGFsaWduPSJjZW50ZXIiPkJ1aWxkIFlvdXIgPGEgaHJlZj0iaHR0cDovL3d3dy5tZW1iZXJ3aW5nLmNvbS8iPkZyZWUgTWVtYmVyc2hpcCBTaXRlPC9hPiA8L2gxPg0KPGgyIGFsaWduPSJjZW50ZXIiPndpdGggPGEgaHJlZj0iaHR0cDovL3d3dy5tZW1iZXJ3aW5nLmNvbS9kb3dubG9hZC93b3JkcHJlc3MtbWVtYmVyc2hpcC1zaXRlLXBsdWdpbi1tZW1iZXJ3aW5nLyI+
V29yZHByZXNzIE1lbWJlcnNoaXAgU2l0ZSBQbHVnaW4gTWVtYmVyV2luZzwvYT4gPC9oMj4NCjxoMiBhbGlnbj0iY2VudGVyIj5GcmVlIE1lbWJlcnNoaXAgc2l0ZSBEaXNjdXNzaW9uLCBUaXBzLCBSZXNvdXJjZXMgYW5kIEhlbHAgaXMgYXZhaWxhYmxlIGF0IDxhIGhyZWY9Imh0dHA6Ly9mb3J1bS5tZW1iZXJ3aW5nLmNvbS8iPk1lbWJlcnNoaXAgU2l0ZSBGb3J1bTwvYT48L2gyPg0KPC9ib2R5Pg0KPC9odG1sPg==
VAR___VAR;
      exit (base64_decode ($var));
      }

   $_req = '';
   foreach ($_MW_REQUEST as $key => $value)
      {
      $value = urlencode(stripslashes($value));
      $_req .= "&$key=$value";
      }


   //---------------------------------------
   // Sanitize sent variables into '$_inputs' array
   //
   $_inputs = array ();

   $_inputs['item_name']        = isset($_MW_REQUEST['item_name'])?$_MW_REQUEST['item_name']:"";
   $_inputs['first_name']       = isset($_MW_REQUEST['customer_first_name'])?$_MW_REQUEST['customer_first_name']:"";
   $_inputs['last_name']        = isset($_MW_REQUEST['customer_last_name'])?$_MW_REQUEST['customer_last_name']:"";
   $_inputs['payer_email']      = isset($_MW_REQUEST['customer_email'])?$_MW_REQUEST['customer_email']:"";
   $_inputs['subscr_id']        = isset($_MW_REQUEST['subscription_id'])?$_MW_REQUEST['subscription_id']:"";
   $_inputs['mc_amount3_gross'] = isset($_MW_REQUEST['payment_amount'])?$_MW_REQUEST['payment_amount']:"";
   $_inputs['mc_currency']      = isset($_MW_REQUEST['payment_currency'])?$_MW_REQUEST['payment_currency']:"";
   $_inputs['txn_id']           = isset($_MW_REQUEST['transaction_id'])?$_MW_REQUEST['transaction_id']:'0';
   $_inputs['txn_type']         = isset($_MW_REQUEST['event_type'])?$_MW_REQUEST['event_type']:"";
   $_inputs['receiver_email']   = isset($_MW_REQUEST['receiver_email'])?$_MW_REQUEST['receiver_email']:"";
   $_inputs['customer_ip']      = isset($_MW_REQUEST['customer_ip'])?$_MW_REQUEST['customer_ip']:"";
   //---------------------------------------

   //---------------------------------------
   // Form desired username and password.
   $_inputs['desired_username'] = isset($_MW_REQUEST['desired_username'])?$_MW_REQUEST['desired_username']:preg_replace ('|\s+|', '', strtolower($_inputs['first_name'] . $_inputs['last_name']));
   $_inputs['desired_password'] = isset($_MW_REQUEST['desired_password'])?$_MW_REQUEST['desired_password']:substr(md5(microtime()), -8);
   //---------------------------------------

   //---------------------------------------
   // Get name of 'new_user_role'.
   // "Premium Gold subscription" => "GOLD"
   // "Premium Gold subscription" -> "GOLD" -> 'gold_member'
   $_inputs['new_user_role'] = NU__Get_Role_by_Item_Name ($_inputs['item_name']);
   //---------------------------------------

   //---------------------------------------
   // Detect admin email.
   $_inputs['admin_email'] = NU__Get_Amin_Email ();
   if (!$_inputs['admin_email'])
      {
      $_inputs['admin_email'] = "webmaster@" . preg_replace ('#^(www\.)?(.*)$#', "$2", $_SERVER['HTTP_HOST']);
      log_event (__FILE__, __LINE__, "WARNING: Cannot determine admin email. Will use generic: '{$_inputs['admin_email']}'");
      }
   //---------------------------------------

   //---------------------------------------
   // Debugging log.
   log_event (__FILE__, __LINE__, "Hit Detected ({$_inputs['txn_type']} for {$_inputs['payer_email']})", $_req);
   //---------------------------------------


   // Process event
   //
   switch ($_MW_REQUEST['event_type'])
      {
      case 'payment_recurring':     // 1st notification
         log_event (__FILE__, __LINE__, "Installment payment processed for {$_inputs['payer_email']} for {$_inputs['item_name']}.");
         NU__Payment_Received ();   // Track payment for affiliate purposes;
         break;   // For subscriptions, only "subscr_signup" will be allowed to add new user.


      case 'payment_one_time':      // One time payment
         log_event (__FILE__, __LINE__, "Success1: Received one time payment from {$_inputs['payer_email']} for {$_inputs['item_name']}.");
         NU__Add_New_User ();
         NU__Payment_Received ();   // Track payment for affiliate purposes;
         break;


      case 'subscr_signup':         // 2nd Notification
         // Payment is made or subscription is initiated.
         log_event (__FILE__, __LINE__, "Success1: Received subscription payment from {$_inputs['payer_email']} for {$_inputs['item_name']}.");
         NU__Add_New_User ();
         break;

      case 'subscr_suspend':
         // Subscription is cancelled.
         // Find 'actual_username' by 'subscr_id' and downgrade his account to 'subscriber', unless he is admin.
         log_event (__FILE__, __LINE__, "Cancel-1: Subscription is suspended for '{$_inputs['payer_email']}'");
         NU__Cancel_User (FALSE);
         break;

      case 'subscr_cancel':
         // Subscription is cancelled. User account will be removed if $mw_options['delete_user_account_when_cancel'] flag is TRUE.
         // Find 'actual_username' by 'subscr_id' and downgrade his account to 'subscriber', unless he is admin.
         log_event (__FILE__, __LINE__, "Cancel-1: Cancellation requested received for '{$_inputs['payer_email']}'");
         NU__Cancel_User (TRUE);
         break;


      default:
         log_event (__FILE__, __LINE__, "Note: Received Unknown txn_type={$_inputs['txn_type']} from {$_inputs['payer_email']} for {$_inputs['item_name']}");
         break;
      }

?>