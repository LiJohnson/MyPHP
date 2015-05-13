<?php
?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />

		<link  type="image/x-icon" rel="shortcut icon" href="javascript:;">

		<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
		<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css">
		<link rel="stylesheet" href="css/fs.css">
		
		<script src="//ajax.useso.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
		<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
		<script src="//gtbcode.sinaapp.com/js/jquery.plugin.js"></script>
		<script src="angular.js"></script>
		<script>
		window.BASE_PATH = "<?=$_GET['basePath']?>";
		window.BASE_URL = "<?=$_GET['baseUrl']?>";
		</script>
		<script src="fs.js"></script>
		<script>$.box = $.box3 || $.box;</script>
		<title>FS</title>
	</head>
	<body ng-app="app"  ng-controller="fileSystemController" >
		<header >
			<nav class="navbar navbar-default animate animate-child" role="navigation">
				<div class="container">
					<div class="navbar-header">
						<button type="button" class="navbar-toggle" data-toggle="collapse" >
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						</button>
						<a class="navbar-brand" >abs</a>		 
					</div>

				<div class="collapse navbar-collapse">
					
					<ul class="nav navbar-nav">
						<li> <a href="javascript:;" ng-click="delete()" >delete</a> </li>
						<li> <a href="javascript:;" ng-click="mkdir()" >mkdir</a> </li>
					</ul>
					<form class="navbar-form navbar-left">
						<div class="form-group file-input" >
							<input type="file" class="form-control" multiple value="abs" name="file" >
							<span class="cover" ></span>
						</div>
					</form>
					<form class="navbar-form navbar-left" >
						<input type="text" ng-model="basePath" ng-change="updatePath()" class="form-control" style="width:200px;" placeholder="basepath" ng-init="basePath='<?=$_GET['basePath']?>'" />
						<input type="text" ng-model="baseUrl" ng-change="updatePath()" class="form-control" style="width:200px;" placeholder="baseUrl"  ng-init="baseUrl='<?=$_GET['baseUrl']?>'" />
					</form>
					<ul class="nav navbar-nav navbar-left">
						<li>
							<table class="table table-bordered nav navbar-nav navbar-left table-condensed navbar-form">
							<tr>
							<td ng-repeat=" p in process" ><div class="process" style={{p.style}}></div>{{p.name}}</td>
							</tr>
						</table>
						</li>
					</ul>
				</div><!-- /.navbar-collapse -->

				</div><!-- /.container-fluid -->
			</nav>
		</header>

		<div class="container fs" >
			<div class="row path" >
				<ul>
					<li ng-repeat="path in paths" ng-click="cd()">
						{{path.name}}
					</li>
				</ul>
			</div>
			<div class="row file" >
				<ul>
					<li ng-repeat="file in files"  >
						<input type="checkbox" ng-click="selectFile()" ng-model="file.checked" >
						<i class="file-icon glyphicon glyphicon-file  {{file.type}}" ng-click="clickFile()" >
						</i>
						<span class="file-name" >
							{{file.name}}
						</span>
					</li>
				</ul>
			</div>
		</div>
	</body>
</html>