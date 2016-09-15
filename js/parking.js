Enter file contents here
var Interval = 30000; // 30 seconds
var startTime;
function runDate()
{
    var currentdate = new Date(); 
    var field = document.getElementById("data");
    var datestring = daysOfWeek[currentdate.getDay()] + ", "
           + currentdate.getDate() + "/"
           + (currentdate.getMonth()+1)  + "/" 
           + currentdate.getFullYear() + " @ " + currentdate.getHours() +" H";
    field.innerHTML = datestring;
}
function start()
{
    try
    {
        runDate();
        startTime = new Date().getTime();
    }
    catch (e )
    {
        console.log(e);
    }
    runTime();
}
function timeChangeCallback()
{
    runDate();
    // retrigger
    setTimeout(timeChangeCallback, (new Date().getTime() % Interval));
}
function runTime()
{
    // get current time in msecs to nearest 30 seconds
    var msecs = new Date().getTime() % Interval;

    // wait until the timeout
    setTimeout(timeChangeCallback, 30000 - msecs);
}
