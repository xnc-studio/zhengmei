define("zhengmei/main/1.0.0/main-debug", [ "modernizr-debug", "jquery-debug", "fastclick-debug", "foundation-debug" ], function(require) {
    require("modernizr-debug");
    var $ = require("jquery-debug");
    require("fastclick-debug");
    require("foundation-debug");
    $(document).foundation();
});
