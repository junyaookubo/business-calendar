(function($){
    $(function(){
        $(document).on('click','.js-month',function(){
            let target = $(this).data('target-calendar');
            $.ajax({
                type: 'POST',
                url: calendar_ajaxUrl,
                data: {
                    action: 'ajax_business_calendar',
                    postid: $(this).data('postid'),
                    month: $(this).data('month'),
                }
            }).then(function(res){
                $(target).empty();
                $(target).append(res);
            }).catch(function(error){
                console.log(error);
            });
        });
    });
})(jQuery);