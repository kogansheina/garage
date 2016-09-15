package garage;

import java.util.ArrayList;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;
import java.util.Calendar;          

public class lot
{
    private int maxLevels;
    private int maxLines;
    private int maxPlaces;
    private level[] levels;
    /* arrays - converts the dates to numbers, and day_of_week to number */
    private ArrayList<Integer> freeDaysInWeek;
    private ArrayList<Long> freeDays;
    private Calendar calendar; 
    private ArrayList<spot> listOfSpots;
    
    /* arrays as they are read from the xml file */
    public ArrayList<String> holidays;
    public ArrayList<String> weekends; 

    lot(int level, int line, int number)
    {
        holidays = new ArrayList<>();
        weekends = new ArrayList<>();
        listOfSpots = new ArrayList<>();
        maxLevels = level; 
        maxLines = line;
        maxPlaces = number; 
        levels = new level[maxLevels];
        for (int i=0; i < maxLevels; i++)
            levels[i] = new level(i,line,number); 
       calendar = Calendar.getInstance();
    }
    
    /*********************/
    /* public procedures */
    /*********************/
    
    // convert the weekend names to integer
    // freeDaysIn Week array contains the indexes of the weekend - numbering from 1 
    // convertes holidays date to be strings without leading zero
    //
    public void setWeekends()
    {
        freeDaysInWeek = new ArrayList<>(weekends.size());
        freeDays = new ArrayList<>(holidays.size());
        String[] DayOfWeek = {"Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"};
        for (int i=0; i < weekends.size(); i++)
        {
            String t = weekends.get(i);
            t = t.substring(0,1).toUpperCase() + t.substring(1);
            for (int j=0; j < DayOfWeek.length; j++)
            {
                if (t.equals(DayOfWeek[j]))
                {
                    freeDaysInWeek.add(i,j+1);
                    break;
                }
            }
        }
        SimpleDateFormat format = new SimpleDateFormat("dd-MM-yyyy",Locale.ENGLISH);
        for (int i=0; i < holidays.size(); i++)
        {
            try
            {
                Date date = format.parse(holidays.get(i));
                freeDays.add(date.getTime());
            }
            catch (Exception e)
            {
                System.err.println("Got an exception! lot - setWeekends");
                System.err.println(e.getMessage());
            }
        }
    }
    public void freeSpot(spot s,Date date)
    {
        levels[s.level].freeSpot(s,date);
    }
    public int getSize()
    {
        return (maxLevels * maxLines * maxPlaces);
    }
    public spot findSpotAndReserve(String user, contract c, long currentDate, int dayOfWeek, int hours)
    {
        spot s = null;

        if (contractIsHonored(c,currentDate,dayOfWeek,hours))
        {
            for (int l = 0; l < maxLevels; l++)
            {
                if (levels[l].getAvailability() > 0)
                {
                    s = levels[l].ReserveSpotLevel(user,c);
                    s.getInfo();
                    break;
                }
            }
        }
        else
        {
            for (int l = 0; l < maxLevels; l++)
            {
                s = levels[l].getReservedSpot(c);
                if (s != null)
                    s.setFree(currentDate,hours);
                else
                    break;
            }
        }
        return s;
    }
    public spot findSpotAndOccupy(String customer, String plate)
    {
        spot s = null;
        Boolean good;

        /* take the current date and time and the index of the day in the week  */
        Date date = new Date();
        long currentDate = date.getTime();                  // current date as long 
        calendar.setTime(date);                             // set calendar to current date
        int dayOfWeek = calendar.get(Calendar.DAY_OF_WEEK); // current day of week
        int hours = calendar.get(Calendar.HOUR_OF_DAY);     // current hour
        contract c = new contract(plate,customer);
        if (contractIsHonored(c,currentDate,dayOfWeek,hours))
        {
            for (int l = 0; l < maxLevels; l++)
            {
                if (levels[l].getAvailability() > 0)
                {
                    s = levels[l].findSpotLevel(customer, plate);
                    break;
                }
            }
        }
        return s;
    }

    /*********************/
    /* private procedures */
    /*********************/

    // returns TRUE if :
    //   the contract is set for holydays also (holiday = 1)
    //   the current date is not a holyday or a weekend
    private Boolean hasHoliday(contract c, long datestr, int dayOfWeek)
    {
        int i;
        
        if (c.holiday == 1)
            return true; // pay for holidays also
        
        for (i=0; i < freeDays.size(); i++)  
        {   // check day,month,year of the current date against holidays 
            if (freeDays.get(i) == datestr) // today is a holiday
                break;
        } 
        if (i == freeDays.size()) // today is not a holiday, go to check weekends
        {
            for (i=0; i < freeDaysInWeek.size(); i++) 
            {
                if (dayOfWeek == freeDaysInWeek.get(i)) // today is an weekend
                    break;
            }
            if (i == freeDaysInWeek.size()) // today is not a weekend
                return true;
            else // is an weekend and the contract has not holiday set
                return false;
        }
        else // is a holiday and the contract has not holiday set
            return false;
    }
    // return true if the current conditions permits to honor the contract
    private Boolean contractIsHonored(contract c, long currentDate, int dayOfWeek, int currentHour)
    {
        if (hasHoliday(c,currentDate,dayOfWeek)) 
        {
            // check range
            /* 
            if today >= contract start
               then if period is 0 or today <= contract end
                   then if current hour is in range of strat - end hour
                      then give spot
            */
            if (c.contract.equals("daylist"))
            {
                for (int y=0; y < c.period.length; y++)
                {
                    if (dayOfWeek == c.period[y])
                        return checkHours(c,currentHour);
                }
            }
            else
            {
                if (c.start <= currentDate)
                {
                    if ((c.period[0] == 0) || (c.end >= currentDate))
                       return checkHours(c,currentHour);
                }
            }
        }
        return false;
    }
    private Boolean checkHours(contract c, int currentHour)
    {
        //System.out.format("go check hours : start=%d current=%d end=%d\n",c.hour_start, currentHour,c.hour_end);
        if ((c.hour_start <= currentHour) && (c.hour_end >= currentHour))
            return true;
        return false;
    }
    public spot dequeueSpot(int index) 
    {
        try
        {
            synchronized (this.listOfSpots) 
            {  // lock on list
                while (this.listOfSpots.size() == 0) 
                {
                   this.listOfSpots.wait();     // wait on list
                }
                spot s = this.listOfSpots.remove(index);
                this.listOfSpots.notify();
                return s;
            }
        }
        catch(InterruptedException e)
        { 
            System.err.println("Got an exception! camera-dequeueSpot");
            System.out.print(e.getMessage());
        }
        return null;
    }
    public spot findSpot(String auto)
    {
        for (int y=0; y < listOfSpots.size(); y++)
        {
            if (listOfSpots.get(y).auto.equals(auto))
                return dequeueSpot(y);
        }
        return null;
    }
    public void enqueueSpot(spot value) 
    {
        try
        {
            synchronized (this.listOfSpots) 
            {  // lock on list
                while (this.listOfSpots.size() == getSize()) 
                {
                   this.listOfSpots.wait();   // wait on list
                }

                this.listOfSpots.add(value);
                this.listOfSpots.notify();
            }
        }
        catch(InterruptedException e)
        { 
            System.err.println("Got an exception! camera-enqueueSpot");
            System.out.print(e.getMessage());
        }
    }
}

/*
*****                *** LEVEL class ****
*/
class level
{
    private spot[] spots; 
    private int maxLines;
    private int maxPlaces;
    private int level;
    private int free;
    private int reserved;
    
    level(int level, int line, int number)
    {
        this.level = level;
        free = line*number;
        maxLines = line;         
        maxPlaces = number;  
        spots = new spot[this.free];
        for (int j=0; j < line; j++)
        {
            for (int k=0; k < number; k++)
            {
                int index = j*maxPlaces + k;
                spots[index] = new spot(level,j,k);
            }
        }
    }
    public int getSize()
    {
        return (maxLines * maxPlaces);
    } 
     
    public int getAvailability()
    {
        return free-reserved;
    }
    
    public void freeSpot(spot s,Date date)
    {
        SpotStatus stat = s.setFree(date);
        if (stat == SpotStatus.RESERVED)
            reserved--;
        free++;
    }
    public spot ReserveSpotLevel(String user, contract c)
    {
        spot s = null;
        for (int l = 0; l < maxLines; l++)
        {
            for (int p = 0; p < maxPlaces; p++)
            {
                int index = l*maxPlaces + p;
                if (spots[index].status == SpotStatus.FREE)
                {
                    free--;
                    reserved++;
                    s = spots[index];
                    s.setReserve(c);
                    s.customer = user;
                    break;
                }
            }
        }
        return s;
    }
    public spot getReservedSpot(contract c)
    {
        spot s = null;
        for (int next=0; next < getSize(); next++)
        {
            if ((spots[next].status == SpotStatus.RESERVED) && (spots[next].current_contract == c))
                return s;
        }
        return s;
    }
    
    public spot findSpotLevel(String customer, String plate)
    {
        spot s = null;
        for (int l = 0; l < maxLines; l++)
        {
            for (int p = 0; p < maxLines; p++)
            {
                int index = l*maxPlaces + p;
                if (spots[index].hasOwner(customer, plate))
                    return spots[index];
                if ((spots[index].status == SpotStatus.RESERVED) && (spots[index].customer != null))
                {
                    spots[index].auto = plate;
                    return spots[index];
                }
                if (spots[index].status == SpotStatus.FREE)
                {
                    contract current_contract = new contract(plate, customer);
                    if (current_contract.contract == null) /* no registration for this customer and auto */
                    {
                        current_contract = new contract(customer);
                        if (current_contract.contract != null) /* there is a registration for this customer */
                        {
                            free--;
                            s = spots[index];
                            s.setOccupied(customer,plate,current_contract);
                        }
                        return s;
                    }
                    if (current_contract.contract.equals("none")) /* customer and auto registrated, but no contract */
                    {
                        free--;
                        s = spots[index];
                        s.setOccupied(customer,plate,current_contract);
                        return s;
                    }
                }
            }
        }
        return s;
    }
}

/*
*****                *** SPOT class ****
*/
enum SpotStatus 
{
    FREE, OCCUPIED, RESERVED
}
class spot
{
    public int level;
    public int line;
    public int number;
    
    public contract current_contract;
    public SpotStatus status;
    public String customer;
    public String auto;

    spot(int level, int line, int number)
    {
        status = SpotStatus.FREE;
        customer = null;
        auto = null;
        setLocation(level, line, number);
        current_contract = null;
    }
    spot(int location)
    {
        status = SpotStatus.FREE;
        setLocation(location);
        customer = null;
        auto = null;
        current_contract = null;
    }
    public void setLocation(int level, int line, int number)
    {
        this.level = level;
        this.line = line;
        this.number = number;
    }
    public void setLocation(int location)
    {
        level = location/100;
        int restloc = location%100;
        line = restloc/10;
        number = restloc%10;
    }
    public void setOccupied(String customer, String autono, contract c)
    {
        status = SpotStatus.OCCUPIED;
        current_contract = c;
        setOwner(customer, autono);
    }
    public SpotStatus setFree(Date date)
    {
        Calendar calendar = Calendar.getInstance();
        long currentDate = date.getTime();                  // current date as long 
        calendar.setTime(date);                             // set calendar to current date
        int hours = calendar.get(Calendar.HOUR_OF_DAY);     // current hour
        return setFree(currentDate,hours);
    }
    public SpotStatus setFree(long date, int hour)
    {
        SpotStatus stat = this.status;
        if (stat == SpotStatus.RESERVED)
        {
            // update missing
            current_contract.updateCounters("missies", date, hour);
        }
        else
        {
            // update over,under
            current_contract.updateCounters("ovrestays", date, hour);
            current_contract.updateCounters("understays", date, hour);
            // make a bill
            current_contract.makeBill(date, hour);
        }
        this.status = SpotStatus.FREE;
        customer = null;
        auto = null;
        current_contract = null;
        
        return stat;
    }
    public void setReserve(contract c)
    {
        status = SpotStatus.RESERVED;
        current_contract = c;
    }
    public void setOwner(String customer, String autono)
    {
        this.customer = customer;
        this.auto = autono;
    }
    public Boolean hasOwner(String customer, String autono)
    {
        if ((this.customer == customer) && (this.auto == autono))
            return true;
        return false;
    }
    public void getInfo()
    {
       System.out.println("SPOT: status="+status+" location="+level+","+line+","+number);
       current_contract.getContract();
    }  
}
