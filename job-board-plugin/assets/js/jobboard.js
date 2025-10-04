jQuery(document).ready(function ($) {
  $("#job-application-form").on("submit", function (e) {
    e.preventDefault();
    var form = this;

    // Client-side validation: ensure resume file is present and is a PDF
    var resumeInput = form.querySelector('#resume');
    if ( resumeInput ) {
      if ( resumeInput.files.length === 0 ) {
        $("#application-message").html('<span style="color: red;">Please attach your resume (PDF).</span>');
        resumeInput.focus();
        return;
      }
      var file = resumeInput.files[0];
      var type = file.type || '';
      var name = file.name || '';
      var isPdf = (type === 'application/pdf') || name.toLowerCase().endsWith('.pdf');
      if ( ! isPdf ) {
        $("#application-message").html('<span style="color: red;">Resume must be a PDF file.</span>');
        resumeInput.focus();
        return;
      }
    }

    var formData = new FormData(this);
    formData.append("action", "submit_job_application");
    formData.append("nonce", jobBoardAjax.nonce);

    $.ajax({
      url: jobBoardAjax.ajax_url,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.success) {
          $("#application-message").html(
            '<span style="color: green;">' + response.data + "</span>"
          );
          // Optionally reset the form on success
          $("#job-application-form")[0].reset();
        } else {
          $("#application-message").html(
            '<span style="color: red;">' + response.data + "</span>"
          );
          // If it's a duplicate-email message, focus the email field for user correction.
          var msg = (response.data || '').toString().toLowerCase();
          if ( msg.indexOf('already applied') !== -1 || msg.indexOf('already applied for this job') !== -1 ) {
            $("#email").focus();
          }
        }
      },
      error: function () {
        $("#application-message").html(
          '<span style="color: red;">Error submitting application.</span>'
        );
      },
    });
  });
});
