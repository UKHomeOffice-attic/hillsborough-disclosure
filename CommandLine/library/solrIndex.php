<?php

class field {
	protected $name;
	protected $value;
	
	public function __construct($name, $value = "") {
		$this->name = $name;
		$this->value = $value;
	}

	public function Name() {
		return ($this->name);
	}
		
	public function setName($name) {
		$this->name = $name;
	}

	public function setValue($value) {
		$this->value = $value;
	}
	
	public function Value() {
		return ($this->value);
	}
	
	public function toString() {
			return ("<field name=\"$this->name\">$this->value</field>\r\n");
	}
	
}

class solrIndex {
	protected $operation;	// default = 'add'
	protected $doc_boost;	// default = 0
	protected $hip_uid;
	protected $hip_location;
	protected $hip_series_title;
	protected $hip_title;
	protected $hip_format;
	protected $hip_description;
	protected $hip_series_subtitle;
	protected $hip_date;	// Not included in output if empty
	protected $hip_corporate;
	protected $hip_contrib_org;
	protected $hip_chapter;
	protected $hip_archive_ref;
	protected $hip_outofscope_reason;
	protected $hip_enddate;
	protected $hip_content;
	protected $hip_report;
	
	public function __construct() {
		$this->operation = "add";
		$this->doc_boost = 1000;
		$this->hip_uid = new field("hip_uid");
		$this->hip_location = new field ("hip_location");
		$this->hip_series_title = new field ("hip_series_title");
		$this->hip_title = new field ("hip_title");
		$this->hip_format = new field ("hip_format");
		$this->hip_description = new field ("hip_description");
		$this->hip_series_subtitle = new field ("hip_series_subtitle");
		$this->hip_date = new field ("hip_date");
		$this->hip_corporate = new field ("hip_corporate");
		$this->hip_contrib_org = new field ("hip_contrib_org");
		$this->hip_chapter = new field ("hip_chapter");
		$this->hip_archive_ref = new field ("hip_archive_ref");
		$this->hip_outofscope_reason = new field ("hip_outofscope_reason");
		$this->hip_enddate = new field ("hip_enddate");
		$this->hip_content = new field ("hip_content");
		$this->hip_report = new field ("hip_report");
	}

	public function setOperation($operation) {
		$this->operation = $operation;
	}
	
	public function setDocBoost($doc_boost) {
		$this->doc_boost = $doc_boost;
	}
	
	public function setUid($uid) {
		$this->hip_uid->setValue($uid);
	}

	public function setLocation($location) {
		$this->hip_location->setValue($location);
	}
	
	public function setSeriesTitle($series_title) {
		$this->hip_series_title->setValue($series_title);
	}
	
	public function setTitle($title) {
		$this->hip_title->setValue($title);
	}
	
	public function setFormat($format) {
		$this->hip_format->setValue($format);
	}
	
	public function setDescription($description) {
		$this->hip_description->setValue($description);
	}
	
	public function setSeriesSubtitle($series_subtitle) {
		$this->hip_series_subtitle->setValue($series_subtitle);
	}
	
	public function setDate($date) {
		$this->hip_date->setValue($date);
	}
	
	public function setCorporate($corporate) {
		$this->hip_corporate->setValue($corporate);
	}
	
	public function setContribOrg($contrib_org) {
		$this->hip_contrib_org->setValue($contrib_org);
	}
	
	public function setChapter($chapter) {
		$this->hip_chapter->setValue($chapter);
	}
	
	public function setArchiveRef($archive_ref) {
		$this->hip_archive_ref->setValue($archive_ref);
	}
	
	public function setOutofscopeReason($outofscope_reason) {
		$this->hip_outofscope_reason->setValue($outofscope_reason);
	}
	
	public function setEndDate($enddate) {
		$this->hip_enddate->setValue($enddate);
	}
	
	public function setContent($content) {
		$this->hip_content->setValue($content);
	}
	
	public function setReport($report) {
		$this->hip_report->setValue($report);
	}
	
	public function writeToFile($filename) {
	$handle = fopen($filename, "wb");
	if ($handle === false) {
		// Report error writing file
		echo "Error: Unable to create file $filename\r\n";
		return false;
	}
	fwrite($handle, "<$this->operation>\r\n");
	fwrite($handle, "<doc boost=\"$this->doc_boost\">\r\n");
	fwrite($handle, $this->hip_uid->toString());
	fwrite($handle, $this->hip_location->toString());
	fwrite($handle, $this->hip_series_title->toString());
	fwrite($handle, $this->hip_title->toString());
	fwrite($handle, $this->hip_format->toString());
	fwrite($handle, $this->hip_description->toString());
	fwrite($handle, $this->hip_series_subtitle->toString());
	if (strlen($this->hip_date->Value()) > 0) {
		fwrite($handle, $this->hip_date->toString());
		}
	fwrite($handle, $this->hip_corporate->toString());
	fwrite($handle, $this->hip_contrib_org->toString());
	fwrite($handle, $this->hip_chapter->toString());
	fwrite($handle, $this->hip_archive_ref->toString());
	fwrite($handle, $this->hip_outofscope_reason->toString());
	if (strlen($this->hip_date->Value()) > 0) {
		fwrite($handle, $this->hip_enddate->toString());
		}
	fwrite($handle, $this->hip_content->toString());
	fwrite($handle, $this->hip_report->toString());
	fwrite($handle, "</doc>\r\n");
	fwrite($handle, "</$this->operation>\r\n");
	fclose($handle);
	}
	
}

?>