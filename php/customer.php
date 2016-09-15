<?php header('Content-Type: text/html; application/json; Content-Encoding: "DEFLATE"; charset=utf-8');
/*
the server side of an application which may add/chg/delete/display/search into a table with some 
text fields (possible in some language - besides english, and some fields of options
  
*/

require_once "./common.php";

function doJson($row,$plate,$full)
{
    if ($full)
        return array(
                 'name'=>$row['name'],
                 'address'=>$row['address'],
                 'email'=>$row['email'],
                 'identity'=>$row['idtype'],
                 'identno'=>$row['idno'],
                 'credit'=>$row['credit'],
                 'telephone'=>$row['telephone'],
                 'autos'=>$plate);
    else
        return array(
                 'identity'=>$row['idtype'],
                 'identno'=>$row['idno'],
                 'autos'=>$plate);
}

function fillContract($rowa,$row)
{
    if ($rowa['autono'] == null)
        $arraytoJson = array('autono'=>'undefined');
    else
        $arraytoJson = array('autono'=>$rowa['autono']);
    $arraytoJson['contract']=$row['contract'];
    if ($row['include_holyday'] == 1)
        $arraytoJson['holiday']='yes';
    else
        $arraytoJson['holiday']='no';
    $arraytoJson['start']=$row['start'];
    if ($row['period'][strlen($row['period'])-1] == ',')
        $row['period'] = substr($row['period'],0,strlen($row['period'])-1);
    $arraytoJson['period']=$row['period'];
    $arraytoJson['starthour'] = $row['starthour'];
    $arraytoJson['endhour'] = $row['endhour'];
    
    return $arraytoJson;
}
// retrieve all the customers and send them as a json string to the user
function listCustomer($sel1,$sel2,$full)
{
    $conn = mysql_connect(constant("DBSERVER"),constant("DBUSER"),constant("DBPASSWORD"));
    $db_selected = mysql_select_db(MYDB,$conn);
    if (!$db_selected)
    {
        echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
        return;		
    } 
    $J = array();
    if ($sel1 == 'all') /* list all */
    {
        /* take all customers */
        if ($full)
            $rsc=mysql_query("SELECT * FROM ".MYTABLE.";");
        else
            $rsc=mysql_query("SELECT id,idtype,idno FROM ".MYTABLE.";");
        if (!$rsc) 
        {
            echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
            return $J;
        }
        while ($rowc=mysql_fetch_array($rsc))
        {
            /* for customer , take all its pairs */
            $rs=mysql_query("SELECT * FROM ".MYLIST." WHERE customer=".$rowc['id'].";");
            if (!$rs) 
            {
                echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
                return $J;
            }
            $autos = array();
            while ($row=mysql_fetch_array($rs))
            {
                /* for each pair - read the info from auto */
                $rsa=mysql_query("SELECT * FROM ".MYSECTABLE." WHERE plate=".$row['plate'].";");
                if (!$rsa) 
                {
                    echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
                    return $J;
                }
                $rowa = mysql_fetch_array($rsa);
                if ($sel2 == "c+a")
                    $arraytoJson = array('autono'=>$rowa['autono']);
                else    
                    $arraytoJson = fillContract($rowa,$row);
                array_push($autos,$arraytoJson);
            }
            array_push($J,doJson($rowc,$autos,$full));
        }
    }
    else if ($sel1 != 'auto') /* list all selected from customer table */
    {
        if ($sel1 == "identno")
        {
            $ident = substr($sel2,0,1);
            $idno = substr($sel2,1,strlen($sel2));
            /* query customers table according to criteria */
            $rsc=mysql_query("SELECT * FROM ".MYTABLE." WHERE idtype='".$ident."' and idno='".$idno."';");
        }
        else
        {
            /* query customers table according to criteria */
            $rsc=mysql_query("SELECT * FROM ".MYTABLE." WHERE ".$sel1."='".$sel2."';");
        }
        if (!$rsc) 
        {
            echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
            return $J;
        }
        while ($rowc=mysql_fetch_array($rsc))
        {
            /* for every customer fitting the criteria, take the pair customer - auto */
            /* for customer , take all its pairs */
            $rs=mysql_query("SELECT * FROM ".MYLIST." WHERE customer=".$rowc['id'].";");
            if (!$rs) 
            {
                echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
                return $J;
            }
            $autos = array();
            while ($row=mysql_fetch_array($rs))
            {
                /* for each pair - read the info from auto */
                $rsa=mysql_query("SELECT * FROM ".MYSECTABLE." WHERE plate=".$row['plate'].";");
                if (!$rsa) 
                {
                    echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
                    return $J;
                }
                $rowa = mysql_fetch_array($rsa);
                $arraytoJson = fillContract($rowa,$row);
                array_push($autos,$arraytoJson);
            }
            array_push($J,doJson($rowc,$autos,true));
        }
    }
    else /* list all selected from autos table */
    {
        /* translate auto number to plate number */
        $plate = stringToNumber($sel2);
        /* query autos table */
        $sql="SELECT * FROM ".MYSECTABLE." WHERE plate=".$plate.";"; 
        $rs=mysql_query($sql);
        $rowa=mysql_fetch_array($rs);
        if ($rowa)
        {
            /* bring all pairs customer, plate corresponding to given plate */
            $rs=mysql_query("SELECT * FROM ".MYLIST." WHERE plate=".$plate.";");
            if (!$rs) 
            {
                echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
                return $J;
            }
            
            while($row = mysql_fetch_array($rs))
            {
                $autos = array();
                $sql="SELECT * FROM ".MYTABLE." WHERE id=".$row['customer'].";"; 
                $rsc=mysql_query($sql);
                if (!$rsc) 
                {
                    echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
                    return;		
                }
                /* select all customers haveing the required auto */
                $rowc=mysql_fetch_array($rsc);
                $arraytoJson = fillContract($rowa,$row);
                array_push($autos,$arraytoJson);
                array_push($J,doJson($rowc,$autos,true));
            }
        }
    }
    // send as response the JSON string of the array of records
    echo json_encode($J);
}
// delete the record with the given id
function delCustomer($idstr)
{
    $conn = mysql_connect(constant("DBSERVER"),constant("DBUSER"),constant("DBPASSWORD"));
    $db_selected = mysql_select_db(MYDB,$conn);
    if (!$db_selected)
    {
        echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
        return;		
    }
    $id = stringToNumber($idstr);
    /* delete customer */
    mysql_query("DELETE FROM ".MYTABLE." WHERE id=".$id.";");
    mysql_query("DELETE FROM ".BILLS." WHERE customer=".$id.";");
    $rs=mysql_query("SELECT * FROM ".MYLIST." WHERE customer=".$id.";");
    /* for all autos of the deleted customer */
    $autos = array();
    $ids = array();
    while ($row=mysql_fetch_array($rs))
    {
        array_push($autos,$row['plate']);
        array_push($ids,$row['id']);
    }
    /* delete from MYLIST all customer's pairs */
    $deleted = mysql_query("DELETE FROM ".MYLIST." WHERE customer=".$id.";");
    for ($i=0; $i < count(autos); $i++)
    {
        $a = $autos[$i];
        if (!mysql_query("DELETE FROM ".OVER." WHERE contract=".$ids[$i].";"))
        {
            echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
            return;
        }
        if (!mysql_query("DELETE FROM ".UNDER." WHERE contract=".$ids[$i].";"))
        {
            echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
            return;
        }
        if (!mysql_query("DELETE FROM ".MISSES." WHERE contract=".$ids[$i].";"))
        {
            echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
            return;
        }
        $rs=mysql_query("SELECT * FROM ".MYLIST." WHERE plate=".$a.";");
        if (mysql_fetch_array($rs) == null) /* empty - then delete auto also */
            if (!mysql_query("DELETE FROM ".MYSECTABLE." WHERE plate=".$a.";"))
                echo("Error ".__LINE__+" MySQL : ".mysql_error()." ".$a."<br>");
    }
    echo $id;
}
// change the data of the required customer
function dochgCustomer($idstr,$name,$addr,$email,$phone, $credit)
{
    $conn = mysql_connect(constant("DBSERVER"),constant("DBUSER"),constant("DBPASSWORD"));
    $db_selected = mysql_select_db(MYDB,$conn);
    if (!$db_selected)
    {
        echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
       return;		
    } 
    $id = stringToNumber($idstr);
    $str = '';
    if ($name != null)
        $str .= "',name='".$name."'";
    if ($addr != null)
        $str .= "',address='".$addr."'";
    if ($email != null)
        $str .= "',email='".$email."'";
    if ($phone != null)
        $str .= "',phone='".$phone."'";
    if ($credit != null)
        $str .= "',credit='".$credit."'";
    $str = substr($str,2,strlen($str)-2); // remove the first ', chars
    if (!mysql_query("UPDATE ".MYTABLE." SET ".$str." WHERE id=$id;"))
    {
        echo("Error ".__LINE__+" MySQL : ".mysql_error()."<br>");
        return;
    }
    echo 'customer';
}
// just retrieve and send to user the info of the required customer
function chgCustomer($id)
{
    listCustomer("identno",$id,true);
}
/* main entry of the server program
*/
if (isset($_REQUEST['action']))
    switch($_REQUEST['action'])
    {
    case 'listCustomer':
        if (isset($_REQUEST['full']))
        {
            if ($_REQUEST['full'] == 'yes')
                listCustomer($_REQUEST['selection'],$_REQUEST['field'],true);
            else
                listCustomer($_REQUEST['selection'],$_REQUEST['field'],false);
        }
        else
            listCustomer($_REQUEST['selection'],$_REQUEST['field'],false);
        break;
    case 'delCustomer':
        delCustomer($_REQUEST['rowid']);
        break;
    case 'chgCustomer':
        /* retrieve the required customer and send the info to browser */
        chgCustomer($_REQUEST['rowid']);
        break;
    case 'doCustomer':
        /* actually performs the chane with the new data */
        $name = null;
        $addr = null;
        $email = null;
        $phone = null;
        $credit = null;
        if (isset($_REQUEST['name'])) $name = $_REQUEST['name'];
        if (isset($_REQUEST['addr'])) $addr = $_REQUEST['addr'];
        if (isset($_REQUEST['email'])) $email = $_REQUEST['email'];
        if (isset($_REQUEST['phone'])) $phone = $_REQUEST['phone'];
        if (isset($_REQUEST['creditno'])) $credit = $_REQUEST['creditno'];
        dochgCustomer($_REQUEST['rowid'],$name,$addr,$email,$phone,$credit);
        break;
    case 'load':
    case 'holiday':
    case 'save':
        break;
    default:
        echo("Error Wrong action(customer.php) : ".$_REQUEST['action']."<br>");
        break;
    }
?>
