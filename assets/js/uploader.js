(function ($) {
    $.fn.uploader = function (options) {

        var sumOfSize = 0;

        var $widget = getWidget(this),
                $fieldFill = $widget.find('.attachments'),
                $progressBar = $widget.find('#progressBar'),
                $progressOuter = $widget.find('#progressOuter');

        var buttons = [
            $widget.find('.uploader_label')
        ];
        checkDisplayFileTable();
        var settings = $.extend({
            button: buttons,
            dropzone: $widget.find('.uploader_label'),
            multipart: true,
            multiple: true,
            hoverClass: 'hover',
            focusClass: 'focus',
            responseType: 'json',
            debug: true,
            startXHR: function () {
                $progressOuter.removeClass('hidden'); // make progress bar visible
                this.setProgressBar($progressBar);
            },
            endXHR: function (filename) {
                $progressOuter.addClass('hidden'); // make progress bar visible
            },
            onSubmit: function (filename, extension) {
                // Create the elements of our progress bar
                var progress = $().append('div'), // container for progress bar
                        bar = $().append('div'), // actual progress bar
                        fileSize = $().append('div'), // container for upload file size
                        wrapper = $().append('div'), // container for this progress bar
                        progressBox = $().append('progressBox'); // on page container for progress bars

                // Assign each element its corresponding class
                progress.className = 'progress';
                bar.className = 'bar';
                fileSize.className = 'size';
                wrapper.className = 'wrapper';

                // Assemble the progress bar and add it to the page
                progress.append(bar);
                wrapper.innerHTML = '<div class="name">' + filename + '</div>'; // filename is passed to onSubmit()
                wrapper.append(fileSize);
                wrapper.append(progress);
                progressBox.append(wrapper); // just an element on the page to hold the progress bars

                // Assign roles to the elements of the progress bar
                this.setProgressBar(bar); // will serve as the actual progress bar
                this.setFileSizeBox(fileSize); // display file size beside progress bar
                this.setProgressContainer(wrapper); // designate the containing div to be removed after upload


            },
            onComplete: function (filename, response) {
                if (!response) {
                    writeMessage('filename: ' + filename + 'Unable to upload file', true);
                    return;
                }
                if (response.success === true) {
                    writeMessage('<strong>' + escapeTags(filename) + '</strong>' + ' successfully uploaded. <br> ', true);
                    var $thubs = $widget.find('.image_preview div');
                    $thubs.fadeOut(500);
                    $thubs.remove();
                    $widget.find('.image_preview div').fadeOut(500).remove();
                    var $buttons = $widget.find('.upload_buttons');
                    $buttons.addClass('hidden');

                    var newValue = getFilesList();
                    newValue.push(response.filelink);
                    var galleryImage = '';
                    if (options["galleryType"] === true) {
                        galleryImage = '<img src=' + options["tempPreviewUrl"] + '/' + response.filelink + ' style="width:50px">';
                    }
                    $fieldFill.val(JSON.stringify(newValue));
                    var tempFiles = $widget.find('#bodyfileLinks');
                    var newItem = '<tr id row_' + response.filelink + '>' +
                            '<td class="col-lg-8 col-md-8 col-sm-8 col-xs-8">' +
                            galleryImage +
                            '<a href="' + options["tempPreviewUrl"] + '/' + response.filelink + '"  target="_blank" rel="popover" >' + '<span class="glyphicon glyphicon-paperclip" aria-hidden="true"></span>' + response.filelink + '</a>' +
                            '</td>' +
                            '<td class="col-lg-2 col-md-2 col-sm-2 col-xs-2"><span class="label label-warning">Temp New File</span></td>' +
                            '<td class="col-lg-2 col-md-2 col-sm-2 col-xs-2">' +
                            '<button type="button" id="temp_remove_file_' + response.filelink + '" class="btn btn-danger btn-xs">' +
                            '<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>' +
                            '</button>    ' +
                            '</td>' +
                            '</tr>';
                    checkDisplayFileTable();
                    tempFiles.append(newItem);
                    displayLabel();
                } else {
                    if (response.msg) {
                        writeError(escapeTags(response.msg));
                    } else {
                        writeError('An error occurred and the upload failed.');
                    }
                }
            },
            onChange: function (filename, extension, uploadBtn, size, file) {

                // check file exist
                if (uploader.getQueueSize() > 0) {
                    for (i = 0; i < uploader.getQueueSize(); i++) {
                        if (uploader._queue[i].name === filename && uploader._queue[i].size === size) {
                            writeError('file ' + filename + ' already exist !');
                            return false;
                        }
                    }
                }

                if (size > options['max_ini_FileSize']) {
                    writeError('Unable to upload file ! Maximum size : ' + options['max_ini_FileSize'] + ' Kbytes <br>');
                    return false;
                }

                if (uploader.getQueueSize() >= (options['maxUploads'])) {
                    writeError('Unable to upload file ! Maximum uploads : ' + options['maxUploads']);
                    return false;
                } else {
                    if (file.type.substr(0, 5) === 'image') {
                        var $img_preview = $widget.find(".image_preview");
                        previewImage(file, $img_preview);
                        var $buttons = $widget.find('.upload_buttons');
                        $buttons.removeClass('hidden');
                        $widget.find(".uploader_label span").addClass('hidden');
                    } else {
                        var $img_preview = $widget.find(".image_preview");
                        previewFile(file, $img_preview);
                        var $buttons = $widget.find('.upload_buttons');
                        $buttons.removeClass('hidden');
                        $widget.find(".uploader_label span").addClass('hidden');
                    }
                }

                writeError('');
            }
            ,
            onError: function () {
                writeError('Unable to upload file');
            },
            // Extension Error
            onExtError: function (filename, extension) {
                writeError(options['ext_error_text']);
            },
            onSizeError: function () {
                writeError(options['size_error_text']);
            }

        }, options);

        var uploader = new ss.SimpleUpload(settings);
        $widget.data('uploader', uploader);

        function getWidget($element)
        {
            return $element.parents('.upload_widget');
        }

        // Write Message
        function writeMessage(message, append) {
            var $msgbox = $widget.find('#msgBox');
            var $msgboxHtml = $widget.find('#msgBox').html();

            if (append) {
                $msgbox.html($msgboxHtml + '<br>' + message);
            } else {
                $widget.find('#msgBox').html(message);
            }
        }
        function writeError(message) {
            var $errbox = $widget.find('#errBox');
            if (message !== '') {
                $errbox.html('<div class="alert alert-warning" role="alert">' + message + '</div>').show();
            } else {
                $errbox.hide();
            }
        }

        function escapeTags(str) {
            return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
        }
        /*
         * Preview Image
         */
        function previewImage(file, $display) {

            var oFReader = new FileReader();
            oFReader.readAsDataURL(file);
            oFReader.onload = function (oFREvent) {
                var item = '<div class="imgBox col-sm-4 col-lg-3 col-md-3 col-xs-6" id="thumb_' + file.name + '" ><img class="img-thumbnail"  src = ' + oFREvent.target.result + ' >';
                item += '<br>' + file.name + '</div>';
                $display.append(item);
            };
        }

        /*
         * Preview File
         */
        function previewFile(file, $display) {
            var oFReader = new FileReader();
            oFReader.readAsDataURL(file);
            oFReader.onload = function (oFREvent) {
                console.log('in Preview File');
                var item = '<div class="imgBox col-sm-4 col-lg-3 col-md-3 col-xs-6" id="' + file.name + '"  ><img class="img-thumbnail"  src = "' + options['noPhotoImage'] + '" >';
                item += '<br>' + file.name + '</div>';
                $display.append(item);
            };
        }

        //delete file from temp path
        function deleteTempFile(filename)
        {
            $.post({
                url: 'deletetempfile',
                data: {'filename': filename},
                success: function (response) {
                    // do something
                },
                error: function () {
                    // do something

                }
            });
        }

        // Remove files from list and update html
        function removeFromFilesList(filename) {
            console.log('delete : ' + filename);
            var oldValue = getFilesList();
            var newValue = oldValue;
            newValue.splice($.inArray(filename, newValue), 1);
            if (newValue.length === 0) {
                $fieldFill.val('');
            } else {
                $fieldFill.val(JSON.stringify(newValue));
            }
        }

        // get list of files from html
        function getFilesList() {
            var fList = [];
            var files = $fieldFill.val(); // $widget.find(".attachments").val();
            console.log('Get Files List :' + files);
            if (files !== '') {
                fList = JSON.parse(files);
            }
            return fList;
        }

        function checkDisplayFileTable() {
            if ($fieldFill.val() === '') {
                $widget.find('#attachments_list').addClass('hidden');
            } else {
                $widget.find('#attachments_list').removeClass('hidden');
            }
        }

        function removeMsgBox() {
            $widget.find("#msgBox").addClass('hidden');
        }

        function removeErrorBox() {
            $widget.find("#errBox").addClass('hidden');
        }
        function displayLabel() {
            $widget.find(".uploader_label span").removeClass('hidden');
        }

        /*
         * Display popover image
         */
        function displayPopupImage(obj, answer) {
            if (answer) {
                popOver(obj);
            }
        }

        /*
         * Check if image is valid
         */
        function IsValidImageUrl(obj, callback) {
            var img = new Image();
            var a_href = $(obj).attr('href');
            img.onerror = function () {
                callback(obj, false);
            },
                    img.onload = function () {
                        callback(obj, true);
                    },
                    img.src = a_href;
        }

        function popOver(obj) {
            var a_href = $(obj).attr('href');
            $(obj).popover({
                placement: 'top',
                trigger: 'hover',
                html: true,
                content: '<div class="media"><a href="#" class="pull-left"><img src="' + a_href + '" class="media-object" alt="Sample Image"></a><div class="media-body"><h4 class="media-heading"></h4><p></p></div></div>'
            });
        }

        function isImageOk(img) {
            // During the onload event, IE correctly identifies any images that
            // weren't downloaded as not complete. Others should too. Gecko-based
            // browsers act like NS4 in that they report this incorrectly.
            if (!img.complete) {
                return false;
            }

            // However, they do have two very useful properties: naturalWidth and
            // naturalHeight. These give the true size of the image. If it failed
            // to load, either of these should be zero.
            if (typeof img.naturalWidth !== "undefined" && img.naturalWidth === 0) {
                return false;
            }

            // No other way of checking: assume it's ok.
            return true;
        }
        // Clear Button

        //$('.upload_widget')
        $widget
                .on('click', "#SubmitBtn", {}, function () {
                    var $img = $widget.find('.thumbnail');
                    var data = $img.data;
                    var filesCount = uploader.getQueueSize();
                    for (i = 0; i < filesCount; i++) {
                        data[yii.getCsrfParam()] = yii.getCsrfToken();
                        data['filename'] = uploader._queue[0].name;
                        uploader.setData(data);
                        uploader.submit();
                    }
                })
                .on('click', '#ClearBtn', function () {
                    uploader.clearQueue();
                    $widget.find('.image_preview div').remove();
                    $widget.find('#msgBox').empty();
                    //$widget.find(".uploader_label span").removeClass('hidden');
                    var $buttons = $widget.find('.upload_buttons');
                    $buttons.addClass('hidden');
                    removeMsgBox();
                    removeErrorBox();
                    displayLabel();
                })
                .on('click', "[id*='temp_remove_file']", {}, function (e) {
                    // Delete files from temp directory and delete it from array
                    var row_id = this.id;
                    $(this).closest('tr').remove();
                    var del_filename = row_id.substring(17, row_id.length);
                    deleteTempFile(del_filename);
                    removeFromFilesList(del_filename);
                    checkDisplayFileTable();
                    removeMsgBox();
                    removeErrorBox();
                })
                .on('click', "[id*='delete_file']", {}, function () {
                    console.log('Button Delete File' + this.id);
                    //Delete file names from array
                    var row_id = this.id;
                    $(this).closest('tr').remove();
                    var del_filename = row_id.substring(12, row_id.length);
                    removeFromFilesList(del_filename);
                    checkDisplayFileTable();
                    removeMsgBox();
                    removeErrorBox();
                })
                .popover({html: true,
                    trigger: 'hover',
                    placement: 'top',
                    selector: '#bodyfileLinks tr td a[rel=popover]',
                    content: function () {
                        var a_href = $(this).attr('href');
                        var img = new Image();
                        img.src = a_href;
                        if (isImageOk(img)) {
                            return '<div class="media"><a href="#" class="pull-left"><img src="' + a_href + '" class="media-object" alt="Sample Image"></a><div class="media-body"><h4 class="media-heading"></h4><p></p></div></div>';
                        }
                    }
                })
                ;
    };
})(jQuery);