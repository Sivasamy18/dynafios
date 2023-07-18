<div class="modal fade" id="create-role-modal" tabindex="1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between ">
                <h5 class="modal-title" id="exampleModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <form id="createForm" method='POST'>
                            @csrf
                            <div class="form-group">
                                <label for="name">Name:</label>
                                <input type="text" class="form-control createAction" id="name" name="create_name" pattern="^[a-zA-Z_. ]+$" maxlength="50" required placeholder="example_role-permission.accepted">
                                <p class="createError text-danger text-xs"></p>
                            </div>
                            <button type="submit" class="btn btn-primary createButton"></button>
                            <!-- <button type="button" id="rolesAdd" class="btn btn-primary createButton"></button> -->
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
        $(document).ready(function() {
            $('#createForm').submit(function(e) {
            try {
                e.preventDefault();
                let action = $(this).data('action')
                let type = $(this).data('type')
                var formData = $(this).serialize();
                $.ajax({
                    url: action,
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        $(`.${type}`).html(response);
                        $('#createForm').trigger("reset");
                        $('#create-role-modal').modal('hide');
                    },
                    error: function(xhr, status, error) {
                        $('#createForm').trigger("reset");
                        $('#create-role-modal').modal('hide');
                        $(`#alert-div`).append(`<div id="alert_id" class="alert alert-danger alert-dismissible">${xhr.responseJSON.data}</div>`)
                        setTimeout(() => {
                            let alert_var = document.getElementById("alert_id")
                            alert_var.remove();
                        }, 3000)
                    }
                });
            } catch (e) {
                console.log(e);
            }
        },
        $(".close").on('click', function() {
            $('#createForm').trigger("reset");
            $('.createError').text("");
        })
    ); 

                            // click function



    // $('#rolesAdd').click(function(e) {
    //         try {
    //             e.preventDefault();
    //             let action = $(this).data('action')
    //             let type = $(this).data('type')
    //             var formData = $(this).serialize();
    //             $.ajax({
    //                 url: action,
    //                 method: 'POST',
    //                 data: formData,
    //                 success: function(response) {
    //                     $(`.${type}`).html(response);
    //                     $('#createForm').trigger("reset");
    //                     $('#create-role-modal').modal('hide');
    //                 },
    //                 error: function(xhr, status, error) {
    //                     $('#createForm').trigger("reset");
    //                     $('#create-role-modal').modal('hide');
    //                     $(`#alert-div`).append(`<div id="alert_id" class="alert alert-danger alert-dismissible">${xhr.responseJSON.data}</div>`)
    //                     setTimeout(() => {
    //                         let alert_var = document.getElementById("alert_id")
    //                         alert_var.remove();
    //                     }, 3000)
    //                 }
    //             });
    //         } catch (e) {
    //             console.log(e);
    //         }
    //     },
    //     $(".close").on('click', function() {
    //         $('#createForm').trigger("reset");
    //         $('.createError').text("");
    //     })
    // );


});
</script>