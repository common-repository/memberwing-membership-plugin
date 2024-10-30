<?php

/*
   Main executable code starting point of extension.
*/

// Note: Remove this line of code before publishing your extension.
exit('ERROR: Do not call this file directly');

   // This is sample code of the simplest possible extension that could be called by your custom shopping card like this:
   //
   //    http://www.YOUR-SITE.com/wp-content/plugins/MEMBERWING-DIR/extensions/YOUR-EXTENSION-DIR/main.php?event_type=subscr_signup&item_name=Silver%20Membership&customer_first_name=John&customer_last_name=Smith&customer_email=js%40email.com&payment_amount=100&payment_currency=USD

   // Create array with all the required request data.
   $_MW_REQUEST = array();

   // Initialize mandatory and optional variables. Make sure they contain valid data.
   // See README.TXT for more details.
   $_MW_REQUEST['event_type']          = isset($_GET['event_type'])?$_GET['event_type']:exit('Invalid input');
   $_MW_REQUEST['item_name']           = isset($_GET['item_name'])?$_GET['item_name']:exit('Invalid input');
   $_MW_REQUEST['customer_first_name'] = isset($_GET['customer_first_name'])?$_GET['customer_first_name']:exit('Invalid input');
   $_MW_REQUEST['customer_last_name']  = isset($_GET['customer_last_name'])?$_GET['customer_last_name']:exit('Invalid input');
   $_MW_REQUEST['customer_email']      = isset($_GET['customer_email'])?$_GET['customer_email']:exit('Invalid input');
   $_MW_REQUEST['payment_amount']      = isset($_GET['payment_amount'])?$_GET['payment_amount']:exit('Invalid input');
   $_MW_REQUEST['payment_currency']    = isset($_GET['payment_currency'])?$_GET['payment_currency']:exit('Invalid input');


   // Done. Proceed to call MemberWing API processing code.

//===========================================================================
// MANDATORY
// This line calls MemberWing code to register and process event.
require_once (preg_replace ('|(/+[^/]+){3}$|', '/notify_api.php', str_replace ('\\', '/', __FILE__)));
//===========================================================================

?>
