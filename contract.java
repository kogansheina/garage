package garage;

import java.sql.*;
import java.util.Calendar; 
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;
         
/*
*****                *** CONTRACT class ****
*/
public class contract                                                 
{
    public long customer;
    public long auto;
    private long contractid;
    public String contract = null;
    public int holiday;
    public int[] period = new int[7];
    public long start;
    public long end;
    public int hour_start;
    public int hour_end;
    
    contract(long id)
    {
        queryDB("customer="+id,false);
    }
    contract(String auto, String customer)
    {
        String user = customer.substring(0,1).toUpperCase() + customer.substring(1);
        this.customer = this.StringToNumber(user);    
        this.auto = this.StringToNumber(auto); 
        queryDB("customer="+this.customer+" AND plate="+this.auto,true);
    }
    contract(String customer)
    {
        String user = customer.substring(0,1).toUpperCase() + customer.substring(1);
        this.customer = this.StringToNumber(user);    
        queryDB("customer="+this.customer,false);
    }
    public void updateCounters(String countertype, long date, int hour)
    {
        try 
        {
            Class.forName("com.mysql.jdbc.Driver");
        } 
        catch (ClassNotFoundException e) 
        {
            System.out.println("Where is your MySQL JDBC Driver?");
            return;
        }
        try
        {
            // create our mysql database connection
            String myUrl = "jdbc:mysql://localhost/garage";
            Connection conn = DriverManager.getConnection(myUrl,"root","");
            String query = "SELECT counter FROM "+countertype+" WHERE id="+customer+" and contract="+contractid+";";
            //System.out.println("Query="+query);
            Statement st = conn.createStatement();
            ResultSet rs = st.executeQuery(query);
            // iterate through the java resultset
            if (rs.next())
            {
                int counter = rs.getInt("counter");
                if (countertype.equals("missies"))
                {
                    counter++;
                    query = "UPDATE missies SET counter= " +counter+" WHERE id="+customer+" and contract="+contractid+";";
                    st.executeQuery(query);
                }
                else if (countertype.equals("overstays"))
                {
                    if ((date > end) || (hour > hour_start))
                    {
                        counter++;
                        query = "UPDATE overstays SET counter= " +counter+" WHERE id="+customer+" and contract="+contractid+";";
                        st.executeQuery(query);
                    }
                }
                else
                {
                    if ((date < end) || (hour < hour_start))
                    {
                        counter++;
                        query = "UPDATE understays SET counter= " +counter+" WHERE id="+customer+" and contract="+contractid+";";
                        st.executeQuery(query);
                    }
                }
            }
            st.close();
        } // try
        catch (Exception e)
        {
          System.err.println("Got an exception! - queryDB");
          System.err.println(e.getMessage());
        }
    }
    public void makeBill(long date, int hour)
    {
        try 
        {
            Class.forName("com.mysql.jdbc.Driver");
        } 
        catch (ClassNotFoundException e) 
        {
            System.out.println("Where is your MySQL JDBC Driver?");
            return;
        }
        try
        {
            // create our mysql database connection
            String myUrl = "jdbc:mysql://localhost/garage";
            Connection conn = DriverManager.getConnection(myUrl,"root","");
            String set = "customer,auto,contract,date,hour";
            String vals = String.valueOf(customer)+","+String.valueOf(auto)+","+String.valueOf(contractid)+","+
                String.valueOf(date)+","+String.valueOf(hour);
            String query = "INSERT INTO bills ("+set+") VALUES ("+vals+");";
            System.out.println("Query="+query);
            Statement st = conn.createStatement();
            ResultSet rs = st.executeQuery(query);
            st.close();
        } // try
        catch (Exception e)
        {
          System.err.println("Got an exception! - queryDB");
          System.err.println(e.getMessage());
        }
    }
    /*********************/
    /* private procedures */
    /*********************/
    private void updateDB(long customer, long auto, String start,Statement st)
    {
        try
        {
            String query = "UPDATE autousers SET start="+start+
                           " WHERE customer="+customer+" AND plate="+auto+";";
            System.out.println("QUERY="+query);
            ResultSet rs = st.executeQuery(query);
        }
        catch (Exception e)
        {
          System.err.println("Got an exception! - updateDB");
          System.err.println(e.getMessage());
        }
    }
    // select is the query string
    // fill  - if 0, take all the contract data from DB
    // otherwise fill it for today
    private void queryDB(String select, Boolean update)
    {
        try 
        {
            Class.forName("com.mysql.jdbc.Driver");
        } 
        catch (ClassNotFoundException e) 
        {
            System.out.println("Where is your MySQL JDBC Driver?");
            return;
        }
        try
        {
            // create our mysql database connection
            String myUrl = "jdbc:mysql://localhost/garage";

            Connection conn = DriverManager.getConnection(myUrl,"root","");

            // our SQL SELECT query.
            String query = "SELECT * FROM autousers WHERE "+select+";";
            //System.out.println("Query="+query);
            
            // create the java statement
            Statement st = conn.createStatement();

            // execute the query, and get a java resultset
            ResultSet rs = st.executeQuery(query);

            Calendar calendar = Calendar.getInstance();
            // iterate through the java resultset
            if (rs.next())
            {
                contractid = rs.getLong("id");
                auto = rs.getLong("plate");
                customer = rs.getLong("customer");
                this.contract = rs.getString("contract");
                if (this.contract == null)
                {
                    System.out.format("contract null: %d %d\n",customer, auto);
                }
                holiday = rs.getInt("include_holyday");
                String perioddb = rs.getString("period");
                String tokens[] = perioddb.split(",");
                for (int y=0; y < tokens.length; y++)
                {
                    this.period[y] = Integer.parseInt(tokens[y]);
                }
                String start = rs.getString("start");
                SimpleDateFormat format = new SimpleDateFormat("dd-MM-yyyy",Locale.ENGLISH);
                Date date;
                if (!start.equals("future"))
                {
                    date = format.parse(start);
                }
                else if (update)
                {
                    date = new Date();
                    updateDB(customer, auto,format.format(date),st);
                }
                else
                {
                    st.close();
                    return;
                }
                this.start = date.getTime();
                hour_start = rs.getInt("starthour");
                hour_end = rs.getInt("endhour");
                calendar.setTimeInMillis(this.start);
                // calculate the end of the contract and return the string of the new date
                if ((this.period[0] > 0) && !contract.equals("daylist"))
                {
                    switch (contract)
                    {
                    case "daily":
                        calendar.roll(Calendar.DAY_OF_MONTH , this.period[0]); 
                        break;
                    case "weekly":
                        calendar.roll(Calendar.DAY_OF_MONTH , this.period[0] * 7); 
                        break;
                    case "monthly":
                        calendar.roll(Calendar.MONTH, this.period[0]); 
                        break;
                    default:
                        break;
                    }
                }
                end = calendar.getTimeInMillis();
            } // while
            st.close();
        } // try
        catch (Exception e)
        {
          System.err.println("Got an exception! - queryDB");
          System.err.println(e.getMessage());
        }
    } // queryDB
    
    private int StringToNumber(String s)
    {
        int n = 0;
        for (int i = 0; i < s.length(); i++)
        {
            int c = (int)s.charAt(i);
            n += c*Math.pow(10,i);
        }
        return n;
    }
    /*********************/
    /* public procedures */
    /*********************/ 
    public void getContract()
    {
        System.out.format("%s : holiday=%d period=%d start=%d end=%d start hour=%d end hour=%d\n",
                          contract,holiday,period[0],start,end,hour_start,hour_end);
    }
}
