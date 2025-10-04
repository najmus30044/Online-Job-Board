(function($){
    'use strict';

    var JobApps = {
        init: function(){
            this.bindActions();
        },
        bindActions: function(){
            var self = this;
            $(document).on('click', '.job-app-edit', function(e){
                e.preventDefault();
                var id = $(this).data('id');
                self.openEditModal(id);
            });

            $(document).on('click', '.job-app-delete', function(e){
                e.preventDefault();
                var id = $(this).data('id');
                if ( confirm( jobAppsData.confirm_delete ) ) {
                    self.deleteApplication(id, $(this).closest('tr'));
                }
            });

            $(document).on('submit', '#job-app-edit-form', function(e){
                e.preventDefault();
                self.submitEditForm(this);
            });

            // Close modal
            $(document).on('click', '.job-app-modal .close-modal', function(e){
                e.preventDefault();
                $('.job-app-modal').remove();
            });
        },
        openEditModal: function(id){
            var self = this;
            $.post(jobAppsData.ajax_url, {
                action: 'job_app_get',
                id: id,
                nonce: jobAppsData.nonce
            }, function(resp){
                if ( resp.success ) {
                    self.showModal(resp.data);
                } else {
                    alert(resp.data || jobAppsData.msg_fail);
                }
            });
        },
        showModal: function(data){
            var html = '<div class="job-app-modal" style="position:fixed;left:0;top:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:9999;display:flex;align-items:center;justify-content:center;">';
            html += '<div style="background:#fff;padding:20px;max-width:600px;width:100%;border-radius:4px;position:relative;">';
            html += '<a href="#" class="close-modal" style="position:absolute;right:10px;top:10px;font-size:18px">✕</a>';
            html += '<h2>' + jobAppsData.edit_title + '</h2>';
            html += '<form id="job-app-edit-form" enctype="multipart/form-data">';
            html += '<input type="hidden" name="action" value="job_app_update">';
            html += '<input type="hidden" name="id" value="' + data.id + '">';
            html += '<p><label>' + jobAppsData.label_name + '<br><input type="text" name="full_name" value="' + (data.full_name || '') + '" required style="width:100%;"></label></p>';
            html += '<p><label>' + jobAppsData.label_email + '<br><input type="email" name="email" value="' + (data.email || '') + '" required style="width:100%;"></label></p>';
            html += '<p><label>' + jobAppsData.label_phone + '<br><input type="text" name="phone" value="' + (data.phone || '') + '" style="width:100%;"></label></p>';
            html += '<p><label>' + jobAppsData.label_resume + '<br><input type="file" name="resume"></label></p>';
            if ( data.resume_url ) {
                html += '<p><a href="' + data.resume_url + '" target="_blank">' + jobAppsData.download_resume + '</a></p>';
            }
            html += '<p><input type="hidden" name="nonce" value="' + jobAppsData.nonce + '"><button type="submit" class="button button-primary">' + jobAppsData.btn_save + '</button> <a href="#" class="close-modal button">' + jobAppsData.btn_cancel + '</a></p>';
            html += '</form></div></div>';

            $('body').append(html);
        },
        submitEditForm: function(form){
            var $form = $(form);
            var formData = new FormData(form);
            $.ajax({
                url: jobAppsData.ajax_url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(resp){
                    if ( resp.success ) {
                        alert(jobAppsData.msg_update_success);
                        location.reload();
                    } else {
                        alert(resp.data || jobAppsData.msg_fail);
                    }
                }
            });
        },
        deleteApplication: function(id, $row){
            $.post(jobAppsData.ajax_url, {
                action: 'job_app_delete',
                id: id,
                nonce: jobAppsData.nonce
            }, function(resp){
                if ( resp.success ) {
                    alert(jobAppsData.msg_delete_success);
                    // Remove row from table
                    $row.remove();
                } else {
                    alert(resp.data || jobAppsData.msg_fail);
                }
            });
        }
    };

    $(document).ready(function(){
        JobApps.init();
    });
})(jQuery);