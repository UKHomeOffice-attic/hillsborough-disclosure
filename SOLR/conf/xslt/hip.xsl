<?xml version='1.0' encoding='UTF-8'?>

<!-- 

BRANDED XSLT FOR SOLR SEARCH RESULTS
Keith Halcrow, 19th April 2012.

 -->
<xsl:stylesheet version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform'>
<xsl:output method="html" media-type="text/html" encoding="UTF-8" indent="yes" /> 
<xsl:variable name="defaultqf">-hip_outofscope_reason:["" TO *]</xsl:variable>
<xsl:variable name="siteName" select="'Hillsborough Independent Panel'"/>
<xsl:variable name="title" select="concat('Search results')"/>
<xsl:variable name="query" select="response/lst[@name='responseHeader']/lst[@name='params']/str[@name='q']"/>
<xsl:variable name="source" select="response/lst[@name='responseHeader']/lst[@name='params']/str[@name='sourcesearch']"/>
<xsl:variable name="subquery" select="response/lst[@name='responseHeader']/lst[@name='params']/str[@name='fq']"/>
<xsl:variable name="rows" select="response/result[@name='response']/@numFound"/>
<xsl:variable name="perpage" select="response/lst[@name='responseHeader']/lst[@name='params']/str[@name='rows']"/>
<xsl:variable name="numFound" select="response/result/@numFound"/>
<xsl:variable name="start" select="response/result/@start"/>
<xsl:variable name="pages" select="ceiling($numFound div $perpage)"/>
<xsl:variable name="page" select="floor($start div $perpage)+1"/>
<xsl:variable name="lastcount" select="$start+$perpage"/>
<xsl:if test="$lastcount > $numFound"><xsl:variable name="lastcount" select="$numFound"/></xsl:if>
<xsl:variable name="searchTitle" select="'Search results'"/>	
<!-- 
<xsl:if test="$source = 'cat'"><xsl:variable name="searchTitle" select="'Catalogue search results'"/></xsl:if>
 -->
 
 
<xsl:template match='/'>
	<xsl:text disable-output-escaping="yes">&lt;!DOCTYPE html&gt;</xsl:text>

	<xsl:text disable-output-escaping="yes">
	&lt;!--[if IEMobile 7]>&lt;html class="no-js iem7"&gt;&lt;![endif]--&gt;
	&lt;!--[if lt IE 7]>&lt;html class="no-js ie6" lang="en"&gt;&lt;![endif]--&gt;
	&lt;!--[if (IE 7)&amp;!(IEMobile)]&gt;&lt;html class="no-js ie7" lang="en"&gt;&lt;![endif]--&gt;
	&lt;!--[if IE 8]&gt;&lt;html class="no-js ie8" lang="en"&gt;&lt;![endif]--&gt;
	&lt;!--[if (gte IE 9)|(gt IEMobile 7)|!(IEMobile)|!(IE)]&gt;&lt;!--&gt;&lt;html class="no-js" lang="en"&gt;&lt;!--&lt;![endif]--&gt;
	</xsl:text>
	<head>
			<title><xsl:value-of select="$title"/></title>
			<xsl:call-template name="css"/>
	</head>
	<body class="clearfix">
		<xsl:call-template name="siteheader" />
		
		<div class="clearfix">			
			<xsl:call-template name="navigationblock" />			
			<div class="content clearfix">

				<xsl:choose>
					<xsl:when test="$query != '*:*'">
<!--					<xsl:choose>
							<xsl:when test="not($subquery != '')">							
								<h1>
									<xsl:choose>
										<xsl:when test="$source='cat'">
											Catalogue search results
										</xsl:when>
										<xsl:otherwise>
											Search results
										</xsl:otherwise>
									</xsl:choose>				
								</h1>
							</xsl:when>
							<xsl:otherwise>
-->
								<h1>
									<xsl:choose>
										<xsl:when test="$source='cat'">
											Catalogue search results:
										</xsl:when>
										<xsl:otherwise>
											Search results:
										</xsl:otherwise>
									</xsl:choose>				
									<xsl:value-of select="$query"/>
								</h1>
<!--
							</xsl:otherwise>
						</xsl:choose>			
-->					
					</xsl:when>
					<xsl:otherwise>
						<h1>
							<xsl:choose>
								<xsl:when test="$source='cat'">
									Catalogue search results
								</xsl:when>
								<xsl:otherwise>
									Search results
								</xsl:otherwise>
							</xsl:choose>										
						</h1>
					</xsl:otherwise>
				</xsl:choose>			
				
				<div role="main">
				
					<xsl:if test="number($numFound) &lt; 1" >
						<p>No results found.</p>
						<p>If you can't find what you're looking for, you may like to try another method. To find more information on using our site to search for material <a href="/help/">go to our help pages</a>.</p>
					</xsl:if>
	
					<xsl:if test="number($numFound) &gt; 0" >
						<xsl:apply-templates select="response/result/doc"/>
					</xsl:if>	
					
					<div id="pagination">
						<xsl:choose>
							<xsl:when test="$pages &gt; 1">
								<div class="pagination">
									<ul>
										<xsl:choose>
											<xsl:when test="$page &gt; 1">
												<li>
													<a href="#" onClick="pageResults({$page - 1});">Previous</a>
												</li>
											</xsl:when>
										</xsl:choose> 
										
										<xsl:call-template name="loopPages">
												<xsl:with-param name="goUntil" select="$page + 7"/>
												<xsl:with-param name="currentPage" select="$page"/>
												<xsl:with-param name="startAt" select="$page - 7"/>
												<xsl:with-param name="totalPages" select="number($pages)"/>
										</xsl:call-template>
										
										<xsl:choose>
											<xsl:when test="$page &lt; $pages">
												<li>
													<a href="#" onClick="pageResults({$page + 1});">Next</a>
												</li>
											</xsl:when>
										</xsl:choose> 
									</ul>
								</div>
							</xsl:when>
						</xsl:choose> 
						<br/>
					</div>
				</div>
				<div role="complementary">
					<xsl:call-template name="editsearch" />
					<xsl:call-template name="resultsummary" />
					<xsl:call-template name="resultkey" />
					<xsl:call-template name="searchinfoblock" />
					
				</div>
			</div>
		</div>			

		<xsl:call-template name="sitefooter" />
	</body>
	<xsl:text disable-output-escaping="yes">
		&lt;/html&gt;
	</xsl:text>
</xsl:template>
  
  <xsl:template name="loopPages">
        <xsl:param name="startAt">0</xsl:param>
        <xsl:param name="goUntil">0</xsl:param>
        <xsl:param name="currentPage">0</xsl:param>
        <xsl:param name="totalPages">0</xsl:param>

		<xsl:if test="number($goUntil) &gt; number($totalPages)">
			<xsl:variable name="goUntil" select="number($totalPages)"/>
		</xsl:if>
		
		<xsl:if test="number($startAt) &lt; number(1)">
			<xsl:variable name="startAt" select="1"/>
		</xsl:if>		
		
		
        <xsl:if test="number($startAt) &lt; number($goUntil)+1">
			<xsl:if test="number($startAt) &lt; number($totalPages)+1">
        
                <xsl:choose>
                        <xsl:when test="number($startAt) = number($currentPage)">
 	<xsl:text disable-output-escaping="yes">
	&lt;li class="active"&gt;
	
	</xsl:text>
                                        <a><xsl:value-of select="$startAt"/></a>
 	<xsl:text disable-output-escaping="yes">
	&lt;/li&gt;
		</xsl:text>
	
                        </xsl:when>
                        <xsl:otherwise>
								<xsl:if test="number($startAt) &gt; number(0)">
									<li>
                                        <a href="#" onClick="pageResults({$startAt});"><xsl:value-of select="$startAt"/></a>
									</li>
								</xsl:if>									
                        </xsl:otherwise>
                </xsl:choose>
                <xsl:call-template name="loopPages">
                        <xsl:with-param name="startAt" select="$startAt + 1"/>
                        <xsl:with-param name="goUntil" select="$goUntil"/>
                        <xsl:with-param name="currentPage" select="$currentPage"/>
						<xsl:with-param name="totalPages" select="$pages"/>
                </xsl:call-template>
			</xsl:if>
		</xsl:if>
</xsl:template>

  <xsl:template match="doc">
			
	<xsl:variable name="iconimg"><xsl:value-of select="str[@name='hip_format']"/></xsl:variable>


	<xsl:element name="article">
		<xsl:attribute name="class">result</xsl:attribute>  
 	
		<header>
			<h1>
				<xsl:element name="a">
				<xsl:attribute name="href"><xsl:value-of select="str[@name='hip_location']" /></xsl:attribute>
				<xsl:value-of select="str[@name='hip_title']" />
				</xsl:element>
			</h1>
			<time>
				<xsl:if test="date[@name='hip_date'] != ''">			
					<xsl:call-template name="FormatDate">
					  <xsl:with-param name="DateTime" select="date[@name='hip_date']"/>
					</xsl:call-template>
				</xsl:if>	
			</time>
		</header>
		<xsl:element name="a">
		<xsl:attribute name="href"><xsl:value-of select="str[@name='hip_location']" /></xsl:attribute>
		<xsl:attribute name="class">
			<xsl:choose>
				<xsl:when test="$iconimg = 'Document'">pdf-icon</xsl:when>
				<xsl:when test="$iconimg = 'Unknown'">pdf-icon</xsl:when>
				<xsl:when test="$iconimg = 'Photograph'">image-icon</xsl:when>
				<xsl:when test="$iconimg = 'Video'">video-icon</xsl:when>
				<xsl:when test="$iconimg = 'Audio'">audio-icon</xsl:when>
				<xsl:when test="$iconimg = 'Page'">page-icon</xsl:when>
				<xsl:otherwise>page-icon</xsl:otherwise>
			</xsl:choose>
		</xsl:attribute> </xsl:element>

		<p>		
			<xsl:if test="str[@name='hip_description'] != ''">			
				<xsl:if test="str[@name='hip_description'] != '0'">			
					<xsl:value-of select="substring(str[@name='hip_description'], 1, 200)" />
					<xsl:if test="string-length(str[@name='hip_description']) &gt; 200">
						<xsl:element name="span">
							<xsl:attribute name="style">display:none</xsl:attribute>
							<xsl:attribute name="id">show_<xsl:value-of select="str[@name='hip_uid']"/></xsl:attribute>
							<xsl:value-of select="substring(str[@name='hip_description'], 201)" />
						</xsl:element>

						<xsl:element name="span">
							<xsl:attribute name="style">display:inline</xsl:attribute>
							<xsl:attribute name="id">show_<xsl:value-of select="str[@name='hip_uid']"/>_more</xsl:attribute>
							<xsl:text> ... </xsl:text> 
						</xsl:element>
						 
						<xsl:element name="a">
							<xsl:attribute name="href">javascript:toggleDescription('show_<xsl:value-of select="str[@name='hip_uid']"/>');</xsl:attribute>
							<xsl:attribute name="id">show_<xsl:value-of select="str[@name='hip_uid']"/>_link</xsl:attribute>
							<xsl:text>more</xsl:text>
						</xsl:element>
					</xsl:if>
					<br/>
				</xsl:if>
			</xsl:if>
		</p>

		<footer>
			<ul>
				<li><strong>Contributor: </strong> <span class="numeral"><xsl:value-of select="str[@name='hip_contrib_org']"/></span></li>
				<li><strong>Contributor ref: </strong> <span class="numeral"><xsl:value-of select="str[@name='hip_archive_ref']"/></span></li>
				<li><strong>Unique ID: </strong> <span class="numeral"><xsl:value-of select="str[@name='hip_uid']"/></span></li>
			</ul>
		</footer>

	</xsl:element>

  </xsl:template>

  <xsl:template match="*"/>
  
  <xsl:template name="FormatDate">
    <xsl:param name="DateTime" />
    <xsl:variable name="mo">
      <xsl:value-of select="substring($DateTime,6,2)" />
    </xsl:variable>
    <xsl:variable name="day">
      <xsl:value-of select="substring($DateTime,9,2)" />
    </xsl:variable>
    <xsl:variable name="year">
      <xsl:value-of select="substring($DateTime,1,4)" />
    </xsl:variable>

    <xsl:value-of select="$day"/>
    <xsl:value-of select="' '"/>
    <xsl:choose>
      <xsl:when test="$mo = '01'">January</xsl:when>
      <xsl:when test="$mo = '02'">February</xsl:when>
      <xsl:when test="$mo = '03'">March</xsl:when>
      <xsl:when test="$mo = '04'">April</xsl:when>
      <xsl:when test="$mo = '05'">May</xsl:when>
      <xsl:when test="$mo = '06'">June</xsl:when>
      <xsl:when test="$mo = '07'">July</xsl:when>
      <xsl:when test="$mo = '08'">August</xsl:when>
      <xsl:when test="$mo = '09'">September</xsl:when>
      <xsl:when test="$mo = '10'">October</xsl:when>
      <xsl:when test="$mo = '11'">November</xsl:when>
      <xsl:when test="$mo = '12'">December</xsl:when>
    </xsl:choose>
    <xsl:value-of select="' '"/>
    <xsl:value-of select="$year"/>
  </xsl:template>
  
  <xsl:template name="siteheader">
		<header role="banner" class="clearfix">
		<a href="/">
			<hgroup class="clearfix">
				<h1>Hillsborough Independent Panel</h1>
				<h2>Disclosed Material and Report</h2>
			</hgroup>
		</a>		

		<nav class="clearfix">
			<ul>
				<li><a href="/">Home</a></li>
				<li><a href="/contact-us">Contact</a></li>
				<li><a href="/glossary">Glossary</a></li>
				<li><a href="/site-map">Site map</a></li>
				<li><a href="/help">Help</a></li>
			</ul>
<form method="get" action="/search/select" role="search">
<p>
<input name="rows" type="hidden" value="10" />
<input name="fq" type="hidden" value="-hip_outofscope_reason:['' TO *]" />
<input name="q" id="q" type="text" placeholder="Search everything" autocomplete="off" list="search-list" />
<button>Search</button>
<datalist id="search-list">
	<option value="Disaster" />
	<option value="Football" />
	<option value="Hillsborough" />
	<option value="Liverpool" />
	<option value="Sheffield" />
</datalist></p>
<p  class="search-option">
<a href="/advancedsearch/">Advanced search</a></p>
</form>
		</nav>
	</header>
  </xsl:template>
  
  <xsl:template name="sitefooter">
	<footer role="contentinfo" class="clearfix">
		<nav>
			<ul>
				<li><a href="/website-accessibility">Accessibility</a></li>
				<li><a href="/terms-conditions/">Terms and conditions</a></li>
				<li><a href="/cookies/">Cookies</a></li>
				<li><a href="/open-data/">Open data</a></li>
			</ul>
		</nav>
		<small>&#169; Crown Copyright 2012</small>
	</footer>
  </xsl:template>
  
  <xsl:template name="breadcrumb">
	<div class="breadcrumb">
	<p><a href="/">Home</a> &gt; Search results
		</p>

	</div>
	<!-- <div class="clearfix"> -->
  </xsl:template>
  
  <xsl:template name="navigationblock">
	<nav role="navigation">
		<ul class="menu">
			<li><a href="/report/">The Report</a></li>
			<li><a href="/browse/">Browse the disclosed material</a></li>
			<li><a href="/catalogue/index/organisation/all/outofscope/all/perpage/20/page/1.html">Catalogue of all material considered for disclosure</a></li>
			<li><a href="/disclosure-process/">The disclosure process</a></li>
			<li><a href="/the-independent-panel/">The Independent Panel</a></li>
		</ul>
	</nav>
  </xsl:template>

  <xsl:template name="downloadreport">
		<div class="action">
			<h3><a href="/repository/report.pdf">Download the report</a> <a title="Help with PDFs" class="help" href="/help/">?</a></h3>
		</div>
  </xsl:template>

  <xsl:template name="editsearch">
	<xsl:choose>
		<xsl:when test="$source='adv' or $source='cat'">
			<div class="box">
				<p>You can return to the 
				<xsl:choose>
					<xsl:when test="$source='cat'">
						catalogue  
					</xsl:when>
					<xsl:otherwise>
						advanced 
					</xsl:otherwise>
				</xsl:choose>				
				search form to <a href="javascript:EditQuery();">edit your criteria</a>.</p>
			</div>
		</xsl:when>
	</xsl:choose>				
  </xsl:template>  
  
  
  <xsl:template name="resultsummary">
		<div class="box">

			<xsl:variable name="resulttitle" select="concat($numFound, ' results')"/>

			
			
			<ul class="tools">
				
				<xsl:choose>
					<xsl:when test="number($numFound) &lt; 1">
						<li>No results found</li>
					</xsl:when>	
					<xsl:otherwise>
						<xsl:choose>
							<xsl:when test="number($start + $perpage) &lt; number($numFound)">
								<li><xsl:value-of select="$start + 1"/>-<xsl:value-of select="$start + $perpage"/> of <xsl:value-of select="$resulttitle"/></li>
							</xsl:when>	
							<xsl:otherwise>
								<li><xsl:value-of select="$start + 1"/>-<xsl:value-of select="$numFound"/> of <xsl:value-of select="$resulttitle"/></li>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:otherwise>
				</xsl:choose>

				<li class="options">
					<strong>Show: </strong>
						<xsl:element name="a">
							<xsl:attribute name="title">10 results</xsl:attribute>  
							<xsl:attribute name="href">#</xsl:attribute>  
							<xsl:attribute name="onclick">pageResultCount(10);</xsl:attribute>  
							<xsl:choose>
								<xsl:when test="$perpage = '10'">
									<xsl:attribute name="class">selectedoptions</xsl:attribute>  
								</xsl:when>	
							</xsl:choose>
							<xsl:value-of select="'10'"/>
						</xsl:element>
						<xsl:element name="a">
							<xsl:attribute name="title">50 results</xsl:attribute>  
							<xsl:attribute name="href">#</xsl:attribute>  
							<xsl:attribute name="onclick">pageResultCount(50);</xsl:attribute>  
							<xsl:choose>
								<xsl:when test="$perpage = '50'">
									<xsl:attribute name="class">selectedoptions</xsl:attribute>  
								</xsl:when>	
							</xsl:choose>
							<xsl:value-of select="'50'"/>
						</xsl:element>
						<xsl:element name="a">
							<xsl:attribute name="title">100 results</xsl:attribute>  
							<xsl:attribute name="href">#</xsl:attribute>  
							<xsl:attribute name="onclick">pageResultCount(100);</xsl:attribute>  
							<xsl:choose>
								<xsl:when test="$perpage = '100'">
									<xsl:attribute name="class">selectedoptions</xsl:attribute>  
								</xsl:when>	
							</xsl:choose>
							<xsl:value-of select="'100'"/>
						</xsl:element>
				</li>
			</ul>
		</div>
  </xsl:template>  
  
  <xsl:template name="resultkey">
	<div class="box">
		<ul class="key">
			<li class="key-camera"><abbr>Image</abbr></li>
			<li class="key-doc"><abbr>Page</abbr></li>
			<li class="key-pdf"><abbr>PDF</abbr></li>
		</ul>
	</div>
  </xsl:template>
  
  <xsl:template name="searchinfoblock">
	<div class="box">
		<p>To make the text inside disclosed documents searchable, we have used Optical Character Recognition (OCR) software to 'read' words on the scanned images of pages. Documents may not appear in the search results if the OCR software was unable to recognise the relevant words, eg where they are hand-written, or the paper original was in poor condition.</p>
		<p>Go to <a href="/help/">Help</a> for advice on searching.</p>
	</div>
  </xsl:template>
 
  <xsl:template name="css">
	<meta name="description" content="" />
	<meta name="author" content="" />

	<!-- http://t.co/dKP3o1e -->
	<meta name="HandheldFriendly" content="True" />
	<meta name="MobileOptimized" content="320" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0;" />  

	<!-- For all browsers -->
	<link rel="stylesheet" media="screen" href="/css/style.css" />
	<link rel="stylesheet" media="print" href="/css/print.css" />
	<!-- For progressively larger displays -->
	<link rel="stylesheet" media="only screen and (min-width: 480px)" href="/css/480.css" />
	<link rel="stylesheet" media="only screen and (min-width: 768px)" href="/css/768.css" />
	<link rel="stylesheet" media="only screen and (min-width: 992px)" href="/css/992.css" />
	<link rel="stylesheet" media="only screen and (min-width: 1382px)" href="/css/1382.css" />
	<!-- For Retina displays -->
	<link rel="stylesheet" media="only screen and (-webkit-min-device-pixel-ratio: 2), only screen and (min-device-pixel-ratio: 2)" href="/css/2x.css" />

	<xsl:text disable-output-escaping="yes">
	&lt;!--[if (lt IE 9) &amp; (!IEMobile)]&gt;
	&lt;link rel="stylesheet" media="screen" href="/css/480.css" /&gt;
	&lt;link rel="stylesheet" media="screen" href="/css/768.css" /&gt;
	&lt;link rel="stylesheet" media="screen" href="/css/992.css" /&gt;
	&lt;![endif]--&gt;
	</xsl:text>
	
	<!-- Scripts -->
	<script src="/js/libs/jquery-1.5.1.min.js"></script>
	<script src="/js/libs/modernizr-custom.js"></script>
	<script src="/js/search.js"></script>
    <script src="/js/plugins.js"></script>

	<xsl:text disable-output-escaping="yes">
		&lt;!--[if (lt IE 9) &amp; (!IEMobile)]&gt;
	</xsl:text>
	<script src="/js/libs/jquery-extended-selectors.js"></script>
	<script src="/js/libs/selectivizr-min.js"></script>
	<script src="/js/libs/imgsizer.js"></script>
	<script src="/js/utils.js"></script>
	<xsl:text disable-output-escaping="yes">
		&lt;![endif]--&gt;
	</xsl:text>

	<!-- For iPhone 4 -->
	<link rel="apple-touch-icon-precomposed" sizes="114x114" href="/img/h/apple-touch-icon.png" />
	<!-- For iPad 1-->
	<link rel="apple-touch-icon-precomposed" sizes="72x72" href="/img/m/apple-touch-icon.png" />
	<!-- For iPhone 3G, iPod Touch and Android -->
	<link rel="apple-touch-icon-precomposed" href="/img/l/apple-touch-icon-precomposed.png" />
	<!-- For Nokia -->
	<link rel="shortcut icon" href="/img/l/apple-touch-icon.png" />
	<!-- For everything else -->
	<link rel="shortcut icon" href="/favicon.ico" />

	<!--iOS. Delete if not required -->
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
	<link rel="apple-touch-startup-image" href="/img/splash.png" />

	<!--Microsoft. Delete if not required -->
	<meta http-equiv="cleartype" content="on" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />

	<!-- http://t.co/y1jPVnT -->
	<link rel="canonical" href="/" />

	<script type="text/javascript">
	 	<xsl:text disable-output-escaping="yes">
			function getSearchType()
			{
				querystring = location.search;  
				key = 'sourcesearch';
				start=querystring.indexOf(key+"="); 
				if (start>=0)
				{		

					end=querystring.indexOf("&amp;", start+1); 
					if (end&lt;1)
						end = querystring.length;		
					originalparam=querystring.substring(start + key.length + 1, end);
					return originalparam;
				}
				return null;
			}
		</xsl:text>	
	</script>
 	
	<script type="text/javascript">
	 	<xsl:text disable-output-escaping="yes">
		  var _gaq = _gaq || [];
		  _gaq.push(['_setAccount', ga_tc]);
		  _gaq.push(['_trackPageview']);
		  (function() {
		    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.' + ga_domain + '/ga.js';
		    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		  })();
		
			
		function EditQuery()
		{		
			var url = '/advancedsearch/' + window.location.href.slice(window.location.href.indexOf('?'));
			if (getSearchType()=='cat')
				url = '/cataloguesearch/' + window.location.href.slice(window.location.href.indexOf('?'));
			location.href = url;
		}
		
		function toggleDescription(id)
		{
			var obj = document.getElementById(id);
			
			var isVisible = false;
			if (obj.style.display == 'inline')
				isVisible = true;

			if (!isVisible)
			{
				obj.style.display = "inline";
				document.getElementById(id + "_link").innerHTML = "less";
			}
			else
			{
				obj.style.display = "none";
				document.getElementById(id + "_link").innerHTML = "more";
			}
		}

		</xsl:text>	
		
	</script>
	
  </xsl:template>

</xsl:stylesheet>
