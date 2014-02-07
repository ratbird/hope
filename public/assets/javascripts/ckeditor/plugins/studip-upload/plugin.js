CKEDITOR.plugins.add('studip-upload', {
    icons: 'upload',
    init: function(editor){
        // utilities
        var isString = function(object) {
                return (typeof object) === 'string';
            },
            isImage = function(mime_type){
                return isString(mime_type) && mime_type.match('^image');
            },
            isSVG = function(mime_type){
                return isString(mime_type) && mime_type === 'image/svg+xml';
            },
            insertNode = function($node){
                editor.insertHtml($('<div>').append($node).html());
            },
            insertImage = function(file){
                insertNode($('<img />').attr({
                    src: file.url,
                    alt: file.name,
                    title: file.name
                }));
            },
            insertLink = function(file){
                insertNode($('<a>').attr({
                    href: file.url,
                    type: file.type,
                    target: '_blank',
                    rel: 'nofollow'
                }).append(file.name));
            },
            insertFile = function(file){
                // NOTE StudIP sends SVGs as application/octet-stream
                if (isImage(file.type) && !isSVG(file.type)) {
                    insertImage(file);
                } else {
                    insertLink(file);
                }
            },
            handleUploads = function(fileList){
                if (!fileList) {
                    alert('Das Hochladen der Datei(en) ist fehlgeschlagen.');
                    return;
                }

                var errors = [];
                $.each(fileList, function(index, file){
                    if (file.error) {
                        errors.push(file.name + ': ' + file.error);
                    } else {
                        insertFile(file);
                    }
                });
                if (errors.length) {
                    var message = 'Es konnten nicht alle Dateien'
                        + ' hochgeladen werden.\n\n';
                    alert(message + errors.join('\n'));
                }
            };

        // actual file upload handler
        // NOTE depends on jQuery File Upload plugin being loaded beforehand!
        // TODO integrate jQuery File Upload plugin into studip-upload
        var inputId = 'fileupload';
        editor.on('instanceReady', function(event){
            function appendTo($node) {
                $('<input>')
                    .attr({
                        id: inputId,
                        type: 'file',
                        name: 'files[]',
                        multiple: true
                    })
                    .css('display', 'none')
                    .appendTo($node)
                    .fileupload({
                        url: editor.config.studipUpload_url,
                        singleFileUploads: false,
                        dataType: 'json',
                        dropZone: $node,
                        done: function(e, data){
                            handleUploads(data.result.files);
                        }
                    });

                // drop zone effects
                $('<div class="dropzone">drop your files</div>')
                    .appendTo($node);

                $node
                    .css('position', 'relative')
                    .bind('dragover', function(event){
                        $(body).addClass('drag');
                        event.preventDefault();
                    }).bind('dragleave drop', function(event){
                        $(body).removeClass('drag');
                        event.preventDefault();
                    });

                $node
                    .find('iframe')
                    .bind('drop dragover', function(event){
                        event.preventDefault();
                    });
            };

            var container = event.editor.container.$,
                body = $(event.editor.container.$)
                    .find('iframe')
                    .contents()
                    .find('body');

            appendTo($(container));
            appendTo(body);
        });

        // disable default browser drop action
        $(document).bind('drop dragover', function(event){
            event.preventDefault();
        });

        // ckeditor
        editor.addCommand('upload', {    // command handler
            exec: function(editor){
                // NOTE upload works only one time, if $('#fileupload') is
                //      stored in variable
                $('#' + inputId).click();
            }
        });
        editor.ui.addButton('upload', {  // toolbar button
            label: 'Datei hochladen',
            command: 'upload',
            toolbar: 'insert,80'
        });
    }
});
