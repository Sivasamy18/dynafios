@extends('layouts/default')
@section('body')
    <header id="header">
        <div class="inner">
            <a class="brand" href="/"></a>
        </div>
    </header>
    <div id="wrapper">
        <div id="page">
            <div id="dynafios_overview_home"></div>
            <nav class="menu menu-fixed">
                <ul>
                    <li class="menu-item active-menu-item"><a href="#dynafios_overview_home">Home</a></li>
                    <li class="menu-item"><a href="#dynafios_overview">DYNAFIOS Overview</a></li>
                    <li class="menu-item"><a href="#dynafios_physician_interface">Physician Interface</a></li>
                    <li class="menu-item"><a href="#dynafios_dashboard">Interactive Dashboard</a></li>
                    <li class="menu-item"><a href="#dynafios_reporting">Reporting &amp; Payments</a></li>
                    <li class="menu-item back-menu-item"><a href="/">Back to Dashboard</a></li>
                </ul>
            </nav>
            <section id="features">
                <div class="column">
                    <span class="feature-icon feature-icon-1"></span>

                    <h1 class="heading">Created</h1>

                    <p class="body">
                        With the busy medical<br/>
                        professional <br/>
                        in mind.
                    </p>
                </div>
                <div class="column">
                    <span class="feature-icon feature-icon-2"></span>

                    <h1 class="heading">Engineered</h1>

                    <p class="body">
                        As a "SaaS" application <br/>
                        with mobility in mind.
                    </p>
                </div>
                <div class="column">
                    <span class="feature-icon feature-icon-3"></span>

                    <h1 class="heading">Designed</h1>

                    <p class="body">
                        For ease of use and to<br/>
                        drive contract<br/>
                        compliance
                    </p>
                </div>
                <div class="column">
                    <span class="feature-icon feature-icon-4"></span>

                    <h1 class="heading">Stored</h1>

                    <p class="body">
                        In the cloud real time<br/>
                        with access from <br/>
                        anywhere !
                    </p>
                </div>
                <div class="column">
                    <span class="feature-icon feature-icon-5"></span>

                    <h1 class="heading">E-Signature</h1>

                    <p class="body">
                        For all physician logs to<br/>
                        streamline the payment <br/>
                        process.
                    </p>
                </div>
                <div class="column">
                    <span class="feature-icon feature-icon-6"></span>

                    <h1 class="heading">Reporting</h1>

                    <p class="body">
                        At the Physicain,<br/>
                        practice and hospital<br/>
                        level...
                    </p>
                </div>
            </section>
            <div id="dynafios_physician_interface">
            </div>
            <div id="dynafios_overview">
            </div>
            <div id="dynafios_dashboard">
            </div>
            <div id="dynafios_reporting">
            </div>
            <div id="dynafios_process">
            </div>


            <footer id="footer">
            </footer>
        </div>
    </div>
    <link type="text/css" rel="stylesheet" href="{{ asset('assets/css/overview.min.css') }}"/>
@endsection
@section('scripts')
    <script type="text/javascript">
        $(function () {
            var element = $('.menu-fixed');

            $(window).scroll(function () {
                var scroll = $(window).scrollTop();

                if (scroll >= 473) {
                    $(element).addClass('menu-affixed');
                } else {
                    $(element).removeClass('menu-affixed');
                }
            });

            $('body').scrollspy({ target: '.menu-fixed' });
        });
    </script>
@endsection