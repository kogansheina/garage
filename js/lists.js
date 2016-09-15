function listCustomers()
{
    var query = location.search.substring(1);
    var parameters = {};
    var keyValues = query.split(/&/);
    for (i=0; i < keyValues.length; i++) 
    {
        keyValuePairs = keyValues[i].split(/=/);
        value = keyValuePairs[1];
        parameters[i] = value;
    }
    seld = parameters[0];
    full = parameters[1];
    Draw.selval = parameters[2];
    table = Draw.tableHeader(null,"list");
    fill = 'no';
    if (full == 'true')
        fill = 'yes';
    url = "http://localhost/xampp/garage/php/customer.php?action=listCustomer&selection="+seld+"&full="+fill+"&field="+Draw.selval;
    console.log(url);
    xmlhttp = new XMLHttpRequest();
    xmlhttp.open("POST",url,true);
    xmlhttp.send(null);
    xmlhttp.onreadystatechange=function() 
    {
        if (xmlhttp.readyState==4 && xmlhttp.status==200) 
        {
           // console.log(xmlhttp.responseText);
           // check errors of the server application
           if (xmlhttp.responseText.substring(0,2) == "[{")
           {
               // the response is received encoded in JSON form
               JJ = JSON.parse(xmlhttp.responseText);
               // JJ is the object obtained from parseing the JSON string
               // for each record in the object
               l = 2;
               for (customer = 0; customer < JJ.length; customer++)
               {
                   l += Draw.tableRow(table,l,JJ[customer],'list');
               }
               url = '';
           }
           else                      
               alert(xmlhttp.responseText); 
        } // is status
    } // response function
    
    return false;
}
