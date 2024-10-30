
                  ======================================================================
                  MemberWing 4 - MemberWing Custom Payment Notification Extensions (PNX)
                                         Help and Specification
                  ======================================================================

   MemberWing 4.10 and higher supports custom Payment Notification Extensions for payment processing notifications.
   Custom extensions allows people to add support for custom payment processors and affiliate systems for their membership sites.
   You may design your own shopping cart and have it notify MemberWing about payment.
   MemberWing in turn will add new member to the list of premium users.
   Or you may decide to integrade custom payment processor from another country and market it as a solution for users from this country.
   There are many possibilities for everyone to benefit.

   Sample of source code for minimalistic MemberWing Payment Notification Extension is included inside ./extensions/ directory.

   Here are brief guidelines:

   Every MemberWing custom extension writer must follow these guidelines
   ---------------------------------------------------------------------

      *  Include this file:  ./extensions/NameOfExtension/ipn.php   -  main executable code starting point of extension.
         Your payment processor should notify your PNX via URL such as:
            http://www.SITE-NAME.com/wp-content/plugins/MEMBERWING-DIR/extensions/YourExtension/ipn.php
         and pass purchase event information via $_POST or $_GET variables.
         This concept is similar to Paypal IPN system.
         Once your PNX ipn.php file is invoked - you need to do this:
         -  Parse passed variable and store them into '$_MW_REQUEST' array according to the table below.
         -  Call 'notify_api.php' of MemberWing to complete event processing. It will use '$_MW_REQUEST' array to process payment data.

      *  Include this file:  ./extensions/NameOfExtension/main.php   -  this file, if present, will be invoked via include_once() PHP function by MemberWing at the time of initialization.
         This is a proper place to do any on-init type of functions. Feel free to include your custom actions and filters in it.

      *  Include this file:  ./extensions/NameOfExtension/admin.php  -  admin panel for this extension to appear inside of MemberWing admin settings.
         If your exptension does not need admin panel - just include empty admin.php, such as: <?php  ?>

      *  Include this file:  ./extensions/NameOfExtension/readme.txt -  information about your extension, how-to's, support and contact information.

      *  Use extension code samples as a templates to create your own. The code that is between these lines must be left untouched:
         //===========================================================================
         // MANDATORY
         ...leave this code in tact...
         //===========================================================================

      *  '$_MW_REQUEST' global array must be initialized by your extension. Please refer to this table for details:

         +-----------------------------------------+-------------+--------------------+----------------+---------------------------------------------
         |                                         |  Mandatory/ |   Possible         |Matching or     | Notes
         |       MemberWing variables              |  Optional   |   Values           |similar         |
         |                                         |  (M/O)      |                    |Paypal variable |
         +-----------------------------------------+-------------+--------------------+----------------+---------------------------------------------
         |  $_MW_REQUEST['event_type']             |     M       |'subscr_signup'     |'txn_type'      |   New recurring subscription creation
         |                                         |             |'payment_one_time'  |                |   New one-time lifetime subscription purchase
         |                                         |             |'payment_recurring' |                |   Recurring installment payment for existing subscription
         |                                         |             |'subscr_suspend'    |                |   Existing subscription suspended, access to premium area denied, but user account is not removed.
         |                                         |             |'subscr_cancel'     |                |   Existing subscription cancelled and user account could be removed due to:
         |                                         |             |                    |                |      -  end of term
         |                                         |             |                    |                |      -  forced cancellation by customer
         |                                         |             |                    |                |      -  refund
         |                                         |             |                    |                |      -  chargeback or other payment problem
         |                                         |             |                    |                |      -  forced cancellation by merchant
         +-----------------------------------------+-------------+--------------------+----------------+---------------------------------------------
         |  $_MW_REQUEST['subscription_id']        |     O       |                    |'subscr_id'     | Unique identifier for the act of subscription issued by the payment processor or shopping cart.
         +-----------------------------------------+-------------+--------------------+----------------+---------------------------------------------
         |  $_MW_REQUEST['transaction_id']         |     O       |                    |'txn_id'        | Unique identifier for this event issued by the payment processor or shopping cart.
         |                                         |             |                    |                | Could be considered as "transaction id" - need to be unique and valid for every transaction.
         |                                         |             |                    |                | Optional because some non-money changing events (subscr_signup) does not generate transaction id.
         +-----------------------------------------+-------------+--------------------+----------------+---------------------------------------------
         |  $_MW_REQUEST['payment_amount']         |     O       |'120', '7.95'       |                | Amount of payment received in $_MW_REQUEST['payment_currency']
         |                                         |             |'45.00'             |                | for one time or recurring subscription.
         |                                         |             |                    |                | Optional because some event types does not involve money.
         |                                         |             |                    |'mc_amount3'    | 'mc_amount3' - Paypal recurring subscription payment amount
         |                                         |             |                    |'mc_gross'      | 'mc_gross'   - Paypal single 'Buy' amount
         +-----------------------------------------+-------------+--------------------+----------------+---------------------------------------------
         |  $_MW_REQUEST['payment_currency']       |     O       |'USD','CAD','EUR',  |                | 3 char currency code: USD, CAD, EUR, GBP, JPY, and others according to Paypals
         |                                         |             |'GBP','JPY'         |                | Website Payments Standard Integration Guide
         +-----------------------------------------+-------------+--------------------+----------------+---------------------------------------------
         |  $_MW_REQUEST['item_name']              |     M       |                    |'item_name'     | Name of product as specified by merchant
         +-----------------------------------------+-------------+--------------------+----------------+---------------------------------------------
         |  $_MW_REQUEST['item_id']                |     O       |                    |'item_number'   | Product ID as specified by merchant
         +-----------------------------------------+-------------+--------------------+----------------+---------------------------------------------
         |  $_MW_REQUEST['customer_first_name']    |     M       |                    |'first_name'    | First name or business name of customer
         +-----------------------------------------+-------------+--------------------+----------------+---------------------------------------------
         |  $_MW_REQUEST['customer_last_name']     |     O       |                    |'last_name'     | Optional because 'first_name' could be business name
         +-----------------------------------------+-------------+--------------------+----------------+---------------------------------------------
         |  $_MW_REQUEST['customer_email']         |     M       |                    |'payer_email'   | Customer email
         +-----------------------------------------+-------------+--------------------+----------------+---------------------------------------------
         |  $_MW_REQUEST['customer_ip']            |     O       |"" or "123.45.6.78" |                | IP address of customer - used for affiliate tracking.
         |                                         |             |                    |                | If no IP address is specified - no affiliate tracking will be possible with
         |                                         |             |                    |                | some affiliate systems, such as iDevAffiliate.
         +-----------------------------------------+-------------+--------------------+----------------+---------------------------------------------
         |  $_MW_REQUEST['desired_username']       |     O       |                    |                | Customer username to access membership site - if shopping cart supported this extra field
         +-----------------------------------------+-------------+--------------------+----------------+---------------------------------------------
         |  $_MW_REQUEST['desired_password']       |     O       |                    |                | Customer password to access membership site - if shopping cart supported this extra field
         +-----------------------------------------+-------------+--------------------+----------------+---------------------------------------------
         |  $_MW_REQUEST['receiver_email']         |     O       |                    |'receiver_email'| (Paypals "business" button variable) - receiver of payment.
         +-----------------------------------------+-------------+--------------------+----------------+---------------------------------------------

      *  Make sure your extension is protected from fraudulent invocations, such as this:
         http://www.SITE-NAME.com/wp-content/plugins/memberwing/extensions/YourExtension/ipn.php?item_name=Membership&payment_amount=100
         Best way to accomplish this is to send hash value encoded with secret key shared between your extension code and shopping cart.


   For more information about MemberWing API please contact our development team at:
   http://www.memberwing.com/contact/