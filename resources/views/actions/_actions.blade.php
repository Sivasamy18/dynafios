<div class="filters">
    <a class="{{ HTML::active($filter == 0) }}" href="{{ URL::current().'?filter=0' }}">All</a>
    <!-- Action-Redesign by 1254 -->
   <!-- <a class="{{ HTML::active($filter == 1) }}" href="{{ URL::current().'?filter=1' }}">Management Duties</a>
    <a class="{{ HTML::active($filter == 2) }}" href="{{ URL::current().'?filter=2' }}">Activities</a> -->
</div>
<div class="clearfix"></div>
@if (count($items) > 0)
<table class="table table-striped table-hover actions-table">
    <thead>
    <tr>
        <th>{!! HTML::sort_link('Name', 1, $reverse, $page, $filter) !!}</th>
       <!-- Action-Redesign by 1254 -->
        <th>
         <span>
            <button type="button" id="category_filter_btn"  style="background-color: #212121;border-style: none;" >
                <i class="fa fa-filter" aria-hidden="true"></i>
            </button>
          </span>
            {!! HTML::sort_link('Category', 2, $reverse, $page, $filter) !!}
            @if($category > 0)
                <span class="badge badge-warning" style="padding: 0.3em 0.3em 0.2em 0.5em;">{{$categories[$category]}} <span class="close" id="clearCategory" style="line-height: 0.7;padding: 0 0.3em;">×</span></span>
            @endif
        </th>
        <!-- Action-Redesign by 1254 -->
       <th>{!! HTML::sort_link('Facility Name', 3, $reverse, $page, $filter) !!}</th> 
        <th class="text-right" width="100">Actions</th>
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $action)
        <tr>
            <td><a href="{{ URL::route('actions.edit', $action->id) }}">{{ $action->name }}</a></td>
             <!-- Action-Redesign by 1254 -->
           
            <td><a href="{{ URL::route('actions.edit', $action->id) }}">{{ $action->category_name }}</a></td>
            @if($action->hosptial_name)
            <td><a href="{{ URL::route('actions.edit', $action->id) }}">{{$action->hosptial_name}} </a></td>
            @else 
            <td><a href="{{ URL::route('actions.edit', $action->id) }}"><span style="margin-left: 43px;">All </span> </a></td>
            @endif
            <td class="text-right rowlink-skip">
                <div class="btn-group btn-group-xs">
                    <a class="btn btn-default btn-delete" href="{{ URL::route('actions.delete', $action->id) }}">
                        <i class="fa fa-trash-o fa-fw"></i>
                    </a>
                </div>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="panel panel-default">
    <div class="panel-body">There are currently no actions available for display.</div>
</div>
@endif

        <!-- //Action Redesign by 1254 -->
<div id="category_filter_modal" class="modal">

    <!-- Modal content -->
    <div class="modal-content">

        <span class="close" id="closeFilter" style="margin-right: -20px;margin-top: -24px;">&times;</span>
        <div class="form-group">
            <label class="col-xs-2 control-label">Category</label>

            <div class="col-xs-5" style="margin-left: 0px;width: 65%;margin-top: 0px;">
                {{ Form::select('category', $categories, Request::input('category',$category), [ 'class' => 'form-control', 'id' => 'category' ])
                }}
            </div>

            <div>

                {{--<div class="btn-group btn-group-sm">--}}
                    {{--<a class="btn btn-default" href=""> Submit</a>--}}
                {{--</div>--}}

            </div>
        </div>

    </div>

</div>


<script>

    var modal = document.getElementById("category_filter_modal");

    $('#category_filter_btn').click(function(){
        modal.style.display = "block";
    });
    $('#closeFilter').click(function(){
        modal.style.display = "none";
    });
    $('#category').change(function(){
        modal.style.display = "none";
        reset();
    });
    $('#clearCategory').click(function(){
        modal.style.display = "none";
        $('#category').val(0);
        reset();
    });
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }

    }
</script>

<style>
    body {font-family: Arial, Helvetica, sans-serif;}

    /* The Modal (background) */
    #category_filter_modal {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1; /* Sit on top */
        padding-top: 100px; /* Location of the box */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgb(0,0,0); /* Fallback color */
        background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
    }

    /* Modal Content */

    #category_filter_modal .modal-content {
        background-color: #fefefe;
        margin: 190px;
        padding: 36px;
        border: 1px solid #888;
        width: 45%;
        margin-left: 362px;
        height: 100px;

    /* The Close Button */
    #category_filter_modal .close {
        color: #aaaaaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    #category_filter_modal .close:hover,
    #category_filter_modal .close:focus {
        color: #000;
        text-decoration: none;
        cursor: pointer;
    }
    .form-control1{

    }
</style>