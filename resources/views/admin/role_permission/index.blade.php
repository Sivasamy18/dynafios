@extends('layouts._admin')
@section('main')
@include('partials.confirm._delete')
@include('partials.edit._skeleton')
@include('partials.create._roleAndPermission')
<div id="modal-container"></div>
<div id="alert-div"></div>
<ul class="nav nav-tabs">
    <li class="nav-item active">
        <a class="nav-link active" data-toggle="tab" href="#roles">Roles</a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#permissions">Permissions</a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#users">Users</a>
    </li>
</ul>
<div class="tab-content">
    <div class="tab-pane active" id="roles">
        @include('partials.roleAndPermission._role',$roles)
    </div>
    <div class="tab-pane" id="permissions">
        @include('partials.roleAndPermission._permission',$permissions)
    </div>
    <div class="tab-pane" id="users">
        @include('partials.roleAndPermission._users',$users)
    </div>
</div>
<script>
    $(document).ready(function() {
            $('.editOption').on('click', function() {
                let Id = $(this).data('id');
                let name = $(this).data('name');
                $('#skeletonModalLabel').text(`Edit ${name}`)
                $.ajax({
                    type: 'GET',
                    url: `/admin/dashboard/edit/${name}/${Id}`,
                    data: {
                        '_token': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        $('.tempContiner').html(data);
                    }
                });
            });

            $('.add').click(function() {
                let createType = $(this).data('type');
                let createAction = $(this).data('action');
                $('.modal-title').text(`Create new ${createType}`);
                $('.createButton').text(`Create ${createType}`)
                $('#createForm').data('action', createAction);
                $('#createForm').data('type', createType);
            })

            function debounce(func, delay) {
                var timeoutId;
                return function() {
                    var context = this;
                    var args = arguments;
                    clearTimeout(timeoutId);
                    timeoutId = setTimeout(function() {
                        func.apply(context, args);
                    }, delay);
                };
            };

            //Handle Role and Permission name
            $('.createAction').on('input', debounce(function(e) {
                let regex = /^[a-z_.-]+$/;
                let searchValue = e.target.value;
                let url = $('#createForm').data('type');
                if (regex.test(searchValue) && searchValue.length <= 50) {
                    $.ajax({
                        type: 'GET',
                        url: `/admin/dashboard/roles-permissions/${url.toLowerCase()}/${searchValue}`,
                        data: {
                            '_token': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(data) {
                            $('.createError').text("")
                        },
                        error: function(xhr, status, error, data) {
                            $('.createError').text(xhr.responseJSON.data)
                        }
                    });

                } else {
                    let upperCase = /^[A-Z]+$/;
                    let space = /^[/\s/]+$/;
                    let spectialChar = /^[*@$%+=\/;'!#^&()]+$/;
                    if (upperCase.test(searchValue)) {
                        $('.createError').text("Uppercase are not allowed")
                    }
                    if (space.test(searchValue)) {
                        $('.createError').text("space are not allowed")
                    }
                    if (spectialChar.test(searchValue)) {
                        $('.createError').text("spectial character other than . _ - are not allowed")
                    } else if (searchValue.length === 0) {
                        $('.createError').text("")
                    }
                }
            }, 600));
        
            $('#confirm-delete-btn').on('click', function(e) {
                e.preventDefault();
                let id = $(this).data('id');
                let deleteType = $(this).data('deleteType');
                let deleteName = $(this).data('deleteName')
                $.ajax({
                    url: '/admin/dashboard/delete/' + deleteType + '/' + id,
                    type: 'POST',
                    data: {
                        '_method': 'DELETE', // Use _method to override the POST method
                        '_token': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        $(`#${deleteType+id}`).remove();
                        $(`.${deleteName}`).remove();
                        if (deleteType == "role") {
                            $('.Role').html(data);
                        } else {
                            $('.Permission').html(data);
                        }
                        $('#confirm-delete-modal').modal('hide');
                        $(`#alert-div`).append(`<div id="alert_id" class="alert alert-success alert-dismissible">Successfully deleted</div>`)
                        setTimeout(() => {
                            let alert_var = document.getElementById("alert_id")
                            alert_var.remove();
                        }, 3000)
                    },
                    error: function(xhr, textStatus, error) {
                        $('#confirm-delete-modal').modal('hide');
                    }
                });

            });

        },

    );
</script>

@endsection