<?php /*a:1:{s:104:"D:\phpToolbox\myphp_www\PHPTutorial\WWW\Mywook\wwwroot\qxgl\TPCMS\application\admin\view\rule\index.html";i:1571661562;}*/ ?>
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
<title>权限节点管理</title>
</head>
<body>
<nav class="breadcrumb"><i class="Hui-iconfont">&#xe67f;</i> 首页 <span class="c-gray en">&gt;</span> 权限节点 <a class="btn btn-success radius r" style="line-height:1.6em;margin-top:3px" href="javascript:location.replace(location.href);" title="刷新" ><i class="Hui-iconfont">&#xe68f;</i></a></nav>
<div class="page-container">
	<div class="text-c">
		<form class="Huiform" method="post" action="<?php echo url(); ?>" target="_self">
			<input type="text" class="input-text" style="width:250px" placeholder="权限名称" id="" name="title">
			<button type="submit" class="btn btn-success" id="" name=""><i class="Hui-iconfont">&#xe665;</i> 搜权限节点</button>
		</form>
	</div>
	<div class="cl pd-5 bg-1 bk-gray mt-20"> <span class="l"><!-- <a href="javascript:;" onclick="datadel()" class="btn btn-danger radius"><i class="Hui-iconfont">&#xe6e2;</i> 批量删除</a> --> <a href="javascript:;" onclick="admin_permission_add('添加权限节点','<?php echo url('rule/add'); ?>','','520')" class="btn btn-primary radius"><i class="Hui-iconfont">&#xe600;</i> 添加权限节点</a></span> <span class="r">共有数据：<strong><?php echo htmlentities($count); ?></strong> 条</span> </div>
	<table class="table table-border table-bordered table-bg">
		<thead>
			<tr>
				<th scope="col" colspan="11">权限节点</th>
			</tr>
			<tr class="text-c">
				<th width="25"><input type="checkbox" name="" value=""></th>
				<th width="40">ID</th>
				<th width="40">父级ID</th>
				<th width="200">标题</th>
				<th>规则</th>
				<th>权重</th>
				<th>状态</th>
				<th>菜单</th>
				<th>创建时间</th>
				<th>更新时间</th>
				<th width="100">操作</th>
			</tr>
		</thead>
		<tbody>
			<?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?>
			<tr class="text-c ziji<?php echo htmlentities($vo['id']); ?> fuji<?php echo htmlentities($vo['pid']); ?>" style="<?php if($vo['pid'] != '0'): ?>display: none;<?php endif; ?>" >
				<td><input type="checkbox" value="<?php echo htmlentities($vo['id']); ?>" name="ids"></td>
				<td><?php echo htmlentities($vo['id']); ?></td>
				<td><?php echo htmlentities($vo['pid']); ?></td>
				<td style="text-align: left;"><?php if($vo['pid'] == '0'): ?><i class="Hui-iconfont Hui-iconfont-add" onclick="fuji('fuji<?php echo htmlentities($vo['id']); ?>');" style="font-size: 14px;font-weight: bold;"></i><?php endif; if($vo['pid'] != '0'): ?>&nbsp;&nbsp;&nbsp;<?php endif; ?><?php echo htmlentities($vo['title']); ?></td>
				<td><?php echo htmlentities($vo['name']); ?></td>
				<td><?php echo htmlentities($vo['weigh']); ?></td>
				<td><?php if($vo['status'] == 'normal'): ?>启用 <?php else: ?> 禁用<?php endif; ?></td>
				<td><?php if($vo['ismenu'] == '0'): ?>否 <?php else: ?> 是<?php endif; ?></td>
				<td><?php echo htmlentities($vo['createtime']); ?></td>
				<td><?php echo htmlentities($vo['updatetime']); ?></td>
				<td><a title="编辑" href="javascript:;" onclick="admin_permission_edit('权限节点编辑','<?php echo url('rule/edit'); ?>?ids=<?php echo htmlentities($vo['id']); ?>','1','','520')" class="ml-5" style="text-decoration:none"><i class="Hui-iconfont">&#xe6df;</i></a> &nbsp;&nbsp;&nbsp;
					<a title="删除" href="javascript:;" onclick="admin_permission_del(this,'<?php echo htmlentities($vo['id']); ?>')" class="ml-5" style="text-decoration:none"><i class="Hui-iconfont">&#xe6e2;</i></a>
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
/*
	参数解释：
	title	标题
	url		请求的url
	id		需要操作的数据id
	w		弹出层宽度（缺省调默认值）
	h		弹出层高度（缺省调默认值）
*/
/*管理员-权限-添加*/
function admin_permission_add(title,url,w,h){
	layer_show(title,url,w,h);
}
/*管理员-权限-编辑*/
function admin_permission_edit(title,url,id,w,h){
	layer_show(title,url,w,h);
}

function fuji(fuji) {
	$('.'+fuji).toggle();
}

/*管理员-权限-删除*/
function admin_permission_del(obj,id){
	layer.confirm('确认要删除吗？',function(index){
		$.ajax({
			type: 'POST',
			url: '<?php echo url('rule/del'); ?>',
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
// 				url:"<?php echo url('rule/del'); ?>",
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