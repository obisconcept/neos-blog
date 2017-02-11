function activateNewPostInterface() {
    $('#create-new-post').on('click', function(event){
        event.preventDefault();

        $('tr#create').show();
    });
}


function selectBlogIdentifier(){
    $('.neos-dropdown-menu').children('.blog-filter-item').on('click', function(event){
        event.preventDefault();

        $(this).addClass('selected');

        $(this).parent('ul').children('li').each(function(){
            if(!$(this).hasClass('selected')) {
                $(this).remove();
            } else {
                var text = $(this).children('a').text();
                $(this).parentsUntil('neos-dropdown').children('span').children('a').text(text);
            }
        });
    });
}

function validateInputAndUnlockCreateButton(){

    var button = $('.submit').children('button');
    var form = $('#create');
    var blogSelect = form.find('.blog-filter-item');

    button.on('click', function(event) {
        event.preventDefault();

        // if validation was succesful, activate submitting
        if(button.hasClass('unlocked-title') && button.hasClass('unlocked-title')) {
            form.children('form').submit();
        }
    })

    
    //Validate title and mark status
    form.find('input[type="text"]').on('change', function() {
        var length = $(this).val().length;
        if (length > 2) {
            $(this).css('outline', '1px solid #00a338');
            if(button.hasClass('unlocked-blog')) {
                button.removeClass('neos-disabled')
            }
            button.addClass('unlocked-title');
        } else {
            $(this).css('outline', '1px solid orange');
            button.removeClass('unlocked-title');
            console.log('The title is too short. A minimum of 3 characters needed');
        }
    })

    // validate blog
    blogSelect.on('click', function() {

        if(button.hasClass('unlocked-title')) {
            button.removeClass('neos-disabled')
        }

        button.addClass('unlocked-blog');
        form.children('.neos-action.blog').children('.neos-dropdown').css({outline: '1px solid #00a338', marginTop: '1px'})
    })
    
    
}