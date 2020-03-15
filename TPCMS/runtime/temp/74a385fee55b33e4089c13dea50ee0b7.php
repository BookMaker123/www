<?php /*a:1:{s:106:"D:\phpToolbox\myphp_www\PHPTutorial\WWW\Mywook\wwwroot\qxgl\TPCMS\application\admin\view\system\index.html";i:1571661562;}*/ ?>
﻿<!--_meta 作为公共模版分离出去-->
<!DOCTYPE HTML>
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
<link rel="stylesheet" type="text/css" href="/static/static/h-ui.admin/skin/default/skin.css" id="skin" />
<link rel="stylesheet" type="text/css" href="/static/static/h-ui.admin/css/style.css" />
<!--[if IE 6]>
<script type="text/javascript" src="/static/lib/DD_belatedPNG_0.0.8a-min.js" ></script>
<script>DD_belatedPNG.fix('*');</script>
<![endif]-->
<!--/meta 作为公共模版分离出去-->

<title>基本设置</title>
</head>
<body>
<nav class="breadcrumb"><i class="Hui-iconfont">&#xe67f;</i> 首页
	<span class="c-gray en">&gt;</span>
	系统管理
	<span class="c-gray en">&gt;</span>
	基本设置
	<a class="btn btn-success radius r" style="line-height:1.6em;margin-top:3px" href="javascript:location.replace(location.href);" title="刷新" ><i class="Hui-iconfont">&#xe68f;</i></a>
</nav>
<div class="page-container">
	<form class="form form-horizontal" action="" method="post"  id="form-article-add">
		<div id="tab-system" class="HuiTab">
			<div class="tabBar cl">
				<span>基本设置</span>
				<span>安全设置</span>
				<span>邮件设置</span>
				<span>短信设置</span>
				<span>微信设置</span>
				<span>支付宝设置</span>
				<span>其他设置</span>
			</div>
			<div class="tabCon">
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">
						<span class="c-red">*</span>
						网站名称：</label>
					<div class="formControls col-xs-8 col-sm-9">
						<input type="text" id="website-title" name="title" placeholder="控制在25个字、50个字节以内" value="<?php echo htmlentities($list['title']); ?>" class="input-text">
					</div>
				</div>
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">
						<span class="c-red">*</span>
						关键词：</label>
					<div class="formControls col-xs-8 col-sm-9">
						<input type="text" id="website-Keywords" name="keywords" placeholder="5个左右,8汉字以内,用英文,隔开" value="<?php echo htmlentities($list['keywords']); ?>" class="input-text">
					</div>
				</div>
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">
						<span class="c-red">*</span>
						描述：</label>
					<div class="formControls col-xs-8 col-sm-9">
						<input type="text" id="website-description" name="description" placeholder="空制在80个汉字，160个字符以内" value="<?php echo htmlentities($list['description']); ?>" class="input-text">
					</div>
				</div>
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">
						<span class="c-red">*</span>
						底部版权信息：</label>
					<div class="formControls col-xs-8 col-sm-9">
						<input type="text" id="website-copyright" name="copyright" placeholder="&copy; 2016 H-ui.net" value="<?php echo htmlentities($list['copyright']); ?>" class="input-text">
					</div>
				</div>
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">备案号：</label>
					<div class="formControls col-xs-8 col-sm-9">
						<input type="text" id="website-icp" name="icps" placeholder="京ICP备00000000号" value="<?php echo htmlentities($list['icps']); ?>" class="input-text">
					</div>
				</div>
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">百度统计代码：</label>
					<div class="formControls col-xs-8 col-sm-9">
						<textarea name="tongji" class="textarea"><?php echo htmlentities($list['tongji']); ?></textarea>
					</div>
				</div>
			</div>
			<div class="tabCon">
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">允许访问后台的IP列表： <br><br>(用" , "隔开)</label>
					<div class="formControls col-xs-8 col-sm-9">
						<textarea class="textarea" name="ip_list" placeholder=" 例如：127.0.0.1,222.246.129.80,172.16.4.247" id=""><?php echo htmlentities($list['ip_list']); ?></textarea>
					</div>
				</div>
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">敏感词过滤：<br>(用" | "隔开)</label>
					<div class="formControls col-xs-8 col-sm-9">
						<textarea class="textarea" name="sensitive" placeholder=" 例如：沙雕|二哈|渣渣" id=""><?php echo htmlentities($list['ip_list']); ?></textarea>
					</div>
				</div>
			</div>
			<div class="tabCon">
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">邮件发送模式：</label>
					<div class="formControls col-xs-8 col-sm-9">
						<input type="text"  class="input-text" value="<?php echo htmlentities($list['moshi']); ?>" id="" name="moshi">
					</div>
				</div>
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">SMTP服务器：</label>
					<div class="formControls col-xs-8 col-sm-9">
						<input type="text" id="" value="<?php echo htmlentities($list['smtps']); ?>" name="smtps" class="input-text">
					</div>
				</div>
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">SMTP 端口：</label>
					<div class="formControls col-xs-8 col-sm-9">
						<input type="text" class="input-text" value="<?php echo htmlentities($list['smtp_duankou']); ?>" id="" name="smtp_duankou" >
					</div>
				</div>
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">邮箱帐号：</label>
					<div class="formControls col-xs-8 col-sm-9">
						<input type="text" class="input-text" value="<?php echo htmlentities($list['email']); ?>" id="email" name="email" >
					</div>
				</div>
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">邮箱密码：</label>
					<div class="formControls col-xs-8 col-sm-9">
						<input type="password" id="email-password" name="email_pas" value="<?php echo htmlentities($list['email_pas']); ?>" class="input-text">
					</div>
				</div>
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">收件邮箱地址：</label>
					<div class="formControls col-xs-8 col-sm-9">
						<input type="text" id="email-address" name="email_dizhi" value="<?php echo htmlentities($list['email_dizhi']); ?>" class="input-text">
					</div>
				</div>
			</div>
			<div class="tabCon">
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">阿里大于APPKEY：</label>
					<div class="formControls col-xs-8 col-sm-9">
						<input type="text" id="" value="<?php echo htmlentities($list['app_key']); ?>" name="app_key" class="input-text">
					</div>
				</div>
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">阿里大于secretKey：</label>
					<div class="formControls col-xs-8 col-sm-9">
						<input type="text" class="input-text" value="<?php echo htmlentities($list['secret_key']); ?>" id="" name="secret_key" >
					</div>
				</div>
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">短信签名：</label>
					<div class="formControls col-xs-8 col-sm-9">
						<input type="text" class="input-text" value="<?php echo htmlentities($list['message']); ?>" id="message" name="message" >
					</div>
				</div>
			</div>
						<div class="tabCon">
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">APP的appid：</label>
					<div class="formControls col-xs-8 col-sm-9">
						<input type="text" id="" value="<?php echo htmlentities($list['appid']); ?>" name="appid" class="input-text">
					</div>
				</div>
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">微信商户ID：</label>
					<div class="formControls col-xs-8 col-sm-9">
						<input type="text" class="input-text" value="<?php echo htmlentities($list['mch_id']); ?>" id="" name="mch_id" >
					</div>
				</div>
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">微信商户支付的密钥：</label>
					<div class="formControls col-xs-8 col-sm-9">
						<input type="text" class="input-text" value="<?php echo htmlentities($list['wx_key']); ?>" id="wx_key" name="wx_key" >
					</div>
				</div>
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">公众号的appid：</label>
					<div class="formControls col-xs-8 col-sm-9">
						<input type="text" class="input-text" value="<?php echo htmlentities($list['app_id']); ?>" id="app_id" name="app_id" >
					</div>
				</div>
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">公众号的secret：</label>
					<div class="formControls col-xs-8 col-sm-9">
						<input type="text" class="input-text" value="<?php echo htmlentities($list['app_secret']); ?>" id="app_secret" name="app_secret" >
					</div>
				</div>
				<div class="row cl">
					<label class="form-label col-xs-4 col-sm-2">小程序ID：</label>
					<div class="formControls col-xs-8 col-sm-9">
						<input type="text" class="input-text" value="<?php echo htmlentities($list['miniapp_id']); ?>" id="miniapp_id" name="miniapp_id" >
					</div>
				</div>
			</div>
			<div class="tabCon">
			</div>
		</div>
		<div class="row cl">
			<div class="col-xs-8 col-sm-9 col-xs-offset-4 col-sm-offset-2">
				<button onClick="article_save_submit();" class="btn btn-primary radius" type="submit"><i class="Hui-iconfont">&#xe632;</i> 保存</button>
				<button onClick="layer_close();" class="btn btn-default radius" type="button">&nbsp;&nbsp;取消&nbsp;&nbsp;</button>
			</div>
		</div>
	</form>
</div>

<!--_footer 作为公共模版分离出去-->
<script type="text/javascript" src="/static/lib/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript" src="/static/lib/layer/2.4/layer.js"></script>
<script type="text/javascript" src="/static/static/h-ui/js/H-ui.min.js"></script>
<script type="text/javascript" src="/static/static/h-ui.admin/js/H-ui.admin.js"></script> <!--/_footer 作为公共模版分离出去-->

<!--请在下方写此页面业务相关的脚本-->
<script type="text/javascript" src="/static/lib/My97DatePicker/4.8/WdatePicker.js"></script>
<script type="text/javascript" src="/static/lib/jquery.validation/1.14.0/jquery.validate.js"></script>
<script type="text/javascript" src="/static/lib/jquery.validation/1.14.0/validate-methods.js"></script>
<script type="text/javascript" src="/static/lib/jquery.validation/1.14.0/messages_zh.js"></script>
<script type="text/javascript">
$(function(){
	$('.skin-minimal input').iCheck({
		checkboxClass: 'icheckbox-blue',
		radioClass: 'iradio-blue',
		increaseArea: '20%'
	});
	$("#tab-system").Huitab({
		index:0
	});
});
</script>
<!--/请在上方写此页面业务相关的脚本-->
</body>
</html>
