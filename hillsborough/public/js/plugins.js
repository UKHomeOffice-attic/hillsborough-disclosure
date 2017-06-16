$(document).ready(function() {
				
	$(".expand-transcript").click(function() {
		$(this).parent().next(".transcript").css("height", "auto");
		return false;
	});			
				
	$(".sub-series").css("display", "none");
	$("section.browse:has(.sub-series)").removeClass("open");
			
	$(".backbutton").append('&lt; <a href="#">Back to previous page</a>');
	$(".backbutton a").attr("href", "javascript:history.back()");
				
	
	/*
	$("section.browse:has(article) h1 a:not(article a)").click(function() {
		alert("part1");

 		var sectionid = $(this).attr("id");

		if ($("#sub-" + sectionid).is(":visible")) {
			$("#sub-" + sectionid).slideUp();
			//$(this).parents("section").removeClass("open");
			
			//alert($(this).parents("section").class);
			//var node = $(this).parent().parent().parent();
			$(this).parent().parent().parent().removeClass("open");
		} else {
			$("#sub-" + sectionid).slideDown();
		}
		
		return false;
	});						  
	*/
	
	// New folder icon for processing
	$("section.browse:has(article) a:not(article a)").click(function() {
		
		var sectionid = $(this).attr("id");
		if (sectionid.substring(0,1)=="f")
			sectionid = sectionid.substring(1);
		
		if ($("#sub-" + sectionid).is(":visible"))
		{
            $("#sub-" + sectionid).slideUp(300, function(){
                $(this).parents("section").removeClass("open");
            });
		}
		else
		{
            $(this).parents("section").addClass("open");
            $("#sub-" + sectionid).slideDown(300, function(){
                $("#sub-" + sectionid).visible = true;
                $("#sub-" + sectionid).css("overflow", "hidden");
            });
		}
				
		return false;
	});						  
	
	
	/*
	$("form").submit(function()
	{
		//search form has been submitted and JS enabled so try add upper case query 
		//alert("hello");
		var q = $("#q");
		if (q.val()!="")
		{
			//;
		}
		
	});
	*/

	$("#outofscope, #organisation").change(function() 
	{
		var changeOrg = $("#organisation").val();
		var changeOutOfScope = $("#outofscope").val();
		
		if (changeOrg != "" && changeOutOfScope != "")
		{			
			window.location = "/catalogue/index/organisation/" + changeOrg + "/outofscope/" + changeOutOfScope + "/perpage/20/page/1.html";
		}
	});

	if ($.browser.msie && $.browser.version < 9) 
	{
		var el;
		
		$("select")
		  .each(function() {
		    el = $(this);
		    el.data("origWidth", el.outerWidth()); // IE 8 can haz padding
		  })
		  .focus(function(){
		    $(this).css("width", "auto");
		  })
		  .bind("blur change", function(){
		    el = $(this);
		    el.css("width", el.data("origWidth"));
		  })
	    .bind('blur', function(){
			    el = $(this);
		    el.css("width", el.data("origWidth"));
		  });
		
	}	
	
});

/* 
 *   Google tracking info here for suppression until needed
 */

var ga_tc = 'UA-31561646-1';
var ga_domain = 'google-analytics.com';
