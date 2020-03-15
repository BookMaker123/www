//这个JS 为查询index.
//load 事件
$(function(){
    setTimeout(
        function(){
            //图片修改上传  上传文件方法
            $('#uploadImg').change(function () {
                var fileObj = document.getElementById("uploadImg").files[0]; // js 获取文件对象
                if(fileObj === undefined){
                    AAiguovip.notify('未选择图片', 'danger');
                    return;
                }
                var path = this.value;  //得到的是现在文件的路径
                var ss = path.substr(path.lastIndexOf(".")).toUpperCase();//得到的是后缀名,且转换为大写
                if(ss != ".JPG" && ss !=".PNG" && ss !=".JPEG" && ss != '.BMP'){
                    AAiguovip.notify('请使用JPG、PNG、JPEG格式的图片', 'danger');
                    return;
                }
                if (fileObj.size / 1024 > 1025) { //大于1M，进行压缩上传
                    photoCompress(fileObj, {
                        quality: 0.2
                    }, function (base64Codes) {
                        upload_base64(base64Codes);
                    });
                } else { //小于等于1M 原图上传
                    var file = document.getElementById('uploadImg').files[0];
                    var reader = new FileReader();
                    reader.onload = function(event) {
                        avatar = event.target.result;
                        upload_base64(reader.result);
                    };
                    reader.readAsDataURL(file);
                }
            });
        },500
    )
})



function photoCompress(file, w, objDiv) {
    var ready = new FileReader();
    /*开始读取指定的Blob对象或File对象中的内容. 当读取操作完成时,readyState属性的值会成为DONE,如果设置了onloadend事件处理程序,则调用之.同时,result属性中将包含一个data: URL格式的字符串以表示所读取文件的内容.*/
    ready.readAsDataURL(file);
    ready.onload = function () {
        var re = this.result;
        canvasDataURL(re, w, objDiv)
    }
}

function canvasDataURL(path, obj, callback) {
    var img = new Image();
    img.src = path;
    img.onload = function () {
        var that = this;
        // 默认按比例压缩
        var w = that.width,
            h = that.height,
            scale = w / h;
        w = obj.width || w;
        h = obj.height || (w / scale);
        var quality = 2; // 默认图片质量为0.7
        //生成canvas
        var canvas = document.createElement('canvas');
        var ctx = canvas.getContext('2d');
        // 创建属性节点
        var anw = document.createAttribute("width");
        anw.nodeValue = w;
        var anh = document.createAttribute("height");
        anh.nodeValue = h;
        canvas.setAttributeNode(anw);
        canvas.setAttributeNode(anh);
        ctx.drawImage(that, 0, 0, w, h);
        // 图像质量
        if (obj.quality && obj.quality <= 1 && obj.quality > 0) {
            quality = obj.quality;
        }
        // quality值越小，所绘制出的图像越模糊
        var base64 = canvas.toDataURL('image/jpeg', quality);
        // 回调函数返回base64的值
        callback(base64);
    }
}

//上传中
function upload_base64(base_file){
    AAiguovip.loading();
    $.ajax({
        url: '/vip/chaxun/identify_sn_imei',
        cache: false,
        type: 'POST',
        data: {img: base_file},
        dataType: 'json',
        success: function (rs) {
            AAiguovip.loading('hide');
            if(rs.code == 0){
                AAiguovip.notify('已添加:\n'+rs.message, 'success');
                str=$('#imeitext').val()==''?rs.message:$('#imeitext').val()+'\n'+rs.message;
                $('#imeitext').val(str);
            }else{
                AAiguovip.notify(rs.message, 'danger');
            }
        },
        error:function(e){
            AAiguovip.loading('hide');
        }
    });
}
