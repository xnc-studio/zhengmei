<!DOCTYPE html>
<!--[if IE 9]><html class="lt-ie10" lang="en" > <![endif]-->
<html class="no-js">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>正妹臉書</title>
		<link rel="stylesheet" href="css/normalize.css">
		<link rel="stylesheet" href="css/foundation.css">
		<link rel="stylesheet" href="css/style.css">
	</head>
	<body>
		<header>
			<div class="row header">
			  <div class="small-4 large-7 columns">
			  	<a class="logo" href="#"><span>正妹脸书</span></a>
			  </div>
			  <div class="small-8 large-5 columns">
			  	<ul class="nav hidden-for-small-only">
			  		<li><a href="#">首页</a></li>
			  		<li><a href="/stream">正妹流</a></li>
			  		<li><a href="#">每日PK</a></li>
			  		<li><a href="#">我的正妹</a></li>
			  		<li><a href="#">下载App</a></li>
			  	</ul>
			  </div>
			</div>	
		</header>
		<header class="mobile">
		</header>
		<div class="row" id="helper">
			<div class="large-12 columns">1</div>
		</div>

		<div class="row" id="main">
			<div class="large-8 columns left-panel">
				@yield('left-panel')
			</div>
			<div class="large-4 columns right-panel">
				@yield('right-panel')
			</div>
		</div>
		
		<script src="./sea-modules/seajs/seajs/2.2.0/sea.js"></script>
		<script>
		seajs.config({
		base: "./sea-modules/",
		alias: {
		"modernizr": "modernizr/modernizr/2.7.2/modernizr.js",
		"jquery": "jquery/jquery/1.10.1/jquery.js",
			"fastclick": "fastclick/fastclick/1.0.0/fastclick.js",
			"foundation": "zurb/foundation/5.2.2/foundation.min.js"
		}
		});
		if (location.href.indexOf("dev") > 0) {
		seajs.use("./js/src/main");
		}
		else {
		seajs.use("zhengmei/app/1.0.0/main");
		}
		</script>
	</body>
</html>