const username = 'private';
const password = 'private';
const uri = 'trig.php?username=' + username + '&password=' + password;
$(function(){
    let main_board = CKEDITOR.replace('editor1' /* 绑定 name 属性 */ , {
        removeButtons: 'PasteFromWord'
    });

    // 初始化获取一次
    $.get(uri, function(message, retcode) {
        main_board.setData(message);
    });

    $("#pull").on("click", function() {
        // 手动获取一次
        $.get(uri, function(message, retcode) {
            main_board.setData(message);
        });
    });
    $("#push").on("click", function(){
        // 提交后获取一次
        console.log(main_board.getData());
        $.post(uri,
            { data: main_board.getData() },
            function(message, retcode){
                main_board.setData(message);
            });
    });
});