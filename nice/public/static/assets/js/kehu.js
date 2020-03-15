(function ($) {
    "use strict";    
    var Kehus = function (options) {
        this.init('kehus', options, Kehus.defaults);
    };

    //inherit from Abstract input
    $.fn.editableutils.inherit(Kehus, $.fn.editabletypes.abstractinput);

    $.extend(Kehus.prototype, {
        /**
        Renders input from tpl

        @method render() 
        **/        
        render: function() {
           this.$input = this.$tpl.find('input');
        },       
 
        value2html: function(value, element) {
            if(!value) {
                $(element).empty();
                return; 
            }
            var html = $('<div>').text(value.name).html() + ', ' + $('<div>').text(value.phone).html() + ' st., bld. ' + $('<div>').text(value.kuaidi).html();
            $(element).html(html); 
        },
        
        /**
        Gets value from element's html
        
        @method html2value(html) 
        **/        
        html2value: function(html) {      
          return null;  
        },
      
       /**
        Converts value to string. 
        It is used in internal comparing (not for sending to server).
        
        @method value2str(value)  
       **/
       value2str: function(value) {
           var str = '';
           if(value) {
               for(var k in value) {
                   str = str + k + ':' + value[k] + ';';  
               }
           }
           return str;
       }, 
       
       /*
        Converts string to value. Used for reading value from 'data-value' attribute.
        
        @method str2value(str)  
       */
       str2value: function(str) {
           /*
           this is mainly for parsing value defined in data-value attribute. 
           If you will always set value by javascript, no need to overwrite it
           */
           return str;
       },                
       
       /**
        Sets value of input.
        
        @method value2input(value) 
        @param {mixed} value
       **/         
       value2input: function(value) {
           if(!value) {
             return;
           }
           this.$input.filter('[name="name"]').val(value.name);
           this.$input.filter('[name="phone"]').val(value.phone);
           this.$input.filter('[name="kuaidi"]').val(value.kuaidi);
       },       
       
       /**
        Returns value of input.
        
        @method input2value() 
       **/          
       input2value: function() { 
           return {
              name: this.$input.filter('[name="name"]').val(),
              phone: this.$input.filter('[name="phone"]').val(),
              kuaidi: this.$input.filter('[name="kuaidi"]').val()
           };
       },        
       
        /**
        Activates input: sets focus on the first field.
        
        @method activate() 
       **/        
       activate: function() {
            this.$input.filter('[name="name"]').focus();
       },  
       
       /**
        Attaches handler to submit form in case of 'showbuttons=false' mode        
        @method autosubmit() 
       **/       
       autosubmit: function() {
           this.$input.keydown(function (e) {
                if (e.which === 13) {
                    $(this).closest('form').submit();
                }
           });
       }       
    });

    Kehus.defaults = $.extend({}, $.fn.editabletypes.abstractinput.defaults, {
        tpl: '<div class="editable-kehus"><label><span>客户: </span><input type="text" name="name" class="input-small"></label></div>'+
        '<div class="editable-kehus"><label><span>电话: </span><input type="text" name="phone" class="input-small"></label></div>'+
        '<div class="editable-kehus"><label><span>快递: </span><input type="text" name="kuaidi" class="input-mini"></label></div>',
        inputclass: ''
    });

    $.fn.editabletypes.kehus = Kehus;

}(window.jQuery));