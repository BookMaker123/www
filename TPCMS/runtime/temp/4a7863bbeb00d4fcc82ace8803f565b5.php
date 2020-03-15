<?php /*a:1:{s:105:"D:\phpToolbox\myphp_www\PHPTutorial\WWW\Mywook\wwwroot\qxgl\TPCMS\application\admin\view\admin\index.html";i:1576826555;}*/ ?>
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
<link rel="stylesheet" type="text/css" href="/static/static/h-ui.admin/skin/default/skin.css" id="skin" />
<link rel="stylesheet" type="text/css" href="/static/static/h-ui.admin/css/style.css" />
<!--[if IE 6]>
<script type="text/javascript" src="/static/lib/DD_belatedPNG_0.0.8a-min.js" ></script>
<script>DD_belatedPNG.fix('*');</script>
<![endif]-->
<title>管理员列表</title>
</head>
<body id="body">
<nav class="breadcrumb"><i class="Hui-iconfont">&#xe67f;</i> 首页 <span class="c-gray en">&gt;</span> 管理员管理 <span class="c-gray en">&gt;</span> 管理员列表 <a class="btn btn-success radius r" style="line-height:1.6em;margin-top:3px" href="javascript:location.replace(location.href);" title="刷新" ><i class="Hui-iconfont">&#xe68f;</i></a></nav>
<div class="page-container">
	<form class="submit_form" method="post" action="" target="_self">
	<div class="text-c"> 
		<input type="text" class="input-text" style="width:250px" placeholder="输入管理员名称" id="" name="username">
		<button type="button" class="btn btn-success" id="submit_form" name=""><i class="Hui-iconfont">&#xe665;</i> 搜用户</button>
	</div>
	</form>
	<div class="cl pd-5 bg-1 bk-gray mt-20"> <span class="l"><a href="javascript:;" onclick="datadel()" class="btn btn-danger radius"><i class="Hui-iconfont">&#xe6e2;</i> 批量删除</a> <a href="javascript:;" onclick="admin_add('添加管理员','<?php echo url('admin/add'); ?>','800','680')" class="btn btn-primary radius"><i class="Hui-iconfont">&#xe600;</i> 添加管理员</a></span> <span class="r">共有数据：<strong><?php echo htmlentities($count); ?></strong> 条</span> </div>
	<table class="table table-border table-bordered table-bg">
		<thead>
			<tr>
				<th scope="col" colspan="15">员工列表</th>
			</tr>
			<tr class="text-c">
				<th width="25"><input type="checkbox" name="" value=""></th>
				<th width="40">ID</th>
				<th width="150">管理员账号</th>
				<th width="90">昵称</th>
				<th width="90">所属组别角色</th>
				<th>头像</th>
				<th>手机号</th>
				<th>电子邮箱</th>
				<th>状态</th>
				<th>客户端登录IP</th>
				<th>失败次数</th>
				<th width="130">最后登录时间</th>
				<!-- <th width="100">是否已启用</th> -->
				<th>创建时间</th>
				<th>更新时间</th>
				<th width="100">操作</th>
			</tr>
		</thead>
		<tbody> 
			<?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?>
			<tr class="text-c">
				<td><input type="checkbox" value="<?php echo htmlentities($vo['id']); ?>" name="ids"></td>
				<td><?php echo htmlentities($vo['id']); ?></td>
				<td><?php echo htmlentities($vo['username']); ?></td>
				<td><?php echo htmlentities($vo['nickname']); ?></td>
				<td><?php echo htmlentities($vo['name']); ?></td>
				<td><img width="50" height="50" src="<?php echo htmlentities($vo['avatar']); ?>"></td>
				<td><?php echo htmlentities($vo['phone']); ?></td>
				<td><?php echo htmlentities($vo['email']); ?></td>
				<td><?php if($vo['status'] == 'normal'): ?>启用 <?php else: ?> 禁用<?php endif; ?></td>
				<td><?php echo htmlentities($vo['ip']); ?></td>
				<td><?php echo htmlentities($vo['loginfailure']); ?></td>
				<td><?php echo htmlentities(date('Y-m-d H:i:s',!is_numeric($vo['logintime'])? strtotime($vo['logintime']) : $vo['logintime'])); ?></td>
				<td><?php echo htmlentities(date('Y-m-d H:i:s',!is_numeric($vo['createtime'])? strtotime($vo['createtime']) : $vo['createtime'])); ?></td>
				<td><?php echo htmlentities(date('Y-m-d H:i:s',!is_numeric($vo['updatetime'])? strtotime($vo['updatetime']) : $vo['updatetime'])); ?></td>
				<td class="td-manage">
					<?php if($vo['username'] !=='admin'): ?>
					<a title="编辑" href="javascript:;" onclick="admin_edit('管理员编辑','<?php echo url('admin/edit'); ?>?ids=<?php echo htmlentities($vo['id']); ?>','1','800','680')" class="ml-5" style="text-decoration:none"><i class="Hui-iconfont">&#xe6df;</i></a> 
					<a style="text-decoration:none" class="ml-5" onClick="change_password('修改密码','<?php echo url('admin/password_edit'); ?>?ids=<?php echo htmlentities($vo['id']); ?>','10001','600','300')" href="javascript:;" title="修改密码"><i class="Hui-iconfont">&#xe63f;</i></a>
					<a title="删除" href="javascript:;" onclick="admin_del(this,'<?php echo htmlentities($vo['id']); ?>')" class="ml-5" style="text-decoration:none"><i class="Hui-iconfont">&#xe6e2;</i></a>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; endif; else: echo "" ;endif; ?>
		</tbody>
	</table>
	<?php echo $page; ?>
</div>
<!--_footer 作为公共模版分离出去-->
<script type="text/javascript" src="/static/lib/jquery/1.9.1/jquery.min.js"></script> 
<script type="text/javascript" src="/static/lib/layer/2.4/layer.js"></script>
<script type="text/javascript" src="/static/static/h-ui/js/H-ui.min.js"></script> 
<script type="text/javascript" src="/static/static/h-ui.admin/js/H-ui.admin.js"></script> <!--/_footer 作为公共模版分离出去-->

<!--请在下方写此页面业务相关的脚本-->
<script type="text/javascript" src="/static/lib/My97DatePicker/4.8/WdatePicker.js"></script> 
<script type="text/javascript" src="/static/lib/datatables/1.10.0/jquery.dataTables.min.js"></script> 
<script type="text/javascript" src="/static/lib/laypage/1.2/laypage.js"></script>
<script type="text/javascript">
/*
	参数解释：
	title	标题
	url		请求的url
	id		需要操作的数据id
	w		弹出层宽度（缺省调默认值）
	h		弹出层高度（缺省调默认值）
*/
/*管理员-增加*/
function admin_add(title,url,w,h){
	layer_show(title,url,w,h);
}
/*管理员-删除*/
function admin_del(obj,id){
	layer.confirm('确认要删除吗？',function(index){
		$.ajax({
			type: 'POST',
			url: "<?php echo url('admin/del'); ?>",
			data:{ids:id},
			dataType: 'json',
			success: function(data){
				$(obj).parents("tr").remove();
				layer.msg('已删除!',{icon:1,time:1000});
			},
			error:function(data) {
				console.log(data.msg);
			},
		});		
	});
}

/*密码-修改*/
function change_password(title,url,id,w,h){
	layer_show(title,url,w,h);	
}

/*管理员-编辑*/
function admin_edit(title,url,id,w,h){
	layer_show(title,url,w,h);
}
/*管理员-停用*/
function admin_stop(obj,id){
	layer.confirm('确认要停用吗？',function(index){
		//此处请求后台程序，下方是成功后的前台处理……
		
		$(obj).parents("tr").find(".td-manage").prepend('<a onClick="admin_start(this,id)" href="javascript:;" title="启用" style="text-decoration:none"><i class="Hui-iconfont">&#xe615;</i></a>');
		$(obj).parents("tr").find(".td-status").html('<span class="label label-default radius">已禁用</span>');
		$(obj).remove();
		layer.msg('已停用!',{icon: 5,time:1000});
	});
}

/*管理员-启用*/
function admin_start(obj,id){
	layer.confirm('确认要启用吗？',function(index){
		//此处请求后台程序，下方是成功后的前台处理……
		
		$(obj).parents("tr").find(".td-manage").prepend('<a onClick="admin_stop(this,id)" href="javascript:;" title="停用" style="text-decoration:none"><i class="Hui-iconfont">&#xe631;</i></a>');
		$(obj).parents("tr").find(".td-status").html('<span class="label label-success radius">已启用</span>');
		$(obj).remove();
		layer.msg('已启用!', {icon: 6,time:1000});
	});
}

/* 批量删除 */
function datadel(){
	var chk_value =[]; 
	$('input[name="ids"]:checked').each(function(){ 
		chk_value.push($(this).val()); 
	}); 
	if(chk_value.length<=0){
		layer.confirm("请选择要删除的数据！");
	}else{
		if(layer.confirm("你确定删除吗？"+chk_value)){
			$.ajax({
				url:"<?php echo url('admin/del'); ?>",
				type: "POST",
				data: {ids:chk_value+""},
				success: function () {
					$(obj).parents("tr").remove();
					layer.msg('已删除!',{icon:1,time:1000});
				},
				error:function(data) {
					console.log(data.msg);
				},
			});
	 
		}
	}
}

$("#submit_form").click(function(){
				$(".submit_form").ajaxSubmit({
						type:'post',
						url: "index",
						// target: "#div2",
						dataType:"html",
						success: function(data){
							var html = $(data).find('.table').html();
							$(".table").html(html);
						}
					});
					return false;
});
</script>
</body>
</html>