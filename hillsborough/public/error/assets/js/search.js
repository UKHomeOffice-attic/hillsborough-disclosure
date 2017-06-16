/*		
 *	search.js
 * 	Validate the user selected values and format for the form submission
 * 
 */

 
function reformatdate(origday, origmonth, origyear)
{
	// Check for only partial date
	if (origday!="-" || origmonth!="-" || origyear!="-")
	{
		// Something is selected
		if ((origday!="-" && origmonth!="-" && origyear!="-") ||			// d/m/y
			(origday=="-" && origmonth!="-" && origyear!="-") ||			// m/y
			(origday=="-" && origmonth=="-" && origyear!="-"))				// y
		{
			// All selected
			if (origday=="-") 
				origday = "*";
			if (origmonth=="-") 
				origmonth = "*";
			
			newdate = origyear + "-" + origmonth + "-" + origday;
			return newdate;
		}
		else	// Only partial select
			return "invalid_date";				
	}		
	return "";
}

function SetValidationMessage(text)
{
	document.getElementById("validationmessage").innerHTML = text;
}

function DateFixWildCardEarliest(originalDate)
{
	breakdown = originalDate.toString().split("-");
	if (breakdown[1]=="*")
		breakdown[1] = "01";
	if (breakdown[2]=="*")
		breakdown[2] = "01";
	
	return breakdown[0]+"-"+breakdown[1]+"-"+breakdown[2];
}

function DateFixWildCardLatest(originalDate)
{
	breakdown = originalDate.split("-");
	if (breakdown[1]=="*")
		breakdown[1] = "12";
	if (breakdown[2]=="*")
	{
		switch(breakdown[1])
		{
			case 	"04", 
					"06", 
					"09", 
					"11":
				breakdown[2] = "30";
				break;
			case 	"02":		
				if (((breakdown[0] % 4 == 0) && (breakdown[0] % 100 != 0)) || (breakdown[0] % 400 == 0))
					breakdown[2] = "29";
				else
					breakdown[2] = "28";
				break;
			default:
				breakdown[2] = "31";
				break;
		}
	}	
	return breakdown[0]+"-"+breakdown[1]+"-"+breakdown[2];
}

function trim (str) {
	str = str.replace(/^\s+/, '');
	for (var i = str.length - 1; i >= 0; i--) {
		if (/\S/.test(str.charAt(i))) {
			str = str.substring(0, i + 1);
			break;
		}
	}
	return str;
}

function searchsubmit()
{
	
	startTime = "T00:00:00.000Z";
	endTime = "T23:59:59.999Z";
	
	errormessage = "";
	
	querystring = "";
	error = false; 
		
	enddate = reformatdate(
			document.getElementById("enddateday").value,
			document.getElementById("enddatemonth").value, 
			document.getElementById("enddateyear").value);

	startdate = reformatdate(
			document.getElementById("startdateday").value,
			document.getElementById("startdatemonth").value, 
			document.getElementById("startdateyear").value);
	
	if (startdate=="invalid_date")
	{
		errormessage = errormessage + "<li>You must enter a) the day, month and year or b) the month and year or c) just the year for your start date.</li>";
		error= true;
	}

	if (enddate=="invalid_date")
	{
		errormessage += "<li>You must enter a) the day, month and year or b) the month and year or c) just the year for your end date.</li>";
		error= true;
	}
	
	params = "";
	if ((startdate!="")&&(startdate!="invalid_date"))
	{	
		minDate = "1920-01-01T00:00:00.000Z";
		maxDate = "2030-01-01T00:00:00.000Z";
		
		if ((enddate != "") && (enddate != "invalid_date"))		// We have an end date so use that for the end range
		{
			if (enddate < startdate)
			{
				errormessage += "<li>When specifying an end date please ensure that it is after the start date.</li>";
				error= true;
			}
			else
			{
				startdate = DateFixWildCardEarliest(startdate);
				enddate = DateFixWildCardLatest(enddate);

				startdate += startTime;
				enddate += endTime;

				params += "&fq=hip_search_end:[" + startdate + " TO " + maxDate + "] " +
					"AND " +
					"hip_search_start:[" + minDate + " TO " + enddate + "]";
			}
		}
		else	// No end date specified so look at start date wild cards to calculate
		{
			enddate = startdate;

			startdate = DateFixWildCardEarliest(startdate);
			enddate = DateFixWildCardLatest(enddate);

			startdate += startTime;
			enddate += endTime;

			params += "&fq=hip_search_end:[" + startdate + " TO " + maxDate + "] " +
				"AND " +
				"hip_search_start:[" + minDate + " TO " + enddate + "]";
		}
	}

	if (((startdate=="invalid_date")||(startdate==""))&&((enddate!="invalid_date")&&(enddate!="")))
	{
		errormessage += "<li>Before you can specify an end date, you must first provide a start date.</li>";
		error= true;
	}

	// keywords
	keywords = document.getElementById("keywords").value;
	if ((keywords==null)||(keywords=="")||(keywords=="keywords"))
		keywords="";
	
	//filters
	barcode = document.getElementById("uid").value;
	if ((barcode!=null)&&(barcode!="")&&(barcode!="unique ID"))
		params += "&fq=hip_uid:" + trim(barcode.toUpperCase()) + "*";

	orgref = document.getElementById("orgref").value;
	if ((orgref!=null)&&(orgref!="")&&(orgref!="contributor reference"))
		params += "&fq=hip_archive_ref:" + trim(orgref.toUpperCase()) + "*";

	
	organisation = document.getElementById("organisation").value;
	if (organisation!="-")
		params += "&fq=hip_contrib_org:\"" + organisation + "\"";

	person = document.getElementById("person").value;
	if (person!="-")
		params += "&fq=hip_person:\"" + person + "\"";

	victim = document.getElementById("victim").value;
	if (victim!="-")
		params += "&fq=hip_victim:\"" + victim + "\"";

	corpbody = document.getElementById("corpbody").value;
	if (corpbody!="-")
		params += "&fq=hip_corporate:\"" + corpbody + "\"";

	reportspecific = document.getElementById("onlyreport").checked;
	if (reportspecific==true)
		params += "&fq=hip_chapter:[\"\" TO *]";

	redactiontype = document.getElementById("redtype").value;
	if (redactiontype=="all") {							// both unredacted and redacted
		//Do nothing...  params += "&-fq=hip_redacted:" + redreason;
	} else if (redactiontype=="unredactedonly") {		// all unredacted
		params += "&fq=-hip_redacted:[\"\" TO *]";		
	} else if (redactiontype=="redactedonly") {			// all redacted
		params += "&fq=hip_redacted:[\"\" TO *]";
	} else {											// a specific redaction
		params += "&fq=hip_redacted:" + redactiontype;
	}
	
	if ((!error) && (params=="") && (keywords==""))
	{
		errormessage += "<li>No search criteria has been entered</li>";
		error= true;
	}
	errormessage = "<div class=\"messagedetail\">The following form errors need to be corrected:<ul>" + errormessage + "</ul></div>";
	
	if (error)
	{
		SetValidationMessage(errormessage);
		location.hash = "validationmessage";
	}
	else
	{
		// To be here we are using filters so wildcard the query
		if (keywords=="")
			keywords="*:*";
		
		params += "&fq=-hip_outofscope_reason:[\"\" TO *]";
			
// Redirect
		location.href = "/search/select?q="+keywords+params+"&rows=10";
	}	
}

function catsearchsubmit()
{
	startTime = "T00:00:00.000Z";
	endTime = "T23:59:59.999Z";
	
	errormessage = "";
	
	querystring = "";
	error = false;
		
	enddate = reformatdate(
			document.getElementById("enddateday").value,
			document.getElementById("enddatemonth").value, 
			document.getElementById("enddateyear").value);

	startdate = reformatdate(
			document.getElementById("startdateday").value,
			document.getElementById("startdatemonth").value, 
			document.getElementById("startdateyear").value);

	
	if (startdate=="invalid_date")
	{
		errormessage = errormessage + "<li>You must enter a) the day, month and year or b) the month and year or c) just the year for your start date.</li>";
		error= true;
	}

	if (enddate=="invalid_date")
	{
		errormessage += "<li>You must enter a) the day, month and year or b) the month and year or c) just the year for your end date.</li>";
		error= true;
	}

	if (((startdate=="invalid_date")||(startdate==""))&&((enddate!="invalid_date")&&(enddate!="")))
	{
		errormessage += "<li>Before you can specfy an end date, you must first provide a start date.</li>";
		error= true;
	}

	params = "";
	keywords = "";
	

	//Process dates
	if ((startdate!="")&&(startdate!="invalid_date"))
	{
		minDate = "1920-01-01T00:00:00.000Z";
		maxDate = "2030-01-01T00:00:00.000Z";
		
		if ((enddate != "") && (enddate != "invalid_date"))		
		{
			if (enddate < startdate)
			{
				errormessage += "<li>When specifying an end date please ensure that it is after the start date.</li>";
				error= true;
			}
			else
			{
				startdate = DateFixWildCardEarliest(startdate);
				enddate = DateFixWildCardLatest(enddate);

				startdate += startTime;
				enddate += endTime;

				params += "&fq=hip_search_end:[" + startdate + " TO " + maxDate + "] " +
					"AND " +
					"hip_search_start:[" + minDate + " TO " + enddate + "]";
			}
		}
		else	
		{
			enddate = startdate;

			startdate = DateFixWildCardEarliest(startdate);
			enddate = DateFixWildCardLatest(enddate);

			startdate += startTime;
			enddate += endTime;

			params += "&fq=hip_search_end:[" + startdate + " TO " + maxDate + "] " +
				"AND " +
				"hip_search_start:[" + minDate + " TO " + enddate + "]";

		}
	}
	
	//What are we searching?
	scopeFilter = "";
	oosreason = document.getElementById("oosreason").value; 
	if (oosreason == "") {					// disclosed only
		scopeFilter = "&fq=-hip_outofscope_reason:[\"\" TO *]&fq=hip_contrib_org:[\"\" TO *]";
	} else if (oosreason == "all") {		// all
		scopeFilter = "&fq=hip_contrib_org:[\"\" TO *]";
	} else if (oosreason == "*") {			// all undisclosed
		scopeFilter = "&fq=hip_outofscope_reason:[\"\" TO *]";
	} else {								// filter on an oos reason
		scopeFilter = "&fq=hip_outofscope_reason:" + oosreason;
	}
	
	params += scopeFilter;
	
	// keywords
	keywords = document.getElementById("keyword").value;
	if ((keywords==null)||(keywords=="")||(keywords=="keywords"))
		keywords="";

	//filters
	barcode = document.getElementById("uid").value;
	if ((barcode!=null)&&(barcode!="")&&(barcode!="unique ID"))
		params += "&fq=hip_uid:" + trim(barcode.toUpperCase()) + "*";

	orgref = document.getElementById("orgref").value;
	if ((orgref!=null)&&(orgref!="contributor reference")&&(orgref!=""))
		params += "&fq=hip_archive_ref:" + trim(orgref.toUpperCase()) + "*";
	
	organisation = document.getElementById("organisation").value;
	if (organisation!="-")
		params += "&fq=hip_contrib_org:\"" + organisation+"\"";
	
	if ((!error) && (params=="") && (keywords==""))
	{
		errormessage += "<li>No search criteria has been entered</li>";
		error= true;
	}
	errormessage = "<div class=\"messagedetail\">The following form errors need to be corrected:<ul>" + errormessage + "</ul></div>";
	
	if (error)
	{
		SetValidationMessage(errormessage);
		location.hash = "validationmessage";
	}
	else
	{
		// To be here we are using filters so wildcard the query
		if (keywords=="")
			keywords="*:*";
		
		// Redirect
		location.href = "/search/select?q="+keywords+params+"&rows=10&sourcesearch=cat";
	}	
}

/***************************************************************************
    SEARCH RESULTS MANIPULATION
***************************************************************************/
	
function getParam(querystring, key)
{
	start=querystring.indexOf(key+"="); 
	if (start>=0)
	{		
		end=querystring.indexOf("&", start+1); 
		if (end<1)
			end = querystring.length;		
		originalparam=querystring.substring(start + key.length + 1, end); 	
		return originalparam;
	}
	return null;
}	

function setParam(querystring, key, value)
{
	start=querystring.indexOf(key+"="); 
	if (start>=0)
	{		
		end=querystring.indexOf("&", start+1); 
		
		if (end<1)
			end = querystring.length;
			
		originalparam=querystring.substring(start, end); 
		
		
		return querystring.replace(originalparam, key+"="+value);
	}
	return querystring+"&"+key+"="+value;
}	
	
function pageResults(startResult) 
{ 
	querystring = location.search;  
	rows=getParam(querystring, "rows");
	if (rows==null)
		rows=10;
	startResult = ((startResult - 1) * parseInt(rows));
	querystring = location.search;  
	querystring = setParam(querystring, "start", startResult);
	location.search = querystring;  
}
	
function pageResultCount(perpage) 
{ 
	querystring = location.search;  
	querystring = setParam(querystring, "rows", perpage);
	querystring = setParam(querystring, "start", 0);
	location.search = querystring;  
}

