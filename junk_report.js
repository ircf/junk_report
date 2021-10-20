rcube_webmail.prototype.rcmail_junk_report_move = function(uid) {

	//V1 : only work when in the web console and when in a box, a mail remains selected in "click and drop" at the end of the script
	/*
	rcmail.command('list', 'Junk');
	rcmail.env.uid = uid;
	setTimeout(() => {
		str = ".subject a[href*=\"uid="+uid+"\"]"
		$(str).closest('.message').trigger('mousedown').trigger('mouseup');
		rcmail.command('plugin.markasjunk2.not_junk', 'Junk');
	}, 2000);
	*/

	//V2 : 'list' refresh the page so the move doesn't execute
	/*
	setTimeout(function() {
		rcmail.command('list', 'Junk');
		rcmail.env.uid = uid;
		console.log("list");
		setTimeout(function() {
			console.log("click");
        		str = ".subject a[href*=\"uid="+uid+"\"]";
	        	$(str).closest('.message').trigger('mousedown').trigger('mouseup');
			rcmail.command('plugin.markasjunk2.not_junk', 'Junk');
			console.log("move");
		}, 1000);
	}, 1000);
	*/

	//V3 : works at times 	?but if the page is refreshed, it no longer works?
/*
	rcmail.env.uid = uid;

	rcmail.addEventListener('init', function() {
		console.log("eventListener");
		setTimeout(function() {
			uid =rcmail.env.uid;
                	console.log(uid);
                	str = ".subject a[href*=\"uid="+uid+"\"]";
                	//$(str).closest('.message').trigger('mousedown').trigger('mouseup');
                	rcmail.command('plugin.markasjunk2.not_junk', 'Junk');
                	console.log("have been moved");
                }, 1000);
	});

	setTimeout(function() {
		rcmail.command('list', 'Junk');
		console.log(rcmail.message_list);
	}, 2000);
*/
	//V4

	//rcmail.env.uid = uid;

	//setTimeout(function() { rcmail.command('plugin.markasjunk2.not_junk', 'Junk'); }, 100);

	//Only afterlist and beforelist triggered
/*
	//rcmail.addEventListener('listupdate', function() { window.alert("listupdate"); });
	//rcmail.addEventListener('', function() { window.alert(""); });
	//rcmail.addEventListener('send', function() { window.alert("beforesend"); });
	//rcmail.addEventListener('selectfolder', function() { window.alert("selectfolder"); });
	//rcmail.addEventListener('insertrow', function() { window.alert("insertrow"); });
	//rcmail.addEventListener('responseafterlist', function() { window.alert("responseafterlist"); });
	//rcmail.addEventListener('select', function() { window.alert("select"); });
	//rcmail.addEventListener('load', function() { window.alert("load"); });
	//rcmail.addEventListener('unload', function() { window.alert("unload"); });
	//rcmail.addEventListener('responseafterrefresh', function() { window.alert("responseafterrefresh"); });
	rcmail.addEventListener('beforelist', function() {
			//window.alert("afterlist");
                        uid = rcmail.env.uid;
			console.log(uid);
                        //window.alert(uid);
                        str = ".subject a[href*=\"uid="+uid+"\"]";
                        //$(str).closest('.message').trigger('mousedown').trigger('mouseup');
			setTimeout(function() {rcmail.command('plugin.markasjunk2.not_junk', 'Junk');});
			//rcmail.command('plugin.markasjunk2.not_junk', 'Junk');
			console.log("after move");
		}
	);
	//rcmail.addEventListener('beforelist', function() { window.alert("beforelist"); });
	//rcmail.addEventListener('show-list', function() { window.alert("show-list"); });
*/

	rcmail.addEventListener('init', function() {

		cmd = setTimeout(function() {
			rcmail.command('plugin.markasjunk2.not_junk');
		}, 1000);
		redirect = setTimeout(function() {
			document.location.href="https://mail1.ircf.fr/?_task=mail&_mbox=Junk";
		}, 6000);
	});
}
