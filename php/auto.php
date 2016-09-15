<?php header('Content-Type: text/html; Content-Encoding: "DEFLATE"; charset=utf-8');

require_once "./common.php";

function chgAuto($customerstr, $platestr, $old)
{
    $conn = mysql_connect(constant("DBSERVER"),constant("DBUSER"),constant("DBPASSWORD"));
    $db_selected = mysql_select_db(MYDB,$conn);
    $oldplate = 0;   
    $customer = stringToNumber($customerstr);
    if ($old != null)
        if ($old != "undefined")
            $oldplate = stringToNumber($old);  
    $plate = 0;
    if (strlen($platestr)) 
    {
        $plate = stringToNumber($platestr); 
    }
    else
    { /* auto is delete from the customer 
         remove the contract */
        if ($oldplate)
        {
            $rs = mysql_query("SELECT id FROM ".MYLIST." WHERE plate=".$oldplate." and customer=".$customer.";");
            if (!$rs)
            {
                echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
                return;
            }
            $rowlist = mysql_fetch_array($rs);
            if (!mysql_query("DELETE FROM ".MYLIST." WHERE plate=".$oldplate." and customer=".$customer.";"))
            {
                echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
                return;
            }
            if (!mysql_query("DELETE FROM ".OVER." WHERE contract=".$rowlist['id'].";"))
            {
                echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
                return;
            }
            if (!mysql_query("DELETE FROM ".UNDER." WHERE contract=".$rowlist['id'].";"))
            {
                echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
                return;
            }
            if (!mysql_query("DELETE FROM ".MISSES." WHERE contract=".$rowlist['id'].";"))
            {
                echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
                return;
            }
        }
        else
            return;
    }
    if ($oldplate != 0)
    {
        /* check if there other customers for this auto */
        $rs = mysql_query("SELECT customer FROM ".MYLIST." WHERE plate=".$oldplate.";");
        if (!$rs)
        {
            echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
            return;
        }
        if (mysql_fetch_array($rs) == null) /* no others, go to delete the auto itself */
        {
            if (!mysql_query("DELETE FROM ".MYSECTABLE." WHERE plate=".$oldplate.";"))
            {
                echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
                return;
            }
        }
    }
    if ($plate > 0)
    {
        /* check if the auto has already a contract */
        $rs=mysql_query("SELECT * FROM ".MYLIST." WHERE plate=".$plate.";");
        if (!$rs)
        {
            echo("Error ".__LINE__+" MySQL : ".mysql_error()." ".$sql."<br>");
            return;
        }
        /* add contract */
        if (mysql_fetch_array($rs) == null) 
        {
            /* add the new plate */
            $strv = "'".$plate."','".$platestr."'";
            $sql = "INSERT INTO ".MYSECTABLE." (plate,autono) VALUES (".$strv.");";
            if (!mysql_query($sql))
            {
                echo("Error ".__LINE__+" MySQL : ".mysql_error()." ".$sql."<br>");
                return;
            }
            /* insert record for this (plate,customer) into list table */
            $strv = "'".$customer."','".$plate."','none',0,'0','future',0,24";
            $sql = "INSERT INTO ".MYLIST." (customer,plate,contract,include_holyday,period,start,starthour,endhour)
             VALUES (".$strv.");";
            $err = mysql_query($sql);
            if (!$err) 
            {
                echo("Error ".__LINE__+" MySQL : ".mysql_error()." ".$sql."<br>");
                return;
            }
            $contract = mysql_insert_id();
            $sql = "INSERT INTO ".OVER." (contract) VALUES ('".$contract."');";
            $err = mysql_query($sql);
            if (!$err) 
            {
                echo("Error ".__LINE__+" MySQL : ".mysql_error()." ".$sql."<br>");
                return;
            }
            $sql = "INSERT INTO ".UNDER." (contract) VALUES ('".$contract."');";
            $err = mysql_query($sql);
            if (!$err) 
            {
                echo("Error ".__LINE__+" MySQL : ".mysql_error()." ".$sql."<br>");
                return;
            }
            $sql = "INSERT INTO ".MISSES." (contract) VALUES ('".$contract."');";
            $err = mysql_query($sql);
            if (!$err) 
            {
                echo("Error ".__LINE__+" MySQL : ".mysql_error()." ".$sql."<br>");
                return;
            }
        }
        else
        {
            $rs=mysql_query("SELECT * FROM ".MYLIST." WHERE plate=0 and customer=".$customer.";");
            if (!$rs)
            {
                echo("Error ".__LINE__+" MySQL : ".mysql_error()." ".$sql."<br>");
                return;
            }
            /* update contract */
            while ($row = mysql_fetch_array($rs))
            {
                mysql_query("UPDATE ".MYLIST." SET plate=".$plate." WHERE id=".$row['id'].";")
            }
        }
    }
    echo 'auto';
}

if (isset($_REQUEST['action']))
    switch($_REQUEST['action'])
    {
    case 'chgAuto':
        if (isset($_REQUEST['oldfield']))
            chgAuto($_REQUEST['selection'],$_REQUEST['field'],$_REQUEST['oldfield']);
        else
            chgAuto($_REQUEST['selection'],$_REQUEST['field'],null);
        break;
    default:
        echo("Error Wrong action(auto.php) : ".$_REQUEST['action']."<br>");
        break;
    }
?>
