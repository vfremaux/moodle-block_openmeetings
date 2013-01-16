var Timer;
	 var TotalSeconds;

	 function LeadingZero(Time) {
	     return (Time < 10) ? "0" + Time : + Time;
	 }
	 function UpdateTimer() {
	     var Seconds = TotalSeconds;
	     var Days = Math.floor(Seconds / 86400);
	     Seconds -= Days * 86400;
	     var Hours = Math.floor(Seconds / 3600);
	     Seconds -= Hours * (3600);
	     var Minutes = Math.floor(Seconds / 60);
	     Seconds -= Minutes * (60);
	     var TimeStr = "Temps restant : " + ((Days > 0) ? Days + " days " : "") + LeadingZero(Hours) + "h " + LeadingZero(Minutes) + "min " + LeadingZero(Seconds) + "sec ";
	    // Timer.innerHTML = TimeStr;
	 }

	 function CreateTimer(TimerID, Time) {
	     Timer = document.getElementById(TimerID);
	     TotalSeconds = Time;
	     
	     UpdateTimer();
	     window.setTimeout("Tick()", 1000);
	 }

	 function Tick() {
	     if (TotalSeconds <= 0) {
	         alert("Il ne reste que 5 minutes de réunion. N'oubliez pas de prolonger si vous le souhaitez.");
	         return;
	     }

	     TotalSeconds -= 1;
	     UpdateTimer();
	     window.setTimeout("Tick()", 1000);
	 }