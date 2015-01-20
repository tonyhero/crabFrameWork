/**
 * Created by atom on 7/25/14.
 */

$.validator.addMethod(
    "int", function (value, element) {
        console.log(value);
        // From http://www.whatwg.org/specs/web-apps/current-work/multipage/states-of-the-type-attribute.html#e-mail-state-%28type=email%29
        // Retrieved 2014-01-14
        // If you have a problem with this implementation, report a bug against the above spec
        // Or use custom methods to implement your own email validation
        return this.optional(element) || /^\d+$/.test(value);
    }, "请输入整数"
);



$.validator.addMethod(
    "mobile", function (value, element) {
        console.log(value);
        // From http://www.whatwg.org/specs/web-apps/current-work/multipage/states-of-the-type-attribute.html#e-mail-state-%28type=email%29
        // Retrieved 2014-01-14
        // If you have a problem with this implementation, report a bug against the above spec
        // Or use custom methods to implement your own email validation
        return this.optional(element) || /^1[\d]{10}$/.test(value);
    }, "请输入正确的手机号"
);


$.validator.addMethod(
    "datetime", function (value, element) {
        // From http://www.whatwg.org/specs/web-apps/current-work/multipage/states-of-the-type-attribute.html#e-mail-state-%28type=email%29
        // Retrieved 2014-01-14
        // If you have a problem with this implementation, report a bug against the above spec
        // Or use custom methods to implement your own email validation
//        return this.optional(element) || /^1[\d]{10}$/.test(value);
        return value.match(/^20[\d]{2}-([0][1-9]|[1][0-2])-([0][1-9]|[1-2][0-9]|[3][0-1])\s([0-1][0-9]|[2][0-3]):[0-5][0-9]:[0-5][0-9]$/)
            || value.match(/^20[\d]{2}-([0][1-9]|[1][0-2])-([0][1-9]|[1-2][0-9]|[3][0-1])\s([0-1][0-9]|[2][0-3]):[0-5][0-9]$/);
    }, "请输入正确的时间"
);

jQuery.validator.addMethod("notEqual", function (value, element, param) {
    return this.optional(element) || value != param;
}, "Please specify a different (non-default) value");
