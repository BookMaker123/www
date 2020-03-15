
// JavaScript Document
var timer = null;
var wxid_num = wxlist.length;
var idx = 0;
var hasnum = 0;
var avanum = 0;
var dava = false;
var s = true;
$(function (){
//	searchWID();//自动查询	
	$('#startbutton').click(function (){
		var c = $(this).attr('cmd');
		if (c == 'pause'){
			s = false;
			$(this).html('<i class="fa fa-play text-primary"></i>继续查询')
			$(this).attr('cmd', 'goon')
		}else if (c == 'goon'){
			s = true;
			searchWID();
			$(this).html('<i class="fa fa-pause text-primary"></i>暂停查询')
			$(this).attr('cmd', 'pause')	
		}
	});
	//淡茶
	$('.table-builder #dcha').click(function (){
		var name = $(this).attr('name');
		checkrepairs(name,'d');
	});
});

function searchWID(){
	if (idx >= wxid_num){
		//clearInterval(timer);
		return;	
	}
	if (!s){
		return;	
	}
	hasnum++;
	checkrepairs(wxlist[idx++]+h, 'cc');
	$('#hasnum').html(hasnum);
}

function checkrepairs(n, l){
	hou=''
	$('#tiamoo-'+n+hou).html('<i class="fa fa-spinner fa-spin"></i>');
	var loading = false;
	setTimeout(function (){
		if (!loading){
			if(l!='d') searchWID();
			loading = true;
			$('#tiamoo-'+n+hou).html('<span class="badge badge-pill badge-danger">查询失败,重新查询!</span>');	
		}
	},3000);
	$.post(
	base_url+	'?'+'t='+Math.random(),
		{'tiamo':n},
		function (obj){
			if (!loading){
				//setTimeout(searchWID,200);
				if(l!='d') searchWID();
			}
			loading = true;
			if (obj.status){
				//searchWID();
				if (obj.tishi){
					avanum++;
                    $('#tiamoo-'+n+hou).html(obj.tishi);
				}else{					
					$('#tiamoo-'+n+hou).html('已经出结果');
				}
				$('#avanum').html(avanum);				
			}else{
				if (obj.tishi)
				$('#tiamoo-'+n+hou).html(obj.tishi);
				else
				$('#tiamoo-'+n+hou).html('查询失败');	
			}
		},
		'json'
	);
};
