@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-exclamation-circle fa-fw icon"></i> Actions
        <small id="index">{{ $index }}</small>
    </h3>
     <!-- Action-Redesign by 1254 -->
    <div class="btn-group btn-group-sm">
        <a class="btn btn-default" href="{{ URL::route('actions.create') }}"><i class="fa fa-plus-circle fa-fw"  style="margin-top=-3px;"></i>
       Action</a>
    </div>
</div>
<!-- Action-Redesign by 1254    :1202220  -->
    <form class="form form-horizontal search-form" id="searchForm" action="" method="post">
        @csrf
    <div class="col-xs-4 form-group input-group" style="float: right;margin-right: 10%;margin-top: -64px;width: 190px;"> 
    
                                    <span class="input-group-addon"><i class="fa fa-search fa-fw"></i></span>
                                    <input class="form-control" id="searchText" name="searchText" type="text" placeholder="Search Action" style="height:28px;z-index: 0;"/>
   
    </div>
    </form>
@include('layouts/_flash')
<div id="actions" style="position: relative">
    {!! $table !!}
</div>
<div id="links">
    {!! $pagination !!}
</div>
<div id="modal-confirm-delete" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Delete Action?</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this action?</p>

                <p><strong style="color: red">Warning!</strong><br>
                    This action will delete this action and any associated data. There is no way to
                    restore this data once this action has been completed.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">Delete</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div><!-- /.modal -->
@endsection
@section('scripts')
<script type="text/javascript">
    $(function () {
        Dashboard.confirm({
            button: '.btn-delete',
            dialog: '#modal-confirm-delete',
            dialogButton: '.btn-primary'
        });

        Dashboard.pagination({
            container: '#actions',
            filters: '#actions .filters a',
            sort: '#actions .table th a',
            links: '#links',
            pagination: '#links .pagination a'
        });


        $.ajaxSetup({
            beforeSend: function (xhr,settings) {
                    xhr.setRequestHeader("Search", $('#searchText').val());
                    xhr.setRequestHeader("Filter", $('#category').val());
                //alert(settings.data);
                //alert(settings.url);
            },
            headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
        });

        $("#searchForm").submit(function(event) {
            /* stop form from submitting normally */
            event.preventDefault();
            reset();
        });
    });
    function reset(){
        $("#actions .filters a").first().trigger("click");
        /*if( $('#links ul').length ) {
            $("#links ul li a").first().trigger("click");
        }else{
         $("#actions .filters a").first().trigger("click");
        }*/
    }
</script>
@endsection