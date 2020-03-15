
var wxid_num = wxlist.length;
var idx = 0;
var hasnum = 0;
var avanum = 0;

var idx2 = 0;
var hasnum2 = 0;
var avanum2 = 0;
var s = true;
$(function (){
    $('#startrepairs').click(function (){
        var c = $(this).attr('cmd');
        var c_type  = $(this).attr('c-type');
        console.log('c='+c);
        console.log('c_type='+c_type);

        if (c == 'pause'){
            s = false;
            $(this).html('<i class="fa fa-play text-primary"></i> 继续查询')
            $(this).attr('cmd', 'goon')
        }else if (c == 'goon'){
            s = true;
            //go_check_all(c_type);  //查询所有当前内容
            //return;
            check(c_type);  //开始时去拿我们找到的数据
            $(this).html('<i class="fa fa-pause text-primary"></i> 暂停查询')
            $(this).attr('cmd', 'pause')
        }
    });
    $('#startcheckid').click(function (){
        var c = $(this).attr('cmd');
        var c_type  = $(this).attr('c-type');
        if (c == 'pause'){
            s = false;
            $(this).html('<i class="fa fa-play text-primary"></i> 继续查询ID激活锁')
            $(this).attr('cmd', 'goon')
        }else if (c == 'goon'){
            s = true;
            check(c_type);
            $(this).html('<i class="fa fa-pause text-primary"></i> 暂停查询ID激活锁')
            $(this).attr('cmd', 'pause')
        }
    });
    $('#startcheckimeiorsn').click(function (){
        var c = $(this).attr('cmd');
        var c_type   = $(this).attr('c-type');
        if (c == 'pause'){
            s = false;
            $(this).html('<i class="fa fa-play text-primary"></i> 继续IMEI/序列号互转')
            $(this).attr('cmd', 'goon')
        }else if (c == 'goon'){
            s = true;
            check(c_type);
            $(this).html('<i class="fa fa-pause text-primary"></i> 暂停IMEI/序列号互转')
            $(this).attr('cmd', 'pause')
        }
    });
    //dan
    $('.table-builder #dchawx').click(function (){
        var name = $(this).attr('name');
        checkrepairs(name,'wx','d');
    });
});

function check(c_type){
    if (!s){
        return;
    }
    if(c_type=='wx'){
        if (idx >= wxid_num){
            return;
        }
        hasnum++;
        checkrepairs(wxlist[idx++], c_type);
        $('#hasnum').html(hasnum);
    }
    else{
        if (idx2 >= wxid_num){
            return;
        }
        hasnum2++;
        checkrepairs(wxlist[idx2++], c_type);
        $('#hasnum').html(hasnum2);
    }


}
///这个是去找数据来显示
function checkrepairs(n, c_type,for1=0){

    var start = new Date().getTime();
    hou=c_type;
    $('#tiamoo-'+n+hou).html('<i class="fa fa-spinner fa-spin"></i>');
    var loading = false;
    setTimeout(function (){
        if (!loading){
            if(for1!='d') check(c_type);
            loading = true;
            $('#tiamoo-'+n+hou).html('<i class="fa fa-spinner fa-spin"></i>');
        }
    },15000);  //15000 后再去查询

    //去查询
    $.post(
        aiguovip.checkapi+	'?t='+Math.random(),
        {'tiamo':n,'c_type':c_type},
        function (obj){
            if (!loading){
                if(for1!='d') check(c_type);
            }
            loading = true;
            if (obj.status){
                var end = new Date().getTime();
                if (obj.tishi){
                    avanum++;
                    miao=((end-start)/1000)
                    $('#tiamoo-'+n+hou).html(obj.tishi );
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

var all_start =false; //定义是不是开启了全部查询
var all_start_count =0; //完成的个数
var check_array={}; //查询结果存放
//批量查询出来 所有的进度
function go_check_all(c_type){
    if(all_start == true ){
        console.log('正在查询中');
        return;
    }

    all_start=true; //开始了
    all_start_count =0; //已经查完的个数
    for(var i=0 ;i < wxid_num ; i++){
        sn_id = wxlist[i];  //查询的记录ID
        console.log(sn_id);
        //去查询
        $.post(
            aiguovip.checkapi+	'?t='+Math.random(),
            {
                'tiamo':sn_id,
                'c_type':c_type
            },
            function (obj){
                var end = new Date().getTime();
                last_check_time=( end / 1000); //最后一次的更新时间
                var sn_arr=[]; // 查找到的内容
                sn_arr['check_time']= last_check_time;
                if (obj.status){
                    if (obj.tishi){
                        sn_arr['tishi']= obj.tishi;
                    }else{
                        sn_arr['tishi']= '已经出结果';
                    }
                }else{
                    if (obj.tishi)
                        sn_arr['tishi']= obj.tishi;
                    else
                        sn_arr['tishi']= '查询失败';
                }

                sn_arr['sn_id']= obj.sn_id;

                check_array[obj.sn_id]= sn_arr;  //把记录都存好了


                all_start_count++ ; //完成个数加1
                if(all_start_count == wxid_num ){
                    console.log({'check_array':check_array});
                    all_start = false ;
                }
            },
            'json'
        );

    }



}