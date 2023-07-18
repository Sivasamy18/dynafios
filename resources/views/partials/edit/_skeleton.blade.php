<div class="modal fade" id="create-edit-modal" tabindex="1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between ">
                <h5 class="modal-title" id="skeletonModalLabel">Edit role</h5>
                <button type="button" class="sekeltonClose" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="tempContiner">
                            <div id="permissions-spinner" class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $('.sekeltonClose').on('click', function() {
        $('.tempContiner').html("");
    })
</script>