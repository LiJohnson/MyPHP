<?php
if( $_GET['debug'] ){
	var_dump(array('Get' => $_GET , 'POST' => $_POST));
	var_dump($_SERVER);
	exit();
}
if( $_GET['post'] ){
	include __DIR__ ."/../MyCurl.php";

	$client = new MyCurl();
	$client->setHeader($_POST['header']);
	$client->setCookie($_POST['cookie']);

	$client->setPostData($_POST['postData']);
	$client->isAjax($_POST['ajax']);

	if( $_POST['post'] ){
		$res .= ($client->post($_POST['debugUrl']));
		$res .= $client->post($_POST['url']);
	}else{
		$res .= ($client->get($_POST['debugUrl']));
		$res .= $client->get($_POST['url']);
	}

	echo $res;
exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
	<!--
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">
	-->
	<script src="http://gtbcode.sinaapp.com/load.php?type=js&load=jquery.js,jquery.plugin.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
	<script>$.box = $.box3 || $.box;</script>
	<title>testweibo</title>
</head>
<body>
	<div class="container-fluid">
		<form class="form-horizontal col-md-6" action="?post=post" method="post" target="post">
			<input type="hidden" name="debugUrl">
			<div class="form-group">
				<label for="url" class="control-label col-md-2">url</label>
				<div class="col-md-10"><input type="url" name="url" class="form-control"></div>
			</div>

			<div class="form-group">
				<label for="header" class="control-label col-md-2">header</label>
				<div class="col-md-10"><textarea name="header" class="form-control" rows="5" ></textarea></div>
			</div>

			<div class="form-group">
				<label for="cookie" class="control-label col-md-2">cookie</label>
				<div class="col-md-10"><textarea name="cookie" class="form-control" rows="5" ></textarea></div>
			</div>

			<div class="form-group">
				<label for="post" class="control-label col-md-2">post</label>
				<div class=" col-md-10">
					<input type="checkbox" name="post">
					<textarea name="postData"  rows="5" class="form-control"></textarea>
				</div>
			</div>
			
			<div class="form-group">
				<div class="col-md-offset-2 col-md-10">
					<div class="checkbox">
						<label>
							<input type="checkbox" name="ajax">ajax
						</label>
					</div>
				</div>
			</div>

			<div class="form-group">
				<div class="col-md-offset-2 col-md-10"><input type="submit" class="btn btn-primary"></div>
			</div>
		</form>

		<iframe class="col-md-6" id="post" name="post" height="2000" ></iframe>
	</div>
	<script>document.querySelectorAll('[name=debugUrl]')[0].value=location.href+"?debug=debug"</script>
</body>
</html>

