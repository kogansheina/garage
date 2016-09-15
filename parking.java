package garage;

import java.util.Scanner;

public class parking
{
    parking()
    {
        CommandObject cmdobj = new CommandObject();
        Scanner scanner = new Scanner(System.in);
        command cmd = new command(cmdobj);
        Thread threadMain = new Thread(cmd);
        threadMain.start();
        try
        {
            Thread.sleep(1); /* 1 msec */
        }
        catch (InterruptedException e)
        {
            System.err.println("Got an exception! parking - main");
            System.err.println(e.getMessage());
        }
        // run 'settings' command, for the default parameters
        cmdobj.setCommand("setting");
        synchronized(cmdobj)
        {
            cmdobj.notify();
        }
        // wait until the parking size is defined from the file
        try
        {
            while (cmd.queueSize == 0)
                Thread.sleep(1); /* 1 msec */
        }
        catch (InterruptedException e)
        {
            System.err.println("Got an exception! parking - wait settings");
            System.err.println(e.getMessage());
        }
        // run 'reserve' command to create the periodic thread
        cmdobj.setCommand("reserve");
        synchronized(cmdobj)
        {
            cmdobj.notify();
        }
        try
        {
            // and wait until it runs once
            while (!cmdobj.ready)
                Thread.sleep(1); /* 1 msec */
        }
        catch (InterruptedException e)
        {
            System.err.println("Got an exception! parking - wait ready");
            System.err.println(e.getMessage());
        }
        // run 'go' command to create the entry and exit threads
        cmdobj.setCommand("go");
        synchronized(cmdobj)
        {
            cmdobj.notify();
        }
        try
        {
            Thread.sleep(1); /* 1 msec */
        }
        catch (InterruptedException e)
        {
            System.err.println("Got an exception! parking - wait go");
            System.err.println(e.getMessage());
        }
        // wait for interactive commands
        System.out.println("Commands");
        // first string is a command, the next strings in line are parameters
        while (true)
        {
            String c = scanner.nextLine();
            if (c.equals("exit") || c.equals("quit"))
            {
                cmdobj.setCommand("stop");
                synchronized(cmdobj)
                {
                    cmdobj.notify();
                }
                System.exit(0);
            }
            String[] tokens = c.split(" ");
            cmdobj.setCommand(tokens[0]);
            for (int i=1; i < tokens.length; i++)
            {
                cmdobj.setParameter(tokens[i]);
            }
            synchronized(cmdobj)
            {
                cmdobj.notify();
            }
        }
    }
    public static void main(String[] args) 
    {
        new parking();
    }
}
