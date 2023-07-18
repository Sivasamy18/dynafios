@extends('layouts/default')

@section('body')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="error-template">
                    <h1><span class="orange">Oops!</span> There was a problem.</h1>
                    <div class="error-details">
                        Sorry, an error has occurred, someone from the Dynafios team will be reaching out to you shortly!
                    </div>
                    <div class="error-actions">
                        <a href="/" class="btn btn-primary btn-lg"><span class="glyphicon glyphicon-home"></span>
                            Take Me Home </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<style>
    .orange {
        color: #f68a1f;
    }

    .error-template {
        padding: 40px 15px;
        text-align: center;
    }

    .error-actions {
        margin-top: 15px;
        margin-bottom: 15px;
    }

    .error-actions .btn {
        margin-right: 10px;
    }
</style>
