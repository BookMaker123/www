<?php /*a:2:{s:105:"D:\phpToolbox\myphp_www\PHPTutorial\WWW\Mywook\wwwroot\qxgl\TPCMS\application\admin\view\index\index.html";i:1576743567;s:110:"D:\phpToolbox\myphp_www\PHPTutorial\WWW\Mywook\wwwroot\qxgl\TPCMS\application\admin\view\admin\myselfinfo.html";i:1576657018;}*/ ?>
﻿<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
<meta name="renderer" content="webkit|ie-comp|ie-stand">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no" />
<meta http-equiv="Cache-Control" content="no-siteapp" />
<link rel="Bookmark" href="/favicon.ico" >
<link rel="Shortcut Icon" href="/favicon.ico" />
<!--[if lt IE 9]>
<script type="text/javascript" src="/static/lib/html5shiv.js"></script>
<script type="text/javascript" src="/static/lib/respond.min.js"></script>
<![endif]-->
<link rel="stylesheet" type="text/css" href="/static/static/h-ui/css/H-ui.min.css" />
<link rel="stylesheet" type="text/css" href="/static/static/h-ui.admin/css/H-ui.admin.css" />
<link rel="stylesheet" type="text/css" href="/static/lib/Hui-iconfont/1.0.8/iconfont.css" />
<link rel="stylesheet" type="text/css" href="/static/static/h-ui.admin/skin/green/skin.css" id="skin" />
<link rel="stylesheet" type="text/css" href="/static/static/h-ui.admin/css/style.css" />
<script type="text/javascript" src="/static/lib/jquery/1.9.1/jquery.min.js"></script> 
<script type="text/javascript" src="/static/lib/jquery.validation/1.14.0/jquery.validate.js"></script>
<!--[if IE 6]>
<script type="text/javascript" src="/static/lib/DD_belatedPNG_0.0.8a-min.js" ></script>
<script>DD_belatedPNG.fix('*');</script>
<![endif]--> 
<title>YPcms&nbsp;后台管理系统</title>
<meta name="keywords" content=" ">
<meta name="description" content=" ">
</head>
<body>
<header class="navbar-wrapper">
	<div class="navbar navbar-fixed-top">
		<div class="container-fluid cl"> <a class="logo navbar-logo f-l mr-10 hidden-xs" href="#"><b>YPcms&nbsp;后台管理系统</b></a> <a class="logo navbar-logo-m f-l mr-10 visible-xs" href="/aboutHui.shtml"><b>YPcms&nbsp;后台管理系统</b></a> 
			<span class="logo navbar-slogan f-l mr-10 hidden-xs"></span> 
			<a aria-hidden="false" class="nav-toggle Hui-iconfont visible-xs" href="javascript:;">&#xe667;</a>
			<nav class="nav navbar-nav">
				<ul class="cl">
					<li class="dropDown dropDown_hover"><a href="javascript:;" class="dropDown_A"><i class="Hui-iconfont">&#xe600;</i> 新增 <i class="Hui-iconfont">&#xe6d5;</i></a>
						<ul class="dropDown-menu menu radius box-shadow">
							<li><a href="javascript:;" onclick="article_add('添加资讯','article-add.html')"><i class="Hui-iconfont">&#xe616;</i> 资讯</a></li>
							<li><a href="javascript:;" onclick="picture_add('添加资讯','picture-add.html')"><i class="Hui-iconfont">&#xe613;</i> 图片</a></li>
							<li><a href="javascript:;" onclick="product_add('添加资讯','product-add.html')"><i class="Hui-iconfont">&#xe620;</i> 产品</a></li>
							<li><a href="javascript:;" onclick="member_add('添加用户','member-add.html','','510')"><i class="Hui-iconfont">&#xe60d;</i> 用户</a></li>
					</ul>
					<li class="navbar-levelone current"><a href="javascript:;">平台</a></li>
					<li class="navbar-levelone"><a href="javascript:;">商城</a></li>
					<li class="navbar-levelone"><a href="javascript:;">财务</a></li>
					<li class="navbar-levelone"><a href="javascript:;">手机</a></li>
				</li>
			</ul>
		</nav>
		<nav id="Hui-userbar" class="nav navbar-nav navbar-userbar hidden-xs">
			<ul class="cl">
				<li><a href="<?php echo url('member/login/index',[],'','i'); ?>" target="_blank">进入会员中心</a></li>
				<li><a href="<?php echo url('/',[],'','www'); ?>" target="_blank">进入前台</a></li>
				<li>账号：<?php echo htmlentities($list['username']); ?></li>
				<li><img class="admin-img" src="<?php echo htmlentities($list['avatar']); ?>"></li>
				<li class="dropDown dropDown_hover">
					<a href="#" class="dropDown_A"> <?php echo htmlentities($list['nickname']); ?> <i class="Hui-iconfont">&#xe6d5;</i></a>
					<ul class="dropDown-menu menu radius box-shadow">
						<li><a href="javascript:;" onClick="myselfinfo()">个人信息</a></li>
						<li><a href="javascript:;" onClick="clears()">清除缓存</a></li>
						<li><a href="#">切换账户</a></li>
						<li><a href="<?php echo url('login/logout'); ?>">退出</a></li>
				</ul>
			</li>
				<li id="Hui-msg"> <a href="#" title="消息"><span class="badge badge-danger">1</span><i class="Hui-iconfont" style="font-size:18px">&#xe68a;</i></a> </li>
				<li id="Hui-skin" class="dropDown right dropDown_hover"> <a href="javascript:;" class="dropDown_A" title="换肤"><i class="Hui-iconfont" style="font-size:18px">&#xe62a;</i></a>
					<ul class="dropDown-menu menu radius box-shadow">
						<li><a href="javascript:;" data-val="green" title="默认（绿色）">默认（绿色）</a></li>
						<li><a href="javascript:;" data-val="default" title="黑色">黑色</a></li>
						<li><a href="javascript:;" data-val="blue" title="蓝色">蓝色</a></li>
						<li><a href="javascript:;" data-val="red" title="红色">红色</a></li>
						<li><a href="javascript:;" data-val="yellow" title="黄色">黄色</a></li>
						<li><a href="javascript:;" data-val="orange" title="橙色">橙色</a></li>
					</ul>
				</li>
			</ul>
		</nav>
	</div>
</div> 
</header>
<aside class="Hui-aside">
	<div class="menu_dropdown bk_2">
		<?php if(is_array($ruleList) || $ruleList instanceof \think\Collection || $ruleList instanceof \think\Paginator): $i = 0; $__LIST__ = $ruleList;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?>
		<dl id="menu-<?php echo htmlentities($vo['contro']); ?>">
			<?php if($vo['level'] =='0'): ?>
			<dt><i class="Hui-iconfont">&#xe62d;</i> <a data-href="<?php if($vo['title'] =='控制台'): ?><?php echo url('index/welcome'); else: ?><?php echo url($vo['url']); ?><?php endif; ?>" data-title="<?php echo htmlentities($vo['title']); ?>" href="javascript:void(0)"><?php echo htmlentities($vo['title']); ?></a><i class="Hui-iconfont menu_dropdown-arrow">&#xe6d5;</i></dt>
			<?php endif; ?>
			<dd>
				<ul>
					<?php if(is_array($ruleList) || $ruleList instanceof \think\Collection || $ruleList instanceof \think\Paginator): $i = 0; $__LIST__ = $ruleList;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;if($vo['id'] ==$v['pid']): ?>
					<li><a data-href="<?php echo url($v['url']); ?>" data-title="<?php echo htmlentities($v['title']); ?>" href="javascript:void(0)"><?php echo htmlentities($v['title']); ?></a></li>
					<?php endif; ?>
					<?php endforeach; endif; else: echo "" ;endif; ?>
				</ul>
			</dd>
		</dl>
		<?php endforeach; endif; else: echo "" ;endif; ?>
	</div>
	<div class="menu_dropdown bk_2" style="display:none">
		<dl id="menu-aaaaa">
			<dt><i class="Hui-iconfont">&#xe616;</i> 二级导航1<i class="Hui-iconfont menu_dropdown-arrow">&#xe6d5;</i></dt>
			<dd>
				<ul>
					<li><a data-href="article-list.html" data-title="资讯管理" href="javascript:void(0)">三级导航</a></li>
				</ul>
			</dd>
		</dl>
	</div>

	<div class="menu_dropdown bk_2" style="display:none">
		<dl id="menu-bbbbb">
			<dt><i class="Hui-iconfont">&#xe616;</i> 二级导航2<i class="Hui-iconfont menu_dropdown-arrow">&#xe6d5;</i></dt>
			<dd>
				<ul>
					<li><a data-href="article-list.html" data-title="资讯管理" href="javascript:void(0)">三级导航</a></li>
				</ul>
			</dd>
		</dl>
	</div>

	<div class="menu_dropdown bk_2" style="display:none">
		<dl id="menu-ccccc">
			<dt><i class="Hui-iconfont">&#xe616;</i> 二级导航3<i class="Hui-iconfont menu_dropdown-arrow">&#xe6d5;</i></dt>
			<dd>
				<ul>
					<li><a data-href="article-list.html" data-title="资讯管理" href="javascript:void(0)">三级导航</a></li>
				</ul>
			</dd>
		</dl>
	</div>

</aside>
<div class="dislpayArrow hidden-xs"><a class="pngfix" href="javascript:void(0);" onClick="displaynavbar(this)"></a></div>
<section class="Hui-article-box">
	<div id="Hui-tabNav" class="Hui-tabNav hidden-xs">
		<div class="Hui-tabNav-wp">
			<ul id="min_title_list" class="acrossTab cl">
				<li class="active">
					<span title="我的桌面" data-href="<?php echo url('index/welcome'); ?>">我的桌面</span>
					<em></em></li>
		</ul>
	</div>
		<div class="Hui-tabNav-more btn-group"><a id="js-tabNav-prev" class="btn radius btn-default size-S" href="javascript:;"><i class="Hui-iconfont">&#xe6d4;</i></a><a id="js-tabNav-next" class="btn radius btn-default size-S" href="javascript:;"><i class="Hui-iconfont">&#xe6d7;</i></a></div>
</div>
	<div id="iframe_box" class="Hui-article">
		<div class="show_iframe">
			<div style="display:none" class="loading"></div>
			<iframe scrolling="yes" frameborder="0" src="<?php echo url('welcome'); ?>"></iframe>
	</div>
</div>
</section>

<div class="contextMenu" id="Huiadminmenu">
	<ul>
		<li id="closethis">关闭当前 </li>
		<li id="closeall">关闭全部 </li>
</ul>
</div>

<!-- 个人中心页面 -->
﻿<div id="myselfinfo" class="text-c .hidden-md">
	<div class="text-c">
		<div class="user-info">
			<div class="col-md-offset-1 img-responsive">
				<img src="<?php echo htmlentities($list['avatar']); ?>" class="avatar size-XXXL round" id="ed_avatar" alt="头像"> 
			</div>
			<div class="text-l va-b img-responsive ml-20">
				<span>帐户名：<?php echo htmlentities($list['username']); ?></span><br/>
				<span>用户名：<span id ="ed_nickname"><?php echo htmlentities($list['nickname']); ?></span></span><br/>
				<span>手机号码：<span id ="ed_phone"><?php echo htmlentities($list['phone']); ?></span></span><br/>
				<span>电子邮箱：<span id ="ed_email"><?php echo htmlentities($list['email']); ?></span></span>	
			</div>		
				<!-- <div class="user-account">
					<p class="tip">下午好，Tom</p>  
				</div>
				<div class="user-modify">
					<a href="#">修改资料&gt;</a>
				</div> -->
		</div>
		<div class="mt-50">
			<div class="codeView docs-example">
				<form id="user-info" action="" method="post" class="form form-horizontal text-l" id="demoform-1">
					<legend >修改个人信息/密码</legend>
					<div class="row cl">
						<label class="form-label col-xs-4 col-sm-3">头&nbsp;&nbsp;&nbsp;像：</label>
						<div class="formControls col-xs-8 col-sm-9"> <span class="btn-upload form-group">
							<input class="input-text upload-url" type="text"  id="uploadfile-2" readonly="">
							<a href="javascript:void();" class="btn btn-primary upload-btn"><i class="Hui-iconfont"></i> 浏览文件</a>
							<input type="file" multiple="" id="files" name="avatar" onchange="imgsrs(this)" class="input-file">
							</span> </div>
					</div>
					<div class="row cl">
						<label class="form-label col-xs-4 col-sm-3"></label>
						<div class="formControls col-xs-8 col-sm-9">
							<img id="shows" src="<?php echo htmlentities($list['avatar']); ?>"  alt="" style="border: 1px solid #cccccc;width: 150px;height: 150px;">
						</div>
					</div>
					<div class="row cl">
						<label class="form-label col-xs-4 col-sm-3">用户名：</label>
						<div class="formControls col-xs-8 col-sm-9">
							<input type="text" class="input-text" name="nickname" autocomplete="off" placeholder="用户名">
						</div>
					</div>
					<div class="row cl">
						<label class="form-label col-xs-4 col-sm-3">手机号码：</label>
						<div class="formControls col-xs-8 col-sm-9">
							<input type="text" class="input-text" name="phone" autocomplete="off" placeholder="手机号码">
						</div>
					</div>
					<div class="row cl">
						<label class="form-label col-xs-4 col-sm-3">电子邮箱：</label>
						<div class="formControls col-xs-8 col-sm-9">
							<input type="text" class="input-text" name="email" autocomplete="off" placeholder="电子邮箱">
						</div>
					</div>
					<div class="row cl">
						<label class="form-label col-xs-4 col-sm-3">密码：</label>
						<div class="formControls col-xs-8 col-sm-9">
							<input type="password" class="input-text" id="password" name="password" autocomplete="off" placeholder="密码">
						</div>
					</div>
					<div class="row cl">
						<label class="form-label col-xs-4 col-sm-3">确认密码：</label>
						<div class="formControls col-xs-8 col-sm-9">
							<input type="password" class="input-text" name="password2" autocomplete="off" placeholder="确认密码">
						</div>
					</div>
					<div class="row cl">
						<div class="col-xs-8 col-sm-9 col-xs-offset-4 col-sm-offset-3">
							<input class="btn btn-primary radius" id="submit" type="submit" value="提交">
							<input class="btn btn-warning radius" type="reset" value="重置">
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
	<script type="text/javascript">
		$("#user-info").validate({
				rules:{
					nickname:{
						required:false,
						minlength:4,
						maxlength:16,
					},
					phone:{
						required:false,
						isPhone:true,
					},
					email:{
						required:false,
						email:true,
					},
					// password:"required",
					password2:{
						equalTo: "#password"
					},
				},
				onkeyup:false,
				focusCleanup:true,
				success:"valid",
				submitHandler:function(form){
					$("#user-info").ajaxSubmit({
						type:'post',
						url: "user_edit",
						target: "#div2",
						dataType:"json",
						success: function(data){
							var icons = 1
							if (data.code == 1) {
								icons = 1
								if (data.data.nickname) {
									$("#ed_nickname").text(data.data.nickname);
								}
								if (data.data.phone) {
									$("#ed_phone").text(data.data.phone);
								}
								if (data.data.email) {
									$("#ed_email").text(data.data.email);
								}
								if (data.data.avatar) {
									$("#ed_avatar").attr("src",data.data.avatar);
								}
							$("#user-info").resetForm();
							} else {
								icons = 2
							}
							layer.msg(data.msg, {icon:icons,time:1000}, function(){
									if (data.url) {
										location.href = data.url;
									}
								});
						},
						error: function(XmlHttpRequest, textStatus, errorThrown){
							// layer.msg(textStatus, {icon:1,time:1000}, function(){
							// 			var index = parent.layer.getFrameIndex(window.name);
							// 			// parent.location.reload(); //刷新父页面
							// 			parent.layer.close(index);
							// 		});
						}
					});
					return false;
				}
			});
	</script> 
</div>

<!--_footer 作为公共模版分离出去-->

<script type="text/javascript" src="/static/lib/layer/2.4/layer.js"></script>
<script type="text/javascript" src="/static/static/h-ui/js/H-ui.min.js"></script>
<script type="text/javascript" src="/static/static/h-ui.admin/js/H-ui.admin.js"></script> <!--/_footer 作为公共模版分离出去-->

<!--请在下方写此页面业务相关的脚本-->
	<!--请在下方写此页面业务相关的脚本-->
 
	<script type="text/javascript" src="/static/lib/jquery.validation/1.14.0/validate-methods.js"></script> 
	<script type="text/javascript" src="/static/lib/jquery.validation/1.14.0/messages_zh.js"></script> 
	<script type="text/javascript" src="/static/lib/jquery.contextmenu/jquery.contextmenu.r2.js"></script>
	<script type="text/javascript" src="/static/validation/user_info.js"></script>

<script type="text/javascript">
var myselfinfo_html2 = '';
$(function(){
	/*$("#min_title_list li").contextMenu('Huiadminmenu', {
		bindings: {
			'closethis': function(t) {
				console.log(t);
				if(t.find("i")){
					t.find("i").trigger("click");
				}		
			},
			'closeall': function(t) {
				alert('Trigger was '+t.id+'\nAction was Email');
			},
		}
	});*/
	$("body").Huitab({
		tabBar:".navbar-wrapper .navbar-levelone",
		tabCon:".Hui-aside .menu_dropdown",
		className:"current",
		index:0,
	});
});

/*个人信息*/
function myselfinfo(){
	var myselfinfo_html = $("#myselfinfo").html();
	//备份
	if (myselfinfo_html) {
		myselfinfo_html2 = myselfinfo_html;
	}

	var obj = document.getElementById("myselfinfo");//建议使用ID
	if (obj != null) {
		obj.parentNode.removeChild(obj);
	}
	layer.open({
		type: 1,
		area: ['800px','700px'],
		fix: false, //不固定
		maxmin: true,
		shade:0.4,
		title: '查看信息',
		content: myselfinfo_html2
	});


}

/*资讯-添加*/
function article_add(title,url){
	var index = layer.open({
		type: 2,
		title: title,
		content: url
	});
	layer.full(index);
}
/*图片-添加*/
function picture_add(title,url){
	var index = layer.open({
		type: 2,
		title: title,
		content: url
	});
	layer.full(index);
}
/*产品-添加*/
function product_add(title,url){
	var index = layer.open({
		type: 2,
		title: title,
		content: url
	});
	layer.full(index);
}
/*用户-添加*/
function member_add(title,url,w,h){
	layer_show(title,url,w,h);
}
/*清除缓存*/
function clears() {
	$.ajax({
             type: "post",
             url: "clear_all",
             data: {},
             dataType: "json",
             success: function(data){
				layer.msg(data.msg, {icon:1,time:1000}, function(){
									if (data.url) {
										location.href = data.url;
									}
								});
			 }
		});

}

function imgsrs(currentObj){
	var reader = new FileReader()
	fileObj = document.getElementById('files').files[0];
	reader.readAsDataURL(fileObj)
	reader.onload = function(e) {
		document.getElementById('shows').src= this.result;
	  }
    }

</script> 

</body>
</html>