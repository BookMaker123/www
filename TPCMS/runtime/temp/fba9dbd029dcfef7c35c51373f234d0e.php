<?php /*a:1:{s:106:"D:\phpToolbox\myphp_www\PHPTutorial\WWW\Mywook\wwwroot\qxgl\TPCMS\application\admin\view\member\index.html";i:1571730062;}*/ ?>
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
<title>会员管理</title>
</head>
<body>
<nav class="breadcrumb"><i class="Hui-iconfont">&#xe67f;</i> 首页 <span class="c-gray en">&gt;</span> 会员管理 <span class="c-gray en">&gt;</span> 会员管理列表 <a class="btn btn-success radius r" style="line-height:1.6em;margin-top:3px" href="javascript:location.replace(location.href);" title="刷新" ><i class="Hui-iconfont">&#xe68f;</i></a></nav>
<div class="page-container">
	<div class="text-c"> 
		<!-- 日期范围：
		<input type="text" onfocus="WdatePicker({ maxDate:'#F{$dp.$D(\'datemax\')||\'%y-%M-%d\'}' })" id="datemin" class="input-text Wdate" style="width:120px;">
		-
		<input type="text" onfocus="WdatePicker({ minDate:'#F{$dp.$D(\'datemin\')}',maxDate:'%y-%M-%d' })" id="datemax" class="input-text Wdate" style="width:120px;"> -->
		<input type="text" class="input-text" style="width:250px" placeholder="输入会员名称" id="username" name="username">
		<input type="text" class="input-text" style="width:250px" placeholder="输入会员电话" id="mobile" name="mobile">
		<input type="text" class="input-text" style="width:250px" placeholder="输入邮箱" id="email" name="email">
		<button type="submit" class="btn btn-success radius" id="" name=""><i class="Hui-iconfont">&#xe665;</i> 搜会员</button>
	</div>
	<div class="cl pd-5 bg-1 bk-gray mt-20"> <span class="l"><a href="javascript:;" onclick="datadel()" class="btn btn-danger radius"><i class="Hui-iconfont">&#xe6e2;</i> 批量删除</a> <a href="javascript:;" onclick="member_add('添加用户','<?php echo url('member/add'); ?>','','800')" class="btn btn-primary radius"><i class="Hui-iconfont">&#xe600;</i> 添加会员</a></span> <span class="r">共有数据：<strong><?php echo htmlentities($count); ?></strong> 条</span> </div>
	<div class="mt-20">
	<table class="table table-border table-bordered table-hover table-bg">
		<thead>
			<tr class="text-c">
				<th width="25"><input type="checkbox" name="" value=""></th>
				<th width="80">ID</th>
				<th width="100">会员名</th>
				<th width="100">昵称</th>
				<th width="100">头像</th>
				<th width="40">性别</th>
				<th width="90">手机</th>
				<th width="150">邮箱</th>
				<th width="">登录IP</th>
				<th width="130">加入时间</th>
				<th width="130">登录时间</th>
				<th width="70">状态</th>
				<th width="100">操作</th>
			</tr>
		</thead> 
		<tbody>
			<?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?>
			<tr class="text-c">
				<td><input type="checkbox" value="<?php echo htmlentities($vo['id']); ?>" name="ids"></td>
				<td><?php echo htmlentities($vo['id']); ?></td>
				<td><u style="cursor:pointer" class="text-primary" onclick="member_show('<?php echo htmlentities($vo['username']); ?>','<?php echo url('member/show'); ?>?ids=<?php echo htmlentities($vo['id']); ?>','','520','520')"><?php echo htmlentities($vo['username']); ?></u></td>
				<td><?php echo htmlentities($vo['nickname']); ?></td>
				<td><img width="50" height="50" src="<?php echo htmlentities($vo['avatar']); ?>"></td>
				<td><?php if($vo['sex']=='0'): ?>保密<?php elseif($vo['sex']=='1'): ?>男<?php elseif($vo['sex']=='2'): ?>女<?php endif; ?></td>
				<td><?php echo htmlentities($vo['mobile']); ?></td>
				<td><?php echo htmlentities($vo['email']); ?></td>
				<td><?php echo htmlentities($vo['loginip']); ?></td>
				<td class="text-l"><?php echo htmlentities(date('Y-m-d H:i:s',!is_numeric($vo['createtime'])? strtotime($vo['createtime']) : $vo['createtime'])); ?></td>
				<td><?php echo htmlentities(date('Y-m-d H:i:s',!is_numeric($vo['logintime'])? strtotime($vo['logintime']) : $vo['logintime'])); ?></td> 
				<td class="td-status"><span class="label label-success radius"><?php if($vo['status']=='0'): ?>待审核<?php elseif($vo['status']=='1'): ?>审核通过<?php elseif($vo['status']=='2'): ?>审核失败<?php elseif($vo['status']=='3'): ?>删除<?php endif; ?></span></td>
				<td class="td-manage">
					<!-- <a style="text-decoration:none" onClick="member_stop(this,'10001')" href="javascript:;" title="停用"><i class="Hui-iconfont">&#xe631;</i></a>  -->
					<a title="编辑" href="javascript:;" onclick="member_edit('编辑','<?php echo url('member/edit'); ?>?ids=<?php echo htmlentities($vo['id']); ?>','','','800')" class="ml-5" style="text-decoration:none"><i class="Hui-iconfont">&#xe6df;</i></a> 
					<a style="text-decoration:none" class="ml-5" onClick="change_password('修改密码','<?php echo url('member/password_edit'); ?>?ids=<?php echo htmlentities($vo['id']); ?>','10001','600','300')" href="javascript:;" title="修改密码"><i class="Hui-iconfont">&#xe63f;</i></a>
					<a title="删除" href="javascript:;" onclick="member_del(this,'<?php echo htmlentities($vo['id']); ?>')" class="ml-5" style="text-decoration:none"><i class="Hui-iconfont">&#xe6e2;</i></a>
				</td>
			</tr>
			<?php endforeach; endif; else: echo "" ;endif; ?>
		</tbody>
	</table>
<?php echo $page; ?>
	</div>
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
$(function(){
	$('.table-sort').dataTable({
		"aaSorting": [[ 1, "desc" ]],//默认第几个排序
		"bStateSave": true,//状态保存
		"aoColumnDefs": [
		  //{"bVisible": false, "aTargets": [ 3 ]} //控制列的隐藏显示
		  {"orderable":false,"aTargets":[0,8,9]}// 制定列不参与排序
		]
	});
	
});
/*用户-添加*/
function member_add(title,url,w,h){
	layer_show(title,url,w,h);
}
/*用户-查看*/
function member_show(title,url,id,w,h){
	layer_show(title,url,w,h);
}
/*用户-停用*/
function member_stop(obj,id){
	layer.confirm('确认要停用吗？',function(index){
		$.ajax({
			type: 'POST',
			url: '',
			dataType: 'json',
			success: function(data){
				$(obj).parents("tr").find(".td-manage").prepend('<a style="text-decoration:none" onClick="member_start(this,id)" href="javascript:;" title="启用"><i class="Hui-iconfont">&#xe6e1;</i></a>');
				$(obj).parents("tr").find(".td-status").html('<span class="label label-defaunt radius">已停用</span>');
				$(obj).remove();
				layer.msg('已停用!',{icon: 5,time:1000});
			},
			error:function(data) {
				console.log(data.msg);
			},
		});		
	});
}

/*用户-启用*/
function member_start(obj,id){
	layer.confirm('确认要启用吗？',function(index){
		$.ajax({
			type: 'POST',
			url: '',
			dataType: 'json',
			success: function(data){
				$(obj).parents("tr").find(".td-manage").prepend('<a style="text-decoration:none" onClick="member_stop(this,id)" href="javascript:;" title="停用"><i class="Hui-iconfont">&#xe631;</i></a>');
				$(obj).parents("tr").find(".td-status").html('<span class="label label-success radius">已启用</span>');
				$(obj).remove();
				layer.msg('已启用!',{icon: 6,time:1000});
			},
			error:function(data) {
				console.log(data.msg);
			},
		});
	});
}
/*用户-编辑*/
function member_edit(title,url,id,w,h){
	layer_show(title,url,w,h);
}
/*密码-修改*/
function change_password(title,url,id,w,h){
	layer_show(title,url,w,h);	
}
/*用户-删除*/
function member_del(obj,id){
	layer.confirm('确认要删除吗？',function(index){
		$.ajax({
			type: 'POST',
			url: '<?php echo url('member/del'); ?>',
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
				url:"<?php echo url('member/del'); ?>",
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

</script> 
</body>
</html>