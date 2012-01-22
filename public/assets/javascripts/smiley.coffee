jQuery ($) ->
    $('.smiley-select select').change () ->
        $(this).closest('form').submit();
    
    $('.smiley-toggle').live 'click', (event) ->
        element = $(this)
        element.attr 'disabled': true
        element.addClass 'ajax'
        $.getJSON(
            element.attr 'href'
            (state) ->
                element.removeClass 'ajax'
                element.toggleClass 'favorite', state
                element.attr 'disabled': false
        )
        event.preventDefault()

    $('a[href*="admin/smileys/edit"], a[href*="admin/smileys/upload"]').live 'click', (event) ->
        href = $(this).attr 'href'
        $('<div class="smiley-modal"/>').load href, () ->
            $(this).hide().appendTo 'body'

            options = 
                modal  : true
                width  : $(this).outerWidth() + 50
                height : $(this).outerHeight() + 50
                title  : $('thead', this).remove().text()
                close  : () ->
                    $(this).remove()
            
            $(this).dialog options
        
        event.preventDefault()
        
    $('.smiley-modal .button.cancel').live 'click', (event) ->
        $(this).closest('.smiley-modal').dialog 'close'
        event.preventDefault()