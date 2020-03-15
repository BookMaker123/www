/*****************************************************************
 * jQuery Validate扩展验证方法  (linjq)    
 * Modified by guojunhui
 * Date modified:01/01/2017  
*****************************************************************/
$(function(){
     // $("#user-info").validate({
     //      rules:{
     //           nickname:{
     //                required:true,
     //                minlength:4,
     //                maxlength:16,
     //           },
     //           phone:{
     //                required:false,
     //                isPhone:true,
     //           },
     //           email:{
     //                required:false,
     //                email:true,
     //           },
     //           // password:"required",
     //           password2:{
     //                equalTo: "#password"
     //           },
     //      },
     //      onkeyup:false,
     //      focusCleanup:true,
     //      success:"valid",
     //      submitHandler:function(form){
     //           $("#user-info").ajaxSubmit({
     //                success: function(data){
     //                     layer.msg(data, {icon:1,time:1000}, function(){
     //                                    var index = parent.layer.getFrameIndex(window.name);
     //                                    // parent.location.reload(); //刷新父页面
     //                                    parent.layer.close(index);
     //                               });
     //                },
     //                error: function(XmlHttpRequest, textStatus, errorThrown){
     //                     layer.msg(textStatus, {icon:1,time:1000}, function(){
     //                                    var index = parent.layer.getFrameIndex(window.name);
     //                                    // parent.location.reload(); //刷新父页面
     //                                    parent.layer.close(index);
     //                               });
     //                }
     //           });
     //      }
     // })

     jQuery.extend(jQuery.validator.messages, {
          required: "必选字段",
          remote: "请修正该字段",
          email: "请输入正确格式的电子邮件",
          url: "请输入合法的网址",
          date: "请输入合法的日期",
          dateISO: "请输入合法的日期 (ISO).",
          number: "请输入合法的数字",
          digits: "只能输入整数",
          creditcard: "请输入合法的信用卡号",
          equalTo: "请再次输入相同的值",
          accept: "请输入拥有合法后缀名的字符串",
          maxlength: jQuery.validator.format("请输入一个 长度最多是 {0} 的字符串"),
          minlength: jQuery.validator.format("请输入一个 长度最少是 {0} 的字符串"),
          rangelength: jQuery.validator.format("请输入 一个长度介于 {0} 和 {1} 之间的字符串"),
          range: jQuery.validator.format("请输入一个介于 {0} 和 {1} 之间的值"),
          max: jQuery.validator.format("请输入一个最大为{0} 的值"),
          min: jQuery.validator.format("请输入一个最小为{0} 的值")
        });
})