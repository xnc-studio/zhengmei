define(function(require) {
    require('modernizr');
    var $ = require('jquery');
    require('fastclick');
    require('foundation');
    $(document).foundation();

    // var canvas = $('.backblur');
    // var context = canvas.get(0).getContext('2d');
    // var img = new Image();
    // img.onload=function(){
    // 	context.drawImage(img,300,200);
    // }
    // img.src=canvas.attr('src');
    // alert(1);
});
