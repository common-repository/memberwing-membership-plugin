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

//===========================================================================
// Load custom MemberWing extensions

   $MW_ext_dirname = dirname(__FILE__) . '/extensions';
   $MW_ext_dirname = str_replace ('\\', '/', $MW_ext_dirname);
   $MW_dh = @opendir ($MW_ext_dirname);
   if ($MW_dh)
      {
      while (($MW_found_file = @readdir($MW_dh)) !== FALSE)
         {
         // Load main.cpp from any extension which directory is not named like 'Sample_'.
         if (is_dir($MW_ext_dirname . "/$MW_found_file") && $MW_found_file[0] != '.' && strcmp('YourExtensionSample', $MW_found_file) && strncmp ('Sample_', $MW_found_file, 7) && file_exists ($MW_ext_dirname . "/$MW_found_file/main.php"))
            {
            include_once ($MW_ext_dirname . "/$MW_found_file/main.php");
            }
         }
      closedir($MW_dh);
      }
//===========================================================================

?>