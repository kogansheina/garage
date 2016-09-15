package garage;

import java.util.ArrayList;

public class camera 
{
    private ArrayList<String> list;
    private int maxSize;
    private Boolean wait;

    camera(int maxsize)
    {
        this.list = new ArrayList<>();
        this.maxSize = maxsize;
        this.wait  = false;
    }
    public void setWaitForUser(Boolean v)
    {
        this.wait = v;
    }
    public Boolean waitForUser()
    {
        return this.wait;
    }
    public String dequeue() 
    {
        try
        {
            synchronized (this.list) 
            {  // lock on list
                while (this.list.size() == 0) 
                {
                   this.list.wait();     // wait on list
                }

                String el = this.list.remove(0);
                this.list.notify();
                return el;
            }
        }
        catch(InterruptedException e)
        { 
            System.err.println("Got an exception! camera-dequeue");
            System.out.print(e.getMessage());
        }
        return "";
    }

    public void enqueue(String value) 
    {
        try
        {
            synchronized (this.list) 
            {  // lock on list
                while (this.list.size() == this.maxSize) 
                {
                   this.list.wait();   // wait on list
                }
                this.list.add(value);
                this.list.notify();
            }
        }
        catch(InterruptedException e)
        { 
            System.err.println("Got an exception! camera-enqueue");
            System.out.print(e.getMessage());
        }
    }
}

