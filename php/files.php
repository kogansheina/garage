<?php header('Content-Type: text/html; application/json; Content-Encoding: "DEFLATE"; charset=utf-8');

require_once "./common.php";

function printdir($path,$action)
{
    $dir_handle = opendir($path);
    if (!$dir_handle) die("Unable to open ".$path);
    while ($file = readdir($dir_handle))
    {
        $newfile = $path."/".$file; 
        if ( $file != "." )
        {
            if (is_dir($newfile))
            {
                $newfile .= "/";
                if ($file == "..")
                    echo "<a style = 'color:green' href=files.php?from=files&path=".$newfile.
                        "&action=".$action.">".$file."</a><br/>";
                else
                    echo "<a style = 'color:green' href=files.php?from=files&path=".$newfile.
                    "&action=".$action.">[+] ".$file."</a><br/>";
            }
            else if (!is_link($newfile))
            {
                if (strrpos($newfile,'.xml') != false) 
                {
                    echo "<a style = 'color:red' href=files.php?from=files&path=".$newfile.
                        "&action=".$action.">".$file."</a><br/>";
                }
            }
        }
    }
    closedir($dir_handle);
}

function Parse($file)
{
	$data = implode("", file($file));
	$xml_parser = xml_parser_create();
	if (xml_parse_into_struct($xml_parser, $data, $values, $tags)==0)
    {
        echo "There are errors into XML file !!!<br>";
        $errmsg = sprintf("XML parse error %d '%s' at line %d, column %d (byte index %d)",
                    xml_get_error_code($xml_parser),
                    xml_error_string(xml_get_error_code($xml_parser)),
                    xml_get_current_line_number($xml_parser),
                    xml_get_current_column_number($xml_parser),
                    xml_get_current_byte_index($xml_parser));
        echo $errmsg;
        xml_parser_free($xml_parser);

        return;
    }
	xml_parser_free($xml_parser);
    $customrecord = 0;
    for ($i=1; $i<count($values)-1;$i++) 
    {
        $a=$values[$i];
        //print_r($a); echo "<br>";
        switch ($a['tag']) 
        {
        case "CUSTOMER":
            if (($a['type'] == 'open') || ($a['type'] == 'complete'))
            {
                $b=$a['attributes'];
                $address = 'undefined';
                $email = 'undefined';
                $name = 'undefined';
                if (isset($b['NAME'])) $address = $b['NAME']; 
                if (isset($b['ADDRESS'])) $address = $b['ADDRESS']; 
                if (isset($b['EMAIL'])) $email = $b['EMAIL']; 
                $customrecord = addCustomer($name, $address, $email, 
                            $b['IDENT'], $b['IDNUMBER'], $b['CREDITNUMBER'],$b['TELEPHONE']); 
            }
            break;
        case "AUTO":
            if (($a['type'] == 'open') || ($a['type'] == 'complete'))
            {
                $b=$a['attributes'];
                addAuto($customrecord, $b['AUTONO']);
            }
            break;
        case "CONTRACT":
            if (($a['type'] == 'open') || ($a['type'] == 'complete'))
            {
                $b=$a['attributes'];
				$holyday = 'no';
				$period = '0';
				$start = 'today';
				$starthour = 0;
				$endhour = 24;
                $auto = 0;
				if (isset($b['AUTO'])) $auto = $b['AUTO'];
				if (isset($b['HOLIDAY'])) $holyday = $b['HOLIDAY'];
				if (isset($b['PERIOD'])) $period = $b['PERIOD'];
				if (isset($b['START'])) $start = $b['START'];
				if (isset($b['START_HOUR'])) $starthour = $b['START_HOUR'];
				if (isset($b['END_HOUR'])) $endhour = $b['END_HOUR'];
                addContract($b['CUSTOMER'], $auto, $b['TYPE'], $holyday,$period,$start,$starthour,$endhour);
            }
            break;
        } // switch
    } // for
} // parse

if (isset($_REQUEST['action']))
{
    if ($_REQUEST['action'] == 'load')
    {
        if (isset($_REQUEST['from'])) 
        {
              if (isset($_REQUEST['path']) && (strlen($_REQUEST['path']) > 0)) 
              {
                  $pp = $_REQUEST['path'];
                  if (!is_file($pp))
                  {
                      if (is_dir($pp))            	
                          printdir($pp,$_REQUEST['action']);           		      
                      else 
                          echo "Wrong file name : ".$pp."<br>";
                  }
                  else
                  {
                      Parse($pp);
                  }
              } // ifset path
        } // ifset from    
        else
        {
            printdir(".",$_REQUEST['action']);
        } // else
    }    
    else if ($_REQUEST['action'] == 'save')
    {
        $cur_time=date("Y-m-d-H-i");
        $f = "../data_save-".$cur_time.".xml";
        /* remove the old file */
        if (file_exists($f))
        {
            chmod($f,0666); 
            unlink($f);
        }
        $file = fopen($f, "w+");
        if ($file) 
        {
            fwrite($file,'<?xml version="1.0" encoding="utf-8"?>');
            fwrite($file,"\n<data>\n");
            $conn = mysql_connect(constant("DBSERVER"),constant("DBUSER"),constant("DBPASSWORD"));
            $db_selected = mysql_select_db(MYDB,$conn);
            if (!$db_selected)
            {
               echo(__LINE__+'Error open ' .MYDB.' => '.mysql_error());
               return;		
            } 
            /* for all customers */
            $rs=mysql_query("SELECT * FROM ".MYTABLE.";");
            while ($row=mysql_fetch_array($rs))
            {
                $line = 'ident="'.$row['idtype'].'" idnumber="'.$row['idno'].'" creditnumber="'.$row['credit'].
                    '" telephone="'.$row['telephone'].'"';
                if ($row['name'] != 'undefined')
                    $line .= ' name="'.$row['name'].'"';
                if ($row['address'] != 'undefined')
                    $line .= ' address="'.$row['address'].'"';
                if ($row['email'] != 'undefined')
                    $line .= ' email="'.$row['email'].'"';
                fwrite($file,"<CUSTOMER ".$line."></CUSTOMER>\n");
                $rsl=mysql_query("SELECT * FROM ".MYLIST." WHERE customer=".$row['id'].";");
                /* select all pairs for the given customer */
                while ($rowl=mysql_fetch_array($rsl))
                {
                    /* add an auto for the customer */
                    $sql="SELECT * FROM ".MYSECTABLE." WHERE plate=".$rowl['plate'].";"; 
                    $rsa=mysql_query($sql);
                    $rowa=mysql_fetch_array($rsa);
                    $line = 'autono="'.$rowa['autono'].'"'; 
                    fwrite($file,"<AUTO ".$line."></AUTO>\n");
                    /* for all pairs - look for contract, etc */
                    $rssec = mysql_query("SELECT * FROM ".MYLIST." WHERE customer=".$row['id']." and plate=".$rowl['plate'].";");
                    while ($rowsec=mysql_fetch_array($rssec))
                    {
                        //print_r($rowsec); echo "<br>";
                        $line = 'customer="'.$row['idtype'].$row['idno'].'" auto="'.$rowa['autono'].'"';
                        $line_next = '';
                        if ($rowsec['include_holyday'] == 1)
                            $line_next .= ' holiday="yes"';
                        else
                            $line_next .= ' holiday="no"';
                        $line_next .= ' type="'.$rowsec['contract'].'"';
                        if ($rowsec['period'][strlen($rowsec['period'])-1] == ',')
                            $rowsec['period'] = substr($rowsec['period'],0,strlen($rowsec['period'])-1);
                        $line_next .= ' period="'.$rowsec['period'].'"';
                       $line_next .= ' start="'.$rowsec['start'].'"';
                       $line_next .= ' start_hour="'.$rowsec['starthour'].'"';
                       $line_next .= ' end_hour="'.$rowsec['endhour'].'"';
                       if (strlen($line_next))
                           fwrite($file,"<CONTRACT ".$line.$line_next."></CONTRACT>\n");
                    }
                }
            }
            fwrite($file,"</data>\n");
            fclose($file);
            echo "XML generated file : ".$f."<br>";
        }
    }
}
?>

