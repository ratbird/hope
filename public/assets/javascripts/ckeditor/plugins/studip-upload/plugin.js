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
                editor.insertHtml($('<div>').append($node).html() + ' ');
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
                var errors = [];
                $.each(fileList, function(index, file){
                    if (file.url) {
                        insertFile(file);
                    } else {
                        errors.push(file.name + ': ' + file.error);
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
            var $container = $(event.editor.container.$);

            // install upload handler
            $('<input>')
                .attr({
                    id: inputId,
                    type: 'file',
                    name: 'files[]',
                    multiple: true
                })
                .css('display', 'none')
                .appendTo($container)
                .fileupload({
                    url: editor.config.studipUpload_url,
                    singleFileUploads: false,
                    dataType: 'json',
                    done: function(e, data){
                        if (data.result.files) {
                            handleUploads(data.result.files);
                        } else {
                            alert('Das Hochladen der Datei(en) ist fehlgeschlagen.\n\n' + data.result);
                        }
                    },
                    fail: function (e, data) {
                        alert(
                            'Das Hochladen der Datei(en) ist fehlgeschlagen.'
                            + '\n\nFehler: ' + data.errorThrown.message
                        );
                    }
                });
        });

        // ckeditor
        editor.addCommand('upload', {    // command handler
            exec: function(editor){
                // NOTE if  $('#' + inputId) is stored in variable then
                //      upload works only once
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

