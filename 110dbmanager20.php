<?php
/*
 PHP Mini MySQL Admin
 (c) 2004-2007 Oleg Savchuk <osa@viakron.com>
 (c) 2007 110mb.com (110DB Manager is 90% phpminiadmin, and all extra features are part of 110mb.com
 Charset support - thanks to Alex Didok http://www.main.com.ua

 Light standalone PHP script for easy access MySQL databases.
 http://phpminiadmin.sourceforge.net
*/

 $ACCESS_PWD=''; #script access password, SET IT if you want to protect script from public access

 #DEFAULT db connection settings
 $DB=array(
 'user'=>"",#required
 'pwd'=>"", #required
 'db'=>"",  #default DB, optional
 'host'=>"",#optional
 'port'=>"",#optional
 'chset'=>"",#default charset, optional
 );

//constants
 $VERSION='1.3.070213';
 $MAX_ROWS_PER_PAGE=50; #max number of rows in select per one page
 $is_limited_sql=0;
 $self=$_SERVER['PHP_SELF'];

 session_start();

//for debug set to 1
 ini_set('display_errors',0);
// error_reporting(E_ALL ^ E_NOTICE);

//strip quotes if they set
 if (get_magic_quotes_gpc()){
  $_COOKIE=array_map('killmq',$_COOKIE);
  $_REQUEST=array_map('killmq',$_REQUEST);
 }

 if (!$ACCESS_PWD) {
    $_SESSION['is_logged']=true;
    loadcfg();
 }

 if ($_REQUEST['login']){
    if ($_REQUEST['pwd']!=$ACCESS_PWD){
       $err_msg="Invalid password. Try again";
    }else{
       $_SESSION['is_logged']=true;
       loadcfg();
    }
 }

 if ($_REQUEST['logoff']){
    $_SESSION = array();
    session_destroy();
	setcookie("conn[db]",  FALSE,-1);
    setcookie("conn[user]",FALSE,-1);
    setcookie("conn[pwd]", FALSE,-1);
    setcookie("conn[host]",FALSE,-1);
    setcookie("conn[port]",FALSE,-1);
    setcookie("conn[chset]",FALSE,-1);
    $url=$self;
    if (!$ACCESS_PWD) $url='/';
    header("location: $url");
	exit;
 }

 if (!$_SESSION['is_logged']){
    print_login();
    exit;
 }

 if ($_REQUEST['savecfg']){
    savecfg();
 }

 loadsess();

 if ($_REQUEST['showcfg']){
    print_cfg();
    exit;
 }
 //get initial values
 $SQLq=trim($_REQUEST['q']);
 $page=$_REQUEST['p']+0;
 if ($_REQUEST['refresh'] && $DB['db'] && !$SQLq) $SQLq="show tables";

 if (db_connect('nodie')){
    $time_start=microtime_float();
   
    if ($_REQUEST['phpinfo']){
       ob_start();phpinfo();$sqldr=ob_get_clean();
    }else{
     if ($DB['db']){
      if ($_REQUEST['shex']){
//*************Added code section 9 start **************************************
//this section allow export tables selected by user
       if($_REQUEST['texp']){
	   //drop session variables for tables export if exists
    	if($_SESSION["texp"]){
			unset($_SESSION["tables"]);
			unset($_SESSION["texp"]);
		}
    	$_SESSION["tables"] = $_REQUEST["Tables"];
		$_SESSION["texp"] = 1;
	   }
//*************Added code section 9 end **************************************	  	   
	   print_export();
      }elseif ($_REQUEST['doex']){
       do_export();
      }elseif ($_REQUEST['shim']){
       print_import();
      }elseif ($_REQUEST['doim']){
       do_import();
//*************Added code section 5 start **************************************
// first else if statement need for check request variable crtb - ñreate table if it is present in the scope, the function for
// displaying create table functionality called
      }elseif ($_REQUEST['crtb']){
	   do_create_table();
// second else if statement need for check request variable ccrtb - complete ñreate table if it is present in the scope, the function for preparing create table statement according to selected parameters called
      }elseif ($_REQUEST['ccrtb']){
	   complete_create_table();
//*************Added code section 5 end **************************************
      }elseif (!$_REQUEST['refresh'] || preg_match('/^select|show|explain/',$SQLq) ) perform_sql($SQLq,$page);  #perform non-selet SQL only if not refresh (to avoid dangerous delete/drop)
     }else{
        $err_msg="Select DB first";
     }
    }
    $time_all=ceil((microtime_float()-$time_start)*10000)/10000;
   
    print_screen();
 }else{
    print_cfg();
 }

//**************** functions

//*****************Added code section 4 start*****************  
function complete_create_table(){
	//get necessary variables from request scope, for correct "create table" statement creation.
	$crt_stmt = "";
	$table_name = $_REQUEST["crtb_name"];
	$fields_amount = $_REQUEST["fields_amount"];
	$field_name = $_REQUEST["field_name"];
	$field_type = $_REQUEST["field_type"];
	$field_length = $_REQUEST["field_length"];
	$field_attribute = $_REQUEST["field_attribute"];
	$field_null = $_REQUEST["field_null"];
	$field_default = $_REQUEST["field_default"];
	$field_extra = $_REQUEST["field_extra"];
	$field_fulltext = $_REQUEST["field_fulltext"];
	
	// Arrays of kinds of indexes
	$Pkeys = array();
	$Indexes = array();
	$Uniques = array();
	$Fulltext = array();
	// CREATE TABLE statement creation		
	$crt_stmt .= "CREATE TABLE`".$table_name."`(\n";
	for($i=0;$i<$fields_amount;$i++){
		$crt_stmt .= "`".$field_name[$i]."` ".$field_type[$i]."( ".$field_length[$i]." ) ".
						$field_attribute[$i]." ".$field_null[$i];
		if($field_extra[$i] == "AUTO_INCREMENT"){
			if($_REQUEST["field_key_".$i] == 'primary'){
				$crt_stmt .= " AUTO_INCREMENT PRIMARY KEY ";
			}else{
				$crt_stmt .= " AUTO_INCREMENT ";
			}
			if(in_array($i,$field_fulltext)) $Fulltext[] = $field_name[$i];			
		}else{
			if(!empty($field_default[$i])) $crt_stmt .= " DEFAULT '".$field_default[$i]."'";
			if($_REQUEST["field_key_".$i] == 'primary') $Pkeys[] = $field_name[$i];
			if($_REQUEST["field_key_".$i] == 'index') $Indexes[] = $field_name[$i];
			if($_REQUEST["field_key_".$i] == 'unique') $Uniques[] = $field_name[$i];
			if(in_array($i,$field_fulltext)) $Fulltext[] = $field_name[$i];
		}
		$crt_stmt .= ",\n";
	}
	if(!empty($Pkeys)) $crt_stmt .= "PRIMARY KEY ( `".implode("`,`", $Pkeys)."` ) ,\n";
	if(!empty($Indexes)) $crt_stmt .= "INDEX ( `".implode("`,`", $Indexes)."` ) ,\n";	
	if(!empty($Uniques)) $crt_stmt .= "UNIQUE ( `".implode("`,`", $Uniques)."` ) ,\n";
	if(!empty($Fulltext)) $crt_stmt .= "FULLTEXT ( `".implode("`,`", $Fulltext)."` ) ,\n";	
	
	$crt_stmt = substr($crt_stmt, 0, -2)."\n) TYPE = ".strtoupper($_REQUEST["tbl_type"])." COMMENT = '".$_REQUEST["comment"]."';";
	perform_sql($crt_stmt);	
}

//function for completion processing according to selected action 
function with_selected_action($act,$tables,$data_base = null){
	$q = "";
	if(empty($tables)) return "show tables";

	switch($act){
		case "Drop": $q = "DROP TABLE ".implode(",", $tables);break;
		
		case "Empty": $q .= "TRUNCATE TABLE ".implode(";TRUNCATE TABLE ", $tables);break;
		
		case "Optimize": $q = "OPTIMIZE TABLE ".implode(",", $tables);break;
		
		case "Repair": $q = "REPAIR TABLE ".implode(",", $tables);break;
		
		default: $q = "";
	}
	return $q;
}
//*****************Added code section 4 end*****************  
function perform_sql($q, $page=0){
 global $dbh, $DB, $out_message, $sqldr, $reccount, $MAX_ROWS_PER_PAGE, $is_limited_sql;
 $rc=array("o","e");
 $dbn=$DB['db'];

//added variable
 $tabl_name="";
 $with_selected = $_REQUEST["with_selected"];
 $tables = $_REQUEST["Tables"];
 $data_base = $_REQUEST["db"];
 $data_export = "";
//end added variables 
//multiple queries processing
 if($with_selected != "") $q = with_selected_action($with_selected,$tables,$data_base);
 $sql_querys = split(';', $q);
 foreach($sql_querys as $q){
//end mult processing 
	 if (preg_match("/^select|show|explain|optimize|repair/i",$q)){ //added 2 additional command optimize|repair
		$sql=$q;
	    $is_show_tables=($q=='show tables');
    	$is_show_crt=(preg_match('/^show create table/i',$q));

	    if (preg_match("/^select/i",$q) && !preg_match("/limit +\d+/i", $q)){
    	   $offset=$page*$MAX_ROWS_PER_PAGE;
	       $sql.=" LIMIT $offset,$MAX_ROWS_PER_PAGE";
    	   $is_limited_sql=1;
	    }
		$sth=db_query($sql, 0, 'noerr');
	    if($sth==0){
    	   $out_message = "Error ".mysql_error($dbh);
	    }else{
    	   $reccount=mysql_num_rows($sth);
	       $fields_num=mysql_num_fields($sth);
 
    	   $w="width='100%' ";
		   
	       if ($is_show_tables) $w='';
		   
    	   $sqldr="<table border='0' cellpadding='1' cellspacing='1' $w class='res'>";
	       $headers="<tr class='h'>";
    	   
		   for($i=0;$i<$fields_num;$i++){
        	  $meta=mysql_fetch_field($sth,$i);
	          $fnames[$i]=$meta->name;

			  if ($is_show_tables) $headers.="<th>&nbsp;&nbsp;</th>"; //add empty header cell for check box
		  
	          $headers.="<th ";
    		  $headers.=">&nbsp;$fnames[$i]&nbsp;&nbsp;</th>";	
    	   }
	       if ($is_show_tables) 
/*	   						$headers.="<th>&nbsp;Show Create Table&nbsp;</th>
	   								   <th>&nbsp;Explain&nbsp;</th>
									   <th>&nbsp;Indexes&nbsp;</th>
									   <th>&nbsp;Export&nbsp;</th>
									   <th>&nbsp;Drop&nbsp;</th>
									   <th>&nbsp;Truncate&nbsp;</th>";
*/
//header changed according to new requirements									   
		   $headers.= "<th colspan='4'>Actions</th>
					  <th class='ah'>&nbsp;&nbsp;Size&nbsp;&nbsp;</th>
					  <th class='ah'>&nbsp;&nbsp;Overhead&nbsp;&nbsp;</th>
					  <th class='ah'>&nbsp;&nbsp;Records&nbsp;&nbsp;</th>
					  <th class='ah'>&nbsp;&nbsp;Type&nbsp;&nbsp;</th>";	   	   	   	   
    	   $headers.="</tr>\n";
	       $sqldr.=$headers;
    	   $swapper=false;
	       while($hf=mysql_fetch_assoc($sth)){
    	     if ($is_show_tables) $sqldr.="<tr class='e'>";
			 else $sqldr.="<tr class='".$rc[$swp=!$swp]."'>";
	
	         for($i=0;$i<$fields_num;$i++){
    	        $v=$hf[$fnames[$i]];
				$more='';
    			$tabl_name = $v;			
			//get additional info for table
				$tmp_sql = "SHOW TABLE STATUS FROM $dbn like '$v'";
				$tmp_sth = db_query($tmp_sql, 0, 'noerr');
				$tmp_hf = mysql_fetch_assoc($tmp_sth);
				$table_size = round($tmp_hf['Index_length']/1024,2). " KB";
				$table_overheads = round($tmp_hf['Data_free']/1024,2). " KB";
				$table_records = $tmp_hf["Rows"];
				!empty($tmp_hf["Type"]) ? $table_type = $tmp_hf["Type"] : $table_type = $tmp_hf["Engine"];
            //get additional info for table	end
				if ($is_show_tables && $i==0 && $v){
				   $v="<a href=\"?db=$dbn&q=select+*+from+$v\">$v</a>".
    	           $more="<td><div align=\"center\"><font size=\"1\"><a href=\"?db=$dbn&q=show+create+table+$v\">Show Create Table</a>		</font></div></td>"
        	       ."<td><div align=\"center\"><font size=\"1\"><a href=\"?db=$dbn&q=explain+$v\">Explain</a></font></div></td>"
            	   ."<td><div align=\"center\"><font size=\"1\"><a href=\"?db=$dbn&q=show+index+from+$v\">Indexes</a></font></div>	</td>"
//comented because no need on current configuration			   
//               ."<td><div align=\"center\"><font size=\"1\"><a href=\"?db=$dbn&shex=1&t=$v\">Export</a></font></div></td>"
//               ."<td><div align=\"center\"><font size=\"1\"><a href=\"?db=$dbn&q=drop+table+$v\" onclick='return ays()'>Drop</a></font></div></td>"
	               ."<td><div align=\"center\"><font size=\"1\"><a href=\"?db=$dbn&q=truncate+table+$v\" onclick='return ays()'>Truncate</a></font></div></td>"
    			   ."<td class='ae'><div align=\"center\"><font size=\"1\">$table_size</font></div></td>"
    			   ."<td class='ae'><div align=\"center\"><font size=\"1\">$table_overheads</font></div></td>"
    			   ."<td class='ae'><div align=\"center\"><font size=\"1\">$table_records</font></div></td>"
	    		   ."<td class='ae'><div align=\"center\"><font size=\"1\">$table_type</font></div></td>";			   			   	            }
        	
			    if ($is_show_crt) $v="<pre>$v</pre>";
            	//add checkbox before table name, check appropriate checkboxes if the table has been selected
				if ($is_show_tables){
				   $checked = "";
				   if(in_array($tabl_name, $_REQUEST["Tables"])) $checked = "checked='checked'";
				   $sqldr.="<td><input id='".$hf[$fnames[$i]]."' type='checkbox' value='".$hf[$fnames[$i]]."' name='Tables[]' $checked/></td>";
				}
				$sqldr.="<td>$v".(!v?"<br />":'')."</td>";
        	 }
	         $sqldr.="</tr>\n";
    	   }
		   
		   // show check box with select unselect option
		   if ($is_show_tables){
			   //add select/unselect all  checkbox
			   $sqldr.="<tr>\n";
			   $sqldr.="<td colspan='10'><br>\n";
			   if($_REQUEST["c_all"] == "on") $c_all = "checked='checked'";
			   $sqldr.="<input name='c_all' type='checkbox' onClick='Select_Unselect_All(this.checked)' $c_all> Select/Unselect all\n";
			   $sqldr.="</td>\n";
			   $sqldr.="</tr>\n";
				//add actions selector for checked table/s
			   $sqldr.="<tr>\n";
			   $sqldr.="<td colspan='10'><br>\n";
			   $sqldr.="With Selected: \n";
			   $sqldr.="<select name='with_selected' onChange='Selected_Action(this.value)'>\n";
		   
			   $select_options = array("", "Export", "Drop", "Empty","Optimize","Repair");
			   foreach($select_options as $value){
				   $selected = "";
				   if($value == "") $selected = "selected='selected'";
				   $sqldr.="<option value='".$value."' $selected>".$value."</option>\n";		   	
			   }
			   $sqldr.="</select>\n";
			   $sqldr.="</td>\n";
			   $sqldr.="</tr>\n";
				//add create table functionallity
   			   $sqldr.="<tr>\n";
			   $sqldr.="<td colspan='10'><br>\n";
			   $sqldr.="Create New Table: <input type='text' id='crtb_name' name='crtb_name' value=''>&nbsp;";
			   $sqldr.="with this many fields inside it: <input type='text' id='fields_amount' name='fields_amount' value='' size='7'>&nbsp;&nbsp;";
			   $sqldr.="<input type='button' name='crt' value='Create' onClick='do_crtb()'>\n";			   			   
			   $sqldr.="</td>\n";
			   $sqldr.="</tr>\n";

		   }
    	   $sqldr.="</table>\n";
	    }
	 }elseif (preg_match("/^update|insert|replace|delete|drop|truncate|alter|create/i",$q)){
	    $sth = db_query($q, 0, 'noerr');
    	if($sth==0){
	       $out_message="Error ".mysql_error($dbh);
		   
		   if($_REQUEST["ccrtb"] == 1) {
		   		unset($_REQUEST["ccrtb"]);
				$_REQUEST['crtb'] = 1;
			   	foreach($_REQUEST as $key => $value){
				   $_SESSION[$key] = $value;
		   		}
				unset($_SESSION["ccrtb"]);
		   		$out_message .= "&nbsp;&nbsp;&nbsp;[<a href='#' onclick=\"edit_table();\">Edit table</a>]";// added edit EDIT table link in case eny error in create table statement because of incorrect parameter selection
		   }
    	}else{
	       $reccount=mysql_affected_rows($dbh);
    	   $out_message="Done.";
		   if (preg_match("/^insert|replace/i",$q)) $out_message.=" New inserted id=".get_identity();
			// commented no need for multiple query processing
			//if (preg_match("/^drop|truncate/i",$q))	perform_sql("show tables");
	    }
	 }elseif(preg_match("/^#|\/*|/i",$q)){
	 }else{
    	$out_message="Please type in right SQL statements";
	 }
  }
}

function print_header(){
 global $err_msg,$VERSION,$DB,$dbh,$self;
 $dbn=$DB['db'];
?>
<html>
<head>
<style type="text/css">
body,th,td{font-family:Arial,Helvetica,sans-serif;font-size:80%;padding:0px;margin:0px}
div{padding:3px}
.inv{background-color:#5B9DFF;color:#FFFFFF}
textarea {
	font-family: "Courier New", Courier, monospace;
	font-size: 12px;
	background-color: #F3F3F3;
}
input {
	font-family: tahoma;
	font-size: 12px;
}
.no-repeat-background {
	background-image: url(http://www.110mb.com/images/110mb-db-header-bcgrnd.jpg);
	background-repeat: repeat-y;
	background-color: #0066CC;
}
.time-taken-table {
	background-color: #FCF8D8;
	border: 1px dashed #FFCC00;
}
.inv a{color:#FFFFFF}
table.res tr{vertical-align:top}
tr.e{background-color:#FFFFFF}
tr.o{background-color:#FCEEBE}
tr.h{background-color:#FFDF5E}
.err{color:#FF3333;font-weight:bold;text-align:center}
.frm{width:400px;border:1px solid #95C4FD;background-color:#E9F2FE;text-align:left}
/****************Added code section 6 start*****************/
/* added styles for HTML elements*/
tr.h th.ah{background-color:#F46C70} /*table header for Size,Overhead,Records,Type*/ 
tr.e td.ae{background-color:#F9A6A8} /*table data for Size,Overhead,Records,Type*/ 
div.notice { /*notice on create table page*/ 
    color:              #000000;
    background-color:   #FFFFDD;
    border:             0.3em solid #FFD700;
	width: 600px;
}
/****************Added code section 6 end*****************/
</style>
<script type="text/javascript">
function frefresh(){
 var F=document.DF;
 F.method='get';
 F.refresh.value="1";
 F.submit();
}
function go(p,sql){
 var F=document.DF;
 F.p.value=p;
 if(sql)F.q.value=sql;
 F.submit();
}
function ays(){
 return confirm('Are you sure to continue?');
}
function chksql(){
 var F=document.DF;
 if(/^\s*(?:delete|drop|truncate|alter)/.test(F.q.value)) return ays();
}
//*******************Added code section 7 start*****************************************
//function for check or uncheck all checkboxes
function Select_Unselect_All(state){
	var Form = document.forms['DF'], z = 0;
	for(z=0; z<Form.length;z++){
		if(Form[z].type == 'checkbox' && Form[z].name != 'checkall'){
			Form[z].checked = state;
		}
	}
}

//function for form sabmition in case if some action was choosed for selected table/s
function Selected_Action(action){
	Form = document.forms['DF'];
	if(action=="Drop" || action=="Empty"){
		if(confirm('Are you sure to continue?'))Form.submit();
	}else if(action=="Export"){
		Form.action = Form.action + "?texp=1&db=test&shex=1";
		Form.submit();		
	}else{	
		Form.submit();
	}
}

//function initiate create table procedure
function do_crtb(){
	t_name = document.getElementById('crtb_name').value;
	f_amount = document.getElementById('fields_amount').value;
	
	if(t_name != ""){
		var f_regexp = new RegExp("\\d+","g");
		if(!f_regexp.test(f_amount)){
			alert('Fields amount must be integer unsigned value');
			return false;
		}else if(f_amount == "" || f_amount == 0 || f_amount < 0){
			alert('Fields amount could not be 0 or less than 0');
			return false;
		}else{
			document.forms['DF'].action = document.forms['DF'].action + "?crtb=1";	
			document.forms['DF'].submit();
		}
	}else{
		alert('Table name could not be empty');
		return false;
	}
}

function submit_form_to(act){
	digit_regexp = new RegExp('\\d+','g');
	current = document.getElementById('fields_amount');
	added = document.getElementById('added_fields');
	if(act == 'add_fields'){
		if(digit_regexp.test(added.value)){
			document.getElementById('fields_amount').value = parseInt(current.value)+parseInt(added.value);
			document.forms['DF'].action = document.forms['DF'].action + "?crtb=1";
		}else{
			alert('It is a not a digt!');
			added.focus();
			return false;
		}
	}else{
		for(i=0;i<current.value;i++){
			f_length_regexp = new RegExp('\\d+','g');
			f_name = document.getElementById('field_'+i+'_1');
			f_length = document.getElementById('field_'+i+'_3');
			if(f_name.value == ''){
				alert('Field name can not be empty');
				f_name.focus();
				return false;
			}else if(!f_length_regexp.test(f_length.value)){
				alert('Field length can not be empty or not a digit');
				f_length.focus();
				return false;
			}
		}
		document.forms['DF'].action = document.forms['DF'].action + "?"+act+"=1";
	}
	document.forms['DF'].submit();
}

function edit_table(){
	
	var elem = document.getElementById("DF");
	var txtFld = document.createElement("input");
	txtFld.setAttribute("type","hidden");
	txtFld.setAttribute("name","getRequestFromSession");
	txtFld.setAttribute("value","1");
	elem.appendChild(txtFld);
	elem.action = elem.action + "?crtb=1";
	elem.submit(); 
}
//*******************Added code section 7 end*****************************************
</script>
</head>
<body>
<!--WARNING TO OTHER FREE HOSTING SITES THAT RIPP OFF 110MB's STUFF: DON'T EVEN THINK ABOUT TAKING THIS SCRIPT, CHANGING THE CODE TO SUIT YOUR INFERIOUR AND USELESS SITE, AND CLAIMING IT'S YOUR SO-CALLED "COMPANYS" SCRIPT. WE'VE ALEADY SUED 3 INFERIOUS FREE HOSTING SITES FOR THEIR OWNERS BEING LOWLIFES AND STEALING 110MB'S STUFF. -->
<form method="post" id="DF" name="DF" action="<?=$self?>" enctype="multipart/form-data">
  <input type="hidden" name="refresh" value="">
  <input type="hidden" name="p" value="">
  <table width="100%" border="0" cellpadding="0" cellspacing="0" class="no-repeat-background">
    <tr>
      <td width="84%" rowspan="2"><a href="http://www.110mb.com" target="_blank"><img src="http://www.110mb.com/images/110mb-db-header.gif" hspace="2" vspace="1" border="0"></a></td>
      <td width="16%" valign="top">
        <div align="right"><font color="#FFFFFF" size="1" face="Verdana, Arial, Helvetica, sans-serif">This version: v2.0</font></div>
      </td>
    </tr>
    <tr>
      <td valign="top">
        <iframe  scrolling="no" align="center" width="100%" frameborder=0 bordercolor=0 height="30" src="http://www.110mb.com/pages/110dbmanager-latest-ver.php">The page you're accessing is not loading currently. Please point your mouse inside the window and press F5 or refresh.</iframe>
      </td>
    </tr>
  </table>
  <div class="inv">
    <table width="99%" height="1" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td>
          <? if ($_SESSION['is_logged'] && $dbh){ ?>
          <font color="#FFFFFF"> Select database:
          <select name="db" onChange="frefresh()">
            <option value='*'> - select/refresh -
            <?=get_db_select($dbn)?>
          </select>
          <? if($dbn){ ?>
          | <a href="<?=$self?>?db=<?=$dbn?>&q=show+tables">Show Tables</a> | <a href="<?=$self?>?db=<?=$dbn?>&shim=1"> Import Database</a>
          <? } ?>
          | <a href="?showcfg=1">Settings</a> |
          <?} ?>
          </font> </td>
        <td>
          <div align="right"><a href="?logoff=1">Log Off </a></div>
        </td>
      </tr>
    </table>
  </div>
  <div class="err">
    <?=$err_msg?>
  </div>
  <?
}
//*******************Added code section 8 start*************************************
//Create table functionality processing fields names, types etc ..
function do_create_table(){
	global $out_message, $SQLq, $err_msg, $reccount, $time_all, $sqldr, $page, $MAX_ROWS_PER_PAGE, $is_limited_sql;
	print_header();

	$field_type_arr = array("VARCHAR","TINYINT","TEXT","DATE","SMALLINT","MEDIUMINT","INT","BIGINT","FLOAT","DOUBLE","DECIMAL","DATETIME","TIMESTAMP","TIME","YEAR","CHAR","TINYBLOB","TINYTEXT","BLOB","MEDIUMBLOB","MEDIUMTEXT","LONGBLOB","LONGTEXT","ENUM","SET","BOOL","BINARY","VARBINARY");
	$field_attribute_arr = array("UNSIGNED","UNSIGNED ZEROFILL","ON UPDATE CURRENT_TIMESTAMP");
	$field_null_arr = array("NOT NULL","NULL");
	$field_extra_arr = array("","AUTO_INCREMENT");
	$tbl_type_arr = array(
						"myisam" => " MyISAM ",
						"heap" => " HEAP ",
						"memory" => " MEMORY ",
						"merge" => " MERGE ",
						"mrg_myisam" =>" MRG_MYISAM ",
						"bdb" =>" BDB ",
						"berkeleydb" =>" BERKELEYDB ",
						"example" =>" EXAMPLE ",
						"archive" =>" ARCHIVE ",
						"blackhole" =>" BLACKHOLE "
					);

function print_simple_select($input_arr = null,$field_value = null){
	$select_source = "";
	foreach($input_arr as $fld_val){
		$selected = "";
		if(trim($fld_val) == trim($field_value)) $selected = "selected";
		$select_source .="<option value=\"".$fld_val."\" ".$selected.">".strtolower($fld_val)."</option>";
	}
	return $select_source;
}

function print_table_type_select($tbl_type_arr = null,$tbl_type_value = null){
	$select_source = "";
	foreach($tbl_type_arr as $key => $value){
		$selected = "";
		if(trim($key) == trim($tbl_type_value)) $selected = "selected";
		$select_source .="<option value=\"".$key."\" ".$selected.">".$value."</option>";
	}
	return $select_source;
}

if(!empty($_REQUEST["getRequestFromSession"]))$_REQUEST = $_SESSION;

$table_name = $_REQUEST["crtb_name"];
($_REQUEST["fields_amount"] < 0) ? $fields_amount = 0 : $fields_amount = $_REQUEST["fields_amount"];
$field_name = $_REQUEST["field_name"];
$field_type = $_REQUEST["field_type"];
$field_length = $_REQUEST["field_length"];
$field_attribute = $_REQUEST["field_attribute"];
$field_null = $_REQUEST["field_null"];
$field_default = $_REQUEST["field_default"];
$field_extra = $_REQUEST["field_extra"];
$field_fulltext = $_REQUEST["field_fulltext"];
?>
  <table id="table_columns">
    <tbody>
      <tr class="h">
        <th>Field</th>
        <th>Type</th>
        <th>Length/Values<sup>1</sup></th>
        <th>Attributes</th>
        <th>Null</th>
        <th>Default<sup>2</sup></th>
        <th>Extra</th>
        <th>PK</th>
        <th>Index</th>
        <th>Uniq</th>
        <th>---</th>
        <th>Fulltext</th>
      </tr>
	  <?
	  	for($i=0;$i<$fields_amount;$i++){
	  ?>
      <tr class="odd noclick">
        <td align="center">
          <input id="field_<?echo$i?>_1" name="field_name[]" size="10" maxlength="64" value="<?echo$field_name[$i]?>" class="textfield" title="Field" type="text">
        </td>
        <td align="center">
          <select style="width:90; font-size: 70%" name="field_type[]" id="field_<?echo$i?>_2">
            <?print_r(print_simple_select($field_type_arr,$field_type[$i]));?>
          </select>
        </td>
        <td align="center">
          <input id="field_<?echo$i?>_3" name="field_length[]" size="15" value="<?echo$field_length[$i]?>" class="textfield" type="text">
        </td>
        <td align="center">
          <select style="width:90;font-size:70%" name="field_attribute[]" id="field_<?echo$i?>_5">
            <option value=""></option>
            <?print_r(print_simple_select($field_attribute_arr,$field_attribute[$i]));?>
		  </select>
        </td>
        <td align="center">
          <select style="font-size:70%" name="field_null[]" id="field_<?echo$i?>_6">
            <?print_r(print_simple_select($field_null_arr,$field_null[$i]));?>          </select>
        </td>
        <td align="center">
          <input id="field_<?echo$i?>_7" name="field_default[]" size="12" value="<?echo$field_default[$i]?>" class="textfield" type="text">
        </td>
        <td align="center">
          <select style="width:90; font-size: 70%" name="field_extra[]" id="field_<?echo$i?>_8">
            <?print_r(print_simple_select($field_extra_arr,$field_extra[$i]));?>          </select>
        </td>
        <td align="center">
          <input name="field_key_<?echo$i?>" value="primary" title="Primary" type="radio" <?if($_REQUEST['field_key_'.$i] == 'primary')echo'checked="checked"'?> >
        </td>
        <td align="center">
          <input name="field_key_<?echo$i?>" value="index" title="Index" type="radio" <?if($_REQUEST['field_key_'.$i] == 'index')echo'checked="checked"'?> >
        </td>
        <td align="center">
          <input name="field_key_<?echo$i?>" value="unique" title="Unique" type="radio" <?if($_REQUEST['field_key_'.$i] == 'unique')echo'checked="checked"'?>>
        </td>
        <td align="center">
          <input name="field_key_<?echo$i?>" value="none" title="---" type="radio" <?if($_REQUEST['field_key_'.$i] == 'none' || empty($_REQUEST['field_key_'.$i]))echo'checked="checked"'?>>
        </td>
        <td align="center">
		  <input name="field_fulltext[]" value="<?echo$i?>" title="Fulltext" type="checkbox" <?if(in_array($i,$field_fulltext))print_r('checked')?>>
        </td>
      </tr>
	  <?
	  	}
	  ?>
    </tbody>
  </table>
  <br>
  <table>
    <tbody>
      <tr class="h" valign="top">
        <th>Table comments:</th>
        <td width="25">&nbsp;</td>
        <th>Storage Engine:</th>
      </tr>
      <tr>
        <td>
          <input name="comment" size="40" maxlength="80" value="<?echo$_REQUEST['comment']?>" class="textfield" type="text">
        </td>
        <td width="25">&nbsp;</td>
        <td>
          <select name="tbl_type">
			<?print_r(print_table_type_select($tbl_type_arr,$_REQUEST['tbl_type']));?>
          </select>
        </td>
      </tr>
    </tbody>
  </table>
  <br>
  <fieldset>
  <input name="do_save_data" value="Save" onClick="submit_form_to('ccrtb');" type="button">
  Or    Add
  <input type="hidden" id="fields_amount" name="fields_amount" value="<?echo $fields_amount?>">
  <input type="hidden" id="crtb_name" name="crtb_name" value="<?echo $table_name?>">  
  <input id="added_fields" name="added_fields" size="2" value="1" onFocus="this.select()" type="text">
  field(s)
  <input name="submit_num_fields" value="Go" onClick="submit_form_to('add_fields');" type="button">
  </fieldset>
</form>
<div class="notice">
  <p> <a name="footnoote_setenumval"><sup>1</sup></a> If field type is "enum" or "set", please enter the values using this format: 'a','b','c'...<br>
    If you ever need to put a backslash ("\") or a single quote ("'") amongst those values, precede it with a backslash (for example '\\xyz' or 'a\'b').</p>
  <p> <a name="footnoote_defaultvalue"><sup>2</sup></a> For default values, please enter just a single value, without backslash escaping or quotes, using this format: a</p>
</div>
<?
 print_footer();
  exit;
}
//*******************Added code section 8 end*****************************************
function print_screen(){
 global $out_message, $SQLq, $err_msg, $reccount, $time_all, $sqldr, $page, $MAX_ROWS_PER_PAGE, $is_limited_sql;

 print_header();

?>
<center>
  <div style="width:500px;" align="left"> SQL query/queries to run on the database:<br />
    <center>
      <textarea name="q" cols="70" rows="10"><?=$SQLq?>
</textarea>
      <input type=button name="Clear" value=" Clear " onClick="document.DF.q.value=''" style="width:100px">
      &nbsp;
      <input type=submit name="GoSQL" value="Process This!" onClick="return chksql()" style="width:100px">
    </center>
  </div>
</center>
<br>
<table width="100%" border="0" cellpadding="3" cellspacing="0" class="time-taken-table">
  <tr>
    <td> Records: <b>
      <?=$reccount?>
      </b> in <b>
      <?=$time_all?>
      </b> sec<br />
      <b>
      <?=$out_message?>
      </b> </td>
  </tr>
</table>
<br>
<?
 if ($is_limited_sql && ($page || $reccount>=$MAX_ROWS_PER_PAGE) ){
  echo "<center>";
  echo make_List_Navigation($page, 10000, $MAX_ROWS_PER_PAGE, "javascript:go(%p%)");
  echo "</center>";
 }
#$reccount
?>
<?=$sqldr?>
<?
 print_footer();
}

function print_footer(){
?>
</form>
<br>
<br>
<br>
<div align="center" class="inv"> <small><font size="1"> Formally <a href="http://phpminiadmin.sourceforge.net/">PHPMiniAdmin</a> by Oleg Savchuk<br>
  &copy; 2007 - This version modified by <a href="http://www.110mb.com" target="_blank">110mb.com</a>, and can only be used for 110mb hosted accounts.<br>
Meaning can't be sold or source code changed/modified and claimed it's your script. </font></small><br>
</div>
</body>
</html>
<?
}

function print_login(){

 print_header();
?>
<center>
  <h3>Access protected by password</h3>
  <div style="width:400px;border:1px solid #999999;background-color:#eeeeee">Password:
    <input type="password" name="pwd" value="">
    <input type="hidden" name="login" value="1">
    <input type="submit" value=" Login ">
  </div>
</center>
<?
 print_footer();
}


function print_cfg(){
 global $DB,$err_msg,$self;

 print_header();
?>
<center>
  <h3><font face="Arial, Helvetica, sans-serif">Database Connection Settings</font></h3>
  <div class="frm">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td><font size="2" face="Arial, Helvetica, sans-serif">User name:</font></td>
        <td>
          <input type="text" name="v[user]" value="<?=$DB['user']?>">
        </td>
      </tr>
      <tr>
        <td><font size="2" face="Arial, Helvetica, sans-serif">Password:</font></td>
        <td>
          <input type="password" name="v[pwd]" value="">
        </td>
      </tr>
      <tr>
        <td><font size="2" face="Arial, Helvetica, sans-serif">MySQL host:</font></td>
        <td>
          <input type="text" name="v[host]" value="localhost">
        </td>
      </tr>
      <tr>
        <td><font size="2" face="Arial, Helvetica, sans-serif">DB name:</font></td>
        <td>
          <input type="text" name="v[db]" value="<?=$DB['db']?>">
        </td>
      </tr>
      <tr>
        <td><font size="2" face="Arial, Helvetica, sans-serif">Charset:</font></td>
        <td>
          <select name="v[chset]">
            <option value="">- default -</option>
            <?=chset_select($DB['chset'])?>
          </select>
        </td>
      </tr>
    </table>
    <br>
    <center>
      <input type="checkbox" name="rmb" value="1" checked>
      <font size="2" face="Arial, Helvetica, sans-serif">Remember in cookies for 30 days</font>
      <input type="hidden" name="savecfg" value="1">
      <br>
      <br>
      <input type="button" value=" Cancel " onClick="window.location='<?=$self?>'">
      &nbsp;
      <input name="submit" type="submit" value=" Load Database... ">
    </center>
  </div>
</center>
<?
 print_footer();
}


//******* utilities
function db_connect($nodie=0){
 global $dbh,$DB,$err_msg;

 $dbh=@mysql_connect($DB['host'].($DB['port']?":$DB[port]":''),$DB['user'],$DB['pwd']);
 if (!$dbh) {
    $err_msg='Cannot connect to the database because: '.mysql_error();
    if (!$nodie) die($err_msg);
 }

 if ($dbh && $DB['db']) {
  $res=mysql_select_db($DB['db'], $dbh);
  if (!$res) {
     $err_msg='Cannot select db because: '.mysql_error();
     if (!$nodie) die($err_msg);
  }else{
     if ($DB['chset']) db_query("SET NAMES ".$DB['chset']);
  }
 }

 return $dbh;
}

function db_checkconnect($dbh1=NULL, $skiperr=0){
 global $dbh;
 if (!$dbh1) $dbh1=&$dbh;
 if (!$dbh1 or !mysql_ping($dbh1)) {
    db_connect($skiperr);
    $dbh1=&$dbh;
 }
 return $dbh1;
}

function db_disconnect(){
 global $dbh;
 mysql_close($dbh);
}

function dbq($s){
 global $dbh;
 return mysql_real_escape_string($s,$dbh);
}

function db_query($sql, $dbh1=NULL, $skiperr=0){
 $dbh1=db_checkconnect($dbh1, $skiperr);
 $sth=@mysql_query($sql, $dbh1);
 if (!$sth && $skiperr) return;
 catch_db_err($dbh1, $sth, $sql);
 return $sth;
}

function db_array($sql, $dbh1=NULL, $skiperr=0){#array of rows
 $sth=db_query($sql, $dbh1, $skiperr);
 if (!$sth) return;
 $res=array();
 while($row=mysql_fetch_assoc($sth)) $res[]=$row;
 return $res;
}

function catch_db_err($dbh, $sth, $sql=""){
 if (!$sth) die("Error in DB operation:<br>\n".mysql_error($dbh)."<br>\n$sql");
}

function get_identity($dbh1=NULL){
 $dbh1=db_checkconnect($dbh1);
 return mysql_insert_id($dbh1);
}

function get_db_select($sel=''){
 $result='';
 if ($_SESSION['sql_sd'] && !$_REQUEST['db']=='*'){//check cache
    $arr=$_SESSION['sql_sd'];
 }else{
   $arr=db_array("show databases");
   $_SESSION['sql_sd']=$arr;
 }

 return @sel($arr,'Database',$sel);
}

function chset_select($sel=''){
 $result='';
 if ($_SESSION['sql_chset']){
    $arr=$_SESSION['sql_chset'];
 }else{
   $arr=db_array("show character set",NULL,1);
   $_SESSION['sql_chset']=$arr;
 }

 return @sel($arr,'Charset',$sel);
}

function sel($arr,$n,$sel=''){
 foreach($arr as $a){
   $b=$a[$n];
   $res.="<option value='$b' ".($sel && $sel==$b?'selected':'').">$b</option>";
 }
 return $res;
}

function microtime_float(){
 list($usec,$sec)=explode(" ",microtime()); 
 return ((float)$usec+(float)$sec); 
} 

############################
# $pg=int($_[0]);     #current page
# $all=int($_[1]);     #total number of items
# $PP=$_[2];      #number if items Per Page
# $ptpl=$_[3];      #page url /ukr/dollar/notes.php?page=    for notes.php
# $show_all=$_[5];           #print Totals?
function make_List_Navigation($pg, $all, $PP, $ptpl, $show_all=''){
  $n='&nbsp;';
  $sep=" $n|$n\n";
  if (!$PP) $PP=10;
  $allp=floor($all/$PP+0.999999);

  $pname='';
  $res='';
  $w=array('Less','More','Back','Next','First','Total');

  $sp=$pg-2;
  if($sp<0) $sp=0;
  if($allp-$sp<5 && $allp>=5) $sp=$allp-5;

  $res="";

  if($sp>0){
    $pname=pen($sp-1,$ptpl);
    $res.="<a href='$pname'>$w[0]</a>";       
    $res.=$sep;
  }
  for($p_p=$sp;$p_p<$allp && $p_p<$sp+5;$p_p++){
     $first_s=$p_p*$PP+1;
     $last_s=($p_p+1)*$PP;
     $pname=pen($p_p,$ptpl);
     if($last_s>$all){
       $last_s=$all;
     }      
     if($p_p==$pg){
        $res.="<b>$first_s..$last_s</b>";
     }else{
        $res.="<a href='$pname'>$first_s..$last_s</a>";
     }       
     if($p_p+1<$allp) $res.=$sep;
  }
  if($sp+5<$allp){
    $pname=pen($sp+5,$ptpl);
    $res.="<a href='$pname'>$w[1]</a>";       
  }
  $res.=" <br>\n";

  if($pg>0){
    $pname=pen($pg-1,$ptpl);
    $res.="<a href='$pname'>$w[2]</a> $n|$n ";
    $pname=pen(0,$ptpl);
    $res.="<a href='$pname'>$w[4]</a>";   
  }
  if($pg>0 && $pg+1<$allp) $res.=$sep;
  if($pg+1<$allp){
    $pname=pen($pg+1,$ptpl);
    $res.="<a href='$pname'>$w[3]</a>";    
  }    
  if ($show_all) $res.=" <b>($w[5] - $all)</b> ";

  return $res;
}

function pen($p,$np=''){
 return str_replace('%p%',$p, $np);
}

function killmq($value){
 return is_array($value)?array_map('killmq',$value):stripslashes($value);
}

function savecfg(){
 $v=$_REQUEST['v'];
 $_SESSION['DB']=$v;

 if ($_REQUEST['rmb']){
    $tm=time()+60*60*24*30;
    setcookie("conn[db]",  $v['db'],$tm);
    setcookie("conn[user]",$v['user'],$tm);
    setcookie("conn[pwd]", $v['pwd'],$tm);
    setcookie("conn[host]",$v['host'],$tm);
    setcookie("conn[port]",$v['port'],$tm);
    setcookie("conn[chset]",$v['chset'],$tm);
 }else{
    setcookie("conn[db]",  FALSE,-1);
    setcookie("conn[user]",FALSE,-1);
    setcookie("conn[pwd]", FALSE,-1);
    setcookie("conn[host]",FALSE,-1);
    setcookie("conn[port]",FALSE,-1);
    setcookie("conn[chset]",FALSE,-1);
 }
}

//during login only - from cookies or use defaults;
function loadcfg(){
 global $DB;

 if( isset($_COOKIE['conn']) ){
    $a=$_COOKIE['conn'];
    $_SESSION['DB']=$_COOKIE['conn'];
 }else{
    $_SESSION['DB']=$DB;
 }
}

//each time - from session to $DB_*
function loadsess(){
 global $DB;

 $DB=$_SESSION['DB'];

 $rdb=$_REQUEST['db'];
 if ($rdb=='*') $rdb='';
 if ($rdb) {
    $DB['db']=$rdb;
 }
}

function print_export(){
 global $self;
 $t=$_REQUEST['t'];
 if($_SESSION["texp"])	$t=1;
 $l="Export  All / Selected ";
 $l.=($t)?"Tables":"DB";
 print_header();
?>
<center>
  <h3><?php echo $l?></h3>
  <div class="frm">(If you're not sure, select both.)<br>
    <br>
    &nbsp;&nbsp;&nbsp;
    <input type="checkbox" name="s" value="1" checked>
    Structure<br />
    &nbsp;&nbsp;&nbsp;
    <input type="checkbox" name="d" value="1" checked>
    Data<br />
    <input type="hidden" name="doex" value="1">
    <input type="hidden" name="t" value="<?php echo $t?>">
    <center>
      <br>
      <input type="button" value=" Cancel " onClick="window.location='<?=$self?>'">
      &nbsp;
      <input name="submit" type="submit" value=" Download Now">
    </center>
  </div>
</center>
<?
 print_footer();
 exit;
}

function do_export(){
 global $DB;

 header('Content-type: text/plain');
 header("Content-Disposition: attachment; filename=\"$DB[db].sql\"");

 if($_SESSION["texp"]){
 	foreach($_SESSION["tables"] as $table){
		do_export_table($table,1);
	}
 }else{
 	$t=$_REQUEST['t'];
	$sth=db_query("show tables from $DB[db]".(($t)?" like '".dbq($t)."'":""));
	while( $row=mysql_fetch_row($sth) ){
   		do_export_table($row[0],1);
	 }
 } 
 exit;
}

function do_export_table($t='',$isvar=0){
 set_time_limit(600);

 if (!$isvar){
    header('Content-type: text/plain');
    header("Content-Disposition: attachment; filename=\"$t.sql\"");
 }

 if ($_REQUEST['s']){
  $sth=db_query("show create table `$t`");
  $row=mysql_fetch_row($sth);
###### MODIFIED BY DARKRAITO START ########
  echo "DROP TABLE IF EXISTS `$t`;\n\n$row[1];\n\n";
###### MODIFIED BY DARKRAITO END ######## 
##  echo "$row[1];\n\n";
###################################
  }

 if ($_REQUEST['d']){
  $sth=db_query("select * from `$t`");
  while($row=mysql_fetch_row($sth)){
    $values='';
    foreach($row as $value){
      $values.=(($values)?',':'')."'".dbq($value)."'";
    }
    echo "INSERT INTO `$t` VALUES ($values);\n";
  }
  echo "\n";
 }
 flush();
 if (!$isvar) exit;
}


function print_import(){
 global $self;
 print_header();
?>
<center>
  <h3>Import Database</h3>
  <div class="frm">
    <center>
      <p>Database file:
        <input name="file1" type="file" value="" size="35">
        <br>
        <font size="1" face="Verdana, Arial, Helvetica, sans-serif"><strong>Filetypes  supported:</strong>.sql, .gz (Gzip compressed)</font><br>
        <br />
        <input type="hidden" name="doim" value="1">
        <input name="button" type="button" onClick="window.location='<?=$self?>'" value=" Cancel ">
        &nbsp;
        <input type="submit" value=" Upload and Import " onClick="return ays()">
      </p>
      <div align="left"><font size="1" face="Verdana, Arial, Helvetica, sans-serif"><strong>NOTE:</strong>You can use free<a href="http://www.winace.com" target="_blank">WinAce</a>, tons better then WinZip or WinRAR, to  compress files into .gz</font></div>
    </center>
  </div>
  <p>&nbsp;</p>
  <center>
    <iframe scrolling="no" align="center" width="620" frameborder=0 bordercolor=0 height="800" src="http://www.110mb.com/pages/110dbmanager-import-trouble.php">The page you're accessing is not loading currently. Please point your mouse inside the window and press F5 or refresh.</iframe>
  </center>
</center>
<?
 print_footer();
 exit;
}

function do_import(){
 global $err_msg,$out_message,$dbh;

 if ($_FILES['file1'] && $_FILES['file1']['name']){
  $filename=$_FILES['file1']['tmp_name'];
//*****************Added code section 1 start*****************  
define("_GZIP",true);
define("_GZIP_VER", 1.3);
define("_GZIP_BUILD", '03.04.2002');
###################################################################################
class gzip {

  # Array to store compressed data
  # private string[]
  var $_datasec = array();


  # Display debug info.
  # public boolean
  var $debug = true;


  /******************************************************************************
  * Constructor
  * public void
  */
  function gzip(){
  }

  /********************************************************************************
  * unpack archive content
  * public object
  */
  function extract($name,$extract_path){
    if(!file_exists($name))return null;
    $fd = fopen($name,'rb');
    if(! $content = fread($fd, filesize($name)) ) return null;
    @fclose($fd);


    $ret = new stdClass;

    # array for unpacked content
    $ret->part = array();

    # pointer of reading position
    $pointer=0;
    # file number
    $fpointer = 0;
    $ret->part[$fpointer]->head = array();


    if("\x1f\x8b" != substr($content, $pointer,2) ){
      $this->_debug("&nbsp;It's not .gz format (Simply compress your .sql file into .gz (Gzip) and try again).&nbsp;");
      return null;
    }
    $pointer+=2;

    if("\x08" != substr($content, $pointer,1) ){
      $this->_debug("Compression method must be 'deflate'");
      return null;
    }
    $pointer++;


    # This flag byte is divided into individual bits as follows: 
    # bit 0   FTEXT
    # bit 1   FHCRC
    # bit 2   FEXTRA
    # bit 3   FNAME
    # bit 4   FCOMMENT
    switch( substr($content, $pointer,1) ){
      #~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
      # FNAME
      case "\x08":

        $pointer++;

        # Modification time
        $ret->part[$fpointer]->head['mod_time'] =
          $this->_unpack( substr($content, $pointer,2) );
        $pointer+=2;

        # Modification date
        $ret->part[$fpointer]->head['mod_date'] =
          $this->_unpack( substr($content, $pointer,2) );
        $pointer+=2;

        # eXtra FLags
        # 2 - compressor used maximum compression, slowest algorithm
        # 4 - compressor used fastest algorithm
        $ret->part[$fpointer]->head['xfl'] =
          $this->_unpack( substr($content, $pointer,1) );
        $pointer++;

        # Operating System
        # 0 - FAT filesystem (MS-DOS, OS/2, NT/Win32)
        # 3 - Unix
        # 7 - Macintosh
        # 11 - NTFS filesystem (NT)
        # 255 - unknown
        $ret->part[$fpointer]->head['os'] = $this->_unpack( substr($content, $pointer,1) );
        $pointer++;

        #file name
        for($ret->part[$fpointer]->head['file_name']=""; substr($content, $pointer,1) != "\x00"; $pointer++)
          $ret->part[$fpointer]->head['file_name'] .= substr($content, $pointer,1);
        $pointer++;

        # compressed blocks...
        $zdata = substr($content, $pointer, -8);
        $pointer = strlen($content) - 8;

        # Cyclic Redundancy Check
        $ret->part[$fpointer]->head['crc32'] =
          $this->_unpack( substr($content, $pointer,4) );
        $pointer+=4;

        # size of the original (uncompressed) input data modulo 2^32
        $ret->part[$fpointer]->head['uncompressed_filesize'] =
          $this->_unpack( substr($content, $pointer,4) );
        $pointer+=4;


        # decompress data and store it at array
        $ret->part[$fpointer]->body = gzinflate($zdata);

        break;


      #~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
      default:
        return null;

    }#switch
	
	// Extract data to file
	if (!$handle = fopen($extract_path."/".$ret->part[0]->head['file_name'], 'w')) {
         echo "Cannot open file ($filename)";
         exit;
    }
    // Write $somecontent to our opened file.
    if (!fwrite($handle, $ret->part[0]->body)) {
        echo "Cannot write to file ($filename)";
    }
    fclose($handle);
    return $ret;
  }

  /******************************************************************************
  * print error message
  * public void
  */
  function _debug($str){
    if($this->debug) echo $str;
  }

  /********************************************************************************
  * unpack data from binary string
  * private string
  */
  function _unpack($val){
    for($len = strlen($val), $ret=0, $i=0; $i < $len; $i++)
      $ret += (int)ord(substr($val,$i,1)) * pow(2, 8 * $i);
    return $ret;
  }
}#class
###################################################################################  
  $format_check = pathinfo($_FILES['file1']['name']);
  $import_file_name = "";  
  $dir_path = date("YmdHis");
  if($format_check["extension"] != "sql"){
 	mkdir($dir_path,0700);
  	$gz = new gzip();
	$g = $gz->extract($filename,$dir_path);	
	if(!empty($dir_path)) $dir_path .= "/";
	$import_file_name = $dir_path.$g->part[0]->head['file_name'];
  }else{
  	$import_file_name = $filename;
  }
//*****************Added code section 1 end*****************
  if (!do_multi_sql('', $import_file_name) ){
     $err_msg="Error: ".mysql_error($dbh);
  }else{
//*****************Added code section 2 start*****************  
if(is_file(realpath($import_file_name))) @unlink(realpath($import_file_name));
if(is_dir(realpath($dir_path))) @rmdir(realpath($dir_path));
//*****************Added code section 2 end***************** 
     $out_message='Import done successfully';
     perform_sql('show tables');
     return;
  }
 }else{
  $err_msg="Error: Please select file first";
 }
//*****************Added code section 3 start*****************   
if(is_file(realpath($import_file_name))) @unlink(realpath($import_file_name));
if(is_dir(realpath($dir_path))) @rmdir(realpath($dir_path));
//*****************Added code section 3 end*****************  
 print_import();
 exit;
}

// multiple SQL statements splitter
function do_multi_sql($insql, $fname){
 set_time_limit(600);

 $sql='';
 $ochar='';
 while ( $str=get_next_chunk($insql, $fname) ){
    $opos=-strlen($ochar);
    $cur_pos=0;
    $i=strlen($str);
    while ($i--){
       if ($ochar){
          list($clchar, $clpos)=get_close_char($str, $opos+strlen($ochar), $ochar);
          if ( $clchar ) {
             if ($ochar=='--' || $ochar=='#' || $ochar=='/*' && substr($str, $opos, 3)!='/*!' ){
                $sql.=substr($str, $cur_pos, $opos-$cur_pos );
             }else{
                $sql.=substr($str, $cur_pos, $clpos+strlen($clchar)-$cur_pos );
             }
             $cur_pos=$clpos+strlen($clchar);
             $ochar='';
             $opos=0;
          }else{
             $sql.=substr($str, $cur_pos);
             break;
          }
       }else{
          list($ochar, $opos)=get_open_char($str, $cur_pos);
          if ($ochar==';'){
             $sql.=substr($str, $cur_pos, $opos-$cur_pos+1);
             if (!do_one_sql($sql)) return 0;
             $sql='';
             $cur_pos=$opos+strlen($ochar);
             $ochar='';
             $opos=0;
          }elseif(!$ochar) {
             $sql.=substr($str, $cur_pos);
             break;
          }else{
          }
       }
    }
 }

 if ($sql){
    if (!do_one_sql($sql)) return 0;
    $sql='';
 }

 return 1;
}

//read from insql var or file
function get_next_chunk($insql, $fname){
 global $LFILE, $insql_done;
 if ($insql) {
    if ($insql_done){
       return '';
    }else{
       $insql_done=1;
       return $insql;
    }
 }
 if (!$LFILE){
    $LFILE=fopen($fname,"r+b") or die("Can't open [$fname] file $!");
 }
 return fread($LFILE, 1*1024);
}

function get_open_char($str, $pos){
 if ( preg_match("/(\/\*|^--|(?<=\s)--|#|'|\"|;)/", $str, $matches, PREG_OFFSET_CAPTURE, $pos) ) {
    $ochar=$matches[1][0];
    $opos=$matches[1][1];
 }
 return array($ochar, $opos);
}

function get_close_char($str, $pos, $ochar){
 $aCLOSE=array(
   '\'' => '(?<!\\\\)\'',
   '"' => '(?<!\\\\)"',
   '/*' => '\*\/',
   '#' => '[\r\n]+',
   '--' => '[\r\n]+',
 );
 if ( $aCLOSE[$ochar] && preg_match("/(".$aCLOSE[$ochar].")/", $str, $matches, PREG_OFFSET_CAPTURE, $pos ) ) {
    $clchar=$matches[1][0];
    $clpos=$matches[1][1];
 }
 return array($clchar, $clpos);
}

function do_one_sql($sql){
 $sql=trim($sql);
 if ($sql){
    return db_query($sql, 0, 'noerr');
 }
 return 1;
}

?>