<?php /*a:1:{s:107:"D:\phpToolbox\myphp_www\PHPTutorial\WWW\Mywook\wwwroot\qxgl\TPCMS\application\admin\view\index\welcome.html";i:1571661562;}*/ ?>
﻿<!DOCTYPE HTML>
<html> 
<head>
<meta charset="utf-8">
<meta name="renderer" content="webkit|ie-comp|ie-stand">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no" />
<meta http-equiv="Cache-Control" content="no-siteapp" />
<!--[if lt IE 9]> 
<script type="text/javascript" src="/static/lib/html5shiv.js"></script>
<script type="text/javascript" src="/static/lib/respond.min.js"></script>
<![endif]-->
<link rel="stylesheet" type="text/css" href="/static/static/h-ui/css/H-ui.min.css" />
<link rel="stylesheet" type="text/css" href="/static/static/h-ui.admin/css/H-ui.admin.css" />
<link rel="stylesheet" type="text/css" href="/static/lib/Hui-iconfont/1.0.8/iconfont.css" />
<link rel="stylesheet" type="text/css" href="/static/static/h-ui.admin/skin/default/skin.css" id="skin" />
<link rel="stylesheet" type="text/css" href="/static/static/h-ui.admin/css/style.css" />
<!--[if IE 6]>
<script type="text/javascript" src="/static/lib/DD_belatedPNG_0.0.8a-min.js" ></script>
<script>DD_belatedPNG.fix('*');</script>
<![endif]-->
<title>我的桌面</title>
</head>
<body>
<div class="page-container">
	<p class="f-20 text-success">欢迎使用YPcms&nbsp;后台管理系统 <span class="f-14"></span></p>
	<p>登录次数：18 </p>
	<p>上次登录IP：222.35.131.79.1  上次登录时间：2014-6-14 11:19:55</p>
	<table class="table table-border table-bordered table-bg">
		<thead>
			<tr>
				<th colspan="7" scope="col">信息统计</th>
			</tr>
			<tr class="text-c">
				<th>统计</th>
				<th>资讯库</th>
				<th>图片库</th>
				<th>产品库</th>
				<th>用户</th>
				<th>管理员</th>
			</tr>
		</thead>
		<tbody>
			<tr class="text-c">
				<td>总数</td>
				<td>92</td>
				<td>9</td>
				<td>0</td>
				<td>8</td>
				<td>20</td>
			</tr>
			<tr class="text-c">
				<td>今日</td>
				<td>0</td>
				<td>0</td>
				<td>0</td>
				<td>0</td>
				<td>0</td>
			</tr>
			<tr class="text-c">
				<td>昨日</td>
				<td>0</td>
				<td>0</td>
				<td>0</td>
				<td>0</td>
				<td>0</td>
			</tr>
			<tr class="text-c">
				<td>本周</td>
				<td>2</td>
				<td>0</td>
				<td>0</td>
				<td>0</td>
				<td>0</td>
			</tr>
			<tr class="text-c">
				<td>本月</td>
				<td>2</td>
				<td>0</td>
				<td>0</td>
				<td>0</td>
				<td>0</td>
			</tr>
		</tbody>
	</table>
	<table class="table table-border table-bordered table-bg mt-20">
		<thead>
			<tr>
				<th colspan="2" scope="col">服务器信息</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th width="30%">服务器计算机名</th>
				<td><span id="lbServerName"><?php echo htmlentities($data['pcname']); ?></span></td>
			</tr>
			<tr>
				<td>服务器操作系统 </td>
				<td><?php echo htmlentities($data['uname']); ?></td>
			</tr>
			<tr>
				<td>服务器版本 </td>
				<td><?php echo htmlentities($data['unames']); ?></td>
			</tr>
			<tr>
				<td>客户端IP</td>
				<td><?php echo htmlentities($data['ip']); ?></td>
			</tr>
			<tr>
				<td>服务器域名</td>
				<td><?php echo htmlentities($data['userdomain']); ?></td>
			</tr>
			<tr>
				<td>PHP版本 </td>
				<td><?php echo htmlentities($data['version']); ?></td>
			</tr>
			<tr>
				<td>Zend版本 </td>
				<td><?php echo htmlentities($data['zend']); ?></td>
			</tr>
			<tr>
				<td>PHP运行方式 </td>
				<td><?php echo htmlentities($data['sapi']); ?></td>
			</tr>
			<tr>
				<td>最大上传限制 </td>
				<td><?php echo htmlentities($data['filesize']); ?></td>
			</tr>
			<tr>
				<td>最大执行时间 </td>
				<td><?php echo htmlentities($data['execution']); ?></td>
			</tr>
			<tr>
				<td>脚本运行占用最大内存 </td>
				<td><?php echo htmlentities($data['memory']); ?></td>
			</tr>
			<tr>
				<td>服务器语言 </td>
				<td><?php echo htmlentities($data['accept']); ?></td>
			</tr>
		</tbody>
	</table>
</div>
<footer class="footer mt-20">
	<div class="container">
		<p>Copyright &copy;2019-2020 xxxxxx All Rights Reserved.<br>
			本后台系统由<a href="" target="_blank" title="xxxxxx">xxxxxx</a>提供前端技术支持</p>
	</div>
</footer>
<script type="text/javascript" src="/static/lib/jquery/1.9.1/jquery.min.js"></script> 
<script type="text/javascript" src="/static/static/h-ui/js/H-ui.min.js"></script> 
</body>
</html>