<?php header('Content-Type: text/html; Content-Encoding: "DEFLATE"; charset=utf-8');
/*
the server side of an application which may add/chg/delete/display/search into a table with some 
text fields (possible in some language - besides english, and some fields of options
  
*/
define("DBSERVER","localhost");
define("DBPASSWORD",""); 
define("DBUSER","root");
define("MYDB","garage");
define("MYTABLE","customers");
define("MYSECTABLE","autos");
define("MYLIST","autousers");
define("OVER","overstays");
define("UNDER","understays");
define("MISSES","missies");
define("BILLS","bills");

function stringToNumber($platestr)
{
    $plate = 0;
    for ($i = 0; $i < strlen($platestr); $i++)
    {
        $plate += ord($platestr[$i])*pow(10,$i);
    }
    return $plate;
}

// add a new auto record
function addAuto($customerid, $platestr)
{
    $conn = mysql_connect(constant("DBSERVER"),constant("DBUSER"),constant("DBPASSWORD"));
    $db_selected = mysql_select_db(MYDB,$conn);
    $strv = '';
    $plate = stringToNumber($platestr);
    $s1 = substr($customerid,0,1);
    $s2 = substr($customerid,1,strlen($customerid)-1);
    $customerid = strtoupper($s1).$s2;
    $customer = stringToNumber($customerid);
    /* add a record to autos table  - if the given auto number does not exist in tbale
    */
    $rq = mysql_query("SELECT * FROM ".MYSECTABLE." WHERE plate=".$plate.";"); 
    if (!$rq)
    {
        echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
        return;
    }
    $rowa = mysql_fetch_array($rq);
    if (!$rowa)
    {
        /* insert vehicle number */
        $strv = "'".$plate."','".$platestr."'";
        $sql = "INSERT INTO ".MYSECTABLE." (plate,autono) VALUES (".$strv.");";
        $err = mysql_query($sql);
        if (!$err) 
        {
            echo("Error ".__LINE__+" MySQL : ".mysql_error()." ".$sql."<br>");
            return;
        }
    }
    $rs = mysql_query("SELECT * FROM ".MYLIST." WHERE plate=0 and customer=".$customer.";");
    $rowa = mysql_fetch_array($rs);
    if ($rowa != null)
       mysql_query("UPDATE ".MYLIST." SET plate=".$plate." WHERE customer=".$customer.";");
    else
       addDummy($customer,$plate);
    echo 'auto';
}
function addDummy($customer,$plate)
{
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

// add a new customer record
function addCustomer($name, $address, $email, $id_type, $id_no, $credit_no, $phone)
{
    $needtocreate = false;
    $conn = mysql_connect(constant("DBSERVER"),constant("DBUSER"),constant("DBPASSWORD"));
    $db_selected = mysql_select_db(MYDB,$conn);
    if (!$db_selected)
    {
       mysql_query("CREATE DATABASE IF NOT EXISTS ".MYDB.";");
       $db_selected = mysql_select_db(MYDB,$conn);
       $needtocreate = true;
    }
    
    if (!$db_selected)
    {
        echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
        return;		
    }
    if ($needtocreate)
    {
        $sql = "CREATE TABLE IF NOT EXISTS ".MYTABLE." (
            id BIGINT NOT NULL PRIMARY KEY,
            name TINYTEXT NOT NULL,
            address TINYTEXT NOT NULL,
            email TINYTEXT NOT NULL,
            idtype CHAR NOT NULL,
            idno TINYTEXT NOT NULL,
            credit TINYTEXT NOT NULL,
            telephone TINYTEXT NOT NULL
        )engine=myisam ;";
        if (!mysql_query($sql))
        {
            echo("Error ".__LINE__+" MySQL : ".mysql_error()." ".MYTABLE."<br>");
            return;		
        }
        $sql = "CREATE TABLE IF NOT EXISTS ".MYSECTABLE." (
            plate BIGINT PRIMARY KEY,
            autono TINYTEXT
        )engine=myisam ;";
        if (!mysql_query($sql))
        {
            echo("Error ".__LINE__+" MySQL : ".mysql_error()." ".MYSECTABLE."<br>");
            return;		
        }
        $sql = "CREATE TABLE IF NOT EXISTS ".MYLIST." (
            id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            plate BIGINT NOT NULL, 
            customer BIGINT NOT NULL,
            contract TINYTEXT NOT NULL,
            include_holyday INT NOT NULL DEFAULT 0,
            period TINYTEXT NOT NULL,
            starthour INT NOT NULL DEFAULT 0,
            endhour INT NOT NULL DEFAULT 24,
            start TINYTEXT NOT NULL,
            FOREIGN KEY (customer) REFERENCES ".MYTABLE."(id)
        )engine=myisam ;";
        if (!mysql_query($sql))
        {
            echo("Error ".__LINE__+" MySQL : ".mysql_error()." ".MYLIST."<br>");
            return;		
        }
        $sql = "CREATE TABLE IF NOT EXISTS ".OVER." (
            id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            contract BIGINT NOT NULL  NOT NULL,
            counter INT NOT NULL DEFAULT 0,
            FOREIGN KEY (contract) REFERENCES ".MYLIST."(id)
        )engine=myisam ;";
        if (!mysql_query($sql))
        {
            echo("Error ".__LINE__+" MySQL : ".mysql_error()." ".OVER."<br>");
            return;		
        }
        $sql = "CREATE TABLE IF NOT EXISTS ".UNDER." (
            id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            contract BIGINT NOT NULL  NOT NULL,
            counter INT NOT NULL DEFAULT 0,
            FOREIGN KEY (contract) REFERENCES ".MYLIST."(id)
        )engine=myisam ;";
        if (!mysql_query($sql))
        {
            echo("Error ".__LINE__+" MySQL : ".mysql_error()." ".UNDER."<br>");
            return;		
        }
        $sql = "CREATE TABLE IF NOT EXISTS ".MISSES." (
            id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            contract BIGINT NOT NULL  NOT NULL,
            counter INT NOT NULL DEFAULT 0,
            FOREIGN KEY (contract) REFERENCES ".MYLIST."(id)
        )engine=myisam ;";
        if (!mysql_query($sql))
        {
            echo("Error ".__LINE__+" MySQL : ".mysql_error()." ".MISSES."<br>");
            return;		
        }
        $sql = "CREATE TABLE IF NOT EXISTS ".BILLS." (
            id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            customer BIGINT NOT NULL,
            auto BIGINT NOT NULL,
            contract BIGINT NOT NULL,
            date BIGINT NOT NULL,
            hour INT NOT NULL DEFAULT 0,
            FOREIGN KEY (customer) REFERENCES ".MYTABLE."(id)
        )engine=myisam ;";
        if (!mysql_query($sql))
        {
            echo("Error ".__LINE__+" MySQL : ".mysql_error()." ".MISSES."<br>");
            return;		
        }
    }
    $customer = stringToNumber($id_type.$id_no);
    /* add customer */
    $strn = 'id,name,address,email,idtype,idno,credit,telephone';
    $strv = "'".$customer."','".$name."','".$address."','".$email."','"
        .$id_type."','".$id_no."','".$credit_no."','".$phone."'";
    $sql = "INSERT INTO ".MYTABLE." (".$strn.") VALUES (".$strv.");";
    $err = mysql_query($sql);
    if (!$err) 
    {
        echo("Error ".__LINE__+" MySQL : ".mysql_error()." ".$sql."<br>");
        return;
    }
    
    addDummy($customer,0);
    // return as response the mySQL index of the latest inserted record
    echo 'customer';
    return $id_type.$id_no;
}

function addContract($customerid,$autono,$contract,$holyday,$period,$start,$starthour,$endhour)
{
    $conn = mysql_connect(constant("DBSERVER"),constant("DBUSER"),constant("DBPASSWORD"));
    $db_selected = mysql_select_db(MYDB,$conn);
    if (($autono != null) && ($autono != "undefined"))
        $plate = stringToNumber($autono);
    else
        $plate = 0;
    $s1 = substr($customerid,0,1);
    $s2 = substr($customerid,1,strlen($customerid)-1);
    $customerid = strtoupper($s1).$s2;
    $customer = stringToNumber($customerid);
    $strn = '';
    $contract = strtolower($contract);
    if (($contract == "weekly")  || ($contract == "monthly") ||
        ($contract == "daylist") || ($contract == "daily")  || ($contract == "none"))
    {
        $strn .= 'contract = "'.$contract.'"';
    }
    else
    {
        $strn .= 'contract = "none"';
    }
    if ($holyday == 'yes') 
        $strn .= ',include_holyday=1';
    else
        $strn .= ',include_holyday=0';
    $strn .= ',period="'.$period.'"'; 
    if ($start == 'today')
    {
        $start=date("j-m-Y");
    }
    else if ($start != 'future')
    {
        $my = explode('-', $start);
        if (($my[2] > 0) && ($my[1]>=1) && ($my[1]<=12) && ($my[0]>=1) && ($my[0]<=31))
        {
            $strn .= ',start="'.$start.'"';
        }
        else
        {
            $strn .= ',start="1-1-1999"';
            echo("Error : start attribute has an incorrect value - it is not set ".$start."<br>");
        }
    }
    else
    {
        $strn .= ',start="'.$start.'"';
    }
    $sh = intval($starthour);
    if ($sh > 24)
        echo("Error : start hour has an incorrect value - it is not set ".$starthour."<br>");
    else
        $strn .= ',starthour='.$starthour;
    $sh = intval($endhour);
    if ($sh > 24)
        echo("Error : start hour has an incorrect value - it is not set ".$endhour."<br>");
    else
        $strn .= ',endhour='.$endhour;
    if (!mysql_query("UPDATE ".MYLIST." SET ".$strn." WHERE customer=".$customer." and plate=".$plate.";"))
    {
        echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>".$strn."<br>");
        return;
    }    

    echo 'contract';
}
if (isset($_REQUEST['action']))
    switch($_REQUEST['action'])
    {
    case 'addCustomer':
        addCustomer($_REQUEST['name'],$_REQUEST['addr'],$_REQUEST['email'],
                    $_REQUEST['ident'],$_REQUEST['identno'],
                    $_REQUEST['creditno'],$_REQUEST['phone']);
        break;
    case 'addAuto':
        addAuto($_REQUEST['selection'],$_REQUEST['field']);
        break;
    case 'addContract':
        $auto = null;
        if (isset($_REQUEST['autono']))
            $auto = $_REQUEST['autono'];
        addContract($_REQUEST['customer'],$auto,$_REQUEST['contract'],
                    $_REQUEST['holiday'],$_REQUEST['period'],
                    $_REQUEST['start'],$_REQUEST['starthour'],$_REQUEST['endhour']);
        break;
    }
?>
