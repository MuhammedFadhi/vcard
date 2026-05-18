<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "effedoco_vcard";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
} 

/** 
 * Web base (for URLs) and filesystem base (for saving files).
 * config.php is inside /includes, so project root is dirname(__DIR__).
 */
// if (!defined('APP_BASE'))  define('APP_BASE', '/digitalcard/');                        // web root for this app
if (!defined('APP_BASE')) define('APP_BASE', '/vcard/');  // keep the trailing slash
// ---- Define full base URL automatically ----
$scheme = !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
            ? $_SERVER['HTTP_X_FORWARDED_PROTO']
            : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

if (!defined('APP_URL')) {
  // e.g. https://effedo.app/digitalcard/
  define('APP_URL', $scheme . '://' . $host . APP_BASE);
}
// 🔹 Global favicon URL (absolute path so it works for all company slugs)
if (!defined('FAVICON_URL')) {
  define('FAVICON_URL', 'https://sada.effedo.app/themes/default/admin/assets/images/icon.png');
}
// if (!headers_sent()) {
//   echo '
//     <link rel="icon" type="image/png" href="'.FAVICON_URL.'">
//     <link rel="apple-touch-icon" href="'.FAVICON_URL.'">
//     <link rel="shortcut icon" href="'.FAVICON_URL.'" type="image/png">
//   ';
// }


if (!defined('APP_FS'))    define('APP_FS', realpath(dirname(__DIR__)).DIRECTORY_SEPARATOR); // C:\xampp\htdocs\digitalcard\
if (!defined('UPLOAD_FS')) define('UPLOAD_FS', APP_FS.'uploads'.DIRECTORY_SEPARATOR);       // C:\xampp\htdocs\digitalcard\uploads\
if (!defined('UPLOAD_WEB'))define('UPLOAD_WEB', APP_BASE.'uploads/');                       // /digitalcard/uploads/
