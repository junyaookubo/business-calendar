(function($){
    $(function(){
        $(document).on('click','.js-month',function(){
            let target = $(this).data('target-calendar');
            let year = $(this).parents('.calendar-table').data('year');
            let month;
            if($(this).data('month') == 0){
                year -= 1;
                month = 12;
                console.log(month);
            }else if($(this).data('month') == 13){
                year += 1;
                month = 1;
            }else{
                month = $(this).data('month');
            }
            
            $.ajax({
                type: 'POST',
                url: calendar_ajaxUrl,
                data: {
                    action: 'ajax_business_calendar',
                    postid: $(this).data('postid'),
                    year: year,
                    month: month,
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