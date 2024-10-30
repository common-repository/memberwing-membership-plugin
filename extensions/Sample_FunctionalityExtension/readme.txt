
                  ======================================================================
                  MemberWing 4 - MemberWing Functionality Extension for MemberWing
                                         Help and Specification
                  ======================================================================

   MemberWing 4.52 and higher supports custom Functionality Extensions.
   Custom extensions allows people to add extra functionality to MemberWing, like custom filters, actions and functions.
   You may write the whole plugin-like code, place it into 'main.php' file and it will be automatically invoked my MemberWing code upon initialization.

   Here are brief guidelines:

   Every MemberWing custom functionality extension writer must follow these guidelines
   -----------------------------------------------------------------------------------

      *  Include this file:  ./extensions/NameOfExtension/main.php   -  main executable code starting point of extension.
         This file, if present, will be invoked via include_once() PHP function by MemberWing at the time of initialization.
         This is a proper place to do any on-init type of functions. Feel free to include your custom actions and filters in it as well as any other files you need.
         See main.php file for sample of code.

      *  Include this file:  ./extensions/NameOfExtension/admin.php  -  admin panel for this extension to appear inside of MemberWing admin settings.
         If your exptension does not need admin panel - just include empty admin.php, such as: <?php  ?>

      *  Include this file:  ./extensions/NameOfExtension/readme.txt -  information about your extension, how-to's, support and contact information.

   Testing this sample extension
   -----------------------------

      *  Rename the directory for this extension to anything that does not start with 'Sample_' string. This way it (main.php) will be automatically loaded
         during MemberWing plugin initialization time.


   For more information about MemberWing API please contact our development team at:
   http://www.memberwing.com/contact/