var daysOfWeek = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
var daysOfWeekLabel = ["SUNDAY","MONDAY","TUESDAY","WEDNESDAY","THURSDAY","FRIDAY","SATURDAY"];

var Draw =
{
    /* all, i+a+c (id,auto,contract), c+a(customer+auto) */
    selval   : 'all', 
    response : [],
    mandatory_cols :["ID","Number"],
    customer_cols : ['Name','Address','E-mail','Phone','Credit Card'],
    customer_mandatory : [false,false,false,true,true],
    auto_cols : ['Registration'],
    contract_cols : ['Type','Holiday','Units','Start','From','To'],
    contract_mandatory : [true,false,false,false,false,false],
    howManyAutos : 0,
    
    /* draw header of the table - 2 lines 
    command : add, chg, list   
    */
    tableHeader : function(table,command)
    {
        /**** create the table, add it to DOM */
        if (table == null)
        {
            table = document.createElement('table');
            document.body.appendChild(table);
        }
        mandatory = '';        
        /**** add and chg command will draw the mandatory sign */        
        if (command != "list")
        {
            mandatory = " (*)".fontcolor("red");
            this.selval = 'all';
        }
        /**********   
           line 1 
        **********/
        row = table.insertRow();
        cell = document.createElement('th');
        cell.innerHTML = "Customer";
        cell.setAttribute("style", "background-color:goldenrod"); 
        
        /**** when only id in customer part, it takes 2 columns,
        otherwise it takes 2+5 columns */
        
        if (this.selval == 'i+a+c')
            cell.setAttribute("colspan", "2");
        else 
            cell.setAttribute("colspan", "7"); 
        row.appendChild(cell);
        /**** the auto part */
        cell = document.createElement('th');
        cell.innerHTML = "Auto";
        cell.setAttribute("style", "background-color:lightgreen"); 
        row.appendChild(cell);        
        /**** make the contract part only for list all or id+auto+contract */        
        if (this.selval != 'c+a')
        {
            cell = document.createElement('th');
            cell.innerHTML = "Contract";
            cell.setAttribute("style", "background-color:lightskyblue"); 
            cell.setAttribute("colspan", "6"); 
            row.appendChild(cell);
        }        
        /**********   
           line 2 
        **********/        
        /**** id and id number columns are in all list types */        
        row = table.insertRow();
        row.setAttribute("style", "background-color:palegoldenrod"); 
        for (i=0; i< this.mandatory_cols.length; i++)
        {        
            cell = document.createElement('th');
            cell.innerHTML = this.mandatory_cols[i]+mandatory;
            row.appendChild(cell);
        }        
        /**** draw customer columns */        
        if (this.selval != 'i+a+c')
        {
            for (i=0; i < this.customer_cols.length; i++)
            {
                cell = document.createElement('th');
                if (this.customer_mandatory[i])
                    cell.innerHTML = this.customer_cols[i]+mandatory;
                else
                    cell.innerHTML = this.customer_cols[i];
                row.appendChild(cell);
            }
        }                
        /**** draw auto columns */        
        cell = document.createElement('th');
        cell.innerHTML = this.auto_cols[0]+mandatory;
        row.appendChild(cell);       
        /**** draw contract columns  - for list all and id+auto+contract */        
        if (this.selval != 'c+a')
        {
            for (i=0; i < this.contract_cols.length; i++)
            {
                cell = document.createElement('th');
                if (this.contract_mandatory[i])
                    cell.innerHTML = this.contract_cols[i]+mandatory;
                else
                    cell.innerHTML = this.contract_cols[i];
                row.appendChild(cell);
            }
        }  
              
        return table;
    },
    insertPeriod : function(val,idx)
    {
        var inp  = document.createElement("select");
        inp.setAttribute("id", idx);
        inp.multiple = true; 
        var res = val.split(",");
        for (var k=0; k < daysOfWeek.length; k++)
        {
            var opt = this.createOption(daysOfWeek[k],(k+1).toString()); 
            itis = false;
            for (var j=0; j < res.length; j++)
            {
                if (res[j] == daysOfWeek[k])
                {
                    itis = true;
                    break;
                }
            }
            if (itis)
                opt.text = daysOfWeekLabel[k];
            else  
                opt.text = daysOfWeek[k];
            inp.appendChild(opt);
        }
        inp.onchange = function()
        {
            var sel = $("#"+idx).val();
            Draw.response[idx] += sel+',';
        };
        
        return inp;
    },
    insertOption : function(field,idx,options,values)
    {
        inp  = document.createElement("select");
        inp.setAttribute("id", idx); 
        for (i=0; i < options.length; i++)
        {
            txt = options[i];
            opt = this.createOption(txt,values[i]); 
            inp.appendChild(opt);
            if (field == values[i])
                opt.selected = true;
        }
        inp.onchange = function(){
            Draw.response[idx] = $("#"+idx).val();
        };
        
        return inp;
    },
    insertHour : function(fieldstr,field,index)
    {
        idx = fieldstr+index;
        inp  = document.createElement("select");
        inp.setAttribute("id", idx); 
        for (i=0; i < 24; i++)
        {
            opt = this.createOption(i.toString(),i);
            inp.appendChild(opt);
            if (field == i.toString())
                opt.selected = true;
        }
        
        return inp;
    },
    oneVehicle   :  function (row,vehileArray,command,index)
    {
        var auto = 'undefined';
        var contract = 'none';
        var holiday = 'no';
        var period = '0';
        var start = 'future';
        var starthour = '0';
        var endhour = '24';
        var cell = null;
        var inp = null;
        if (vehileArray != null)
        {
            auto = vehileArray['autono'];
            contract = vehileArray['contract'];
            holiday = vehileArray['holiday'];
            periodstr = vehileArray['period'];
            start = vehileArray['start'];
            starthour = vehileArray['starthour'];
            endhour = vehileArray['endhour'];
            if (contract == 'daylist')
            {
                var res = periodstr.split(",");
                var perioda = []; 
                for (var k=0; k < res.length; k++)
                {
                    perioda[k] = daysOfWeek[res[k]-1];
                }
                period = perioda.join();
            }
            else
                period = periodstr;
        }
        if (command == 'list')
        {
            if (auto == 'undefined')
                cell = this.addCell(row,auto,1,"lightsalmon");
            else if (auto == null)
                cell = this.addCell(row,'undefined',1,"lightsalmon");
            else
                cell = this.addCell(row,auto,1,"green");
            if (this.selval != 'c+a')
            {
                if (contract == 'none')
                    cell = this.addCell(row,contract,1,"lightsalmon");
                else
                    cell = this.addCell(row,contract,1);
                this.addCell(row,holiday,1);
                this.addCell(row,period,1);
                if (start == 'future')
                    cell = this.addCell(row,start,1,"lightsalmon");
                else
                    cell = this.addCell(row,start,1);
                this.addCell(row,starthour,1);
                this.addCell(row,endhour,1);
            } 
        }
        else 
        {
           if (auto == 'undefined')
                cell = this.inputTextCell(row,"autono"+index,auto,12,1,"lightsalmon");
            else
                cell = this.inputTextCell(row,"autono"+index,auto,12,1,"green");
            cell = this.addCell(row,null,1);
            inp = this.insertOption(contract,"contract"+index,
                  ["None","Weekly","Monthly","Daily","DayList"],
                  ["none","weekly","monthly","daily","daylist"]);
            cell.appendChild(inp);
            cell = this.addCell(row,null,1);
            inp  = this.insertOption(holiday,"holiday"+index,["Include","Without"],["yes","no"]);
            cell.appendChild(inp);
            
            if (contract == 'daylist')
            {
                cell = this.addCell(row,null,1);
                inp = this.insertPeriod(period,"period"+index);
                cell.appendChild(inp);
            }
            else
                this.inputTextCell(row,"period"+index,period,5,1);
            
            this.inputTextCell(row,"start"+index,start,10,1);
            cell = this.addCell(row,null,1);
            inp  = this.insertHour("starthour",starthour,index);
            inp.onchange = function(){Draw.response["starthour"+index] = $("#starthour"+index).val();}
            cell.appendChild(inp);
            cell = this.addCell(row,null,1);
            inp  = this.insertHour("endhour",endhour,index);
            inp.onchange = function(){Draw.response["endhour"+index] = $("#endhour"+index).val();}
            cell.appendChild(inp);
        }
               
        return cell;
    },
    createOption : function(txt,val)
    {
        opt = document.createElement("option");        
        t = document.createTextNode(txt);
        opt.appendChild(t);
        opt.setAttribute("value",val);  
          
        return opt;
    },
    inputTextCell : function (row,field,value,length,span,color)
    {
        var cell = row.insertCell();
        var inp  = document.createElement("input");
        inp.setAttribute("type", "text");     
        inp.setAttribute("size", length.toString()); 
        inp.style.textAlign = "center";    
        inp.setAttribute("value",value);     
        inp.setAttribute("id", field);
        if (color != null)
            inp.setAttribute("style","color:"+color); 
        else if (value == 'undefined')
            inp.setAttribute("style","color:lightsalmon"); 
        cell.appendChild(inp);
        inp.onchange = function(){
            Draw.response[field] = $("#"+field).val();
        };
        cell.setAttribute("rowspan",span.toString());
        
        return cell;
    },
    inputTextAreaCell : function(row,field,value,length,span)
    {
        var cell = row.insertCell();
        var inp  = document.createElement("textarea");
        inp.setAttribute("rows", "5"); 
        inp.setAttribute("cols", length.toString()); 
        inp.style.textAlign = "center";    
        inp.value = value;     
        inp.setAttribute("id", field);
        if (value == 'undefined')
            inp.setAttribute("style","color:lightsalmon"); 
        cell.appendChild(inp);
        inp.onchange = function(){Draw.response[field] = $("#"+field).val();};
        cell.setAttribute("rowspan",span.toString());
        
        return cell;
    },
    tableRow    : function(table,index,J,command)
    {
        var vehicles = [];
        var span = 1;
        var name = '';
        var address = '';
        var email = '';
        var telephone = '';
        var credit = '';
        var cust_id = 'I';
        var identno = '';
        var cell = null;
        var inp = null;
        var vehileArray = null;
        if ((command == 'add') || (command == 'chg'))
            this.selval = 'all';
        if (command == 'add')
        {
            this.response['cust_id'] = 'I';
        }
        /****  J is json string, it is null for add command */
        if (J != null)
        {
            /**** for command and list options, auto and id are drawn */
            vehicles = J['autos'];
            if (vehicles.length > 0)
                span = vehicles.length;
            cust_id = J['identity'];
            identno = J['identno']; 
            /**** for all cases besides list id+auto+contract, take customer data */   
            if (this.selval != 'i+a+c')
            {    
                name = J['name'];
                address = J['address'];
                email = J['email'];
                telephone = J['telephone'];
                credit = J['credit'];
            }
        }
        var row = table.insertRow(index);
        /**** set the first 2 mandatory columns; for commands chg and list the 2 colums are drawn as 1 */
        if (command != 'add')
        {
            cell = this.addCell(row,cust_id+identno,1);
            cell.setAttribute("colspan","2");
            cell.setAttribute("rowspan",span.toString());
        }
        else 
        {
            cell = this.addCell(row,null,1);
            inp = this.insertOption(cust_id,"cust_id",["Identity","Passport"],["I","P"]);
            cell.appendChild(inp);
            this.inputTextCell(row,"cust_id_no",identno,10,span);
        }
        /**** if list - just draw the other columns */
        if (command == 'list')
        {
            /* if customer part is needed */
            if (this.selval != 'i+a+c')
            {
                this.addCell(row,name,span);
                this.addCell(row,address,span);
                this.addCell(row,email,span);
                this.addCell(row,telephone,span);
                this.addCell(row,credit,span);
            }
        }
        else /* add or chg commands */
        {
            this.inputTextAreaCell(row,"cust_name",name,8,span);
            this.inputTextAreaCell(row,"cust_adr",address,8,span);
            this.inputTextAreaCell(row,"cust_email",email,8,span);
            this.inputTextAreaCell(row,"cust_tel",telephone,8,span);
            this.inputTextAreaCell(row,"cust_credit_no",credit,12,span);
        }
        this.howManyAutos = span;
        /* no autos defined for this customer */
        if (vehicles.length == 0)
        {
            //if (command == 'list')
            //    return span;
            this.oneVehicle(row,null,command,"0");
        }
        else
        {
            /* draw autos */    
            vehileArray = vehicles[0];
            this.oneVehicle(row,vehileArray,command,"0");
            /* if they are more than 1 auto for this customer */
            for (var i=1; i < vehicles.length; i++)
            {
                vehileArray = vehicles[i];
                row = table.insertRow(index+i);
                this.oneVehicle(row,vehileArray,command,i.toString());
            }
        }
        
        return span;    
    },
    addCell  : function(row, textarray, span,color)
    {
        var text = '';
        if (textarray != null)
            text = textarray;
        var cell = row.insertCell();
        cell.style.textAlign = "center";
        cell.innerHTML = text;
        if (span > 1)
            cell.setAttribute("rowspan",span.toString());
        if (color != null)
            cell.setAttribute("style","color:"+color);
        else if (textarray == 'undefined')
            cell.setAttribute("style","color:lightsalmon");
        return cell;
    }
};

