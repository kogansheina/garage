Enter file contents here<?php header('Content-Type: text/html; application/json; Content-Encoding: "DEFLATE"; charset=utf-8');
/*
the server side of an application which may add/chg/delete/display/search into a table with some 
text fields (possible in some language - besides english, and some fields of options
  
*/
require_once "./common.php";

function doContract($customerid,$autonoid,$contract,$holyday,$period,$start,$starthour,$endhour)
{    
    $customerid = strtoupper(substr($customerid,0,1)).substr($customerid,1,strlen($customerid)-1);
    $customer = stringToNumber($customerid);    
    $autono = stringToNumber($autonoid);
    $conn = mysql_connect(constant("DBSERVER"),constant("DBUSER"),constant("DBPASSWORD"));
    $db_selected = mysql_select_db(MYDB,$conn);
    if (!$db_selected)
    {
        echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
        return;		
    }
    $strn = '';
    if ($contract != null)
    {
        $strn .= 'contract="'.$contract.'"';
    }
    if ($holyday != null)
    {
        $h = 0;
        if ($holyday == 'yes')
            $h = 1;
        $strn .= ',include_holyday="'.$h.'"';
    }
    if ($period != null)
    {
        $strn .= ',period="'.$period.'"';
    }
    if ($start != null)
        $strn .= ',start="'.$start.'"';
    if ($starthour != null)
        $strn .= ',starthour="'.$starthour.'"';
    if ($endhour != null)
        $strn .= ',endhour="'.$endhour.'"';
    if (substr($strn,0,1) == ',')
        $strn = substr($strn,1,strlen($strn)-1);
    $rs = mysql_query("SELECT * FROM ".MYLIST." WHERE customer=".$customer." and plate=".$autono.";");
    if (mysql_fetch_array($rs) == null)
    {
        echo "contract not yet updated <br>";
    }
    /* update the list */
    if (!mysql_query("UPDATE ".MYLIST." SET ".$strn." WHERE customer=".$customer." and plate=".$autono.";"))
    {
        echo("Error ".__LINE__+" MySQL : ".mysql_error()." ".$strn."<br>");
        return;
    }
    echo 'contract';
}
// just retrieve and send to user the info of the required customer
/* main entry of the server program
*/
if (isset($_REQUEST['action']))
    switch($_REQUEST['action'])
    {
    case 'doContract':
        $contract = null;
        $holiday = null;
        $period = null;
        $start = null;
        $starthour = null;
        $endhour = null;
        if (isset($_REQUEST['contract'])) $contract = $_REQUEST['contract'];
        if (isset($_REQUEST['holiday'])) $holiday = $_REQUEST['holiday'];
        if (isset($_REQUEST['period'])) $period = $_REQUEST['period'];
        if (isset($_REQUEST['start'])) $start = $_REQUEST['start'];
        if (isset($_REQUEST['starthour'])) $starthour = $_REQUEST['starthour'];
        if (isset($_REQUEST['endhour'])) $endhour = $_REQUEST['endhour'];
        /* actually performs the chane with the new data */
        doContract($_REQUEST['customer'],$_REQUEST['autono'],$contract,
                    $holiday,$period,$start,$starthour,$endhour);
        break;
    default:
        echo("Error Wrong action(contract.php) : ".$_REQUEST['action']."<br>");
        break;
    }
?>
