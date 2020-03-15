<?php /*a:1:{s:105:"D:\phpToolbox\myphp_www\PHPTutorial\WWW\Mywook\wwwroot\qxgl\TPCMS\application\admin\view\group\index.html";i:1571661562;}*/ ?>
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
<title>角色管理</title>
</head>
<body>
<nav class="breadcrumb"><i class="Hui-iconfont">&#xe67f;</i> 首页 <span class="c-gray en">&gt;</span> 角色管理 <a class="btn btn-success radius r" style="line-height:1.6em;margin-top:3px" href="javascript:location.replace(location.href);" title="刷新" ><i class="Hui-iconfont">&#xe68f;</i></a></nav>
<div class="page-container">
	<div class="cl pd-5 bg-1 bk-gray"> <span class="l"> <!-- <a href="javascript:;" onclick="datadel()" class="btn btn-danger radius"><i class="Hui-iconfont">&#xe6e2;</i> 批量删除</a> --> <a class="btn btn-primary radius" href="javascript:;" onclick="admin_role_add('添加角色','<?php echo url('group/add'); ?>','800')"><i class="Hui-iconfont">&#xe600;</i> 添加角色</a> </span> <span class="r">共有数据：<strong><?php echo htmlentities($count); ?></strong> 条</span> </div>
	<table class="table table-border table-bordered table-hover table-bg">
		<thead>
			<tr>
				<th scope="col" colspan="8">角色管理</th>
			</tr>
			<tr class="text-c">
				<th width="25"><input type="checkbox" value="" name=""></th>
				<th width="40">ID</th>
				<th width="200">父级</th>
				<th>角色名称</th>
				<th width="300">状态</th>
				<th>创建时间</th>
				<th>更新时间</th>
				<th width="70">操作</th>
			</tr>
		</thead>
		<tbody>
			<?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?>
			<tr class="text-c">
				<td><input type="checkbox" value="<?php echo htmlentities($vo['id']); ?>" name="ids"></td>
				<td><?php echo htmlentities($vo['id']); ?></td>
				<td><?php echo htmlentities($vo['pid']); ?></td>
				<td class="jiaose"><?php echo htmlentities($vo['name']); ?></td>
				<td><?php if($vo['status'] == 'normal'): ?>启用 <?php else: ?> 禁用<?php endif; ?></td>  
				<td><?php echo htmlentities($vo['createtime']); ?></td>
				<td><?php echo htmlentities($vo['updatetime']); ?></td>
				<td class="f-14">
					<?php if($vo['name'] !=='超级管理'): ?>
					<a title="编辑" href="javascript:;" onclick="admin_role_edit('角色编辑','<?php echo url('group/edit'); ?>?ids=<?php echo htmlentities($vo['id']); ?>','1')" style="text-decoration:none"><i class="Hui-iconfont">&#xe6df;</i></a> 
					<a title="删除" href="javascript:;" onclick="admin_role_del(this,'<?php echo htmlentities($vo['id']); ?>')" class="ml-5" style="text-decoration:none"><i class="Hui-iconfont">&#xe6e2;</i></a>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; endif; else: echo "" ;endif; ?>
		</tbody>
	</table>
</div>
<!--_footer 作为公共模版分离出去-->
<script type="text/javascript" src="/static/lib/jquery/1.9.1/jquery.min.js"></script> 
<script type="text/javascript" src="/static/lib/layer/2.4/layer.js"></script>
<script type="text/javascript" src="/static/static/h-ui/js/H-ui.min.js"></script> 
<script type="text/javascript" src="/static/static/h-ui.admin/js/H-ui.admin.js"></script> <!--/_footer 作为公共模版分离出去-->

<!--请在下方写此页面业务相关的脚本-->
<script type="text/javascript" src="/static/lib/datatables/1.10.0/jquery.dataTables.min.js"></script>
<script type="text/javascript">
/*管理员-角色-添加*/
function admin_role_add(title,url,w,h){
	layer_show(title,url,w,h);
}
/*管理员-角色-编辑*/
function admin_role_edit(title,url,id,w,h){
	layer_show(title,url,w,h);
}
/*管理员-角色-删除*/
function admin_role_del(obj,id){
	layer.confirm('角色删除须谨慎，确认要删除吗？',function(index){
		$.ajax({
			type: 'POST',
			url: '<?php echo url('group/del'); ?>',
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

/* 批量删除 */
// function datadel(){
// 	var chk_value =[]; 
// 	$('input[name="ids"]:checked').each(function(){ 
// 		chk_value.push($(this).val()); 
// 	}); 
// 	if(chk_value.length<=0){
// 		layer.confirm("请选择要删除的数据！");
// 	}else{
// 		if(layer.confirm("你确定删除吗？"+chk_value)){
// 			$.ajax({
// 				url:"<?php echo url('group/del'); ?>",
// 				type: "POST",
// 				data: {ids:chk_value+""},
// 				success: function () {
// 					$(obj).parents("tr").remove();
// 					layer.msg('已删除!',{icon:1,time:1000});
// 				},
// 				error:function(data) {
// 					console.log(data.msg);
// 				},
// 			});
	 
// 		}
// 	}
// }

</script>
</body>
</html>