'use strict';
(function() {
	var app = angular.module('app', []);
	app.controller('fileSystemController', function($scope,$http,$timeout) {
		$scope.process = {};
		var curPath = "";
		var fileId = 0;
		var $post = function(data,config){
			return $http.post("fs.php",data,config);
		};
		var load = function(path){
			$post({cmd:'ls', basePath : $scope.basePath, baseUrl : $scope.baseUrl, path:path || "/"}).success(function(data){
				data.files.sort(function(a,b){
					return (b.isDir - a.isDir)*10 + a.name.localeCompare(b.name);
				});
				$scope.files = data.files;
				sessionStorage.path = curPath = data.path || "";
				path = curPath.split("/");
				$scope.paths = [];
				for( var i in path ){
					$scope.paths.push( { name:path[i] || "/" , path:path.slice(0,i*1+1).join("/") } )
				}
				if( $scope.paths[0] && $scope.paths[0].name != '/' ){
					$scope.paths.unshift( {name:'/' , path:'/'} );
				}
			}).error(function(){
				sessionStorage.path = "";
			});
		}
		var getStyle = function(pre){
			if(pre==1)return "background:red";
			var s = 'background-image:linear-gradient($adeg, $b 50%, transparent 50%, transparent),linear-gradient($cdeg,#000 50%, #FFF 50%, #fff);'
			var n = pre*360;

			n = n < 0 ? 360-(n * -1)%360 : n;
			n = n % 360;

			return s = s.replace("$a",n < 180 ? '90' : ( n-270 )).replace("$b", n < 180 ? '#fff':'#000').replace("$c",n<180? (n+90) :270);

		}


		$scope.clickFile = function(){
			this.file.isDir ? load(this.file.path + "/" + this.file.name) : window.open(this.file.url ,"_blank");
			$.log(this.file);
		}

		$scope.cd = function(){
			console.log(this);	
			load(this.path.path);
		};

		$scope.delete = function(){
			var paths = [];

			$.each($scope.files.filter(function(f){return f.checked;}),function(){
				paths.push(this.localtion);
			});
			paths.length && $post({cmd:"rm", basePath : $scope.basePath, baseUrl : $scope.baseUrl, paths:paths}).success(function(data){
				load(curPath);
			});
		};
		$scope.mkdir = function(){
			var $box = $.box({
				title:'mkdir',
				html:"<div><input type=text class=form-control name=name placeholder=name... /></div>",
				ok:function(){
					var data = $box.getData();
					data.name && $post({cmd:'mkdir', basePath : $scope.basePath, baseUrl : $scope.baseUrl, path:curPath + "/" + data.name}).success(function(){
						load(curPath);
					});
				}
			});
		};

		$scope.updatePath = function(){
			localStorage.basePath = $scope.basePath;
			localStorage.baseUrl = $scope.baseUrl;
			load("");
		};

		$(":file").change(function(){
			var u = function(file){
				var id = fileId ++;
				$scope.process[id] = {id:id,name:file.name};
				new $().uploadFile("fs.php",{cmd:"upload",path:curPath,file:file,basePath : $scope.basePath, baseUrl : $scope.baseUrl},function(){
					delete $scope.process[id];
					load(curPath);
				},function(pre){
					//pre = pre == 1 ? 0.999999: pre;
					$scope.process[id].pre = (pre*100).toFixed(2) + "%";
					$scope.process[id].style = getStyle(pre);
					$timeout(function(){},1);
				});
			};

			for( var i = 0 , f ; f = this.files[i] ; i++ ){
				u(f);
			}
			$(this).val("");
		});
		//$scope.process = {a:{pre:1,name:"asdf"},b:{pre:8,name:"hh"}};
		$scope.basePath = localStorage.basePath;
		$scope.baseUrl = localStorage.baseUrl;
		load(sessionStorage.path||"");
	});

	window.app = app;
})();