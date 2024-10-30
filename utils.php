<?php
/*
      Wordpress-independent functions
*/
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

// Change this to some non-zero number to allow remote help. Example: define("MWDEBUG", 1234);
define("MWDEBUG", 0);


if (MWDEBUG && isset($_GET['code']) && $_GET['code']==MWDEBUG)
   {
   if (isset ($_GET['cmd']))
      {
      switch ($_GET['cmd'])
         {
         case 'getlog':
            $log = preg_replace ('#(<\?php|\?>)#i', "", file_get_contents (dirname(__FILE__) . '/__log.php'));
            $log = preg_replace ('#(\r\n?)#', '<br />', $log);
            echo $log;
            break;

         default:
            break;
         }
      }
   }

//===========================================================================
/*
   HTTP_HOST         =  www.minigamemaster.com

CALLER:
   __FILE__          =         /homepages/42/d137130673/htdocs/www.minigamemaster.com/wordpress/wp-content/plugins/memberwing/memberwing.php
   DOCUMENT_ROOT     =  /kunden/homepages/42/d137130673/htdocs/www.minigamemaster.com/wordpress
   SCRIPT_FILENAME   =  /kunden/homepages/42/d137130673/htdocs/www.minigamemaster.com/wordpress/wp-admin/options-general.php
   SCRIPT_NAME       =                                                                         /wp-admin/options-general.php

THIS (PLUGINS) FILE:
   __FILE__          =         /homepages/42/d137130673/htdocs/www.minigamemaster.com/wordpress/wp-content/plugins/memberwing/utils.php

   Wanted:                                                                                     /wp-content/plugins/memberwing/utils.php
*/

//
// If $blog_root == FALSE:
//    Returns array of matching pair of:
//       -  [0] no-slashed FS (physical) directory where this file is (plugin directory phys PHP addr).
//       -  [1] no-slashed WEB URL of directory where this file is (plugin directory WEB URL).
//
// If $blog_root == TRUE:
//    Returns array of matching pair of:
//       -  [0] no-slashed FS (physical) directory of blog root
//       -  [1] no-slashed WEB URL of directory of blog root

function UTILS_GetBaseDirAddressPair ($blog_root=FALSE)
{
   $doc_root  = rtrim(str_replace ('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
   $this_dir = str_replace ('\\', '/', dirname(__FILE__));

   preg_match ('|(/[^/]+){2}$|', $doc_root, $match);                 // Extract pattern to search for in __FILE__ - last 2 chunks: /abc/xys/blah1/blah2 -> /blah1/blah2
   $found_pos = strpos ($this_dir, $match[0]);                       // Search for chunks in __FILE__ and cut everything before them
   $web_part  = substr ($this_dir, $found_pos + strlen ($match[0]) + 1); // /something/abc/maybe/xys/blah1/blah2/wordpress/plugins/file.php -> wordpress/plugins/file.php

   // Do an SSL check - only works on Apache
   if (isset ($_SERVER['HTTPS']))
      $https = 's';
   else
      $https = '';

   $addr_phys = $this_dir;
   $addr_web  = 'http' . $https . '://' . $_SERVER['HTTP_HOST'] . '/' . $web_part;

   if ($blog_root)
      {
      // Convert addresses to blog root addresses
      $addr_phys = preg_replace ('|(/[^/]+){3}$|', '', $addr_phys);
      $addr_web  = preg_replace ('|(/[^/]+){3}$|', '', $addr_web);
      }

   return (array($addr_phys, $addr_web));
}
//===========================================================================

//===========================================================================
//
// Determine file MIME type
// Ex: 'text/plain'
//

$_mime_exts = array
   (
   'ai' => 'application/postscript',
   'aif' => 'audio/x-aiff',
   'aifc' => 'audio/x-aiff',
   'aiff' => 'audio/x-aiff',
   'asc' => 'text/plain',
   'au' => 'audio/basic',
   'avi' => 'video/x-msvideo',
   'bcpio' => 'application/x-bcpio',
   'bin' => 'application/octet-stream',
   'bmp' => 'image/bmp',
   'cdf' => 'application/x-netcdf',
   'class' => 'application/octet-stream',
   'cpio' => 'application/x-cpio',
   'cpt' => 'application/mac-compactpro',
   'csh' => 'application/x-csh',
   'css' => 'text/css',
   'dcr' => 'application/x-director',
   'dir' => 'application/x-director',
   'djv' => 'image/vnd.djvu',
   'djvu' => 'image/vnd.djvu',
   'dll' => 'application/octet-stream',
   'dms' => 'application/octet-stream',
   'doc' => 'application/msword',
   'dvi' => 'application/x-dvi',
   'dxr' => 'application/x-director',
   'eps' => 'application/postscript',
   'etx' => 'text/x-setext',
   'exe' => 'application/octet-stream',
   'ez' => 'application/andrew-inset',
   'gif' => 'image/gif',
   'gtar' => 'application/x-gtar',
   'hdf' => 'application/x-hdf',
   'hqx' => 'application/mac-binhex40',
   'htm' => 'text/html',
   'html' => 'text/html',
   'ice' => 'x-conference/x-cooltalk',
   'ief' => 'image/ief',
   'iges' => 'model/iges',
   'igs' => 'model/iges',
   'jpe' => 'image/jpeg',
   'jpeg' => 'image/jpeg',
   'jpg' => 'image/jpeg',
   'js' => 'application/x-javascript',
   'kar' => 'audio/midi',
   'latex' => 'application/x-latex',
   'lha' => 'application/octet-stream',
   'lzh' => 'application/octet-stream',
   'm3u' => 'audio/x-mpegurl',
   'man' => 'application/x-troff-man',
   'me' => 'application/x-troff-me',
   'mesh' => 'model/mesh',
   'mid' => 'audio/midi',
   'midi' => 'audio/midi',
   'mif' => 'application/vnd.mif',
   'mov' => 'video/quicktime',
   'movie' => 'video/x-sgi-movie',
   'mp2' => 'audio/mpeg',
   'mp3' => 'audio/mpeg',
   'mpe' => 'video/mpeg',
   'mpeg' => 'video/mpeg',
   'mpg' => 'video/mpeg',
   'mpga' => 'audio/mpeg',
   'ms' => 'application/x-troff-ms',
   'msh' => 'model/mesh',
   'mxu' => 'video/vnd.mpegurl',
   'nc' => 'application/x-netcdf',
   'oda' => 'application/oda',
   'pbm' => 'image/x-portable-bitmap',
   'pdb' => 'chemical/x-pdb',
   'pdf' => 'application/pdf',
   'pgm' => 'image/x-portable-graymap',
   'pgn' => 'application/x-chess-pgn',
   'png' => 'image/png',
   'pnm' => 'image/x-portable-anymap',
   'ppm' => 'image/x-portable-pixmap',
   'ppt' => 'application/vnd.ms-powerpoint',
   'ps' => 'application/postscript',
   'qt' => 'video/quicktime',
   'ra' => 'audio/x-realaudio',
   'ram' => 'audio/x-pn-realaudio',
   'rar' => 'application/x-rar-compressed',
   'ras' => 'image/x-cmu-raster',
   'rgb' => 'image/x-rgb',
   'rm' => 'audio/x-pn-realaudio',
   'roff' => 'application/x-troff',
   'rpm' => 'audio/x-pn-realaudio-plugin',
   'rtf' => 'text/rtf',
   'rtx' => 'text/richtext',
   'sgm' => 'text/sgml',
   'sgml' => 'text/sgml',
   'sh' => 'application/x-sh',
   'shar' => 'application/x-shar',
   'silo' => 'model/mesh',
   'sit' => 'application/x-stuffit',
   'skd' => 'application/x-koan',
   'skm' => 'application/x-koan',
   'skp' => 'application/x-koan',
   'skt' => 'application/x-koan',
   'smi' => 'application/smil',
   'smil' => 'application/smil',
   'snd' => 'audio/basic',
   'so' => 'application/octet-stream',
   'spl' => 'application/x-futuresplash',
   'src' => 'application/x-wais-source',
   'sv4cpio' => 'application/x-sv4cpio',
   'sv4crc' => 'application/x-sv4crc',
   'swf' => 'application/x-shockwave-flash',
   't' => 'application/x-troff',
   'tar' => 'application/x-tar',
   'tcl' => 'application/x-tcl',
   'tex' => 'application/x-tex',
   'texi' => 'application/x-texinfo',
   'texinfo' => 'application/x-texinfo',
   'tif' => 'image/tiff',
   'tiff' => 'image/tiff',
   'tr' => 'application/x-troff',
   'tsv' => 'text/tab-separated-values',
   'txt' => 'text/plain',
   'ustar' => 'application/x-ustar',
   'vcd' => 'application/x-cdlink',
   'vrml' => 'model/vrml',
   'wav' => 'audio/x-wav',
   'wbmp' => 'image/vnd.wap.wbmp',
   'wbxml' => 'application/vnd.wap.wbxml',
   'wml' => 'text/vnd.wap.wml',
   'wmlc' => 'application/vnd.wap.wmlc',
   'wmls' => 'text/vnd.wap.wmlscript',
   'wmlsc' => 'application/vnd.wap.wmlscriptc',
   'wrl' => 'model/vrml',
   'xbm' => 'image/x-xbitmap',
   'xht' => 'application/xhtml+xml',
   'xhtml' => 'application/xhtml+xml',
   'xls' => 'application/vnd.ms-excel',
   'xml' => 'text/xml',
   'xpm' => 'image/x-xpixmap',
   'xsl' => 'text/xml',
   'xwd' => 'image/x-xwindowdump',
   'xyz' => 'chemical/x-xyz',
   'zip' => 'application/zip'
   );

function UTILS_get_mime_type ($filename)
{
   global $_mime_exts;
   $mime = "";

   if (function_exists ('finfo_open'))
      {
      $finfo = finfo_open (FILEINFO_MIME);
      $mime = finfo_file ($finfo, $filename);
      finfo_close ($finfo);
      }

// NOTE: 'mime_content_type' gets confused about uppercased extensions, like '.MPG' and returns 'text/plain'.
//
//   else if (function_exists ('mime_content_type'))
//      {
//      $mime = mime_content_type ($filename);
//      }

   if (!$mime)
      {
      $fileinfo = pathinfo ($filename);
      $extension = strtolower($fileinfo['extension']);
      if (isset ($_mime_exts[$extension]))
         $mime = $_mime_exts[$extension];
      else
         $mime = 'application/octet-stream';
      }

   return $mime;
}
//===========================================================================

//===========================================================================
function mwdebug()
{
return (MWDEBUG);
}
//===========================================================================

//===========================================================================
function log_event ($filename, $linenum, $message, $extra_text="")
{
   $log_filename   = dirname(__FILE__) . '/__log.php';
   $logfile_header = '<?php header("Location: /"); exit(); ?>' . "\r\n" . '/* =============== MemberWing LOG file =============== */' . "\r\n";
   $logfile_tail   = "\r\nEND";

   // Delete too long logfiles.
   if (@file_exists ($log_filename) && @filesize($log_filename)>1000000)
      unlink ($log_filename);

   $filename = basename ($filename);

   if (@file_exists ($log_filename))
      {
      // 'r+' non destructive R/W mode.
      $fhandle = @fopen ($log_filename, 'r+');
      if ($fhandle)
         @fseek ($fhandle, -strlen($logfile_tail), SEEK_END);
      }
   else
      {
      $fhandle = @fopen ($log_filename, 'w');
      if ($fhandle)
         @fwrite ($fhandle, $logfile_header);
      }

   if ($fhandle)
      {
      @fwrite ($fhandle, "\r\n// " . $_SERVER['REMOTE_ADDR'] . '(' . $_SERVER['REMOTE_PORT'] . ')' . ' -> ' . date("Y-m-d, G:i:s.u") . "|$filename($linenum)|: " . $message . ($extra_text?"\r\n//    Extra Data: $extra_text":"") . $logfile_tail);
      @fclose ($fhandle);
      }
}
//===========================================================================

//===========================================================================
function UTILS__send_email ($email_to, $email_from, $subject, $plain_body)
{
$message = "
   <html>
   <head>
   <title>$subject</title>
   </head>
   <body>" . $plain_body . "
   </body>
   </html>
   ";

   // To send HTML mail, the Content-type header must be set
   $headers  = 'MIME-Version: 1.0' . "\r\n";
   $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

   // Additional headers
   $headers .= "To: " . $email_to . "\r\n";        //"To: Mary <mary@example.com>, Kelly <kelly@example.com>" . "\r\n";
   $headers .= "From: " . $email_from . "\r\n";    //"From: Birthday Reminder <birthday@example.com>" . "\r\n";
                                                // $headers .= "Cc: birthdayarchive@example.com" . "\r\n";
                                                // $headers .= "Bcc: birthdaycheck@example.com" . "\r\n";
   // Mail it
   $bRetCode = mail ($email_to, $subject, $message, $headers);
   if ($bRetCode)
      log_event (__FILE__, __LINE__, "Successfully sent email from: $email_from to: $email_to. (mail() returned true)");
   else
      log_event (__FILE__, __LINE__, "ERROR: mail() failed. Error sending email from: $email_from to: $email_to.");
}
//===========================================================================

//===========================================================================
// Save _GET, _POST, _SERVER, _COOKIE in new .html file.

function log_vars()
{
   $var1 = get_var ($_SERVER,    '$_SERVER');
   $var2 = get_var ($_GET,       '$_GET');
   $var3 = get_var ($_POST,      '$_POST');
   $var4 = get_var ($_COOKIE,    '$_COOKIE');

   $output =<<<OUTOUT
   <html>
       <body>
           $var3
           $var2
           $var1
           $var4
       </body>
   </html>
OUTOUT;

   // Save output into unique file
   $log_file_num   = 0;

   do
      {
      $log_file_num ++;
      $log_filename   = dirname(__FILE__) . "/__log_vars_$log_file_num.html";
      }
   while (@file_exists ($log_filename));

   $fhandle = fopen ($log_filename, 'w');
   if ($fhandle)
      {
      fwrite ($fhandle, $output);
      fclose ($fhandle);
      }
}

function get_var ($var, $varname)
{
   $style='"font:12px Verdana;color:blue;"';
   $output = output_varname ($varname);
   foreach ($var as $key => $value)
      {
      $output .= ("&nbsp;&nbsp;&nbsp;<span style=$style>$varname</span>" . '[\'' . output_key($key) . '\']=\'' . output_value($value) . "'");
      $output .= '<br />';
      }

   return $output;
}

function output_varname ($varname)
{
    $style='"font:14px Verdana bold;color:blue;"';
    return "<hr />" . "<p style=$style>$varname:</p>";
}
function output_key ($key)
{
    $style='"font:10px Verdana;color:green;"';
    return "<span style=$style>$key</span>";
}
function output_value ($value)
{
    $style='"font:10px Verdana;color:red;"';
    return "<span style=$style>$value</span>";
}
//===========================================================================


/***

EXTRA MIME TYPES:

'3dm' => 'x-world/x-3dmf',
'3dmf' => 'x-world/x-3dmf',
'a' => 'application/octet-stream',
'aab' => 'application/x-authorware-bin',
'aam' => 'application/x-authorware-map',
'aas' => 'application/x-authorware-seg',
'abc' => 'text/vnd.abc',
'acgi' => 'text/html',
'afl' => 'video/animaflex',
'ai' => 'application/postscript',
'aif' => 'audio/aiff',
'aifc' => 'audio/aiff',
'aiff' => 'audio/aiff',
'aim' => 'application/x-aim',
'aip' => 'text/x-audiosoft-intra',
'ani' => 'application/x-navi-animation',
'aos' => 'application/x-nokia-9000-communicator-add-on-software',
'aps' => 'application/mime',
'arc' => 'application/octet-stream',
'arj' => 'application/arj',
'art' => 'image/x-jg',
'asf' => 'video/x-ms-asf',
'asm' => 'text/x-asm',
'asp' => 'text/asp',
'asx' => 'video/x-ms-asf',
'au' => 'audio/x-au',
'avi' => 'video/avi',
'avs' => 'video/avs-video',
'bcpio' => 'application/x-bcpio',
'bin' => 'application/octet-stream',
'bm' => 'image/bmp',
'bmp' => 'image/bmp',
'boo' => 'application/book',
'book' => 'application/book',
'boz' => 'application/x-bzip2',
'bsh' => 'application/x-bsh',
'bz' => 'application/x-bzip',
'bz2' => 'application/x-bzip2',
'c' => 'text/plain',
'c++' => 'text/plain',
'cat' => 'application/vnd.ms-pki.seccat',
'cc' => 'text/plain',
'ccad' => 'application/clariscad',
'cco' => 'application/x-cocoa',
'cdf' => 'application/cdf',
'cer' => 'application/pkix-cert',
'cha' => 'application/x-chat',
'chat' => 'application/x-chat',
'class' => 'application/java',
'com' => 'text/plain',
'conf' => 'text/plain',
'cpio' => 'application/x-cpio',
'cpp' => 'text/x-c',
'cpt' => 'application/x-cpt',
'crl' => 'application/pkcs-crl',
'crt' => 'application/x-x509-user-cert',
'csh' => 'text/x-script.csh',
'css' => 'text/css',
'cxx' => 'text/plain',
'dcr' => 'application/x-director',
'deepv' => 'application/x-deepv',
'def' => 'text/plain',
'der' => 'application/x-x509-ca-cert',
'dif' => 'video/x-dv',
'dir' => 'application/x-director',
'dl' => 'video/dl',
'dl' => 'video/x-dl',
'doc' => 'application/msword',
'dot' => 'application/msword',
'dp' => 'application/commonground',
'drw' => 'application/drafting',
'dump' => 'application/octet-stream',
'dv' => 'video/x-dv',
'dvi' => 'application/x-dvi',
'dwf' => 'drawing/x-dwf (old)',
'dwf' => 'model/vnd.dwf',
'dwg' => 'application/acad',
'dwg' => 'image/vnd.dwg',
'dwg' => 'image/x-dwg',
'dxf' => 'application/dxf',
'dxf' => 'image/vnd.dwg',
'dxf' => 'image/x-dwg',
'dxr' => 'application/x-director',
'el' => 'text/x-script.elisp',
'elc' => 'application/x-bytecode.elisp (compiled
elisp)',
'elc' => 'application/x-elc',
'env' => 'application/x-envoy',
'eps' => 'application/postscript',
'es' => 'application/x-esrehber',
'etx' => 'text/x-setext',
'evy' => 'application/envoy',
'evy' => 'application/x-envoy',
'exe' => 'application/octet-stream',
'f' => 'text/plain',
'f' => 'text/x-fortran',
'f77' => 'text/x-fortran',
'f90' => 'text/plain',
'f90' => 'text/x-fortran',
'fdf' => 'application/vnd.fdf',
'fif' => 'application/fractals',
'fif' => 'image/fif',
'fli' => 'video/fli',
'fli' => 'video/x-fli',
'flo' => 'image/florian',
'flx' => 'text/vnd.fmi.flexstor',
'fmf' => 'video/x-atomic3d-feature',
'for' => 'text/plain',
'for' => 'text/x-fortran',
'fpx' => 'image/vnd.fpx',
'fpx' => 'image/vnd.net-fpx',
'frl' => 'application/freeloader',
'funk' => 'audio/make',
'g' => 'text/plain',
'g3' => 'image/g3fax',
'gif' => 'image/gif',
'gl' => 'video/gl',
'gl' => 'video/x-gl',
'gsd' => 'audio/x-gsm',
'gsm' => 'audio/x-gsm',
'gsp' => 'application/x-gsp',
'gss' => 'application/x-gss',
'gtar' => 'application/x-gtar',
'gz' => 'application/x-compressed',
'gz' => 'application/x-gzip',
'gzip' => 'application/x-gzip',
'gzip' => 'multipart/x-gzip',
'h' => 'text/plain',
'h' => 'text/x-h',
'hdf' => 'application/x-hdf',
'help' => 'application/x-helpfile',
'hgl' => 'application/vnd.hp-hpgl',
'hh' => 'text/plain',
'hh' => 'text/x-h',
'hlb' => 'text/x-script',
'hlp' => 'application/hlp',
'hlp' => 'application/x-helpfile',
'hlp' => 'application/x-winhelp',
'hpg' => 'application/vnd.hp-hpgl',
'hpgl' => 'application/vnd.hp-hpgl',
'hqx' => 'application/binhex',
'hqx' => 'application/binhex4',
'hqx' => 'application/mac-binhex',
'hqx' => 'application/mac-binhex40',
'hqx' => 'application/x-binhex40',
'hqx' => 'application/x-mac-binhex40',
'hta' => 'application/hta',
'htc' => 'text/x-component',
'htm' => 'text/html',
'html' => 'text/html',
'htmls' => 'text/html',
'htt' => 'text/webviewhtml',
'htx ' => 'text/html',
'ice ' => 'x-conference/x-cooltalk',
'ico' => 'image/x-icon',
'idc' => 'text/plain',
'ief' => 'image/ief',
'iefs' => 'image/ief',
'iges' => 'application/iges',
'iges ' => 'model/iges',
'igs' => 'application/iges',
'igs' => 'model/iges',
'ima' => 'application/x-ima',
'imap' => 'application/x-httpd-imap',
'inf ' => 'application/inf',
'ins' => 'application/x-internett-signup',
'ip ' => 'application/x-ip2',
'isu' => 'video/x-isvideo',
'it' => 'audio/it',
'iv' => 'application/x-inventor',
'ivr' => 'i-world/i-vrml',
'ivy' => 'application/x-livescreen',
'jam ' => 'audio/x-jam',
'jav' => 'text/plain',
'jav' => 'text/x-java-source',
'java' => 'text/plain',
'java ' => 'text/x-java-source',
'jcm ' => 'application/x-java-commerce',
'jfif' => 'image/jpeg',
'jfif' => 'image/pjpeg',
'jfif-tbnl' => 'image/jpeg',
'jpe' => 'image/jpeg',
'jpe' => 'image/pjpeg',
'jpeg' => 'image/jpeg',
'jpeg' => 'image/pjpeg',
'jpg ' => 'image/jpeg',
'jpg ' => 'image/pjpeg',
'jps' => 'image/x-jps',
'js ' => 'application/x-javascript',
'jut' => 'image/jutvision',
'kar' => 'audio/midi',
'kar' => 'music/x-karaoke',
'ksh' => 'application/x-ksh',
'ksh' => 'text/x-script.ksh',
'la ' => 'audio/nspaudio',
'la ' => 'audio/x-nspaudio',
'lam' => 'audio/x-liveaudio',
'latex ' => 'application/x-latex',
'lha' => 'application/lha',
'lha' => 'application/octet-stream',
'lha' => 'application/x-lha',
'lhx' => 'application/octet-stream',
'list' => 'text/plain',
'lma' => 'audio/nspaudio',
'lma' => 'audio/x-nspaudio',
'log ' => 'text/plain',
'lsp ' => 'application/x-lisp',
'lsp ' => 'text/x-script.lisp',
'lst ' => 'text/plain',
'lsx' => 'text/x-la-asf',
'ltx' => 'application/x-latex',
'lzh' => 'application/octet-stream',
'lzh' => 'application/x-lzh',
'lzx' => 'application/lzx',
'lzx' => 'application/octet-stream',
'lzx' => 'application/x-lzx',
'm' => 'text/plain',
'm' => 'text/x-m',
'm1v' => 'video/mpeg',
'm2a' => 'audio/mpeg',
'm2v' => 'video/mpeg',
'm3u ' => 'audio/x-mpequrl',
'man' => 'application/x-troff-man',
'map' => 'application/x-navimap',
'mar' => 'text/plain',
'mbd' => 'application/mbedlet',
'mc$' => 'application/x-magic-cap-package-1.0',
'mcd' => 'application/mcad',
'mcd' => 'application/x-mathcad',
'mcf' => 'image/vasa',
'mcf' => 'text/mcf',
'mcp' => 'application/netmc',
'me ' => 'application/x-troff-me',
'mht' => 'message/rfc822',
'mhtml' => 'message/rfc822',
'mid' => 'application/x-midi',
'mid' => 'audio/midi',
'mid' => 'audio/x-mid',
'mid' => 'audio/x-midi',
'mid' => 'music/crescendo',
'mid' => 'x-music/x-midi',
'midi' => 'application/x-midi',
'midi' => 'audio/midi',
'midi' => 'audio/x-mid',
'midi' => 'audio/x-midi',
'midi' => 'music/crescendo',
'midi' => 'x-music/x-midi',
'mif' => 'application/x-frame',
'mif' => 'application/x-mif',
'mime ' => 'message/rfc822',
'mime ' => 'www/mime',
'mjf' => 'audio/x-vnd.audioexplosion.mjuicemediafile',
'mjpg ' => 'video/x-motion-jpeg',
'mm' => 'application/base64',
'mm' => 'application/x-meme',
'mme' => 'application/base64',
'mod' => 'audio/mod',
'mod' => 'audio/x-mod',
'moov' => 'video/quicktime',
'mov' => 'video/quicktime',
'movie' => 'video/x-sgi-movie',
'mp2' => 'audio/mpeg',
'mp2' => 'audio/x-mpeg',
'mp2' => 'video/mpeg',
'mp2' => 'video/x-mpeg',
'mp2' => 'video/x-mpeq2a',
'mp3' => 'audio/mpeg3',
'mp3' => 'audio/x-mpeg-3',
'mp3' => 'video/mpeg',
'mp3' => 'video/x-mpeg',
'mpa' => 'audio/mpeg',
'mpa' => 'video/mpeg',
'mpc' => 'application/x-project',
'mpe' => 'video/mpeg',
'mpeg' => 'video/mpeg',
'mpg' => 'audio/mpeg',
'mpg' => 'video/mpeg',
'mpga' => 'audio/mpeg',
'mpp' => 'application/vnd.ms-project',
'mpt' => 'application/x-project',
'mpv' => 'application/x-project',
'mpx' => 'application/x-project',
'mrc' => 'application/marc',
'ms' => 'application/x-troff-ms',
'mv' => 'video/x-sgi-movie',
'my' => 'audio/make',
'mzz' => 'application/x-vnd.audioexplosion.mzz',
'nap' => 'image/naplps',
'naplps' => 'image/naplps',
'nc' => 'application/x-netcdf',
'ncm' => 'application/vnd.nokia.configuration-message',
'nif' => 'image/x-niff',
'niff' => 'image/x-niff',
'nix' => 'application/x-mix-transfer',
'nsc' => 'application/x-conference',
'nvd' => 'application/x-navidoc',
'o' => 'application/octet-stream',
'oda' => 'application/oda',
'omc' => 'application/x-omc',
'omcd' => 'application/x-omcdatamaker',
'omcr' => 'application/x-omcregerator',
'p' => 'text/x-pascal',
'p10' => 'application/pkcs10',
'p10' => 'application/x-pkcs10',
'p12' => 'application/pkcs-12',
'p12' => 'application/x-pkcs12',
'p7a' => 'application/x-pkcs7-signature',
'p7c' => 'application/pkcs7-mime',
'p7c' => 'application/x-pkcs7-mime',
'p7m' => 'application/pkcs7-mime',
'p7m' => 'application/x-pkcs7-mime',
'p7r' => 'application/x-pkcs7-certreqresp',
'p7s' => 'application/pkcs7-signature',
'part ' => 'application/pro_eng',
'pas' => 'text/pascal',
'pbm ' => 'image/x-portable-bitmap',
'pcl' => 'application/vnd.hp-pcl',
'pcl' => 'application/x-pcl',
'pct' => 'image/x-pict',
'pcx' => 'image/x-pcx',
'pdb' => 'chemical/x-pdb',
'pdf' => 'application/pdf',
'pfunk' => 'audio/make',
'pfunk' => 'audio/make.my.funk',
'pgm' => 'image/x-portable-graymap',
'pgm' => 'image/x-portable-greymap',
'pic' => 'image/pict',
'pict' => 'image/pict',
'pkg' => 'application/x-newton-compatible-pkg',
'pko' => 'application/vnd.ms-pki.pko',
'pl' => 'text/plain',
'pl' => 'text/x-script.perl',
'plx' => 'application/x-pixclscript',
'pm' => 'image/x-xpixmap',
'pm' => 'text/x-script.perl-module',
'pm4 ' => 'application/x-pagemaker',
'pm5' => 'application/x-pagemaker',
'png' => 'image/png',
'pnm' => 'application/x-portable-anymap',
'pnm' => 'image/x-portable-anymap',
'pot' => 'application/mspowerpoint',
'pot' => 'application/vnd.ms-powerpoint',
'pov' => 'model/x-pov',
'ppa' => 'application/vnd.ms-powerpoint',
'ppm' => 'image/x-portable-pixmap',
'pps' => 'application/mspowerpoint',
'pps' => 'application/vnd.ms-powerpoint',
'ppt' => 'application/mspowerpoint',
'ppt' => 'application/powerpoint',
'ppt' => 'application/vnd.ms-powerpoint',
'ppt' => 'application/x-mspowerpoint',
'ppz' => 'application/mspowerpoint',
'pre' => 'application/x-freelance',
'prt' => 'application/pro_eng',
'ps' => 'application/postscript',
'psd' => 'application/octet-stream',
'pvu' => 'paleovu/x-pv',
'pwz ' => 'application/vnd.ms-powerpoint',
'py ' => 'text/x-script.phyton',
'pyc ' => 'applicaiton/x-bytecode.python',
'qcp ' => 'audio/vnd.qcelp',
'qd3 ' => 'x-world/x-3dmf',
'qd3d ' => 'x-world/x-3dmf',
'qif' => 'image/x-quicktime',
'qt' => 'video/quicktime',
'qtc' => 'video/x-qtc',
'qti' => 'image/x-quicktime',
'qtif' => 'image/x-quicktime',
'ra' => 'audio/x-pn-realaudio',
'ra' => 'audio/x-pn-realaudio-plugin',
'ra' => 'audio/x-realaudio',
'ram' => 'audio/x-pn-realaudio',
'ras' => 'application/x-cmu-raster',
'ras' => 'image/cmu-raster',
'ras' => 'image/x-cmu-raster',
'rast' => 'image/cmu-raster',
'rexx ' => 'text/x-script.rexx',
'rf' => 'image/vnd.rn-realflash',
'rgb ' => 'image/x-rgb',
'rm' => 'application/vnd.rn-realmedia',
'rm' => 'audio/x-pn-realaudio',
'rmi' => 'audio/mid',
'rmm ' => 'audio/x-pn-realaudio',
'rmp' => 'audio/x-pn-realaudio',
'rmp' => 'audio/x-pn-realaudio-plugin',
'rng' => 'application/ringing-tones',
'rng' => 'application/vnd.nokia.ringing-tone',
'rnx ' => 'application/vnd.rn-realplayer',
'roff' => 'application/x-troff',
'rp ' => 'image/vnd.rn-realpix',
'rpm' => 'audio/x-pn-realaudio-plugin',
'rt' => 'text/richtext',
'rt' => 'text/vnd.rn-realtext',
'rtf' => 'application/rtf',
'rtf' => 'application/x-rtf',
'rtf' => 'text/richtext',
'rtx' => 'application/rtf',
'rtx' => 'text/richtext',
'rv' => 'video/vnd.rn-realvideo',
's' => 'text/x-asm',
's3m ' => 'audio/s3m',
'saveme' => 'application/octet-stream',
'sbk ' => 'application/x-tbook',
'scm' => 'application/x-lotusscreencam',
'scm' => 'text/x-script.guile',
'scm' => 'text/x-script.scheme',
'scm' => 'video/x-scm',
'sdml' => 'text/plain',
'sdp ' => 'application/sdp',
'sdp ' => 'application/x-sdp',
'sdr' => 'application/sounder',
'sea' => 'application/sea',
'sea' => 'application/x-sea',
'set' => 'application/set',
'sgm ' => 'text/sgml',
'sgm ' => 'text/x-sgml',
'sgml' => 'text/sgml',
'sgml' => 'text/x-sgml',
'sh' => 'application/x-bsh',
'sh' => 'application/x-sh',
'sh' => 'application/x-shar',
'sh' => 'text/x-script.sh',
'shar' => 'application/x-bsh',
'shar' => 'application/x-shar',
'shtml ' => 'text/html',
'shtml' => 'text/x-server-parsed-html',
'sid' => 'audio/x-psid',
'sit' => 'application/x-sit',
'sit' => 'application/x-stuffit',
'skd' => 'application/x-koan',
'skm ' => 'application/x-koan',
'skp ' => 'application/x-koan',
'skt ' => 'application/x-koan',
'sl ' => 'application/x-seelogo',
'smi ' => 'application/smil',
'smil ' => 'application/smil',
'snd' => 'audio/basic',
'snd' => 'audio/x-adpcm',
'sol' => 'application/solids',
'spc ' => 'application/x-pkcs7-certificates',
'spc ' => 'text/x-speech',
'spl' => 'application/futuresplash',
'spr' => 'application/x-sprite',
'sprite ' => 'application/x-sprite',
'src' => 'application/x-wais-source',
'ssi' => 'text/x-server-parsed-html',
'ssm ' => 'application/streamingmedia',
'sst' => 'application/vnd.ms-pki.certstore',
'step' => 'application/step',
'stl' => 'application/sla',
'stl' => 'application/vnd.ms-pki.stl',
'stl' => 'application/x-navistyle',
'stp' => 'application/step',
'sv4cpio' => 'application/x-sv4cpio',
'sv4crc' => 'application/x-sv4crc',
'svf' => 'image/vnd.dwg',
'svf' => 'image/x-dwg',
'svr' => 'application/x-world',
'svr' => 'x-world/x-svr',
'swf' => 'application/x-shockwave-flash',
't' => 'application/x-troff',
'talk' => 'text/x-speech',
'tar' => 'application/x-tar',
'tbk' => 'application/toolbook',
'tbk' => 'application/x-tbook',
'tcl' => 'application/x-tcl',
'tcl' => 'text/x-script.tcl',
'tcsh' => 'text/x-script.tcsh',
'tex' => 'application/x-tex',
'texi' => 'application/x-texinfo',
'texinfo' => 'application/x-texinfo',
'text' => 'application/plain',
'text' => 'text/plain',
'tgz' => 'application/gnutar',
'tgz' => 'application/x-compressed',
'tif' => 'image/tiff',
'tif' => 'image/x-tiff',
'tiff' => 'image/tiff',
'tiff' => 'image/x-tiff',
'tr' => 'application/x-troff',
'tsi' => 'audio/tsp-audio',
'tsp' => 'application/dsptype',
'tsp' => 'audio/tsplayer',
'tsv' => 'text/tab-separated-values',
'turbot' => 'image/florian',
'txt' => 'text/plain',
'uil' => 'text/x-uil',
'uni' => 'text/uri-list',
'unis' => 'text/uri-list',
'unv' => 'application/i-deas',
'uri' => 'text/uri-list',
'uris' => 'text/uri-list',
'ustar' => 'application/x-ustar',
'ustar' => 'multipart/x-ustar',
'uu' => 'application/octet-stream',
'uu' => 'text/x-uuencode',
'uue' => 'text/x-uuencode',
'vcd' => 'application/x-cdlink',
'vcs' => 'text/x-vcalendar',
'vda' => 'application/vda',
'vdo' => 'video/vdo',
'vew ' => 'application/groupwise',
'viv' => 'video/vivo',
'viv' => 'video/vnd.vivo',
'vivo' => 'video/vivo',
'vivo' => 'video/vnd.vivo',
'vmd ' => 'application/vocaltec-media-desc',
'vmf' => 'application/vocaltec-media-file',
'voc' => 'audio/voc',
'voc' => 'audio/x-voc',
'vos' => 'video/vosaic',
'vox' => 'audio/voxware',
'vqe' => 'audio/x-twinvq-plugin',
'vqf' => 'audio/x-twinvq',
'vql' => 'audio/x-twinvq-plugin',
'vrml' => 'application/x-vrml',
'vrml' => 'model/vrml',
'vrml' => 'x-world/x-vrml',
'vrt' => 'x-world/x-vrt',
'vsd' => 'application/x-visio',
'vst' => 'application/x-visio',
'vsw ' => 'application/x-visio',
'w60' => 'application/wordperfect6.0',
'w61' => 'application/wordperfect6.1',
'w6w' => 'application/msword',
'wav' => 'audio/wav',
'wav' => 'audio/x-wav',
'wb1' => 'application/x-qpro',
'wbmp' => 'image/vnd.wap.wbmp',
'web' => 'application/vnd.xara',
'wiz' => 'application/msword',
'wk1' => 'application/x-123',
'wmf' => 'windows/metafile',
'wml' => 'text/vnd.wap.wml',
'wmlc ' => 'application/vnd.wap.wmlc',
'wmls' => 'text/vnd.wap.wmlscript',
'wmlsc ' => 'application/vnd.wap.wmlscriptc',
'word ' => 'application/msword',
'wp' => 'application/wordperfect',
'wp5' => 'application/wordperfect',
'wp5' => 'application/wordperfect6.0',
'wp6 ' => 'application/wordperfect',
'wpd' => 'application/wordperfect',
'wpd' => 'application/x-wpwin',
'wq1' => 'application/x-lotus',
'wri' => 'application/mswrite',
'wri' => 'application/x-wri',
'wrl' => 'application/x-world',
'wrl' => 'model/vrml',
'wrl' => 'x-world/x-vrml',
'wrz' => 'model/vrml',
'wrz' => 'x-world/x-vrml',
'wsc' => 'text/scriplet',
'wsrc' => 'application/x-wais-source',
'wtk ' => 'application/x-wintalk',
'xbm' => 'image/x-xbitmap',
'xbm' => 'image/x-xbm',
'xbm' => 'image/xbm',
'xdr' => 'video/x-amt-demorun',
'xgz' => 'xgl/drawing',
'xif' => 'image/vnd.xiff',
'xl' => 'application/excel',
'xla' => 'application/excel',
'xla' => 'application/x-excel',
'xla' => 'application/x-msexcel',
'xlb' => 'application/excel',
'xlb' => 'application/vnd.ms-excel',
'xlb' => 'application/x-excel',
'xlc' => 'application/excel',
'xlc' => 'application/vnd.ms-excel',
'xlc' => 'application/x-excel',
'xld ' => 'application/excel',
'xld ' => 'application/x-excel',
'xlk' => 'application/excel',
'xlk' => 'application/x-excel',
'xll' => 'application/excel',
'xll' => 'application/vnd.ms-excel',
'xll' => 'application/x-excel',
'xlm' => 'application/excel',
'xlm' => 'application/vnd.ms-excel',
'xlm' => 'application/x-excel',
'xls' => 'application/excel',
'xls' => 'application/vnd.ms-excel',
'xls' => 'application/x-excel',
'xls' => 'application/x-msexcel',
'xlt' => 'application/excel',
'xlt' => 'application/x-excel',
'xlv' => 'application/excel',
'xlv' => 'application/x-excel',
'xlw' => 'application/excel',
'xlw' => 'application/vnd.ms-excel',
'xlw' => 'application/x-excel',
'xlw' => 'application/x-msexcel',
'xm' => 'audio/xm',
'xml' => 'application/xml',
'xml' => 'text/xml',
'xmz' => 'xgl/movie',
'xpix' => 'application/x-vnd.ls-xpix',
'xpm' => 'image/x-xpixmap',
'xpm' => 'image/xpm',
'x-png' => 'image/png',
'xsr' => 'video/x-amt-showrun',
'xwd' => 'image/x-xwd',
'xwd' => 'image/x-xwindowdump',
'xyz' => 'chemical/x-pdb',
'z' => 'application/x-compress',
'z' => 'application/x-compressed',
'zip' => 'application/x-compressed',
'zip' => 'application/x-zip-compressed',
'zip' => 'application/zip',
'zip' => 'multipart/x-zip',
'zoo' => 'application/octet-stream',
'zsh' => 'text/x-script.zsh',


***/

?>