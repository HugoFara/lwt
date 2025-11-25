/****************************************************************************
* CountUp script by Praveen Lobo
* http://praveenlobo.com/techblog/javascript-countup-timer
* This notice MUST stay intact(in both JS file and SCRIPT tag) for legal use.
* http://praveenlobo.com/blog/disclaimer/
* 
* --- modified for LWT ---
* server_now   = Server Time Now   = php call "gmmktime()" @ now
* server_start = Server Time Start = php call "gmmktime()" @ begin
* id           = div/span-id
* dontrun      = 0/1
****************************************************************************/

function CountUp(server_now, server_start, id, dontrun) {
	if (server_now < server_start) server_start = server_now;
	this.beginSecs =  
		Math.floor(((new Date()).getTime()) / 1000)
			- server_now + server_start;
	this.dontrun = dontrun;
	this.update(id);
}
 
CountUp.prototype.update = function(id) {
	var nowSecs = Math.floor(((new Date()).getTime()) / 1000);
	var sec = nowSecs - this.beginSecs;
	var min = Math.floor(sec / 60);
	sec = sec - min*60;
	var hr = Math.floor(min / 60);
	min = min - hr*60;
	var r = '';
	if (hr > 0) {
		r += hr < 10 ? ("0" + hr) : hr;
		r += ":";
	}
	r += min < 10 ? ("0" + min) : min;
	r += ":";
	r += sec < 10 ? ("0" + sec) : sec;
	document.getElementById(id).innerHTML = r;
	if (this.dontrun) return;
	var self = this;
	setTimeout(function(){self.update(id);}, 1000);
}