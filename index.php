<?php
$debug = false;
session_start();
global $language;
$languages = array("en_US");
$default_routes = array('Landstraße'    => 75
                        , 'Autobahn'    => 87
                        , 'gemischt'    => 80);
$data_connector = "sqlite"; /* "mysql:host=hostname;dbname=databasename" */
$sqlite_extension = "sq3";
$data_user = "";
$data_password = "";

global $routes_table;
global $db_tables;
$db_tables = array();

if(!$data_table = $_REQUEST["tbl"]) $data_table = "fahrgemeinschaft";
if(!$auth_table = $_REQUEST["auth"]) $auth_table = "administration";
if(!$user_table = $_REQUEST["usr"]) $user_table = "mitfahrer";
if(!$routes_table = $_REQUEST["rts"]) $routes_table = "strecken";
$auth_rights = -1;
$keys = array();
$values = array();
$strecken = array();
$file_without_extension = array();
if(stripos($sqlite_extension, ".") === false)
    $sqlite_extension = ".{$sqlite_extension}";
if(!$database = $_REQUEST["db"]) if(!$database = $_SESSION["db"]) $database = "default{$sqlite_extension}";
//var_dump($sqlite_extension);echo "<br />";
$sqlite_extension_length = strlen($sqlite_extension);

//echo "substr({$database}, ({$sqlite_extension_length} * -1), {$sqlite_extension_length}) != {$sqlite_extension}<br />";
if(substr($database, ($sqlite_extension_length * -1), $sqlite_extension_length) != $sqlite_extension)
    $database .= "{$sqlite_extension}";
if(stripos($database, "./") === false)
    $database = "./{$database}";
$_SESSION["db"] = $database;
if($debug === true && $_REQUEST["delete"])
{
        unset($_SESSION["username"]);
        unset($_SESSION["loggedIn"]);
        unset($_SESSION["rights"]);
        unset($_SESSION["db"]);
        unset($_SESSION["lang"]);
        unset($_SESSION);
        session_destroy();   
}
if($debug === true) {echo "debug: "; var_dump($debug); echo "_SESSION: "; var_dump($_SESSION); echo "<br />\n";}
/******************************************************************************/
/***************************** program code ***********************************/
/******************************************************************************/
$true = 1;
$false = -1;
$admin_list = array();
$user_list = array();
$all_user_list = array();
$ride_sharing = basename ($database,"{$sqlite_extension}");
//array_push($file_without_extension, basename ($database,"{$sqlite_extension}"));
$title .= " (".basename ($database,"{$sqlite_extension}");
if($_SESSION['Debug'] == "true" || intval($_SESSION['Debug']) == 1) $sessionDebug = True;
else $sessionDebug = False;
if($_REQUEST['Debug'] == "true" || intval($_REQUEST['Debug']) == 1) $requestDebug = True;
else $requestDebug = False;
$xorDebug = ($sessionDebug xor $requestDebug);
if($requestDebug) $_SESSION['Debug'] = $xorDebug;
if($_SESSION['Debug']) $database = "debug{$sqlite_extension}";
$data_connection_string = "{$data_connector}:{$database}";

try
{
    if($data_user)
    {
        if($data_password)
        {
			if($data_connector == "sqlite")
				clearstatcache($database, true);
            /*** connect with user_name and password ***/
//echo "Connection string: \"new PDO('{$data_connector}:{$database}', '{$data_user}', '{$data_password}');\"<br />\n";
            $dbh = new PDO("{$data_connector}:{$database}", $data_user, $data_password);
        } else {
            /*** connect with user_name ***/
//echo "Connection string: \"new PDO('{$data_connector}:{$database}', '{$data_user}');\"<br />\n";
            $dbh = new PDO("{$data_connector}:{$database}", $data_user);
        }
    } else {
        /*** connect without authentification ***/
//echo "Connection string: \"new PDO('{$data_connector}:{$database}');\"<br />\n";
        $dbh = new PDO("{$data_connector}:{$database}");
    }
    if($dbh === null) die("Wat is nu los?");
}
catch(PDOException $e)
{
    echo $e->getMessage();
}
//var_dump($dbh); exit("BREAKPOINT");
$dbh->exec("CREATE TABLE IF NOT EXISTS `{$data_table}` (
    `Datum` date NOT NULL,
    `Strecke` int(11) NOT NULL,
    `Insassen` int(11) NOT NULL,
    `Verbrauch` float NOT NULL,
    `Literpreis` float NOT NULL,
    `Fahrer` TEXT NOT NULL,
    `Bezahlt` TEXT NOT NULL
)");
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
$dbh->exec("CREATE TABLE IF NOT EXISTS `{$routes_table}` (
    `Streckenname` TEXT UNIQUE,
    `Distanz` int(11) NOT NULL
)");
$dbh->exec("CREATE TABLE IF NOT EXISTS `{$user_table}` (
                `name` TEXT UNIQUE,
                `role` INT NOT NULL,
                `nopwd` INT NOT NULL,
                `lang` TEXT NOT NULL
)");

if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
$datepicker = "datepicker.{$language}.js";
if(!file_exists($datepicker))
    $datepicker = "datepicker.js";
$with_administration = false;
//echo "with_administration = " .$with_administration ."<br />";
$sql = "SELECT name FROM sqlite_master WHERE type = 'table';";

$db_tables = array();
foreach ($dbh->query($sql) as $row)
{
    array_push($db_tables, $row["name"]);
//echo "Tabellen: ";var_dump($row["name"]);echo "<br />";
    if($row["name"] == $auth_table)
            $with_administration = true;
}
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";

require_once("tools.php");

/******************************** SET LANGUAGE ********************************/
if ($dh = opendir("."))
{ // read directory...
    while (($file = readdir($dh)) !== false)
    {
        // get all languages...
        if(strripos($file, ".lng") == strlen($file) - 4)
            array_push($languages, basename ($file, ".lng"));
        
        // get all sqlite databases...
        if(strripos($file, "{$sqlite_extension}") > 0)
            array_push($file_without_extension, basename ($file, "{$sqlite_extension}"));
//            $file_without_extension = basename ($file,"{$sqlite_extension}"); 
    }
    closedir($dh);
} // ...read directory
$default_language = null;
if($with_administration === false || ($_SESSION["loggedIn"] == $database && $_SESSION["rights"] >= 1))
{
    $default_language = $_REQUEST["default_language"];
}
//var_dump($default_language);
if(!$default_language)
{
    $sql = "SELECT * FROM {$user_table} WHERE `name` = ''";
    foreach ($dbh->query($sql) as $row)
        $default_language = $row["lang"];
    if(!$default_language)
        $default_language = $languages[0];
}
if($debug === true) {var_dump($_SESSION["lang"]);}
if(!$_SESSION["lang"])
    $_SESSION["lang"] = $default_language;
if($debug === true) {var_dump($_SESSION["lang"]);}

//var_dump($default_language);
$default_exists = false;
$sql = "SELECT * FROM {$user_table} WHERE `name` = ''";
foreach ($dbh->query($sql) as $row)
    $default_exists = true;
if($default_exists === true)
    $sql = "UPDATE `{$user_table}` SET `lang` = '{$default_language}' WHERE `name` = '';";
else
    $sql = "INSERT INTO `{$user_table}` (`name`, `role`, `nopwd`, `lang`) VALUES ('', '', '', '{$default_language}');";
//var_dump($sql);
$ret = $dbh->exec($sql);
//var_dump($ret);

if(!$username = $_REQUEST["MY_AUTH_USER"]) $username = $_SESSION["username"];
if($with_administration === true)
{
    $sql = "SELECT * FROM {$user_table} WHERE `name` = '{$username}'";
    foreach ($dbh->query($sql) as $row)
        $default_language = $row["lang"];
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
}
if(!$language = $_REQUEST["lang"])
{
    if(!$_SESSION["username"] && !$_REQUEST["MY_AUTH_USER"])
    {
        if(!$language = $_SESSION["lang"])
        {
            $language = $default_language;
        }
    } else {
        $language = $default_language;
    }
}
$_SESSION["lang"] = $language;
$title = Translate("ride sharing", $language);
if($_SESSION["username"])
    $dbh->exec("UPDATE `{$user_table}` SET `lang` = '{$language}' WHERE `name` = '{$_SESSION["username"]}';");
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
/******************************** SET LANGUAGE ********************************/

if($_REQUEST["alternatives"] && ($with_administration == false || $_SESSION["rights"] >= 2))
{
    $alternatives = explode(";", $_REQUEST["alternatives"]);
//print_r($alternatives);
    for($i=0; $i<count($alternatives); ++$i)
    {
        $way=explode("(", $alternatives[$i]);
//print_r($way);
        array_push($keys, trim($way[0]));
        array_push($values, intval(trim(substr($way[1], 0, -1))));
//print_r($this_way);
        //array_push($strecken, $this_way);
    }
}
$sql = "SELECT * FROM {$routes_table};";
if(in_array($routes_table, $db_tables))
{
    foreach ($dbh->query($sql) as $row)
    {
        array_push($keys, $row["Streckenname"]);
        array_push($values, intval($row["Distanz"]));
    }
}
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
if(count($keys) > 0 && count($values) > 0)
{
    $strecken = array_combine($keys, $values);

//print_r($strecken);
//    $keys = array_keys($strecken);
//print_r($keys);
    for($i=0; $i<count($strecken); ++$i)
    {
        if($strecken[$keys[$i]] > 0)
        {
        $sql = "INSERT INTO `{$routes_table}` (`Streckenname`, `Distanz`)
                    VALUES ('{$keys[$i]}' , '{$strecken[$keys[$i]]}');";
        $dbh->exec($sql);
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
        }
//print_r($sql);echo "<br />";
//var_dump($dbh->errorInfo());echo "<br />";
    }
}
//print_r($strecken);echo "<hr />";

//echo "Session: ";print_r($_SESSION);echo "<br />";
//echo "Request: ";print_r($_REQUEST);echo "<hr />";
$action = $_REQUEST['act'];
switch($action)
{
    case "print":
		$attachment = (bool)$_REQUEST["attachment"];
		printTable($dbh, $_REQUEST["sql"], $_REQUEST["format"]);
		break;
		
	case "insert":
        $sql = "SELECT * FROM `{$data_table}` ORDER BY Datum DESC LIMIT 1";
        $datum = $_REQUEST['datum'];
        $personen = 1;
        $strecke = 80;
        $verbrauch = 0;
        $literpreis = 0;
        $fahrer = "";
        $bezahlt = "";
        foreach ($dbh->query($sql) as $row)
        { // Default von Voreintrag holen...
            $personen = $row['Insassen'];
            $strecke = $row['Strecke'];
            $verbrauch = $row['Verbrauch'];
            $literpreis = $row['Literpreis'];
            $fahrer = $row['Fahrer'];
        } // ...Default von Voreintrag holen
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
        
        // mit tatsächlichen Werten überschreiben...
        if($_REQUEST['verbrauch'])
        {
            if(is_numeric($_REQUEST['verbrauch'])) $verbrauch = $_REQUEST['verbrauch'];
            else $verbrauch = floatval(str_replace(',', '.', $_REQUEST['verbrauch'])); 
        }
        if($_REQUEST['literpreis'])
        {
            if(is_numeric($_REQUEST['literpreis'])) $literpreis = $_REQUEST['literpreis'];
            else $literpreis = floatval(str_replace(',', '.', $_REQUEST['literpreis'])); 
        }
        if($_REQUEST['personen']) $personen = $_REQUEST['personen']; 
        if($_REQUEST['strecke']) $strecke = $_REQUEST['strecke']; 
        if($_REQUEST['fahrer']) $fahrer = $_REQUEST['fahrer']; 
//var_dump($_REQUEST);echo "<br />";
        // ...mit tatsächlichen Werten überschreiben

        $sql = "SELECT * FROM `{$data_table}` WHERE `Datum` = '".$_REQUEST['datum'] ."' ORDER BY Datum";
        $exists = 0;
        foreach ($dbh->query($sql) as $row)
            ++$exists;
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
        if($exists == 0)
        {    
            $sql = "INSERT INTO `{$data_table}` (`Datum`, `Strecke`, `Insassen`, `Verbrauch`, `Literpreis`, `Fahrer`, `Bezahlt`)
                    VALUES ('{$_REQUEST['datum']}'
                    , '{$_REQUEST['strecke']}'
                    , '{$personen}'
                    , '{$verbrauch}'
                    , '{$literpreis}'
                    , '{$fahrer}'";
            if($with_administration === false) $sql .= ", '1');";
            else $sql .= ", '{$fahrer}');";
        }
        else
        {
            $sql = "UPDATE `{$data_table}` SET `Strecke` = '{$strecke}'
                                                , `Insassen` = '{$personen}'
                                                , `Verbrauch` = '{$verbrauch}'
                                                , `Literpreis` = '{$literpreis}'
                                                , `Fahrer` = '{$fahrer}'";
            if($with_administration === false) $sql .= ", `Bezahlt` = '1'";
            else $sql .= ", `Bezahlt` = '{$fahrer}'";
            $sql .= "WHERE `Datum` = '{$_REQUEST['datum']}';";
        }
//var_dump($sql);echo "<br />";
        $dbh->exec($sql);
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
        header("Location: {$_SERVER["PHP_SELF"]}");
        break;
    
    case "delete":
        $sql = "DELETE FROM `{$data_table}`
                WHERE `Datum` = '" .$_POST['datum'] ."';";
        
        $dbh->exec($sql);
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
        header("Location: {$_SERVER["PHP_SELF"]}");
        break;

    case "payed":
        $sql = "SELECT * FROM `{$data_table}` WHERE `Datum` = '".$_REQUEST['datum'] ."' ORDER BY Datum";
        foreach ($dbh->query($sql) as $row)
            if($with_administration === false)
                $bezahlt = intval($row["Bezahlt"]) + 1;
            else
                $bezahlt = "{$row["Bezahlt"]};{$_REQUEST["bezahlt"]}";
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
        $sql = "UPDATE `{$data_table}` SET `Bezahlt` = '{$bezahlt}'
                WHERE `Datum` = '" .$_POST['datum'] ."';";
        
        $dbh->exec($sql);
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
        header("Location: {$_SERVER["PHP_SELF"]}");
        break;

    case "destroy":
        if($with_administration === false || $_SESSION["rights"] >= 2)
        {
//            echo "unlink({$database}) <hr />\n";
            $dbh = null;
            unlink($database);
        }
        header("Location: {$_SERVER["PHP_SELF"]}");
        break;

    case "chpwd":
//var_dump($_POST);
        if($_POST['MY_AUTH_PW'])
        {
            $name_hash = hash('sha512', $_POST['adm']);
            $pwd_hash = hash('sha512', $_POST['MY_AUTH_PW']);
            $sql = "UPDATE `{$auth_table}` SET `password_hash` = '{$pwd_hash}'
                    WHERE `name_hash` = '{$name_hash}';";
            $dbh->exec($sql);
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
//echo "auth_table: ";print_r($sql);echo "<br />";
//var_dump($dbh->errorInfo());echo "<br />";
        }
        $sql = "UPDATE `{$user_table}` SET `nopwd` = '0'
                WHERE `name` = '{$_POST["adm"]}';";
        $dbh->exec($sql);
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
//echo "user_table: ";print_r($sql);echo "<br />";
//var_dump($dbh->errorInfo());echo "<br />";
        header("Location: {$_SERVER["PHP_SELF"]}");
        break;

    case "admin":
//var_dump($_POST);

        $sql = "CREATE TABLE IF NOT EXISTS `{$auth_table}` (
                `name_hash` TEXT NOT NULL ,
                `password_hash` TEXT NOT NULL ,
                `rights` INT NOT NULL
                )";
        $dbh->exec($sql);
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
//echo "auth_table";var_dump($sql);echo "<br />";
//var_dump($dbh);echo "<br />";

if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
//echo "user_table";var_dump($sql);echo "<br />";
//var_dump($dbh->errorInfo());echo "<br />";

        $sql = "SELECT * FROM `{$auth_table}`;";
//var_dump($sql);echo "<br />";
        $exists = 0;
        $new_carpool = true;
        foreach ($dbh->query($sql) as $row)
        {
            $new_carpool = false;
            if($row["name_hash"] == hash('sha512', $_POST['adm']))
                ++$exists;
        }
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
//var_dump($dbh);echo "<br />";
        if($exists == 0)
        {    
            $this_right = intval($_POST["rights"]);
//echo "rights: {$this_right} ({$_POST["rights"]})<br />";
            if($new_carpool && $this_right < 2)
                $this_right = 2;
            $sql = "INSERT INTO `{$auth_table}` (`name_hash`, `password_hash`, `rights`)
                    VALUES('"
                .hash('sha512', $_POST['adm']) ."', '" 
                .hash('sha512', $_POST['pwd']) ."', "
                .$this_right .")";
            $dbh->exec($sql);
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
//var_dump($sql);echo "<br />";
//var_dump($dbh);echo "<br />";
            
            $sql = "INSERT INTO `{$user_table}` (`name`, `role`, `nopwd`, `lang`) VALUES ('{$_POST['adm']}', '{$_POST['rights']}'";
            if(!$_POST['pwd']) $sql .= ", '1'";
            else $sql .= ", '0'";
            $sql .=", '{$language}');";
            $dbh->exec($sql);    
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
//var_dump($sql);echo "<br />";
//var_dump($dbh);echo "<br />";
        } else {
            $sql = "UPDATE `{$auth_table}` SET `rights` = '" .$_POST['rights'] ."'";
            if($_POST['pwd']) $sql .= ", `password_hash` = '" .hash('sha512', $_POST['pwd']) ."'";
            $sql .= "WHERE `name_hash` = '" .hash('sha512', $_POST['adm']) ."';";
        }
//var_dump($sql);
//echo "<script>alert(".$sql.");</script>";
        $dbh->exec($sql);
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
        header("Location: {$_SERVER["PHP_SELF"]}");
        break;

    case "logout":
        unset($_SESSION["username"]);
        unset($_SESSION["loggedIn"]);
        unset($_SESSION["rights"]);
        unset($_SESSION["db"]);
        unset($_SESSION["lang"]);
        session_destroy();
    default:
//var_dump($with_administration);
        if($with_administration === true)
        {
//            $auth = false;
            $sql = "SELECT * FROM `{$auth_table}` ORDER BY `name_hash`;";
            if (!isset($_POST['MY_AUTH_USER']) && !isset($_SESSION["loggedIn"]))
            {
                echo "<form id=login method=post action={$_SERVER["PHP_SELF"]}>
                <input type=hidden name=act value=default>
                <input type=hidden name=db value=\"{$database}\">
                <table id=loginTable>
                <tr><th onClick=\"Toggle(this);\"><nobr>- ".Translate("Login for administration", $language)." ({$ride_sharing})</nobr></th></tr>
                <tr><td><table style=\"display:block;\">
                <tr>
                  <td>".Translate("User name", $language).":</td>
                  <td><input type=text name=MY_AUTH_USER size=40></td>
                </tr>
                <tr>
                  <td>".Translate("Password", $language).":</td>
                  <td><input type=password name=MY_AUTH_PW size=40></td>
                </tr>
                <tr>
                  <td>Administration</td>
                  <td><input type=submit value=\" Login \"> <input type=reset value=\" Reset \"></td>
                </tr>
                </table></td></tr>
                </table>
              </form>";
            } else {
                $auth_user = $_POST["MY_AUTH_USER"];
                $auth_pwd = $_POST["MY_AUTH_PW"];
                $hash_user = hash('sha512', $auth_user);
                $hash_pwd = hash('sha512', $auth_pwd);
//var_dump($sql);
                foreach ($dbh->query($sql) as $row)
                {
//var_dump($row);
                    if($row["name_hash"] == $hash_user
                      && $row["password_hash"] == $hash_pwd)
                    {
                        $_SESSION["username"] = $auth_user; // Creates a cookie saving the username
		                    $_SESSION["loggedIn"] = $database; // Creates a cookie saying the user is logged in
		                    $_SESSION["rights"] = $row["rights"]; // Creates a cookie saying the user is logged in
                        break;
                    }
                }
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
                if($_SESSION["loggedIn"] == $database)
                    $auth_rights = $_SESSION["rights"];
                $needs_pwd = false;
                foreach ($dbh->query("SELECT `nopwd` FROM {$user_table} WHERE name = '{$_SESSION["username"]}'") as $row)
                    if($row["nopwd"] == 1)
                        $needs_pwd = true;
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
                if($needs_pwd)
                { // Password has to be set...
                    exit("<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">
                          <html>
                            <head>
                            <meta http-equiv=\"content-type\" content=\"text/html; charset=windows-1252\">
                            <title>".Translate("Change password", $language)."</title>
                            </head>
                            <body>
                              <form id=login method=post action={$_SERVER["PHP_SELF"]}>
                                <input type=hidden name=act value=chpwd>
                                <input type=hidden name=db value=\"{$database}\">
                                <input type=hidden name=adm value=\"{$_POST["MY_AUTH_USER"]}\">
                                <table id=loginTable>
                                <tr><th onClick=\"Toggle(this);\"><nobr>".Translate("Change password", $language)."</nobr></th></tr>
                                <tr><td><table style=\"display:block;\">
                                <tr>
                                  <td>".Translate("User name", $language).":</td>
                                  <td><b>{$_POST["MY_AUTH_USER"]}</b></td>
                                </tr>
                                <tr>
                                  <td>".Translate("Password", $language).":</td>
                                  <td><input type=password name=MY_AUTH_PW size=40></td>
                                </tr>
                                <tr>
                                  <td></td>
                                  <td><input type=submit value=\" ".Translate("Change", $language)." \"> <input type=reset value=\" ".Translate("Reset", $language)." \"></td>
                                </tr>
                                </table></td></tr>
                                </table>
                              </form>
                            </body>
                          </html>");
                } // ...Password has to be set
            }
        }

        break;
}
//var_dump($auth_rights);echo "<br />";
$admin_form = "";//{$_SESSION["username"]} ";
              if($with_administration === true)
              {
                  $admin_form = "<input type=button value=\"{$_SESSION["username"]} ".Translate("logout", $language)."\" onClick=\"window.location.href='{$_SERVER["PHP_SELF"]}?act=logout'\">";
                  if($_SESSION["rights"] >= 3)
                  {
                      if($_SESSION['Debug']) $admin_form .= "<input type=button value=\"".Translate("Back to", $language)." ".Translate("release database", $language)."\" onClick=\"window.location.href='{$_SERVER["PHP_SELF"]}?Debug=true'\">";
                      else $admin_form .= "<input type=button value=\"".Translate("Back to", $language)." ".Translate("debug database", $language)."\" onClick=\"window.location.href='{$_SERVER["PHP_SELF"]}?Debug=true'\">";
                  }
                  $sql = "SELECT * FROM {$user_table}";

                  foreach ($dbh->query($sql) as $row)
                  {
                      array_push($all_user_list, $row["name"]);
                      if(intval($row["role"]) >= 2)
                      {
                          if($row["role"] >= 3)
                              array_push($admin_list, $row["name"]." (D)");
                          else
                              array_push($admin_list, $row["name"]);
                      } else {
                          array_push($user_list, $row["name"]);
                      }
                  }
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
              }
//print_r($all_user_list);echo "<br />";              
              $admin_form .= "</font></h2>
              <form name=addadmin method=post action={$_SERVER["PHP_SELF"]}>
                <input type=hidden name=act value=admin>
                <input type=hidden name=db value=\"{$database}\">
                <table>
                <tr><th onClick=\"Toggle(this);\"><nobr>+ ".Translate("Create new user", $language)."</nobr></th></tr>
                <tr><td><table style=\"display:none;\">
                <tr>
                  <td>".Translate("New user", $language).":</td>
                  <td><input type=text name=adm size=40></td>
                  <td><b>".Translate("Administrators", $language)."</b></td>
                  <td><b>".Translate("Riders", $language)."</b></td>
                </tr>
                <tr>
                  <td>".Translate("Password", $language).":</td>
                  <td><input type=password name=pwd size=40></td>
                  <td><nobr>{$admin_list[0]}</nobr></td>
                  <td><nobr>{$user_list[0]}</nobr></td>
                </tr>
                <tr>
                  <td>".Translate("Rights", $language).":</td>
                  <td title=\"".Translate("The first user allways is administrator", $language)."\">
                      <select name=rights>
                          <option value=1>".Translate("Use", $language)."</option>
                          <option value=2 selected=\"selected\">".Translate("Administration", $language)."</option>
                          <option value=3>".Translate("Develop", $language)."</option>
                      </select>

                  </td>
                  <td><nobr>";
                  if(count($admin_list) >= 1) $admin_form .= $admin_list[1];
                  $admin_form .= "</nobr></td>
                  <td><nobr>";
                  if(count($user_list) >= 1) $admin_form .= $user_list[1];
                  $admin_form .= "</nobr></td>
                </tr>
                <tr>
                  <td></td>
                  <td><input type=submit value=\" ".Translate("Create", $language)." \"> <input type=reset value=\" ".Translate("Reset", $language)." \"></td>
                  <td><nobr>";
                  if(count($admin_list) >= 2) $admin_form .= $admin_list[2];
                  $admin_form .= "</nobr></td>
                  <td><nobr>";
                  if(count($user_list) >= 2) $admin_form .= $user_list[2];
                  $admin_form .= "</nobr></td>
                </tr>";
                $c = count($admin_list);
                if(count($user_list) > $c)
                    $c = count($user_list);
                for($i=3; $i<$c; ++$i)
                {
                    $admin_form .= "<tr>
                                        <td></td>
                                        <td></td>
                                        <td><nobr>{$admin_list[$i]}</nobr></td>
                                        <td><nobr>{$user_list[$i]}</nobr></td>
                                    </tr>";
                }
                $admin_form .= "</table></td></tr>
                </table>
              </form>";

$dir = ".";
$nav_bar = "<table class=navbar><tr><th onClick=\"Toggle(this);\"><nobr>+ ";
$nav_bar .= Translate("Carpooling", $language);
$nav_bar .= ": </nobr></th><td colspan=2> | ";
if (count($file_without_extension) > 0)
{
    for($i=0; $i<count($file_without_extension); ++$i)
        $nav_bar .= "<a href=\"{$_SERVER["PHP_SELF"]}?db={$file_without_extension[$i]}{$sqlite_extension}\"> {$file_without_extension[$i]} </a> | ";
    $nav_bar .= "</td></tr>";
    $nav_bar .= "<tr><td><form action=\"{$_SERVER["PHP_SELF"]}\" method=\"post\" style=\"display:none;\">
                  <table><tr><td>".Translate("Name of carpooling", $language)."</td>
                  <td>".Translate("Routes", $language)." [".Translate("name1 (distance);name2 (distance);etc.", $language)."]</td>
                  <td>".Translate("Default languages", $language)."</td>
                  <td>".Translate("Action", $language)."</td></tr>
                  <tr><td><input type=text value=\"".basename($database, "{$sqlite_extension}")."\" title=\"".Translate("Everybody can create a new ride sharing", $language)."\" name=db size=22></td>
                  <td><input type=text name=alternatives size=40 title=\"".Translate("name1 (distance);name2 (distance);etc.", $language)."\"></td>
                  <td><select name=\"default_language\">";
    for ($lc=0; $lc<count($languages); ++$lc)
    {
        if($languages[$lc] == $language)
            $nav_bar .= "<option selected=\"selected\">{$languages[$lc]}</option>";
        else
            $nav_bar .= "<option>{$languages[$lc]}</option>";
    }
    $nav_bar .= "</select></td>
                  <td><input type=submit value=\"".Translate("Create", $language)."\"></td></tr></table>
                </form></td></tr>";

    $nav_bar .= "<tr><th><nobr>";
    $nav_bar .= Translate("Languages", $language);
    $nav_bar .= ": ";
//    $nav_bar .= "(";
//    $nav_bar .= Translate("Current language is", $language);
//    $nav_bar .= " \"{$language}\")";
    $nav_bar .= "</nobr></th><td colspan=2> | ";
    for ($lc=0; $lc<count($languages); ++$lc)
    {
        $nav_bar .= "<a href=\"{$_SERVER["PHP_SELF"]}?db={$database}&lang={$languages[$lc]}\"> {$languages[$lc]} </a> | ";
    }

    $nav_bar .= "</td></tr>";
    $nav_bar .= "</table>";
    $nav_bar .= "<hr />\n";
}


echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">
<HTML>
<HEAD>
	<META HTTP-EQUIV=\"CONTENT-TYPE\" CONTENT=\"text/html; charset=windows-1252\">
	<TITLE>{$title}</TITLE>";
echo "<link rel=\"stylesheet\" href=\"datepicker.css\" type=\"text/css\">
      <script type=\"text/javascript\" src=\"{$datepicker}\"></script>";
echo "<script type=\"text/javascript\">
	function Toggle(node)
    {
//        alert(node.innerHTML);
//        alert(navigator.appName);
//        ToggleIE(node);

        switch(navigator.appName)
        {
            case \"Netscape\":
                ToggleFF(node);
                break;
            case \"Microsoft Internet Explorer\":
                ToggleIE(node);
                break;
            default:
                ToggleDefault(node);
                break;
        }

//        alert(node.parentNode.nextElementSibling.firstElementChild.firstElementChild.tagName);
    }
    function ToggleIE(node)
    {
//        alert(node.innerText);
//        alert(node.parentNode.nextSibling.firstChild.firstChild.tagName);
        node.noWrap = true;
        var myText = node.innerText;
        if(!myText) myText = node.textContent
        if(myText.indexOf(\"+\") == 0)
        {
            var newContent = myText.replace(/\+/g, \"-\");
            node.firstChild.innerText = newContent;
            node.parentNode.nextSibling.firstChild.firstChild.style.display = 'block';
        }
        else
        {
            var newContent = myText.replace(/\-/g, \"+\");
            node.firstChild.innerText = newContent;
            node.parentNode.nextSibling.firstChild.firstChild.style.display = 'none';
        }
    }
    function ToggleFF(node)
    {
        node.noWrap = true;
        var myText = node.innerText;
        if(!myText) myText = node.textContent
        if(node.textContent.indexOf(\"+\") == 0)
        {
            var newContent = node.textContent.replace(/\+/g, \"-\");
            node.textContent = newContent;
            node.parentNode.nextElementSibling.firstElementChild.firstElementChild.style.display = 'block';
        }
        else
        {
            var newContent = node.textContent.replace(/\-/g, \"+\");
            node.textContent = newContent;
            node.parentNode.nextElementSibling.firstElementChild.firstElementChild.style.display = 'none';
        }
    }
    function ToggleDefault(node)
    {
        node.noWrap = true;
        var myText = node.innerText;
        if(!myText)
            ToggleFF(node);
        else
            ToggleIE();
    }
  </script>
<style>
form {
	margin:0px;
	padding:0px;
}
table {
	border-style:ridge;
	border-width:1;
	border-collapse:collapse;
	font-family:sans-serif;
	font-size:12px;
}
thead th, tbody th {
	background:#CCCCCC;
	border-style:ridge;
	border-width:1;
	text-align: center;
	vertical-align:bottom;
}
tbody th {
	text-align:center;
	width:20px;
}
tbody td {
	vertical-align:bottom;
}
tbody td {
  padding: 0 5px;
	border: 1px solid #EEEEEE;
}
.navbar tbody td  {
	vertical-align:top;
}
</style>
</HEAD>
<BODY>
{$nav_bar}";
if($debug === true)
{
    echo "<a href=\"?delete=1\">Session löschen</a> <a href=\"{$_SERVER["PHP_SELF"]}\">Seite sauber neu laden</a>";
}

//var_dump($_SESSION);
if($with_administration === false || ($_SESSION["loggedIn"] == $database && $_SESSION["rights"] >= 1))
{
    $rc = 0;
    if(in_array($data_table, $db_tables))
    {
        foreach ($dbh->query("SELECT * FROM {$data_table};") as $row)
            ++$rc;
    }
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";

    if(($rc == 0 && $with_administration == false) || $_SESSION["rights"] >= 2)
        echo $admin_form;
    else if($_SESSION["username"])
        echo "<input type=button value=\"{$_SESSION["username"]} ".Translate("logout", $language)."\" onClick=\"window.location.href='{$_SERVER["PHP_SELF"]}?act=logout'\">";
    echo "<h2><table class=navbar><tr><th><nobr>".Translate("ride sharing", $language)."</nobr><br />".basename($database, "{$sqlite_extension}")."</th>
        <td><input type=\"button\" value=\"".Translate("Reload page", $language)."\" onClick=\"window.location.replace('{$_SERVER["REQUEST_URI"]}')\"></td>
        <td><input type=\"button\" value=\"".Translate("Repeat request", $language)."\" onClick=\"window.location.reload()\"></td>";
    if(($rc == 0 && $with_administration == false) || $_SESSION["rights"] >= 2)
        echo "<td><form action=\"{$_SERVER["PHP_SELF"]}\" method=post>
            <input type=\"hidden\" name=\"act\" value=\"destroy\">
            <input type=\"hidden\" name=\"db\" value=\"{$database}\">
            <input type=\"submit\" value=\"".Translate("Delete ride sharing", $language)."\">
          </form></td>";
    echo "</tr></table></h2>
    <form action=\"{$_SERVER["PHP_SELF"]}\" method=\"post\">
    <input type=\"hidden\" name=\"act\" value=\"insert\">
    <input type=\"hidden\" name=\"db\" value=\"{$database}\">
    <TABLE class=navbar>
    	<TR>
    		<TD><P>".Translate("Date", $language)."</P></TD>
        <TD><P>".Translate("Occupants", $language)."</P></TD>
        <TD><P>".Translate("Rider", $language)."</P></TD>
        <TD><P>".Translate("Distance", $language)."</P></TD>
        <TD><P>".Translate("Consumption (per 100 km)", $language)."</P></TD>
        <TD><P>".Translate("Price/liter", $language)."</P></TD>
        <TD><P>".Translate("Action", $language)."</P></TD>
    	</TR>
    	<TR>
    		<TD>";
    $day = 1;
    if(date("w") == 1)
        $day = 3;
//
//    {
//        echo "<form id=\"date\"><input type=\"text\" id=\"data\" title=\"YYYY-MM-DD\" name=\"datum\" value=\"".date("Y-m-d", mktime(0, 0, 0, date("m"),date("d")-$day,date("Y")))."\" size=10 /><button id=\"trigger\">...</button></form><script type=\"text/javascript\">
//            Calendar.setup({ inputField:\"data\", ifFormat:\"%Y-%m-%d\", button:\"trigger\", firstDay:1 });
//          </script>";
//    } else {
        echo "<input type=\"text\" id=\"data\" title=\"YYYY-MM-DD\" name=\"datum\" value=\"".date("Y-m-d", mktime(0, 0, 0, date("m"),date("d")-$day,date("Y")))."\" size=10 />
              <input type=button value=\"".Translate("select", $language)."\" onclick=\"displayDatePicker('datum', false, 'ymd', '-', '{$language}');\">";
//    }
    echo "</TD>
    		<TD><input type=\"text\" name=\"personen\" size=5 title=\"Falls leer, wird der Wert vom Voreintrag übernommen\"></TD>
    		<TD>";
    		
    		if($with_administration === true)
    		{
            echo "<select name=\"fahrer\">";
            $sql = "SELECT name FROM `{$user_table}`;";
            foreach ($dbh->query($sql) as $row)
            {
                echo "<option value=\"{$row["name"]}\">{$row["name"]}</option>";
            }
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
            echo "</select>";
        }
        else
        {
            echo "<input type=\"text\" name=\"fahrer\" size=15 title=\"Falls leer, wird der Wert vom Voreintrag übernommen\">";
        }
        echo "</TD>
    		<TD>
          <select name=\"strecke\">";
          $strecken_namen = array_keys($strecken);
          for($i=0; $i<count($strecken_namen); ++$i)
          {
              $value = $strecken["$strecken_namen[$i]"];
              $text =  Translate($strecken_namen[$i], $language)." ({$value} km)";
              if($i == 0) echo "<option value=\"{$value}\" selected=\"selected\">{$text}</option>";
              else echo "<option value=\"{$value}\">{$text}</option>";
          }
    echo "</select>
        </TD>
    		<TD><input type=\"text\" name=\"verbrauch\" size=20 title=\"Falls leer, wird der Wert vom Voreintrag übernommen\"></TD>
    		<TD><input type=\"text\" name=\"literpreis\" size=8 title=\"Falls leer, wird der Wert vom Voreintrag übernommen\"></TD>
    		<TD>
          <input type=\"submit\" name=\"insert\" value=\"+\">
    		</TD>
    	</TR>
    </TABLE></form>";
}

if($auth_rights < 0)
    exit("</BODY>
          </HTML>");
$print_sql_query = urlencode("SELECT * FROM {$data_table} ORDER BY Datum DESC");
$html_referer = $_SERVER["HTTP_REFERER"];
$pos = strpos($html_referer, "?");
if($pos !== false)
	$html_referer = substr($html_referer, 0, $pos);

echo "<h2>Statistik";
echo " (<font size=\"-2\">Liste ";
echo "<a href=\"{$html_referer}?act=print&sql={$print_sql_query}\">Druckvorschau</a>";
echo "|"; 
echo "<a href=\"{$html_referer}?act=print&format=pdf&sql={$print_sql_query}\" title=\"Portable document file\">PDF Ansicht</a>";
echo "|"; 
echo "<a href=\"{$html_referer}?act=print&format=pdf&attachment=true&sql={$print_sql_query}\" title=\"Portable document file\">PDF Datei</a>";
echo "|";
echo "<a href=\"{$html_referer}?act=print&format=csv&sql={$print_sql_query}\" title=\"Comma (,) separated values\">CSV</a>";
echo "|";
echo "<a href=\"{$html_referer}?act=print&format=ssv&sql={$print_sql_query}\" title=\"Semicolon (;) separated values\">SSV</a>";
echo "|";
echo "<a href=\"{$html_referer}?act=print&format=txt&sql={$print_sql_query}\" title=\"Plain text file\">TXT</font></a>)";

echo "</h2>";
echo "<TABLE>
      	<TR VALIGN=TOP>
      		<TD>
      			<P>".Translate("Date", $language)."</P>
      		</TD>
      		<TD>
      			<P>".Translate("Occupants", $language)."</P>
      		</TD>
      		<TD>
      			<P>".Translate("Distance", $language)."</P>
      		</TD>
      		<TD>
      			<P>".Translate("Consumption (per 100 km)", $language)."</P>
      		</TD>
      		<TD>
      			<P>".Translate("Price/liter", $language)."</P>
      		</TD>
      		<TD>
      			<P>".Translate("Price/distance", $language)."</P>
      		</TD>
      		<TD>
      			<P>".Translate("Price/occupant", $language)."</P>
      		</TD>
      		<TD>
      			<P>".Translate("Summary", $language)."/".Translate("Person", $language)."</P>
      		</TD>
      		<TD>
      			<P>".Translate("Pay to", $language)."</P>
      		</TD>
      		<TD>
      			<P>".Translate("payed", $language)."</P>
      		</TD>
      	</TR>";

		$sql = "SELECT * FROM {$data_table} ORDER BY Bezahlt ASC, Datum DESC";
    
    $summe = 0;
    $gesamt_summe = 0;
    foreach ($dbh->query($sql) as $row)
    {
//var_dump($row);
        echo "<TR VALIGN=TOP>";
        $date = new DateTime($row['Datum']);
        $day = date_format($date, 'l');
        $date_string = "{$row['Datum']} (".Translate($day, $language).")";
        echo "<TD><nobr>{$date_string}</nobr></TD>";
        echo "<TD>".$row['Insassen']."</TD>";
        
        $strecken_namen = array_keys($strecken);
        for($i=0; $i<count($strecken_namen); ++$i)
        {
            $value = $strecken["$strecken_namen[$i]"];
            if($strecken["$strecken_namen[$i]"] == $row['Strecke'])
            {
                $strecke = Translate($strecken_namen[$i], $language)." ({$value} km)";
                break;
            }
        }

        echo "<TD><nobr>".$strecke."</nobr></TD>";
        echo "<TD>".$row['Verbrauch']."</TD>";
        echo "<TD>".$row['Literpreis']."</TD>";
        
        $streckenpreis = $row['Verbrauch'] * $row['Literpreis'] * $row['Strecke'] /100;
        echo "<TD>".$streckenpreis."</TD>";
        
        $preis = $streckenpreis / $row['Insassen'];
        echo "<TD>".$preis."</TD>";
        
        if($with_administration === false)
            $bezahlt = intval($row['Bezahlt']);
        else
            $bezahlt = explode(";", $row['Bezahlt']);
        
        $personen_offen = $row['Insassen'];
        if(is_array($bezahlt))
            $personen_offen = intval($row['Insassen']) - count($bezahlt);
        else
            $personen_offen = intval($row['Insassen']) - $bezahlt;
            
        if($personen_offen == 0)
        {
            echo "<TD>€ 0,-</TD>";
        }
        else
        {
            $summe += ($preis * $personen_offen);
            echo "<TD>€ ".number_format($summe, 2, ',', '.')." <nobr>(€ ".$summe.")</nobr></TD>";
            //echo "<TD>€ ".round($summe, 2)." (€ ".$summe.")</TD>";
        }
        echo "<TD><nobr>{$row['Fahrer']}</nobr></td>";
        echo "<TD>";
        
        if(is_array($bezahlt))
        {
            $bezahlen = (count($bezahlt) + 1);
//echo count($bezahlt)." von {$row['Insassen']} bezahlt";
            if(count($bezahlt) >= $row['Insassen'])
                echo "<table><tr><td>"
                		.count($bezahlt)." von {$row['Insassen']} ".Translate("payed", $language)."
                    </td><td>
                    <form action={$_SERVER["PHP_SELF"]} method=post>
                    <input type=hidden name=act value=delete>
                    <input type=hidden name=datum value=".$row['Datum'].">
                    <input type=submit name=remove value=\"".Translate("Delete item", $language)."\">
                    </form></td></tr></table>";
            else if($with_administration === false || $_SESSION["rights"] >= 2)
            { 
                echo "<table><tr><td><form action={$_SERVER["PHP_SELF"]} method=post>
                    <input type=hidden name=act value=payed>
                    <input type=hidden name=datum value=".$row['Datum'].">";
                    if($with_administration === false)
                    {
                        echo "<input type=submit name=bezahlt value=\"{{$bezahlen} von {$row['Insassen']} ".Translate("pay", $language)."}\">";
                    } else {
                        for ($uc=0; $uc<count($all_user_list); ++$uc)
                            if($all_user_list[$uc] && !in_array($all_user_list[$uc], $bezahlt))
                                echo "<button type=\"submit\" name=\"bezahlt\" value=\"{$all_user_list[$uc]}\"><nobr>{$all_user_list[$uc]} ".Translate("pay", $language)."...</nobr></button><br />";
                    }
                echo "</form></td>
                    <td><form action={$_SERVER["PHP_SELF"]} method=post>
                    <input type=hidden name=act value=delete>
                    <input type=hidden name=datum value=".$row['Datum'].">
                    <input type=submit name=remove value=\"".Translate("Delete item", $language)."\">
                    </form></td></tr></table>";
            }
            else
                echo count($bezahlt)." von {$row['Insassen']} ".Translate("payed", $language);
        }
        else
        {
            $bezahlen = $bezahlt + 1;
            if($bezahlt >= intval($row['Insassen']))
                echo "<table><tr><td>
                		{$bezahlt} von {$row['Insassen']}
                    </td><td>
                    <form action={$_SERVER["PHP_SELF"]} method=post>
                    <input type=hidden name=act value=delete>
                    <input type=hidden name=datum value=".$row['Datum'].">
                    <input type=submit name=remove value=\"".Translate("Delete item", $language)."\">
                    </form></td></tr></table>";
            else if($with_administration === false || $_SESSION["rights"] >= 2)
            { 
                echo "<table><tr><td><form action={$_SERVER["PHP_SELF"]} method=post>
                    <input type=hidden name=act value=payed>
                    <input type=hidden name=datum value=".$row['Datum'].">
                    <input type=submit name=bezahlt value=\"{$bezahlen} von {$row['Insassen']} bezahlen\">
                    </form></td>
                    <td><form action={$_SERVER["PHP_SELF"]} method=post>
                    <input type=hidden name=act value=delete>
                    <input type=hidden name=datum value=".$row['Datum'].">
                    <input type=submit name=remove value=\"".Translate("Delete item", $language)."\">
                    </form></td></tr></table>";
            }
            else
                echo "{$row['Bezahlt']} von {$row['Insassen']}"; //$row['Bezahlt'];
        }
        echo "</TD>";
      	echo "</TR>";
    }
if($lastDbError = $dbh->errorInfo) echo "Database error: {$lastDbError}<hr />";
    echo "</TABLE>
          </BODY>
          </HTML>";
?>
