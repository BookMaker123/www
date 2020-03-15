(function( factory ) {
	if ( typeof define === "function" && define.amd ) {
		define( ["jquery", "../jquery.validate"], factory );
	} else {
		factory( jQuery );
	}
}(function( $ ) {

/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: ZH (Chinese, 中文 (Zhōngwén), 汉语, 漢語)
 */
$.extend($.validator.messages, {
	required: "This is a required field",
	remote: "Please fix this field",
	email: "Please enter a valid email address",
	url: "Please enter a valid URL",
	date: "Please enter a valid date",
	dateISO: "Please enter a valid date (yyyy-mm-dd)",
	number: "Please enter a valid number",
	digits: "Only numbers can be entered",
	creditcard: "Please enter a valid credit card number",
	equalTo: "Your input is different",
	extension: "Please enter a valid suffix",
	maxlength: $.validator.format("You can enter up to {0} characters"),
	minlength: $.validator.format("At least {0} characters are required"),
	rangelength: $.validator.format("Please enter a string between {0} and {1} in length"),
	range: $.validator.format("Please enter a number in the range {0} to {1}"),
	max: $.validator.format("Please enter a value no greater than {0}"),
	min: $.validator.format("Please enter a value no less than {0}")
});

}));