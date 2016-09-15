package garage;

import java.util.ArrayList;
import java.util.concurrent.ExecutionException;
import java.util.concurrent.Executors;
import java.util.concurrent.ScheduledExecutorService;
import java.util.concurrent.TimeUnit;
import java.sql.*;
import java.util.Date;
import java.util.Calendar;  
        
class CommandObject 
{
    private String command;
    private ArrayList<String> parameters;
    public Boolean ready;
    
    CommandObject() 
    { 
        command = "";
        parameters = new ArrayList<>();
        ready = false;
    } 
    public String getCommand()
    {
        return command;  
    }
    public String getParameter(int index)
    {
        if (index < parameters.size())
            return parameters.remove(index); 
        return ""; 
    }
    public void setCommand(String c)
    {
        command = c;  
    }
    public void setParameter(String param)
    {
        parameters.add(param);
    }
}

public class command implements Runnable 
{    
    private CommandObject cmd;
    private camera entryCamera = null;
    private camera exitCamera = null;
    private lot parkingLot;
    private entryAuto enter = null;
    private exitAuto exit = null;
    private static ScheduledExecutorService scheduler;
    
    // indicates the settings files were read
    public int queueSize;
    // indicated the first reserve finished
    public Boolean ready;
    
    command(CommandObject cmdobj)
    {
        cmd = cmdobj;
        queueSize = 0;
        ready = false;
        scheduler = Executors.newScheduledThreadPool(1);
    }
    @Override
    public void run()
    {
        while(true)
        {
            // wait for asynchronous command
            synchronized(cmd)
            {
                try
                {
                    cmd.wait();
                    switch (cmd.getCommand())
                    {
                    case "setting":
                        readSettings();                    
                        break;
                    case "entry":
                        autoEntry();
                        break;
                    case "user":
                        setUser();
                        break;
                    case "exit":
                        autoExit();
                        break;
                    case "go":
                        // create entr/exit cameras objects
                        // and thread to wait for them
                        entryCamera = new camera(queueSize);
                        exitCamera = new camera(queueSize);
                        enter = new entryAuto(entryCamera,parkingLot);
                        Thread threadEntry = new Thread(enter);
                        exit = new exitAuto(exitCamera,parkingLot);
                        Thread threadExit = new Thread(exit);
                        threadEntry.start();
                        threadExit.start();
                        break;
                    case "reserve":
                        Runnable ob = new ReservedThread(parkingLot,this.cmd);
                        //  Thread scheduling
                        scheduler.scheduleWithFixedDelay(ob, 0, 1, TimeUnit.HOURS);
                        break;
                    case "stop":
                        enter.stop = true;
                        exit.stop = true;
                        scheduler.shutdownNow();
                        break;
                    case "help":
                        System.out.println("Commands : setting,entry,exit,user,statistics,monitor");
                        break;
                    default:
                        System.out.println("Command : unknown, received "+cmd.getCommand());
                        break;
                    }
                } 
                catch (InterruptedException e)
                {
                  System.err.println("Got an exception! command-run");
                  System.err.println(e.getMessage());
                }
            }
        }
    } // override interface run procedure
    
    /**********************/
    /* private procedures */
    /**********************/
    private void readSettings()
    {
        ArrayList<String> dimensions = new ArrayList<>();
        ArrayList<String> keys = new ArrayList<>(); 
        // read 'settings' file - set the parking lot dimensions
        keys.add("LEVELS");
        keys.add("LINES");
        keys.add("PLACES");
        XMLFile x = new XMLFile("garage/settings.xml",keys,dimensions);
        parkingLot = new lot(Integer.parseInt(dimensions.get(0)),
                                 Integer.parseInt(dimensions.get(1)),
                                 Integer.parseInt(dimensions.get(2)));
        keys.clear();
        // read 'holiday' file for holidays and weekends
        keys.add("HOLIDAY");
        x = new XMLFile("garage/holiday.xml",keys,parkingLot.holidays);
        keys.clear();
        keys.add("WEEKEND");
        x = new XMLFile("garage/holiday.xml",keys,parkingLot.weekends);
        // calculate parking lot size
        queueSize = parkingLot.getSize();
        // convert holidays dates to long and weekends to days in week indexes
        parkingLot.setWeekends();
    }
    private void autoEntry()
    {
        if ((entryCamera == null) || (queueSize == 0))
        {
            System.out.println("entryCamera or queueSize are not initilized");
            System.out.println("Need 'go' and/or 'settings' commands");
            return;
        }
        // another auto cannot come before the customer entered his id
        if (!entryCamera.waitForUser())
        {
            entryCamera.enqueue(cmd.getParameter(0)); /* auto plate */
            entryCamera.setWaitForUser(true);
        }
    }
    private void setUser()
    {
        if ((entryCamera == null) || (queueSize == 0))
        {
            System.out.println("entryCamera or queueSize are not initilized");
            System.out.println("Need 'go' and/or 'settings' commands");
            return;
        }
        if (entryCamera.waitForUser())
        {
            // free the lock for another auto to entry
            entryCamera.setWaitForUser(false);
            entryCamera.enqueue(cmd.getParameter(0)); 
            // wake up entryAuto thread 
            synchronized(entryCamera)
            {
                entryCamera.notify();
            }
        }
        else
            System.out.println("auto need to be entered before user");
    }
    private void autoExit()
    {
        if ((exitCamera == null) || (queueSize == 0))
        {
            System.out.println("exitCamera or queueSize are not initilized");
            System.out.println("Need 'go' and/or 'settings' commands");
            return;
        }
        synchronized(exitCamera)
        {
            exitCamera.enqueue(cmd.getParameter(0)); 
            exitCamera.notify();
        }
    }
} // command class

 // Runs periodically - every hour - to free/occupy/reserve spot according to the contract
class ReservedThread implements Runnable 
{
     private lot parkingLot;
     private CommandObject cmd;
     private static String jdbcDriver = "jdbc:mysql://localhost" ;
     
     ReservedThread(lot parkingLot,CommandObject cmdobj)
     {
         this.parkingLot = parkingLot;
         this.cmd = cmdobj;
     }
     @Override
     public void run() 
     {
         try
         {
	         Class.forName(jdbcDriver);
             // create our mysql database connection
             Connection conn = DriverManager.getConnection(jdbcDriver,"root","");
             // create the java statement
             Statement st = conn.createStatement();
             // SQL command to create a database in MySQL.

			 String db = "CREATE DATABASE IF NOT EXISTS garage";
			 int res = st.executeUpdate(db);
        System.out.println("create"); 		 
             // our SQL SELECT query.
             String query = "SELECT id,idtype,idno FROM customers;";
             //System.out.println("Query="+query);
             
             // execute the query, and get a java resultset
              ResultSet rs = st.executeQuery(query);
       System.out.println("query"); 
             // take the current date and time and the index of the day in the week  
             Date date = new Date();
             Calendar calendar = Calendar.getInstance();
             long currentDate = date.getTime();                  // current date as long 
             calendar.setTime(date);                             // set calendar to current date
             int dayOfWeek = calendar.get(Calendar.DAY_OF_WEEK); // current day of week
             int hours = calendar.get(Calendar.HOUR_OF_DAY);     // current hour
             // iterate through the java resultset - all the registered customers
             while (rs.next())
             {
                 long id = rs.getLong("id");
                 String idt = rs.getString("idtype");
                 String idn = rs.getString("idno");
                 contract c = new contract(id);
                 if (c != null) 
                 {
                     if (c.contract != null)
                     {
                        // the customer has a defined auto, but no contract
                        // reserve the spot
                        if (!c.contract.equals("none"))
                            parkingLot.findSpotAndReserve(idt+idn,c,currentDate,dayOfWeek,hours);
                    }
                    //else // customer registered, but no auto
                    //   System.out.println("c.contract null");

                 }
                 //else // customer registered, but no auto
                 //   System.out.println("c null");
             }
             st.close();
             cmd.ready = true;
         }
         catch (Exception e)
         {
           System.err.println("Got an exception! ReservedThread-run");
           System.err.println(e.getMessage());
         }
    }
}
class entryAuto implements Runnable 
{
    private camera user;
    private String waitAuto;
    private String waitCustomer;
    private spot occupy;
    private lot parkingLot;
    public Boolean stop;
    
    entryAuto(camera entry, lot parkingLot)
    {
        user = entry;
        waitAuto = "";
        waitCustomer = "";
        this.parkingLot = parkingLot;
        stop = false;
    }
    @Override
    public void run()
    {
        while(!stop)
        {
        synchronized(user)
        {
            try
            {
                user.wait();
                waitAuto = user.dequeue();
                System.out.println("Auto "+ waitAuto+" enters");
                waitCustomer = user.dequeue();
                System.out.println("Customer "+ waitCustomer+" enters");
                if ((waitAuto.length() != 0) && (waitCustomer.length() != 0))
                {
                    occupy = parkingLot.findSpotAndOccupy(waitCustomer, waitAuto);
                    if (occupy != null)
                    {
                        waitAuto = "";
                        waitCustomer = "";
                        parkingLot.enqueueSpot(occupy);
                        occupy.getInfo();
                    }
                    else
                    {
                        System.out.format("ERROR - spot not found for user='%s' and auto='%s'\n",
                                           waitCustomer, waitAuto);
                        user.enqueue(waitAuto);                        
                        user.enqueue(waitCustomer);
                    } 
                }
            } 
            catch(InterruptedException e){ System.out.print(e);}
        }
        }
    }
}
class exitAuto implements Runnable 
{
    private camera user;
    private String waitAuto;
    private spot occupy;
    private lot parkingLot;
    public Boolean stop;
    
    exitAuto(camera entry, lot parkingLot)
    {
        user = entry;
        waitAuto = "";
        this.parkingLot = parkingLot;
        stop = false;
    }
    @Override
    public void run()
    {
        while(!stop)
        {
            synchronized(user)
            {
                try
                {
                    user.wait();
                    waitAuto = user.dequeue();
                    System.out.println("Auto "+ waitAuto+" exit");
                    /*
                    find occupied slot for this auto and free it 
                    */
                    spot s = parkingLot.findSpot(waitAuto);
                    waitAuto = "";
                    parkingLot.freeSpot(s,new Date());
                }
                catch(InterruptedException e){ System.out.print(e);}
            }
        }
    }
}


