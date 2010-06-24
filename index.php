<?php
require "sp.php";

$action = isset($_GET['action']) ? $_GET['action'] : false;
$sp = new SP($action);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Scrum Poker</title>
		<meta http-equiv="content-type" content="text/html;charset=utf-8" />
		<link rel="stylesheet" href="css/reset.css" type="text/css" media="all" />
		<link rel="stylesheet" href="css/smoothness/theme.css" type="text/css" media="all" />
		<link rel="stylesheet" href="css/structure.css" type="text/css" media="all" />
		
		<style type='text/css'>
			body {
				font-family: helvetica, arial, sans-serif;
			}
		
			#story {
				float: left;
				display: none;
				margin-left: 50px;
				margin-top: 50px;
				width: 60%;
			}
			
			#story h1 {
				font-size: 20px;
				line-height: 30px;
				margin-bottom: 12px;
			}
			
			#story p {
				font-size: 16px;
				line-height: 20px;
				margin-bottom: 12px;
			}
			
			#cards {
				display: none;
				float: left;
				width: 80%;
			}
			
			#wait {
				display: none;
				float: left;
				width: 60%;
				margin-top: 50px;
				margin-left: 50px;
				font-size: 40px;
				color: #888;
			}
			
			#start {
				display: none;
				float: left;
				width: 60%;
				margin-top: 50px;
				margin-left: 50px;
			}
			
			#start button {
				background: #ddd;
				color: #888;
				border: 3px solid #ccc;
				font-size: 100px;
				cursor: pointer;
			}
			
			#skip {
				display: none;
				float: left;
				width: 60%;
				margin-top: 50px;
				margin-left: 50px;
			}
			
			#skip button {
				background: #ddd;
				color: #888;
				border: 3px solid #ccc;
				font-size: 100px;
				cursor: pointer;
			}
			
			#users {
				display: none;
				float: right;
				width: 200px;
				border: 3px solid #ccc; 
				padding: 20px;
				margin-top: 50px;
				margin-right: 50px;
			}
			
			#users h1 {
				font-size: 30px;
				margin-bottom: 10px;
			}
			
			#users div.user {
				font-size: 20px;
				line-height: 25px;
				color: #ccc;
			}
			
			#users div.user.active {
				color: #333;
			}
			
			#users div.user.owner {
				font-weight: bold;
			}
			
			#cards div.card {
				float: left;
				width: 200px;
				text-align: center;
				padding-top: 100px;
				padding-bottom: 100px;
				font-size: 125px;
				margin-left: 50px;
				margin-top: 50px;
				cursor: pointer;
				background: #ddd;
				color: #888;
				border: 3px solid #ccc;
			}
			
			#cards div.card:hover,#cards div.card:focus {
				background: #ccc;
				color: #333;
				border: 3px solid #aaa;
			}
			
			#cards div.card.selected {
				background: #eee;
				color: #000;
				border: 3px solid #000;
			}
			
			#cards div.card.fixed:hover,#cards div.card:focus {
				background: #ddd;
				color: #888;
				border: 3px solid #ccc;
			}
			
			#cards div.card.selected:hover {
				background: #eee;
				color: #000;
				border: 3px solid #000;
			}
			
			#cards div.card div.estimates {
				position: absolute;
			}
			
			#cards div.card div.estimate {
				font-size: 12px;
				padding: 5px;
				border: 1px solid #888;
				background: #ffe;
				margin-left: 10px;
				margin-bottom: 5px;
			}
			
			#start button:hover {
				background: #ccc;
				color: #333;
				border: 3px solid #aaa;
			}
			
			#projects {
				display: none;
			}
			
			#projects div.project {
				background: #ddd;
				color: #888;
				border: 3px solid #ccc;
				float: left;
				width: 300px;
				padding-bottom: 20px;
				padding-top: 20px;
				text-align: center;
				margin-left: 50px;
				margin-top: 50px;
				cursor: pointer;
				font-size: 30px;
			}
			
			#projects div.project:hover,#projects div.project:focus {
				background: #ccc;
				color: #333;
				border: 3px solid #aaa;
			}
			
			#login {
				display: none;
				margin-left: 50px;
				margin-top: 50px;
			}
			
			#login div {
				margin-bottom: 20px;
			}
			
			#login div label {
				font-size: 30px;
				margin-bottom: 10px;
				display: block;
			}
			
			#login div input {
				width: 300px;
				font-size: 30px;
				border: 3px solid #ccc; 
			}
			
			#login div button {
				width: 300px;
				font-size: 30px;
				background: #ddd;
				color: #888;
				border: 3px solid #ccc; 
				cursor: pointer;
			}
			
			#login div button:hover,#login div button:focus {
				background: #ccc;
				color: #333;
				border: 3px solid #aaa;
			}
		</style>
	</head>
	<body>
		
		<div class='ui-helper-clearfix' id='login'>
			<div class='ui-helper-clearfix'>
				<label for='username'>Email</label>
				<input class='ui-corner-all' type='text' value='' id='username' />
			</div>
			<div class='ui-helper-clearfix'>
				<label for='password'>Password</label>
				<input class='ui-corner-all' type='password' value='' id='password' />
			</div>
			<div class='ui-helper-clearfix'><button class='ui-corner-all'>Login</button></div>
		</div>
		
		<div class='ui-helper-clearfix' id='wait'>Waiting for a project owner to start the poker session...</div>
		<div class='ui-helper-clearfix' id='start'><button class='ui-corner-all'>Start Scrum Poker!</button></div>
		
		<div class='ui-helper-clearfix' id='projects'></div>
		
		<div class='ui-helper-clearfix' id='story'>
			<h1></h1>
			<h2>Received <span class='got'>0</span> / <span class='total'>0</span> estimates</h2>
			<p></p>
		</div>
		
		<div class='ui-corner-all ui-helper-clearfix' id='users'>
			<h1>Users</h1>
			<div class='list'></div>
		</div>
				
		<div class='ui-helper-clearfix' id='cards'>
			<div class='ui-corner-all card' id='est1'>
				<div class='ui-corner-all estimates'></div>
				1
			</div>
			<div class='ui-corner-all card' id='est2'>
				<div class='ui-corner-all estimates'></div>
				2
			</div>
			<div class='ui-corner-all card' id='est3'>
				<div class='ui-corner-all estimates'></div>
				3
			</div>
			<div class='ui-corner-all card' id='est5'>
				<div class='ui-corner-all estimates'></div>
				5
			</div>
			<div class='ui-corner-all card' id='est8'>
				<div class='ui-corner-all estimates'></div>
				8
			</div>
		</div>
		
		<div class='ui-helper-clearfix' id='skip'><button class='ui-corner-all'>Skip</button></div>
		
		<script type="text/javascript" src="js/jquery.js"></script>
		<script type="text/javascript" src="js/jquery_ui.min.js"></script>		
		<script type="text/javascript" src="js/plugins/jquery.bbq.js"></script>
		<script type="text/javascript" src="js/plugins/jquery.cookie.js"></script>
		<script type="text/javascript" src="js/plugins/jquery.metadata.js"></script>
		<script type="text/javascript" src="js/main.js"></script>
	</body>
</html>
