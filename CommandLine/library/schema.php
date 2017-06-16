<?php

// import file schema

// format is array( csvcolumn => "mysql_field_title )

// lextranet. changes a lot :(							  
							
$lextranet = array(	0 => "begin_doc_id",
					1 => "short_title",
					2 => "archive_ref_id",
					3 => "owning_organisation",
					4 => "series_title",
					5 => "series_sub_title",
					6 => "description",
					7 => "date_start",
					8 => "date_end",
					9 => "research_significance",
					10 => "out_of_scope_reason",
					11 => "duplicate",
					12 => "passed_panel_review",
					13 => "owners_qa_by",
					14 => "ready_for_panel",
					15 => "for_public_disclosure",
					16 => "catalogue_level",
					17 => "format",
					18 => "related_material" );

// br consult
$brconsult = array( 0 => "begin_doc_id",
				   	1 => "av_box_file_id",
					2 => "archive_ref_id",
					3 => "series_title",
					4 => "series_sub_title",
					5 => "av_sub_sub_series_title",
					6 => "short_title",
					7 => "date_start",
					8 => "date_end",
					9 => "owning_organisation",
					10 => "description",
					11 => "av_related_materials",
					12 => "av_event",
					13 => "format" );


// autopopulate lookups
$autopopulate = array( 0 => "full_title",
					   1 => "presentation_format",
					   2 => "lookup_variants",
					   3 => "url_name" );


// av formats to populate av stuff. might not need this here.
$av_formats = array( "Audio Cassette",
					 "video-VHS" );

/*

search index schema, for reference

<add>
<doc>
  <field name="hip_uid">PDF0001</field>
  <field name="hip_location">http://192.168.1.160/pages/a.html</field>
  <field name="hip_format">pdf</field>
  <field name="hip_content">This is some sample content.</field>
  <field name="hip_box_number">BOX001</field>
  <field name="hip_title">Witness Statement from Gate C</field>
  <field name="hip_series_title">Original Taylor Evidence</field>
  <field name="hip_series_subtitle">Submission for Third Committee</field>
  <field name="hip_contrib_org">South Yorkshire Police</field>
  <field name="hip_description">Witness Statement from Ambulance Worker from Gate C</field>
  <field name="hip_date">1987-05-03T05:30:00Z</field>
  <field name="hip_phase">During</field>
  <field name="hip_subject">Policing</field>
  <field name="hip_victim"></field>
  <field name="hip_person"></field>
  <field name="hip_corporate">Ambulance</field>
</doc>
<doc>
  <field name="hip_uid">PDF0002</field>
  <field name="hip_location">http://192.168.1.160/pages/c.html</field>
  <field name="hip_format">pdf</field>
  <field name="hip_content">This is some more sample content.</field>
  <field name="hip_box_number">BOX001</field>
  <field name="hip_title">Witness Statement from Gate C</field>
  <field name="hip_series_title">Original Taylor Evidence</field>
  <field name="hip_series_subtitle">Submission for Second Committee</field>
  <field name="hip_contrib_org">South Yorkshire Police</field>
  <field name="hip_description">Witness Statement from Member of public from Gate C</field>
  <field name="hip_date">1987-05-03T05:30:00Z </field>
  <field name="hip_phase">During</field>
  <field name="hip_subject">Policing</field>
  <field name="hip_victim"></field>
  <field name="hip_person"></field>
  <field name="hip_corporate">Ambulance</field>
</doc>
</add>
*/

?>