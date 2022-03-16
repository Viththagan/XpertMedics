<?php
	// fieldset {
    // font-family: sans-serif;
    // border: 5px solid #1F497D;
    // background: #ddd;
    // border-radius: 5px;
    // padding: 15px;
// }

// fieldset legend {
    // background: #1F497D;
    // color: #fff;
    // padding: 5px 10px ;
    // font-size: 32px;
    // border-radius: 5px;
    // box-shadow: 0 0 0 5px #ddd;
    // margin-left: 20px;
// }
	echo '<div id="table_style">
	<style>
		table.display{width: 100% !important;}
	.user_list{min-height: 160px;}
	.user_list.paid{ background: #C1F2C2;}
		.printable_table{width: 100%;line-height: 1;}
		.printable_table .ui-spinner{display:none;}
		.printable_table input{height:30px;width:80px;}
		.printable_table .bold td{font-weight: bold !important;}
		.printable_table .bold{font-weight: bold !important;}
		.printable_table .more td{background:#84BB8B !important} 
		.printable_table .today td{background:#D6D6D6 !important;} 			
		.printable_table .passed td{background:#FFEFD5 !important;}
		.printable_table a:link {
	color: #666;
	font-weight: bold;
	text-decoration:none;
}
.warning{background: rgba(226, 0, 31, 0.69) !important;}
.warning_10{background: rgba(226, 73, 0, 0.88) !important}
.warning2{background: rgba(226, 188, 0, 0.54) !important;}
.discount_special_offer{background: rgba(0, 226, 80, 0.28) !important;}
.discount_color_10{background: rgba(226, 219, 0, 0.28) !important;}
.discount_color_15{background: rgba(226, 219, 0, 0.69) !important;}
.discount_color_20{background: rgba(226, 73, 0, 0.65) !important;}
.discount_color_25{background: rgba(226, 0, 31, 0.69) !important;}
.discount_color_0{background: rgba(9, 90, 236, 0.57) !important;}
.printable_table a:visited {
	color: #999999;
	font-weight:bold;
	text-decoration:none;
}
.printable_table a:active,
.printable_table a:hover {
	color: #bd5a35;
	text-decoration:underline;
}
.printable_table {
	font-family:Arial, Helvetica, sans-serif;
	background:#eaebec;
	border:#ccc 1px solid;

	-moz-border-radius:3px;
	-webkit-border-radius:3px;
	border-radius:3px;

}
.printable_table th {
	    padding: 5px 2px 2px 2px;
	border-top:1px solid #fafafa;
	border-bottom:1px solid #e0e0e0;

	background: #ededed;
	background: -webkit-gradient(linear, left top, left bottom, from(#ededed), to(#ebebeb));
	background: -moz-linear-gradient(top,  #ededed,  #ebebeb);
}
.printable_table tr:first-child th:first-child {
	-moz-border-radius-topleft:3px;
	-webkit-border-top-left-radius:3px;
	border-top-left-radius:3px;
}
.printable_table tr:first-child th:last-child {
	-moz-border-radius-topright:3px;
	-webkit-border-top-right-radius:3px;
	border-top-right-radius:3px;
}
.printable_table tr {padding-left:20px;}
.printable_table td:first-child {
	text-align: left;
	padding-left:20px;
	border-left: 0;
}
.printable_table td {
	padding:10px;
	border-top: 1px solid #ffffff;
	border-bottom:1px solid #e0e0e0;
	border-left: 1px solid #e0e0e0;

	background: #fafafa;
	background: -webkit-gradient(linear, left top, left bottom, from(#fbfbfb), to(#fafafa));
	background: -moz-linear-gradient(top,  #fbfbfb,  #fafafa);
}
.printable_table tr.even td {
	background: #f6f6f6;
	background: -webkit-gradient(linear, left top, left bottom, from(#f8f8f8), to(#f6f6f6));
	background: -moz-linear-gradient(top,  #f8f8f8,  #f6f6f6);
}
.printable_table tr:last-child td {
	border-bottom:0;
}
.printable_table tr:last-child td:first-child {
	-moz-border-radius-bottomleft:3px;
	-webkit-border-bottom-left-radius:3px;
	border-bottom-left-radius:3px;
}
.printable_table tr:last-child td:last-child {
	-moz-border-radius-bottomright:3px;
	-webkit-border-bottom-right-radius:3px;
	border-bottom-right-radius:3px;
}
.printable_table tr:hover td {
	background: #f2f2f2;
	background: -webkit-gradient(linear, left top, left bottom, from(#f2f2f2), to(#f0f0f0));
	background: -moz-linear-gradient(top,  #f2f2f2,  #f0f0f0);	
}
.printable_table .title{
  font-weight: bold;
}
.center{text-align:center;}
.right{text-align:right;}
	table.accounts {
  font-family: "Times New Roman", Times, serif;
  border: 1px solid #FFFFFF;
  width: 100%;
  border-collapse: collapse;
}
table.accounts td, table.accounts th {
  border: 1px solid #FFFFFF;
  padding: 3px 2px;
}
table.accounts tbody td {
  font-size: 13px;
}
table.accounts tr:nth-child(even) {
  background: #D0E4F5;
}
table.accounts thead {
  background: #0B6FA4;
  border-bottom: 5px solid #FFFFFF;
}
table.accounts thead th {
  font-size: 17px;
  font-weight: bold;
  color: #FFFFFF;
  text-align: center;
  border-left: 2px solid #FFFFFF;
}
table.accounts thead th:first-child {
  border-left: none;
}

table.accounts tfoot {
  font-size: 14px;
  font-weight: bold;
  color: #333333;
  background: #D0E4F5;
  border-top: 3px solid #444444;
}
table.accounts tfoot td {
  font-size: 14px;
}
table.accounts .title{
  font-size: 16px;
  text-align: center;
  font-weight: bold;
}
	</style>
	</div>';
?>