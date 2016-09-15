var serverIP = "http://localhost/xampp/garage/php/";

var Customer =
{
    name : '',
    address : '',
    email : '',
    credit : '',
    phone : '',
    
    win      : null,
    chgid    : 0,
    autono   : ['undefined'],
    auto_old : [],
    auto_chg : [],
    url_contract : [],
    contract : ['none'],
    holiday  : ['no'],
    period   : [0],
    start    : ['today'],
    starthour: [0],
    endhour  : [24],
    identity_type : 'I',
    identity_no   : '',
    
    done : function(txt,callback)
    {
        callback(false);
        alert(txt);
    },
    addCustomer : function(doit)
    {
        if (!doit)
        {
            Draw.response = [];
            var table = document.getElementById("addtbl");
            var len = table.rows.length;
            for (var i=len-1; i >= 0; i--)
            {
                table.deleteRow(i);    
            }
            document.getElementById("reg").style.display = "none";
        }
        else
        {
            var name                 = Draw.response['cust_name'];
            var address              = Draw.response['cust_adr'];
            var email                = Draw.response['cust_email'];
            this.identity_type       = Draw.response['cust_id'];
            this.identity_no         = Draw.response['cust_id_no'];
            var credit_no            = Draw.response['cust_credit_no'];
            var phone                = Draw.response['cust_tel'];
            if (typeof Draw.response['autono0'] != 'undefined')
                this.autono[0]          = Draw.response['autono0'];
            if (typeof Draw.response['period0'] != 'undefined')
                this.period[0]          = Draw.response['period0'];
            else
                this.period[0] = '0';
            if (typeof Draw.response['contract0'] != 'undefined')
            {
                this.contract[0]        = Draw.response['contract0'];
                if ((this.contract[0] == 'daylist') && (this.period[0] == '0'))
                    error += "DayList Contract needs a list of the days per week\n";
            }
            if (typeof Draw.response['holiday0'] != 'undefined')
                this.holiday[0]         = Draw.response['holiday0'];
            this.start[0]           = Draw.response['start0'];
            this.starthour[0]       = Draw.response['starthour0'];
            this.endhour[0]         = Draw.response['endhour0'];
            error = '';
            if ((typeof this.identity_no == 'undefined') || (this.identity_no.length == 0))
                error += 'Identity number is missing\n';
            if ((typeof credit_no == 'undefined') || (credit_no.length == 0))
                error += 'Credit number is missing\n';
            if ((typeof phone == 'undefined') || (phone.length == 0))
                error += 'Phone number is missing\n';
            if (typeof this.starthour[0] == "undefined")
                this.starthour[0] = 0;
            if (typeof this.endhour[0] == "undefined")
                this.endhour[0] = 24;
            if (this.endhour[0] <= this.starthour[0])
                error += "End hour must be greater than start hour "+this.starthour[0]+"-"+this.endhour[0]+"\n";
            if (error.length)
            {
                Customer.done(error,Customer.addCustomer);
                return false;
            }
            if (typeof this.start[0] == "undefined")
            {
                currentdate = new Date();
                this.start[0] = currentdate.getDate() + '-' + (currentdate.getMonth()+1) + '-' + currentdate.getFullYear();
            }
            else if (this.start[0] == "today")
            {
                currentdate = new Date();
                this.start[0] = currentdate.getDate() + '-' + (currentdate.getMonth()+1) + '-' + currentdate.getFullYear();
            }
            sendCustomer = function(xmlhttp)
            {
                var url = serverIP+"common.php?action=addCustomer&name="+name
                        +"&addr="+address
                        +"&email="+email
                        +"&ident="+Customer.identity_type
                        +"&identno="+Customer.identity_no
                        +"&phone="+phone
                        +"&creditno="+credit_no;
                console.log(url);
                xmlhttp.open("GET",url,true);
                xmlhttp.send(null);
            }
            sendAuto = function(xmlhttp)
            {
                var url = serverIP+"common.php?action=addAuto&selection="+
                    Customer.identity_type+Customer.identity_no+"&field="+Customer.autono[0];
                console.log(url);
                xmlhttp.open("POST",url,true);
                xmlhttp.send(null);
            }
            sendContract = function(xmlhttp)
            {
                var url = serverIP+"common.php?action=addContract&customer="+
                    Customer.identity_type+Customer.identity_no
                        +"&autono="+Customer.autono[0]
                        +"&contract="+Customer.contract[0]
                        +"&holiday="+Customer.holiday[0]
                        +"&period="+Customer.period[0]
                        +"&starthour="+Customer.starthour[0]
                        +"&endhour="+Customer.endhour[0]
                        +"&start="+Customer.start[0];
                console.log(url);
                xmlhttp.open("POST",url,true);
                xmlhttp.send(null);
            }
            xmlhttp = new XMLHttpRequest();
            // function to be called on http response
            xmlhttp.onreadystatechange=function() 
            {
               if (xmlhttp.readyState==4 && xmlhttp.status==200) 
               {
                   //console.log(xmlhttp.responseText);
                   if (xmlhttp.responseText.substring(0,5) == "Error")
                   {
                       Customer.done(xmlhttp.responseText,Customer.addCustomer);
                       return;
                   }
                   if (xmlhttp.responseText == "customer")
                   {
                       if (Customer.autono[0] != 'undefined')
                           sendAuto(xmlhttp);
                       else 
                           if ((Customer.contract[0] != 'undefined') && (Customer.contract[0] != 'none'))
                               sendContract(xmlhttp);
                           else
                               Customer.done('done',Customer.addCustomer);
                   }
                   else if (xmlhttp.responseText == "auto")
                   {
                       if ((Customer.contract[0] != 'undefined') && (Customer.contract[0] != 'none'))
                           sendContract(xmlhttp);
                       else
                           Customer.done('done',Customer.addCustomer);
                   }
                   else if (xmlhttp.responseText == "contract")
                            Customer.done('done',Customer.addCustomer);
                       else 
                            Customer.done(xmlhttp.responseText,Customer.addCustomer);
               }
            }
            sendCustomer(xmlhttp);
        }
        return false;
    },
    registerCustomer   : function()
    {
        Draw.response = [];
        var table = document.getElementById("addtbl");
        Draw.tableHeader(table,"add");
        Draw.tableRow(table,2,null,'add');
        document.getElementById("reg").style.display = "block";
        
        return false;
    },
    listCustomer  : function (all,which,doit)
    {
        var full = true;
        document.getElementById("listformc").style.display = "none";        
        if (!doit) 
        {
            $("#seldc").val("");
            $("#fieldc").val("");
            if (this.win)
                this.win.close();
        }
        else
        {
            if (!all)
            {
                var seld   = $("#seldc").val();
                var selval = $("#fieldc").val();
                var idtype = selval.substring(0,1).toLowerCase();
                if (seld == "identno")
                {
                    if ((idtype == "i") || (idtype == "p"))
                        selval = idtype.toUpperCase() + selval.substring(1,selval.length);
                    else
                    {
                        Customer.done("Identity letter must be 'I' or 'P'",Customer.listCustomer);
                    }
                }
            }
            else
            {
                var seld   = 'all';
                if (which == 'i+a+c')
                    full = false;
                var selval = which; // all, i+a+c, c+a
            }
            var openstr = "list.html?seld="+seld+"&full="+full+"&selval="+selval;
            //console.log(openstr);
            this.win = window.open(openstr,"scrollbars=yes");
        }
        return false;
    },
    changeCustomer   : function(doit)
    {
        if (!doit) 
        {
            $("#chgid").val("");
            document.getElementById("chg").style.display = "none";
            Customer.doCustomer(false);
            document.getElementById("chglist").style.display = "none";
        }
        else
        {
            Draw.response = [];
            this.chgid = $("#chgid").val();
            var idtype = this.chgid.substring(0,1).toLowerCase();
            if ((idtype == "i") || (idtype == "p"))
                this.chgid = idtype.toUpperCase() + this.chgid.substring(1,this.chgid.length);
            else
            {
                Customer.done("Customer ID must start with 'I' or 'P'",Customer.changeCustomer);
                return false;
            }
            var url = serverIP+"customer.php?action=chgCustomer&rowid="+this.chgid;
            console.log(url);
            xmlhttp = new XMLHttpRequest();
            // function to be called on http response
            xmlhttp.onreadystatechange=function() 
            {
               if (xmlhttp.readyState==4 && xmlhttp.status==200) 
               {
                   //console.log(xmlhttp.responseText);
                   if (xmlhttp.responseText.substring(0,2) == "[{")
                   {
                       document.getElementById("chg").style.display = "none";
                       // the response is received encoded in JSON form
                       var JJ = JSON.parse(xmlhttp.responseText);
                       // JJ is the object obtained from parseing the JSON string
                       // for each record in the object
                       table = document.getElementById("chgtbl");
                       Draw.tableHeader(table,"chg");
                       Draw.tableRow(table,2,JJ[0],'chg');
                       var autos = JJ[0]['autos'];
                       for (var i=0; i < autos.length; i++)
                       {
                           Customer.auto_old[i]   = autos[i]['autono'];
                           Customer.auto_chg[i]   = false;
                       }
                       document.getElementById("chglist").style.display = "block";
                   }
                   else                      
                       Customer.done(xmlhttp.responseText,Customer.changeCustomer);
               }
            }
            xmlhttp.open("GET",url,true);
            xmlhttp.send(null);
        }
        return false;
    },
    doCustomer       : function(doit)
    {
        if (!doit) 
        {
            Draw.response = [];
            var table = document.getElementById("chgtbl");
            var len = table.rows.length;
            for (var i=len-1; i >= 0; i--)
            {
                table.deleteRow(i);    
            }
            document.getElementById("chglist").style.display = "none";
            return false;
        }
        var error = '';
        var url_customer = '';
        if (typeof Draw.response['cust_name'] != 'undefined') // the field was changed
        {
            var name = Draw.response['cust_name'];
            if (name.length == 0)
                error += 'Name is missing\n';
            else
                url_customer += "&name="+name;    
        }
        if (typeof Draw.response['cust_adr'] != 'undefined') 
        {
            url_customer += "&addr="+Draw.response['cust_adr'];
        }
        if (typeof Draw.response['cust_email'] != 'undefined') 
        {
            url_customer += "&email="+Draw.response['cust_email'];
        }
        if (typeof Draw.response['cust_credit_no'] != 'undefined') 
        {
            credit_no = Draw.response['cust_credit_no'];
            if (credit_no.length == 0)
                error += 'Credit number is missing\n';
            else
                url_customer += "&creditno="+credit_no;
        }
        if (typeof Draw.response['cust_tel'] != 'undefined') 
        {
            var phone  = Draw.response['cust_tel'];
            if (phone.length == 0)
                error += 'Phone number is missing\n';
            else
                url_customer += "&phone="+phone;
        }
        for (var i=0; i < Draw.howManyAutos; i++)
        {
            this.url_contract[i] = '';
        }
        for (var i=0; i < Draw.howManyAutos; i++)
        {
            istr = i.toString();
            if (typeof Draw.response['autono'+istr] != 'undefined')
            { 
                this.autono[i] = Draw.response['autono'+istr];
            }
            if (typeof Draw.response['contract'+istr] != 'undefined')
            {
                this.contract[i] = Draw.response['contract'+istr];
                this.url_contract[i] += "&contract="+this.contract[i];
            }
            if (typeof Draw.response['holiday'+istr] != 'undefined')
            {
                this.holiday[i] = Draw.response['holiday'+istr];
                this.url_contract[i] += "&holiday="+this.holiday[i];
            }
            if (typeof Draw.response['period'+istr] != 'undefined')
            {
                this.period[i] = Draw.response['period'+istr];
                if (this.period[i][this.period[i].length-1] == ',')
                {
                    this.period[i] = this.period[i].substr(0,this.period[i].length-1);
                    this.url_contract[i] += "&contract=daylist";
                }
                this.url_contract[i] += "&period="+this.period[i];
            }
            if (typeof Draw.response['start'+istr] != 'undefined')
            {
                this.start[i] = Draw.response['start'+istr];
                if ((this.start[i] == "") || (this.start[i] == "today"))
                {
                    currentdate = new Date();
                    this.start[i] = currentdate.getDate() + '-' + (currentdate.getMonth()+1) + '-' + currentdate.getFullYear();
                }
                this.url_contract[i] += "&start="+this.start[i];
            }
            if (typeof Draw.response['starthour'+istr] != 'undefined')
            {
                this.starthour[i] = Draw.response['starthour'+istr];
                if (this.starthour[i] == "")
                    this.starthour[i] = 0;
                this.url_contract[i] += "&starthour="+this.starthour[i];
            }
            if (typeof Draw.response['endhour'+istr] != 'undefined')
            {
                this.endhour[i]  = Draw.response['endhour'+istr];
                if (this.endhour[i] == "")
                    this.endhour[i] = 24;
                this.url_contract[i] += "&endhour="+this.endhour[i];
            }
            if (this.endhour[i] <= this.starthour[i])
                error += "End hour must be greater than start hour "+this.starthour[i]+"-"+this.endhour[i]+"\n";
        }
        if (error.length)
        {
            Customer.done(error,Customer.doCustomer);
            return false;
        } 
        sendCustomer = function(xmlhttp,url_customer)
        {
            var url = serverIP+"customer.php?action=doCustomer&rowid="+Customer.chgid+url_customer;
            console.log(url);
            xmlhttp.open("GET",url,true);
            xmlhttp.send(null);
        }
        sendAuto = function(xmlhttp,j)
        {
            Customer.auto_chg[j]   = true;
            var url = '';
            if ((Customer.auto_old[j] == "undefined") || (Customer.auto_old[j] == ""))
            {
                url = serverIP+"common.php?action=addAuto&selection="+Customer.chgid+"&field="+Customer.autono[j];
            }
            else
                url = serverIP+"auto.php?action=chgAuto&selection="+Customer.chgid+"&field="
                    +Customer.autono[j]+"&oldfield="+Customer.auto_old[j];
            console.log(url);
            Customer.auto_old[j] = Customer.autono[j];
            xmlhttp.open("GET",url,true);
            xmlhttp.send(null);
        }
        sendContract = function(xmlhttp,j)
        {
            var url = serverIP+"contract.php?action=doContract&customer="+Customer.chgid;
            if (Customer.auto_chg[j])
                url += "&autono="+Customer.autono[j]+Customer.url_contract[j];
            else
                url += "&autono="+Customer.auto_old[j]+Customer.url_contract[j];
            Customer.url_contract[j] = '';
            xmlhttp.open("GET",url,true);
            xmlhttp.send(null);
        }
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange=function() 
        {
           if (xmlhttp.readyState==4 && xmlhttp.status==200) 
           {
              if (xmlhttp.responseText.substring(0,5) == "Error")
              {
                  Customer.done(xmlhttp.responseText,Customer.doCustomer);
                  return;
              }
              if (xmlhttp.responseText == 'customer')
              {
                  var moreautos = false;
                  for (var j = 0; j < Customer.autono.length; j++)
                  {
                      if (Customer.autono[j] != Customer.auto_old[j])
                      {
                          moreautos = true;
                          sendAuto(xmlhttp,j);
                          break;
                      }
                  }
                  if (!moreautos) /* no autos changed */
                  {
                      /* try to send contracts */
                      var morecontracts = false;
                      for (var j = 0; j < Customer.url_contract.length; j++)
                      {
                          if (Customer.url_contract[j] != '')
                          {
                              morecontracts = true;
                              sendContract(xmlhttp,j);
                              break;
                          }
                      }
                      if (!morecontracts)
                          Customer.done('done',Customer.doCustomer);
                  }
              }
              else if (xmlhttp.responseText == 'auto')
                   {
                        /* continue to send autos */
                        var moreautos = false;
                        for (var j = 0; j < Customer.autono.length; j++)
                        {
                            if (Customer.autono[j] != Customer.auto_old[j])
                            {
                                moreautos = true;
                                sendAuto(xmlhttp,j);
                                break;
                            }
                        }
                        if (!moreautos) /* send first contract */
                        {
                            morecontract = false;
                            for (var j = 0; j < Customer.url_contract.length; j++)
                            {
                                if (Customer.url_contract[j] != '')
                                {
                                    sendContract(xmlhttp,j);
                                    morecontract = true;
                                    break;
                                }
                            }
                            if (!morecontract)
                                Customer.done('done',Customer.doCustomer);
                        }
                   }
              else if (xmlhttp.responseText == 'contract')
              { 
                  /* continue send contracts */
                  var morecontracts = false;
                  for (var j = 0; j < Customer.url_contract.length; j++)
                  {
                      if (Customer.url_contract[j] != '')
                      {
                          morecontracts = true;
                          sendContract(xmlhttp,j);
                          break;
                      }
                  }
                  if (!morecontracts)
                      Customer.done('done',Customer.doCustomer);
              }
              else
                  Customer.done(xmlhttp.responseText,Customer.doCustomer);
           }
        }
        if (url_customer != '')
            sendCustomer(xmlhttp,url_customer);
        else 
        {
            var moreautos = false;
            for (j = 0; j < this.autono.length; j++)
            {
                if ((this.autono[j] != this.auto_old[j]) && (this.autono[j] != 'undefined'))
                {
                    moreautos = true;
                    sendAuto(xmlhttp,j);
                    break;
                }
            }
            if (!moreautos)
            {
                /* continue send contracts */
                morecontracts = false;
                for (var j = 0; j < this.url_contract.length; j++)
                {
                    if (this.url_contract[j] != '')
                    {
                        morecontracts = true;
                        sendContract(xmlhttp,j);
                        break;
                    }
                }
                if (!morecontracts)
                    Customer.done('done',Customer.doCustomer);
            }
        }
            
        return false;
    },
    deleteCustomer   : function(doit)
    {
        if (!doit) 
        {
            $("#rowid").val("");
            document.getElementById("del").style.display = "none";
        }
        else
        {
            var delid = $("#rowid").val();
            var idtype = delid.substring(0,1).toLowerCase();
            if ((idtype == "i") || (idtype == "p"))
                delid = idtype.toUpperCase() + delid.substring(1,delid.length);
            else
            {
                alert("Customer ID must start with 'I' or 'P'");
                return false;
            }
            var url = serverIP+"customer.php?action=delCustomer&rowid="+delid;
            //console.log(url);
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange=function() 
            {
               if (xmlhttp.readyState==4 && xmlhttp.status==200) 
               {
                   if (xmlhttp.responseText.substring(0,5) == "Error")
                       Customer.done(xmlhttp.responseText,this.deleteCustomer);
                   else
                        Customer.done("done",Customer.deleteCustomer);
                   document.getElementById("del").style.display = "none";
               }
            }
            xmlhttp.open("GET",url,true);
            xmlhttp.send(null);
        }
        return false;
    },
    addAuto     : function(doit)
    {
        if (!doit) 
        {
            $("#addautoid").val("");
            $("#addautonr").val("");
            document.getElementById("auto").style.display = "none";
        }
        else
        {
            var autocustomer = $("#addautoid").val();
            var autonr = $("#addautonr").val();
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange=function() 
            {
               if (xmlhttp.readyState==4 && xmlhttp.status==200) 
               {
                   if (xmlhttp.responseText != 'auto')
                       Customer.done(xmlhttp.responseText,Customer.addAuto);
                   else
                       Customer.done("done",Customer.addAuto);
                   document.getElementById("auto").style.display = "none";
               }
            }
            var url = serverIP+"common.php?action=addAuto&selection="+autocustomer+"&field="+autonr;
            console.log(url);
            xmlhttp.open("POST",url,true);
            xmlhttp.send(null);
        }
        return false;
    }
};
